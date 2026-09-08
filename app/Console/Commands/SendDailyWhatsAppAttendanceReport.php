<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\DailyReportRun;
use App\Models\Holiday;
use App\Models\Leave;
use App\Models\User;
use App\Models\WorkFromHome;
use App\Services\WahaService;
use App\Support\DailyReportSchedule;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendDailyWhatsAppAttendanceReport extends Command
{
    protected $signature = 'attendance:whatsapp-daily-report
                            {--force : Send straight away, whatever the time and even if today\'s report already went out}';

    protected $description = 'Send the daily Absent / Late / Leave list to the configured WhatsApp numbers at the time set in Settings, retrying if WAHA is down';

    /**
     * Runs every minute. It works out for itself whether the report is due,
     * so the time can be changed in Settings without touching the crontab,
     * and a report missed because WAHA was down is tried again rather than
     * being lost for the day.
     */
    public function handle(WahaService $waha): int
    {
        $force = (bool) $this->option('force');
        $now   = Carbon::now(DailyReportSchedule::TIMEZONE);
        $today = $now->toDateString();

        if (!$force && !DailyReportSchedule::isDue($now)) {
            return self::SUCCESS;
        }

        $run = DailyReportRun::forDate($today);

        // Already delivered today; a manual send can still override.
        if (!$force && $run->wasSent()) {
            return self::SUCCESS;
        }

        $result = $this->deliver($waha, $now, $today, $run, $force);

        $this->info($result['message']);

        return self::SUCCESS;
    }

    /**
     * Build and send the report, recording how it went.
     *
     * @return array{ok: bool, message: string, sent: int, failed: int}
     */
    public function deliver(
        WahaService $waha,
        Carbon $now,
        string $today,
        DailyReportRun $run,
        bool $manual = false
    ): array {

        $fail = function (string $reason) use ($run, $now, $manual) {
            $run->fill([
                'status'          => 'failed',
                'attempts'        => $run->attempts + 1,
                'last_attempt_at' => $now,
                'last_error'      => $reason,
                'manual'          => $manual || $run->manual,
            ])->save();

            return ['ok' => false, 'message' => $reason, 'sent' => 0, 'failed' => 0];
        };

        if (!$waha->enabled()) {
            return $fail('WhatsApp (WAHA) is switched off, so the report could not be sent.');
        }

        $status = $waha->connectionStatus();

        if (!$status['connected']) {
            return $fail('WhatsApp session is '.$status['status'].' — '.$status['detail']);
        }

        $mobiles = $waha->dailyReportMobiles();

        if (empty($mobiles)) {
            return $fail('No daily report WhatsApp numbers have been added.');
        }

        $message = $this->buildMessage($today, $now);

        $sent = 0;
        $failed = [];

        foreach ($mobiles as $mobile) {
            if ($waha->sendToMobile($mobile, $message)) {
                $sent++;
            } else {
                $failed[] = $mobile;
                Log::warning('WAHA daily attendance report failed', ['mobile' => $mobile]);
            }
        }

        // Anything delivered counts as done for the day; retrying would only
        // send the same report twice to the numbers that already had it.
        $ok = $sent > 0;

        $run->fill([
            'status'          => $ok ? 'sent' : 'failed',
            'attempts'        => $run->attempts + 1,
            'sent_count'      => $sent,
            'failed_count'    => count($failed),
            'last_attempt_at' => $now,
            'sent_at'         => $ok ? $now : $run->sent_at,
            'last_error'      => $failed
                ? 'Could not reach: '.implode(', ', $failed)
                : null,
            'manual'          => $manual || $run->manual,
        ])->save();

        $message = $ok
            ? 'Report sent to '.$sent.' number(s)'.($failed ? ', '.count($failed).' failed' : '').'.'
            : 'The report could not be sent to any number.';

        return ['ok' => $ok, 'message' => $message, 'sent' => $sent, 'failed' => count($failed)];
    }

    protected function buildMessage(string $today, Carbon $now): string
    {
        $lateRecords = Attendance::with('user')
            ->whereDate('date', $today)
            ->where('status', 'late')
            ->whereHas('user', fn ($q) => $q->where('role', 'employee')->forAttendanceRoster())
            ->get()
            ->unique('user_id')
            ->values();

        $leaveRecords = Leave::with('user')
            ->whereIn('status', ['pending', 'approved'])
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->whereHas('user', fn ($q) => $q->where('role', 'employee')->employed())
            ->get();

        $attendanceUserIds = Attendance::whereDate('date', $today)
            ->whereNotNull('clock_in')
            ->pluck('user_id')
            ->all();

        $leaveUserIds = $leaveRecords->pluck('user_id')->all();

        $wfhUserIds = WorkFromHome::whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->pluck('user_id')
            ->all();

        $holidayUserIds = $this->holidayUserIds($today);

        $isWeekend = Carbon::parse($today, 'Asia/Karachi')->isWeekend();

        $absentEmployees = collect();

        if (!$isWeekend) {
            $absentEmployees = User::where('role', 'employee')
                ->forAttendanceRoster()
                ->whereNotIn('id', $attendanceUserIds)
                ->whereNotIn('id', $leaveUserIds)
                ->whereNotIn('id', $wfhUserIds)
                ->whereNotIn('id', $holidayUserIds)
                ->orderBy('name')
                ->get();
        }

        $lines = [];
        $lines[] = 'LSAF HR — Daily Attendance Report';
        $lines[] = $now->format('d M Y, h:i A');
        $lines[] = '';

        $lines[] = '❌ ABSENT ('.$absentEmployees->count().')';
        if ($absentEmployees->isEmpty()) {
            $lines[] = '- None';
        } else {
            foreach ($absentEmployees as $i => $user) {
                $lines[] = ($i + 1).'. '.$user->name;
            }
        }

        $lines[] = '';
        $lines[] = '⏰ LATE ('.$lateRecords->count().')';
        if ($lateRecords->isEmpty()) {
            $lines[] = '- None';
        } else {
            foreach ($lateRecords as $i => $record) {
                $name = $record->user->name ?? 'Unknown';
                $time = $record->clock_in
                    ? Carbon::parse($record->clock_in)->format('h:i A')
                    : '-';
                $lines[] = ($i + 1).'. '.$name.' ('.$time.')';
            }
        }

        $lines[] = '';
        $lines[] = '🏖 LEAVE ('.$leaveRecords->count().')';
        if ($leaveRecords->isEmpty()) {
            $lines[] = '- None';
        } else {
            foreach ($leaveRecords as $i => $leave) {
                $name = $leave->user->name ?? 'Unknown';
                $type = ucfirst(str_replace('_', ' ', $leave->type));
                $status = ucfirst($leave->status);
                $days = $leave->calculated_days;
                $lines[] = ($i + 1).'. '.$name.' — '.$type.' ('.$days.' day'.($days == 1 ? '' : 's').', '.$status.')';
            }
        }

        $lines[] = '';
        $lines[] = '— LSAF HR System';

        return implode("\n", $lines);
    }

    /**
     * @return array<int, int>
     */
    protected function holidayUserIds(string $today): array
    {
        $holidayUsers = [];

        $holidays = Holiday::with('users')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->get();

        foreach ($holidays as $holiday) {
            if ((int) $holiday->for_all === 1) {
                return User::where('role', 'employee')->pluck('id')->all();
            }

            foreach ($holiday->users as $user) {
                $holidayUsers[] = $user->id;
            }
        }

        return array_values(array_unique($holidayUsers));
    }
}
