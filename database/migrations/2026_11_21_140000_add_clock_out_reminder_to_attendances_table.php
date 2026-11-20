<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * When the employee was reminded to mark their clock-out.
 *
 * The reminder goes out once, a few minutes after the shift ends and well
 * before the half day is charged. Writing the moment onto the attendance row
 * is what stops a job that runs every minute sending it again and again.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('attendances', 'clock_out_reminded_at')) {
            return;
        }

        Schema::table('attendances', function (Blueprint $table) {
            $table->timestamp('clock_out_reminded_at')
                ->nullable()
                ->after('overtime_allowed_until');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('attendances', 'clock_out_reminded_at')) {
            return;
        }

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn('clock_out_reminded_at');
        });
    }
};
