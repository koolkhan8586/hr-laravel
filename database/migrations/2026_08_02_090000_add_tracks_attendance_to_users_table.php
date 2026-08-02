<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Some employees are on the payroll only: they are paid every month but never
 * mark attendance and never apply for leave through the system. Counting them
 * as absent for every working day buries the people who really were away.
 *
 * Everyone keeps being tracked by default; the flag has to be turned off
 * deliberately, one employee at a time or in bulk from the staff list.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'tracks_attendance')) {
                $table->boolean('tracks_attendance')->default(true);
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'tracks_attendance')) {
                $table->dropColumn('tracks_attendance');
            }
        });
    }
};
