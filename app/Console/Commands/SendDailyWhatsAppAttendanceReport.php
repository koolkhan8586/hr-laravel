<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\Leave;
use App\Models\User;
use App\Models\WorkFromHome;
use App\Services\WahaService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendDailyWhatsAppAttendanceReport extends Command
{
    protected $signature = 'attendance:whatsapp-daily-report';

    protected $description = 'Send daily Absent / Late / Leave employee list to configured WhatsApp numbers at 11:38 AM';

    public function handle(WahaService $waha): int
    {
        if (!$waha->enabled()) {
            $this->info('WAHA is disabled. Skipping daily WhatsApp report.');
            return self::SUCCESS;
        }

        $mobiles = $waha->dailyReportMobiles();

        if (empty($mobiles)) {
            $this->warn('No WAHA_DAILY_REPORT_MOBILES configured. Skipping.');
            return self::SUCCESS;
        }

        $now = Carbon::now('Asia/Karachi');
        $today = $now->toDateString();
        $message = $this->buildMessage($today, $now);

        $sent = 0;

        foreach ($mobiles as $mobile) {
            if ($waha->sendToMobile($mobile, $message)) {
                $sent++;
                $this->info("Daily report sent to {$mobile}");
            } else {
                $this->warn("Failed to send daily report to {$mobile}");
                Log::warning('WAHA daily attendance report failed', ['mobile' => $mobile]);
            }
        }

        $this->info("Daily WhatsApp attendance report finished. Sent: {$sent}/".count($mobiles));

        return self::SUCCESS;
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
