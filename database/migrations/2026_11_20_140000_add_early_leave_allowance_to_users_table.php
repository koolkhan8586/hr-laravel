<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * How early one particular employee may leave without it costing half a day.
 *
 * The company rule is one number for everybody. Some people are allowed to
 * go earlier than that - a school run, a standing arrangement - and charging
 * them half a day for it every evening is wrong. This is their own number,
 * and blank means the company rule applies.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'early_leave_allowance_minutes')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedSmallInteger('early_leave_allowance_minutes')
                ->nullable()
                ->after('attendance_override_until');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'early_leave_allowance_minutes')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('early_leave_allowance_minutes');
        });
    }
};
