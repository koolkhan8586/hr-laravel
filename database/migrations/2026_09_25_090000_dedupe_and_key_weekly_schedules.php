<?php

use App\Models\WeeklySchedule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One row per employee per day, spelled consistently.
 *
 * The table had no unique key, so an employee could end up with two rows for
 * the same day - one saying OFF and one holding a shift, or the same day
 * spelled "Saturday" and "saturday". The admin grid read the first matching
 * row and the employee's own page read the last, so the two screens showed
 * different timings for the same person on the same day.
 *
 * Every other lookup in the app matches the day name exactly, so normalising
 * the spelling fixes attendance clock-in, auto clock-out and the WhatsApp
 * reminder at the same time.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->normaliseDayNames();
        $this->removeDuplicates();

        if (Schema::hasIndex('weekly_schedules', 'weekly_schedules_user_day_unique')) {
            return;
        }

        Schema::table('weekly_schedules', function (Blueprint $table) {
            $table->unique(['user_id', 'day_of_week'], 'weekly_schedules_user_day_unique');
        });
    }

    public function down(): void
    {
        Schema::table('weekly_schedules', function (Blueprint $table) {
            $table->dropUnique('weekly_schedules_user_day_unique');
        });
    }

    /** "saturday" and " Sat " become "Saturday". */
    protected function normaliseDayNames(): void
    {
        DB::table('weekly_schedules')
            ->select('id', 'day_of_week')
            ->orderBy('id')
            ->chunk(500, function ($rows) {
                foreach ($rows as $row) {

                    $canonical = WeeklySchedule::normaliseDay($row->day_of_week);

                    if ($canonical && $canonical !== $row->day_of_week) {
                        DB::table('weekly_schedules')
                            ->where('id', $row->id)
                            ->update(['day_of_week' => $canonical]);
                    }
                }
            });

        // Anything that is not a day of the week at all cannot be rostered
        // against and would only block the unique key.
        DB::table('weekly_schedules')
            ->whereNotIn('day_of_week', WeeklySchedule::DAYS)
            ->delete();
    }

    /**
     * Keep the most recently updated row for each employee and day.
     *
     * That is the last thing somebody actually saved, so an admin who set a
     * day to OFF keeps the OFF rather than having an older shift resurrected.
     */
    protected function removeDuplicates(): void
    {
        $keep = [];
        $drop = [];

        DB::table('weekly_schedules')
            ->select('id', 'user_id', 'day_of_week', 'updated_at')
            ->orderBy('id')
            ->get()
            ->each(function ($row) use (&$keep, &$drop) {

                $key = $row->user_id.'|'.$row->day_of_week;

                if (!isset($keep[$key])) {
                    $keep[$key] = $row;
                    return;
                }

                $current = $keep[$key];

                $rowIsNewer = ($row->updated_at ?? '') > ($current->updated_at ?? '')
                    || (($row->updated_at ?? '') === ($current->updated_at ?? '') && $row->id > $current->id);

                if ($rowIsNewer) {
                    $drop[] = $current->id;
                    $keep[$key] = $row;
                } else {
                    $drop[] = $row->id;
                }
            });

        foreach (array_chunk($drop, 500) as $chunk) {
            DB::table('weekly_schedules')->whereIn('id', $chunk)->delete();
        }
    }
};
