<?php

use App\Support\AttendanceRules;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Sunday joins Saturday as a day that counts only when it is scheduled.
 *
 * Sunday is off for everybody here, but a couple of weekly schedules have it
 * sitting in them as a working day - left over from however they were first
 * filled in - so every Sunday was counting as an absence against those
 * employees, and only those employees. Which is money, now that absences
 * come off the pay.
 *
 * The old Saturday-only setting is carried over: if somebody had turned it
 * off, Saturdays stay on the weekly pattern and only Sunday is added.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('app_settings')->where('key', AttendanceRules::DAYS_SET)->exists()) {
            return;
        }

        $saturday = DB::table('app_settings')
            ->where('key', AttendanceRules::SATURDAY_SET)
            ->value('value');

        $days = ($saturday !== null && !$saturday)
            ? 'sunday'
            : 'saturday,sunday';

        DB::table('app_settings')->insert([
            'key' => AttendanceRules::DAYS_SET,
            'value' => $days,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('app_settings')
            ->where('key', AttendanceRules::DAYS_SET)
            ->delete();
    }
};
