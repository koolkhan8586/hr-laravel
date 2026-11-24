<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Services\WahaService;
use App\Support\AttendanceRules;
use App\Support\DutyRoster;
use App\Support\HalfDayCharge;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * A nudge to mark the clock-out, before it costs anything.
 *
 * Most people who never mark one simply walked out and forgot. The half day
 * lands half an hour after the shift ends; this goes out ten minutes after
 * it, says when the charge is due, and gives them the chance to avoid it.
 *
 * Once per day per employee: the moment it is sent is written onto the
 * attendance row, because this runs every minute.
 */
class SendClockOutReminder extends Command
{
    protected $signature = 'attendance:clockout-reminder';

    protected $description = 'Remind employees who have not marked a clock-out, a few minutes after their shift ends';

    public function handle(WahaService $waha): int
    {
        if (! AttendanceRules::remindsAboutClockOut()) {
            $this->info('Clock-out reminders are switched off.');
            return self::SUCCESS;
        }

        $now = Carbon::now(AttendanceRules::TIMEZONE);

        $after = AttendanceRules::reminderMinutes();

        // Today and yesterday, for shifts that run past midnight.
        $open = Attendance::with('user')
            ->whereNotNull('clock_in')
            ->whereNull('clock_out')
            ->whereNull('clock_out_reminded_at')
            ->whereDate('date', '>=', $now->copy()->subDay()->toDateString())
            ->whereDate('date', '<=', $now->toDateString())
            ->get();

        $sent = 0;
        $skipped = 0;

        foreach ($open as $attendance) {

            $user = $attendance->user;

            if (! $user || ! $user->isEmployed()) {
                $skipped++;
                continue;
            }

            $date = HalfDayCharge::dateOf($attendance);

            if (! $date) {
                $skipped++;
                continue;
            }

            $endsAt = DutyRoster::endsAt($user, $date);

            if (! $endsAt) {
                $skipped++;
                continue;
            }

            // An admin who allowed overtime moved the end of the day.
            if ($attendance->overtime_allowed_until) {
                $endsAt = Carbon::parse($attendance->overtime_allowed_until, AttendanceRules::TIMEZONE);
            }

            $remindAt = $endsAt->copy()->addMinutes($after);

            // Not yet, or so long ago that a reminder would only confuse -
            // the day has been dealt with one way or another by then.
            if ($now->lt($remindAt) || $now->gt($remindAt->copy()->addHours(3))) {
                $skipped++;
                continue;
            }

            // Already charged: nothing left to warn about.
            if (HalfDayCharge::existing($user->id, $date)) {
                $skipped++;
                continue;
            }

            $this->send($waha, $attendance, $endsAt);

            $attendance->forceFill(['clock_out_reminded_at' => $now])->save();

            $sent++;

            $this->info('Reminded '.$user->name.' ('.$date->toDateString().')');
        }

        $this->info("Clock-out reminders finished. Sent: {$sent}, Skipped: {$skipped}");

        return self::SUCCESS;
    }

    /**
     * Email, and WhatsApp where there is a number on file.
     *
     * Neither may stop the run: a reminder that does not get out is worth
     * less than the next employee's reminder getting out.
     */
    protected function send(WahaService $waha, Attendance $attendance, Carbon $endsAt): void
    {
        $user = $attendance->user;

        $body = $this->buildMessage($attendance, $endsAt);

        try {
            if (filled($user->email)) {
                Mail::raw($body, function ($message) use ($user) {
                    $message->to($user->email)->subject('Please mark your clock-out');
                });
            }
        } catch (\Throwable $e) {
            Log::error('Clock-out reminder email failed', [
                'user_id' => $user->id,
                'message' => $e->getMessage(),
            ]);
        }

        if (! filled($user->mobile) || ! $waha->enabled()) {
            return;
        }

        try {
            if (! $waha->sendToMobile($user->mobile, $body."\n\n- LSAF HR")) {
                Log::warning('Clock-out reminder WhatsApp was not delivered', ['user_id' => $user->id]);
            }
        } catch (\Throwable $e) {
            Log::error('Clock-out reminder WhatsApp failed', [
                'user_id' => $user->id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    protected function buildMessage(Attendance $attendance, Carbon $endsAt): string
    {
        $user = $attendance->user;

        $lines = [
            'Hi '.$user->name.',',
            '',
            'Your shift ended at '.$endsAt->format('h:i A').' and no clock-out has been marked.',
            'Please mark it now from the HR portal.',
        ];

        $date = HalfDayCharge::dateOf($attendance);

        // Under the time rule nothing is charged on the day: a day with no
        // clock-out counts half the shift, added up with the rest of the
        // month. Say that, not a half day that is no longer charged.
        if ($date && \App\Support\TimeShortfall::enabled()) {

            if (\App\Support\TimeShortfall::appliesOn($date)) {
                $lines[] = '';
                $lines[] = 'If it is not marked, this day counts as time short - half your shift -'
                    .' which is added up at the end of the month. Marking it now avoids that.';
            }

        } elseif ($date && AttendanceRules::rulesApplyOn($date)) {

            $chargeAt = $endsAt->copy()->addMinutes(AttendanceRules::missingMinutes());

            $lines[] = '';
            $lines[] = 'If it is still not marked by '.$chargeAt->format('h:i A')
                .', half a day will be charged - taken off your annual leave,'
                .' or without pay if you have none left.';
        }

        return implode("\n", $lines);
    }
}
