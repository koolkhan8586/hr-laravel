<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\Leave;
use App\Models\User;
use App\Models\WorkFromHome;
use App\Support\DutyRoster;
use Carbon\Carbon;

class MarkAbsent extends Command
{
    protected $signature = 'attendance:mark-absent';
    protected $description = 'Mark employees absent if not clocked in by 11:45 AM';

    public function handle()
    {
        $now = Carbon::now('Asia/Karachi');

        // Only run after 11:45 AM
        if ($now->lt(Carbon::createFromTime(11, 45, 0, 'Asia/Karachi'))) {
            $this->info('Too early to mark absent.');
            return;
        }

        $today = $now->toDateString();

        // Payroll-only staff are paid but never mark attendance, so writing an
        // absent row for them every day is what filled the reports with noise.
        $users = User::tracked()->employed()->get();

        $holidays = Holiday::with('users')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->get();

        $marked = 0;

        foreach ($users as $user) {

            /*
            | An absence now costs a day's pay, so this has to be right.
            | Before, the row was written with no date on it at all and the
            | duplicate check looked at clock_in - which an absent row does
            | not have - so every run wrote another dateless row, for people
            | who were on leave, on holiday, or not even rostered that day.
            */
            $alreadyMarked = Attendance::where('user_id', $user->id)
                ->where(function ($q) use ($today) {
                    $q->whereDate('date', $today)
                      ->orWhereDate('clock_in', $today);
                })
                ->exists();

            if ($alreadyMarked) {
                continue;
            }

            /*
            | Not rostered today: nothing to be absent from.
            |
            | The roster, not the weekly pattern alone. Sunday is off for
            | everybody and Saturdays are decided date by date, but a couple of
            | employees have Sunday sitting in their weekly pattern, so asking
            | only "does the pattern have a shift today?" wrote an absent row
            | for them every Sunday - a row the calendar then showed as an
            | absence and the daily report listed by name.
            */
            if (!(DutyRoster::workingDatesFor([$user], $now, $now)[$user->id]['rostered'][$today] ?? false)) {
                continue;
            }

            $onHoliday = $holidays->contains(
                fn ($h) => (int) $h->for_all === 1 || $h->users->contains('id', $user->id)
            );

            if ($onHoliday) {
                continue;
            }

            $onLeave = Leave::where('user_id', $user->id)
                ->where('status', 'approved')
                ->whereDate('start_date', '<=', $today)
                ->whereDate('end_date', '>=', $today)
                ->exists();

            if ($onLeave) {
                continue;
            }

            $workingFromHome = WorkFromHome::where('user_id', $user->id)
                ->whereDate('start_date', '<=', $today)
                ->whereDate('end_date', '>=', $today)
                ->exists();

            if ($workingFromHome) {
                continue;
            }

            // Nobody is absent before they joined.
            $joined = $user->staff->joining_date ?? null;

            if ($joined && Carbon::parse($joined)->startOfDay()->gt($now->copy()->startOfDay())) {
                continue;
            }

            Attendance::create([
                'user_id'   => $user->id,
                'date'      => $today,
                'clock_in'  => null,
                'clock_out' => null,
                'status'    => 'absent',
            ]);

            $marked++;

            $this->info("Marked absent: {$user->name}");
        }

        $this->info('Absent marking completed. '.$marked.' marked.');
    }
}
