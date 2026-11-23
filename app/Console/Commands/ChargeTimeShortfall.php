<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\AttendanceRules;
use App\Support\MonthlyShortfall;
use App\Support\TimeShortfall;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Settle a finished month's missed time.
 *
 * Minutes are counted every day but charged only here, once, after the month
 * has closed - because the whole point of counting in minutes is that the
 * arithmetic is only fair when the month can be seen whole. Twenty minutes
 * late on a Monday is twenty minutes. The same twenty minutes every working
 * day is seven hours, and seven hours is most of a day.
 *
 * Runs on the first of the month for the month just gone. It can be run by
 * hand for any past month, and running it twice changes nothing: a month
 * already charged is found and left alone.
 */
class ChargeTimeShortfall extends Command
{
    protected $signature = 'attendance:charge-shortfall
                            {--month= : The month to settle, as YYYY-MM. Defaults to last month.}
                            {--dry-run : Work it all out and print it, but charge nothing.}';

    protected $description = 'Charge the time short over a finished month, off the leave balance or without pay';

    public function handle(): int
    {
        if (! TimeShortfall::enabled()) {
            $this->info('Half days are being charged per offence, not time, so nothing is settled here.');
            $this->line('Switch it on under Settings > Attendance Rules > How short time is charged.');

            return self::SUCCESS;
        }

        $now = Carbon::now(AttendanceRules::TIMEZONE);

        $month = $this->option('month')
            ? Carbon::parse($this->option('month').'-01', AttendanceRules::TIMEZONE)->startOfMonth()
            : $now->copy()->subMonthNoOverflow()->startOfMonth();

        if ($month->copy()->endOfMonth()->gte($now->copy()->startOfDay())) {
            $this->warn($month->format('F Y').' is not over yet, so there is nothing to settle.');

            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');

        $this->info(($dryRun ? 'Working out ' : 'Settling ').$month->format('F Y').'.');

        $users = User::where('role', 'employee')
            ->employed()
            ->tracked()
            ->orderBy('name')
            ->get();

        $charged = 0;
        $rows    = [];

        foreach ($users as $user) {

            $sheet = MonthlyShortfall::forUser($user, $month->copy(), $now);

            if ($sheet['days_charged'] <= 0) {
                continue;
            }

            if ($dryRun) {

                $rows[] = [
                    $user->name,
                    TimeShortfall::label($sheet['minutes']),
                    $sheet['days_charged'],
                    $sheet['from_balance'],
                    $sheet['without_pay'],
                    $sheet['charge'] ? 'already charged' : 'would charge',
                ];

                continue;
            }

            $charge = MonthlyShortfall::charge($user, $month->copy(), $now);

            if (! $charge) {
                continue;
            }

            // Already on file from an earlier run: left exactly as it was.
            if (! $charge->wasRecentlyCreated) {
                continue;
            }

            $charged++;

            $rows[] = [
                $user->name,
                $charge->timeLabel(),
                $charge->days_charged,
                $charge->days_from_balance,
                $charge->days_without_pay,
                'charged',
            ];
        }

        if ($rows) {
            $this->table(
                ['Employee', 'Time short', 'Days', 'From balance', 'Without pay', ''],
                $rows
            );
        }

        $this->info($dryRun
            ? 'Dry run: nothing was charged.'
            : $charged.' employee(s) charged for '.$month->format('F Y').'.');

        return self::SUCCESS;
    }
}
