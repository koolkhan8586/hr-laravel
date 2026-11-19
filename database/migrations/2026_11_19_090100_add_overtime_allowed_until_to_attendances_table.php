<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The column the Allow Overtime button has always written to.
 *
 * Three places read attendances.overtime_allowed_until - the admin button
 * that sets it, the clock-out screen, and now the half day charged when no
 * clock-out is marked - but no migration ever created it, so pressing Allow
 * Overtime failed on a column that was not there.
 *
 * It matters more now: an admin who gives somebody extra time is saying the
 * day ends later, and without this the half day would be charged while they
 * were still working.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('attendances', 'overtime_allowed_until')) {
            return;
        }

        Schema::table('attendances', function (Blueprint $table) {
            $table->timestamp('overtime_allowed_until')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('attendances', 'overtime_allowed_until')) {
            return;
        }

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn('overtime_allowed_until');
        });
    }
};
