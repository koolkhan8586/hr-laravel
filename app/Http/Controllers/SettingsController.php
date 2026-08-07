<?php

namespace App\Http\Controllers;

use App\Models\DailyReportWhatsappNumber;
use App\Services\WahaService;
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

        return view('admin.settings.index', compact(
            'wahaStatus',
            'mailStatus',
            'dailyNumbers',
            'envDailyNumbers'
        ));
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
