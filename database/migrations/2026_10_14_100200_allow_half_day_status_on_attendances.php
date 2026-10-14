<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Let an attendance row say "half day".
 *
 * The column was created as enum('present','late','absent'), but four places
 * write 'half_day' to it: clocking out after a short day, the admin marking
 * somebody manually, editing a record by hand, and a half day taken as leave.
 * On MySQL that is a truncation - the status is lost or the write fails
 * outright - which is why half days did not stick and the summary could not
 * count them.
 *
 * Done as a plain string rather than a wider enum: the application already
 * knows the values it uses, and the next status added should not need a
 * migration to go with it.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('attendances', 'status')) {
            return;
        }

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            Schema::getConnection()->statement(
                "ALTER TABLE attendances MODIFY status VARCHAR(20) NOT NULL DEFAULT 'present'"
            );

            return;
        }

        Schema::table('attendances', function (Blueprint $table) {
            $table->string('status', 20)->default('present')->change();
        });
    }

    public function down(): void
    {
        // Narrowing it again would throw away every half day on record, so
        // this is deliberately left alone.
    }
};
