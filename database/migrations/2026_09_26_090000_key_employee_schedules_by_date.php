<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One override row per employee per date.
 *
 * Same lesson as weekly_schedules: without a unique key two rows can exist
 * for one date and nothing decides which counts, so one screen can show a
 * shift while another shows the day off.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->removeDuplicates();

        if (Schema::hasIndex('employee_schedules', 'employee_schedules_user_date_unique')) {
            return;
        }

        Schema::table('employee_schedules', function (Blueprint $table) {
            $table->unique(['user_id', 'date'], 'employee_schedules_user_date_unique');
        });
    }

    public function down(): void
    {
        Schema::table('employee_schedules', function (Blueprint $table) {
            $table->dropUnique('employee_schedules_user_date_unique');
        });
    }

    /** Keep the most recently updated row for each employee and date. */
    protected function removeDuplicates(): void
    {
        $keep = [];
        $drop = [];

        DB::table('employee_schedules')
            ->select('id', 'user_id', 'date', 'updated_at')
            ->orderBy('id')
            ->get()
            ->each(function ($row) use (&$keep, &$drop) {

                // The column is a date, but a stored value may carry a time.
                $date = substr((string) $row->date, 0, 10);
                $key  = $row->user_id.'|'.$date;

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
            DB::table('employee_schedules')->whereIn('id', $chunk)->delete();
        }
    }
};
