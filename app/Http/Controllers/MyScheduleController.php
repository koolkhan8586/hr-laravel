<?php

namespace App\Http\Controllers;

use App\Support\DutyRoster;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * An employee's own duty roster.
 *
 * Deliberately scoped to the signed-in user: the admin schedule screens show
 * everybody, this one shows only the person looking at it.
 */
class MyScheduleController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $month = self::monthFrom($request->month);

        return view('schedule.my', [
            'user'      => $user,
            'week'      => DutyRoster::week($user),
            'days'      => DutyRoster::month($user, $month),
            'today'     => DutyRoster::today($user),
            'month'     => $month,
            'monthName' => $month->format('F Y'),
        ]);
    }

    /**
     * The month to show, from ?month=YYYY-MM.
     *
     * A bookmarked or mistyped value falls back to this month rather than
     * throwing the employee a 500.
     */
    protected static function monthFrom($value): Carbon
    {
        $now = Carbon::now(DutyRoster::TIMEZONE)->startOfMonth();

        if (!is_string($value) || !preg_match('/^(\d{4})-(\d{2})$/', $value, $m)) {
            return $now;
        }

        $year  = (int) $m[1];
        $month = (int) $m[2];

        // Carbon happily rolls "2026-99" forward into 2034, which is not a
        // month anybody asked for.
        if ($month < 1 || $month > 12 || $year < 2000 || $year > 2100) {
            return $now;
        }

        return Carbon::create($year, $month, 1, 0, 0, 0, DutyRoster::TIMEZONE)->startOfMonth();
    }
}
