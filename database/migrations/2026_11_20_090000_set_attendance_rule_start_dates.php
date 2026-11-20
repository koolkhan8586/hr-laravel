<?php

use App\Support\AttendanceRules;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The day the half day rules start counting.
 *
 * Leaving early and not marking a clock-out are rules about how people work
 * from now on. Without a date stored, the first run of the charging job
 * would look at every open shift already in the table - days worked before
 * anybody was told there was a rule - and charge half a day for each.
 *
 * So the date is pinned here, on the deployment that brings the rules in,
 * and can be moved afterwards from Settings.
 */
return new class extends Migration
{
    public function up(): void
    {
        $today = Carbon::now(AttendanceRules::TIMEZONE)->toDateString();

        $this->putIfMissing(AttendanceRules::RULES_FROM, $today);

        // Same thinking for absences, if that one was switched on before
        // this setting existed.
        $this->putIfMissing(
            AttendanceRules::ABSENCES_FROM,
            Carbon::now(AttendanceRules::TIMEZONE)->format('Y-m')
        );

        // Saturdays count only when that date is scheduled.
        $this->putIfMissing(AttendanceRules::SATURDAY_SET, '1');
    }

    public function down(): void
    {
        DB::table('app_settings')
            ->whereIn('key', [AttendanceRules::RULES_FROM, AttendanceRules::SATURDAY_SET])
            ->delete();
    }

    private function putIfMissing(string $key, string $value): void
    {
        $exists = DB::table('app_settings')->where('key', $key)->exists();

        if ($exists) {
            return;
        }

        DB::table('app_settings')->insert([
            'key' => $key,
            'value' => $value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
