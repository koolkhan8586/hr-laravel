<?php

namespace App\Http\Controllers;

use App\Support\AttendanceRules;
use App\Support\MonthlyShortfall;
use App\Support\TimeShortfall;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * An employee's own month: the days they were short, and what it will cost.
 *
 * The whole case for counting minutes instead of charging half days is that
 * it is fair, and a figure nobody can see is not fair however it is worked
 * out. So the employee watches the same number the office watches, every
 * day, while there is still a month left to make it up in.
 */
class MyTimingController extends Controller
{
    public function index(Request $request)
    {
        $now = Carbon::now(AttendanceRules::TIMEZONE);

        $month = Carbon::parse(
            ($request->month ?? $now->format('Y-m')).'-01',
            AttendanceRules::TIMEZONE
        )->startOfMonth();

        $user = $request->user();

        $sheet = MonthlyShortfall::forUser($user, $month->copy(), $now);

        // The months they can look back over: this one and the eleven before,
        // never further back than the day the rule was switched on.
        $from = TimeShortfall::rulesFrom()->copy()->startOfMonth();

        $months = [];

        for ($m = $now->copy()->startOfMonth(), $i = 0; $i < 12 && $m->gte($from); $m->subMonthNoOverflow(), $i++) {
            $months[] = $m->copy();
        }

        return view('attendance.my-timing', [
            'sheet'     => $sheet,
            'month'     => $month,
            'months'    => $months,
            'now'       => $now,
            'enabled'   => TimeShortfall::enabled(),
            'rulesFrom' => TimeShortfall::rulesFrom(),
        ]);
    }
}
