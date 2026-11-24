<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\User;
use App\Support\AttendanceRules;
use App\Support\DutyRoster;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Delete "absent" rows written for days nobody was due in.
 *
 * The nightly job used to look only at the weekly pattern, so an employee with
 * Sunday in theirs got an absent row every Sunday. The salary and the monthly
 * report already ignore such a row, but the calendar, the absent reports and
 * the daily WhatsApp list read it as an absence. They are only ever rows with
 * no clock-in, no leave and no explanation, on a day the roster says was off.
 *
 * Never touches a day somebody clocked in on, or any day they were due in.
 */
class CleanStrayAbsences extends Command
{
    protected $signature = 'attendance:clean-stray-absences
                            {--from= : First date to look at, YYYY-MM-DD. Defaults to the start of last month.}
                            {--to= : Last date to look at. Defaults to yesterday.}
                            {--dry-run : List what would be deleted and delete nothing.}';

    protected $description = 'Delete absent rows written for days the employee was not rostered on';

    public function handle(): int
    {
        $now  = Carbon::now(AttendanceRules::TIMEZONE);
        $from = Carbon::parse($this->option('from') ?: $now->copy()->subMonthNoOverflow()->startOfMonth()->toDateString(), AttendanceRules::TIMEZONE)->startOfDay();
        $to   = Carbon::parse($this->option('to') ?: $now->copy()->subDay()->toDateString(), AttendanceRules::TIMEZONE)->startOfDay();
        $dry  = (bool) $this->option('dry-run');

        $this->info(($dry ? 'Looking at' : 'Cleaning').' absent rows from '.$from->format('d M Y').' to '.$to->format('d M Y').'.');

        $rows = Attendance::with('user')
            ->where('status', 'absent')
            ->whereNull('clock_in')
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('date')
            ->get();

        $deleted = [];

        foreach ($rows->groupBy('user_id') as $userId => $userRows) {

            $user = $userRows->first()->user;

            if (! $user) {
                continue;
            }

            $roster = DutyRoster::workingDatesFor([$user], $from, $to)[$user->id];

            // No roster on file at all means nobody can say they were off.
            if (! $roster['known']) {
                continue;
            }

            foreach ($userRows as $row) {

                $date = Carbon::parse($row->date)->toDateString();

                if ($roster['rostered'][$date] ?? true) {
                    continue;
                }

                $deleted[] = [$user->name, Carbon::parse($date)->format('D d M Y'), $roster['source'][$date] ?? '-'];

                if (! $dry) {
                    $row->delete();
                }
            }
        }

        if ($deleted) {
            $this->table(['Employee', 'Day', 'Why it was off'], $deleted);
        }

        $this->info(count($deleted).' row(s) '.($dry ? 'would be ' : '').'deleted.');

        return self::SUCCESS;
    }
}
