<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What marked the attendance, not just where it was marked from.
 *
 * Location alone cannot catch somebody who connects to an office computer
 * from home and marks attendance on it: the office machine is standing in
 * the office, so its position and its address are both perfect. What gives
 * it away is the machine itself - a desktop browser rather than a phone,
 * and usually one that several employees have marked from.
 *
 * So each mark now records the browser it came from, what kind of thing it
 * looks like, and the address it arrived from.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {

            if (! Schema::hasColumn('attendances', 'device_id')) {
                // A token the browser keeps, so the same browser is
                // recognisable from one day to the next.
                $table->string('device_id', 64)->nullable()->after('location_status');
            }

            if (! Schema::hasColumn('attendances', 'device_kind')) {
                $table->string('device_kind', 20)->nullable()->after('device_id');
            }

            if (! Schema::hasColumn('attendances', 'device_ip')) {
                $table->string('device_ip', 45)->nullable()->after('device_kind');
            }

            if (! Schema::hasColumn('attendances', 'device_agent')) {
                $table->string('device_agent', 255)->nullable()->after('device_ip');
            }
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->index(['device_id', 'date'], 'attendances_device_date_index');
        });

        Schema::table('users', function (Blueprint $table) {

            if (! Schema::hasColumn('users', 'attendance_device_exempt')) {
                // For the few who genuinely mark from a shared computer.
                $table->boolean('attendance_device_exempt')
                    ->default(false)
                    ->after('early_leave_allowance_minutes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex('attendances_device_date_index');
        });

        Schema::table('attendances', function (Blueprint $table) {
            foreach (['device_id', 'device_kind', 'device_ip', 'device_agent'] as $column) {
                if (Schema::hasColumn('attendances', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'attendance_device_exempt')) {
                $table->dropColumn('attendance_device_exempt');
            }
        });
    }
};
