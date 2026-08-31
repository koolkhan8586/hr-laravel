<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\Leave;
use App\Models\User;
use App\Models\WeeklySchedule;
use App\Models\WhatsappAttendanceReminder;
use App\Models\WorkFromHome;
use App\Services\WahaService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendAttendanceWhatsAppReminder extends Command
{
    protected $signature = 'attendance:whatsapp-reminder';

    protected $description = 'Send WhatsApp reminder via WAHA if employee has not marked attendance or applied leave 1 hour after shift start';

    public function handle(WahaService $waha): int
    {
        if (!$waha->enabled()) {
            $this->info('WAHA is disabled. Skipping attendance reminders.');
            return self::SUCCESS;
        }

        $now = Carbon::now('Asia/Karachi');
        $today = $now->toDateString();
        $dayName = $now->format('l');

        $schedules = WeeklySchedule::with(['user.staff', 'shift'])
            ->where('day_of_week', $dayName)
            ->whereNotNull('shift_id')
            ->get();

        $sent = 0;
        $skipped = 0;

        foreach ($schedules as $schedule) {
            $user = $schedule->user;
            $shift = $schedule->shift;

            if (!$user || !$shift || $user->role !== 'employee') {
                $skipped++;
                continue;
            }

            if (! $user->isEmployed()) {
                $skipped++;
                continue;
            }

            if (!filled($user->mobile)) {
                $skipped++;
                continue;
            }

            $shiftStart = Carbon::parse($today.' '.$shift->start_time, 'Asia/Karachi');
            $reminderAt = $shiftStart->copy()->addHour();

            // Only remind after 1 hour past shift start, and within a reasonable window
            // so we don't spam people who were somehow missed overnight.
            if ($now->lt($reminderAt) || $now->gt($reminderAt->copy()->addHours(4))) {
                $skipped++;
                continue;
            }

            if ($this->alreadyReminded($user->id, $today)) {
                $skipped++;
                continue;
            }

            if ($this->hasMarkedAttendance($user->id, $today)) {
                $skipped++;
                continue;
            }

            if ($this->hasLeaveCovering($user->id, $today)) {
                $skipped++;
                continue;
            }

            if ($this->isOnWfh($user->id, $today)) {
                $skipped++;
                continue;
            }

            if ($this->isOnHoliday($user->id, $today)) {
                $skipped++;
                continue;
            }

            $message = $this->buildMessage($user->name, $shiftStart);

            $chatId = $waha->toChatId($user->mobile);
            $ok = $waha->sendToMobile($user->mobile, $message);

            WhatsappAttendanceReminder::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'date' => $today,
                ],
                [
                    'shift_start' => $shift->start_time,
                    'mobile' => $user->mobile,
                    'chat_id' => $chatId,
                    'message' => $message,
                    'status' => $ok ? 'sent' : 'failed',
                    'response' => $ok ? 'ok' : 'send_failed',
                ]
            );

            if ($ok) {
                $sent++;
                $this->info("Reminder sent to {$user->name} ({$user->mobile})");
            } else {
                $this->warn("Failed to send reminder to {$user->name} ({$user->mobile})");
                Log::warning('Attendance WhatsApp reminder failed', [
                    'user_id' => $user->id,
                    'mobile' => $user->mobile,
                ]);
            }
        }

        $this->info("Attendance WhatsApp reminders finished. Sent: {$sent}, Skipped: {$skipped}");

        return self::SUCCESS;
    }

    protected function buildMessage(string $name, Carbon $shiftStart): string
    {
        return "Hi {$name},\n\n"
            ."Our records show you have not marked attendance or applied leave yet.\n"
            ."Your shift started at ".$shiftStart->format('h:i A').".\n\n"
            ."Please mark your attendance or apply leave as soon as possible.\n\n"
            ."— LSAF HR";
    }

    protected function alreadyReminded(int $userId, string $today): bool
    {
        return WhatsappAttendanceReminder::where('user_id', $userId)
            ->whereDate('date', $today)
            ->where('status', 'sent')
            ->exists();
    }

    protected function hasMarkedAttendance(int $userId, string $today): bool
    {
        return Attendance::where('user_id', $userId)
            ->whereDate('date', $today)
            ->whereNotNull('clock_in')
            ->exists();
    }

    protected function hasLeaveCovering(int $userId, string $today): bool
    {
        return Leave::where('user_id', $userId)
            ->whereIn('status', ['pending', 'approved'])
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->exists();
    }

    protected function isOnWfh(int $userId, string $today): bool
    {
        return WorkFromHome::where('user_id', $userId)
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->exists();
    }

    protected function isOnHoliday(int $userId, string $today): bool
    {
        $holidays = Holiday::with('users')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->get();

        foreach ($holidays as $holiday) {
            if ((int) $holiday->for_all === 1) {
                return true;
            }

            if ($holiday->users->contains('id', $userId)) {
                return true;
            }
        }

        return false;
    }
}
