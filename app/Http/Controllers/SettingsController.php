<?php

namespace App\Http\Controllers;

use App\Console\Commands\SendDailyWhatsAppAttendanceReport;
use App\Models\AppSetting;
use App\Models\DailyReportRun;
use App\Models\DailyReportWhatsappNumber;
use App\Services\WahaService;
use App\Support\DailyReportSchedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class SettingsController extends Controller
{
    public function index(WahaService $waha)
    {
        $wahaStatus = $waha->connectionStatus();
        $mailStatus = $this->mailConnectionStatus();
        $dailyNumbers = DailyReportWhatsappNumber::latest()->get();
        $envDailyNumbers = $waha->parseMobileList(config('services.waha.daily_report_mobiles'));

        $reportTime  = DailyReportSchedule::time();
        $reportRetry = DailyReportSchedule::retries();
        $reportUntil = DailyReportSchedule::retryUntil();

        $todayRun = DailyReportRun::whereDate(
            'report_date',
            Carbon::now(DailyReportSchedule::TIMEZONE)->toDateString()
        )->first();

        $recentRuns = DailyReportRun::orderByDesc('report_date')->limit(7)->get();

        $attendanceRules = [
            'early_minutes'   => \App\Support\AttendanceRules::earlyMinutes(),
            'missing_minutes' => \App\Support\AttendanceRules::missingMinutes(),
            'rules_from'      => \App\Support\AttendanceRules::rulesFrom()->toDateString(),
            'saturday_set'    => \App\Support\AttendanceRules::saturdayNeedsSchedule(),
            'deduct_absences' => \App\Support\AttendanceRules::deductsAbsences(),
            'absences_from'   => \App\Support\AttendanceRules::absencesFrom()?->format('Y-m')
                ?? Carbon::now(\App\Support\AttendanceRules::TIMEZONE)->format('Y-m'),
            'auto_clock_out'  => \App\Support\AttendanceRules::autoClockOutEnabled(),
        ];

        return view('admin.settings.index', compact(
            'attendanceRules',
            'wahaStatus',
            'mailStatus',
            'dailyNumbers',
            'envDailyNumbers',
            'reportTime',
            'reportRetry',
            'reportUntil',
            'todayRun',
            'recentRuns'
        ));
    }

    /**
     * What a short day costs, and whether a plain absence costs anything.
     */
    public function saveAttendanceRules(Request $request)
    {
        $data = $request->validate([
            'early_minutes'   => 'required|integer|min:1|max:480',
            'missing_minutes' => 'required|integer|min:1|max:480',
            'rules_from'      => 'nullable|date_format:Y-m-d',
            'saturday_set'    => 'nullable|boolean',
            'deduct_absences' => 'nullable|boolean',
            'absences_from'   => 'nullable|date_format:Y-m',
            'auto_clock_out'  => 'nullable|boolean',
        ]);

        $deductAbsences = $request->boolean('deduct_absences');

        // Left out means "leave it where it is", which for a setting that has
        // never been saved is today - the rules never reach backwards.
        $rulesFrom = $data['rules_from']
            ?? \App\Support\AttendanceRules::rulesFrom()->toDateString();

        // Without a month to start from, absences would be charged for every
        // month on file. Default to this one, so the rule only looks forward.
        $from = $data['absences_from']
            ?? \App\Models\AppSetting::get(\App\Support\AttendanceRules::ABSENCES_FROM)
            ?? Carbon::now(\App\Support\AttendanceRules::TIMEZONE)->format('Y-m');

        \App\Support\AttendanceRules::save([
            \App\Support\AttendanceRules::EARLY_MINUTES   => (int) $data['early_minutes'],
            \App\Support\AttendanceRules::MISSING_MINUTES => (int) $data['missing_minutes'],
            \App\Support\AttendanceRules::RULES_FROM      => $rulesFrom,
            \App\Support\AttendanceRules::SATURDAY_SET    => $request->boolean('saturday_set') ? 1 : 0,
            \App\Support\AttendanceRules::DEDUCT_ABSENCES => $deductAbsences ? 1 : 0,
            \App\Support\AttendanceRules::ABSENCES_FROM   => $from,
            \App\Support\AttendanceRules::AUTO_CLOCK_OUT  => $request->boolean('auto_clock_out') ? 1 : 0,
        ]);

        $message = 'Attendance rules saved. Half days are charged from '
            .Carbon::parse($rulesFrom)->format('d M Y').' onwards.';

        if ($deductAbsences) {
            $message .= ' Days absent with no leave are deducted from '
                .Carbon::parse($from.'-01')->format('F Y').' onwards.';
        }

        return back()->with('success', $message);
    }

    /**
     * When the daily report goes out, and how long to keep trying.
     */
    public function saveDailySchedule(Request $request)
    {
        $request->validate([
            'daily_report_time'        => 'required|date_format:H:i',
            'daily_report_retry_until' => 'required|date_format:H:i',
            'daily_report_retry'       => 'nullable|boolean',
        ]);

        $time  = DailyReportSchedule::normalise($request->daily_report_time, DailyReportSchedule::DEFAULT_TIME);
        $until = DailyReportSchedule::normalise($request->daily_report_retry_until, DailyReportSchedule::DEFAULT_RETRY_UNTIL);

        // Retrying up to a time earlier than the send time would mean never
        // retrying at all, which is not what anyone means by it.
        if ($until <= $time) {
            return back()->with('error',
                'The "keep trying until" time must be later than the send time.');
        }

        AppSetting::put('daily_report_time', $time);
        AppSetting::put('daily_report_retry_until', $until);
        AppSetting::put('daily_report_retry', $request->boolean('daily_report_retry') ? '1' : '0');

        return back()->with('success',
            'Daily report will be sent at '.DailyReportSchedule::label($time).'.');
    }

    /**
     * Send the report right now, whatever the time.
     *
     * This is the way out when WAHA was down at the scheduled minute: fix the
     * session, press the button, and the day's report goes out.
     */
    public function sendDailyReportNow(WahaService $waha)
    {
        $now   = Carbon::now(DailyReportSchedule::TIMEZONE);
        $today = $now->toDateString();

        $run = DailyReportRun::forDate($today);

        $result = app(SendDailyWhatsAppAttendanceReport::class)
            ->deliver($waha, $now, $today, $run, true);

        return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function storeDailyNumber(Request $request)
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'mobile' => 'required|string|max:30|unique:daily_report_whatsapp_numbers,mobile',
        ]);

        DailyReportWhatsappNumber::create([
            'name' => $request->name,
            'mobile' => trim($request->mobile),
            'is_active' => true,
        ]);

        return back()->with('success', 'Daily report WhatsApp number added.');
    }

    public function toggleDailyNumber($id)
    {
        $number = DailyReportWhatsappNumber::findOrFail($id);
        $number->update(['is_active' => !$number->is_active]);

        return back()->with('success', 'Number '.($number->is_active ? 'enabled' : 'disabled').'.');
    }

    public function destroyDailyNumber($id)
    {
        DailyReportWhatsappNumber::findOrFail($id)->delete();

        return back()->with('success', 'Daily report WhatsApp number removed.');
    }

    public function sendTestEmail(Request $request)
    {
        $request->validate([
            'test_email' => 'required|email',
        ]);

        try {
            Mail::raw(
                "This is a test email from LSAF HR.\n\nIf you received this, your email server is connected.",
                function ($message) use ($request) {
                    $message->to($request->test_email)
                        ->subject('LSAF HR — Email Server Test');
                }
            );

            return back()->with('success', 'Test email sent to '.$request->test_email.'.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Email test failed: '.$e->getMessage());
        }
    }

    /**
     * @return array{connected: bool, status: string, detail: string, config: array}
     */
    protected function mailConnectionStatus(): array
    {
        $mailer = (string) config('mail.default');
        $host = (string) config('mail.mailers.smtp.host');
        $port = (int) config('mail.mailers.smtp.port');
        $from = (string) config('mail.from.address');
        $username = config('mail.mailers.smtp.username');

        $config = [
            'mailer' => $mailer,
            'host' => $host,
            'port' => $port,
            'username' => $username ?: '(not set)',
            'from' => $from,
        ];

        if (in_array($mailer, ['log', 'array'], true)) {
            return [
                'connected' => false,
                'status' => strtoupper($mailer),
                'detail' => 'Mailer is set to "'.$mailer.'". Emails are not sent to a real SMTP server.',
                'config' => $config,
            ];
        }

        if ($mailer !== 'smtp' || !filled($host) || !$port) {
            return [
                'connected' => false,
                'status' => 'NOT_CONFIGURED',
                'detail' => 'SMTP host/port is missing or mailer is not smtp.',
                'config' => $config,
            ];
        }

        $errno = 0;
        $errstr = '';
        $socket = @fsockopen($host, $port, $errno, $errstr, 5);

        if ($socket) {
            fclose($socket);

            return [
                'connected' => true,
                'status' => 'REACHABLE',
                'detail' => "SMTP host {$host}:{$port} is reachable.",
                'config' => $config,
            ];
        }

        return [
            'connected' => false,
            'status' => 'UNREACHABLE',
            'detail' => $errstr ?: "Could not connect to {$host}:{$port} (error {$errno}).",
            'config' => $config,
        ];
    }
}
