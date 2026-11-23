<?php

namespace App\Http\Controllers;

use App\Exports\StaffMonthlyReportExport;
use App\Models\User;
use App\Support\AttendanceRules;
use App\Support\MonthlyShortfall;
use App\Support\TimeShortfall;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

/**
 * An employee's own month: the days they were short, and what it will cost.
 *
 * The whole case for counting minutes instead of charging half days is that
 * it is fair, and a figure nobody can see is not fair however it is worked
 * out. So the employee watches the same number the office watches, every
 * day, while there is still a month left to make it up in.
 *
 * The office opens the very same page for any one employee, so what an admin
 * reads and what the employee reads can never differ.
 */
class MyTimingController extends Controller
{
    public function index(Request $request)
    {
        return view('attendance.my-timing', self::data($request->user(), $request, false));
    }

    public function export(Request $request)
    {
        return self::download($request->user(), $request);
    }

    /** The page for one employee, as the office sees it. */
    public static function data(User $subject, Request $request, bool $asAdmin): array
    {
        $now = Carbon::now(AttendanceRules::TIMEZONE);

        $month = Carbon::parse(
            ($request->month ?? $now->format('Y-m')).'-01',
            AttendanceRules::TIMEZONE
        )->startOfMonth();

        $sheet = MonthlyShortfall::forUser($subject, $month->copy(), $now);

        // The months to look back over: this one and the eleven before, never
        // further back than the day the rule was switched on - but always
        // including the one being shown.
        $from = TimeShortfall::rulesFrom()->copy()->startOfMonth();

        $months = [];

        for ($m = $now->copy()->startOfMonth(), $i = 0; $i < 12; $m->subMonthNoOverflow(), $i++) {
            if ($m->gte($from) || $m->format('Y-m') === $month->format('Y-m')) {
                $months[] = $m->copy();
            }
        }

        return [
            'sheet'     => $sheet,
            'month'     => $month,
            'months'    => $months,
            'now'       => $now,
            'enabled'   => TimeShortfall::enabled(),
            'rulesFrom' => TimeShortfall::rulesFrom(),
            'subject'   => $subject,
            'asAdmin'   => $asAdmin,
        ];
    }

    /** One employee's month, day by day, as an Excel sheet. */
    public static function download(User $subject, Request $request)
    {
        $data  = self::data($subject, $request, false);
        $sheet = $data['sheet'];

        $rows = [];

        foreach ($sheet['days'] as $i => $day) {
            $rows[] = [
                $i + 1,
                $day['date']->format('d M Y'),
                $day['day_name'],
                $day['timing'] ?? '-',
                $day['clock_in'] ?? '-',
                $day['clock_out'] ?? 'Not marked',
                $day['late'] > 0 ? TimeShortfall::label($day['late']) : '-',
                $day['early'] > 0 ? TimeShortfall::label($day['early']) : '-',
                $day['missing'] > 0 ? TimeShortfall::label($day['missing']) : '-',
                TimeShortfall::label($day['minutes']),
            ];
        }

        // The total, and what it comes to, as the last two lines.
        $rows[] = ['', 'TOTAL', '', '', '', '',
            TimeShortfall::label($sheet['late']),
            TimeShortfall::label($sheet['early']),
            TimeShortfall::label($sheet['missing']),
            TimeShortfall::label($sheet['minutes'])];

        $rows[] = ['', 'Comes to (days)', $sheet['days_charged'], '',
            'From leave balance', $sheet['from_balance'], 'Without pay', $sheet['without_pay'], '', ''];

        $filename = 'timing_'.$data['month']->format('Y-m').'_'.Str::slug($subject->name).'.xlsx';

        return Excel::download(
            new StaffMonthlyReportExport(
                ['#', 'Date', 'Day', 'Timing', 'Clock In', 'Clock Out',
                 'Late', 'Left Early', 'No Clock-out', 'Short By'],
                $rows
            ),
            $filename
        );
    }
}
