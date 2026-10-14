<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Let a loan row say "cancelled".
 *
 * An employee may withdraw their own application while it is still waiting
 * for a decision. The row is kept rather than deleted so the approvers - who
 * were sent a WhatsApp about it - see that it was withdrawn instead of
 * finding a dead link.
 *
 * Made a plain string for the same reason the attendance status is: the
 * application knows the values it uses, and the next one should not need a
 * migration to go with it.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('loans', 'status')) {
            return;
        }

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            Schema::getConnection()->statement(
                "ALTER TABLE loans MODIFY status VARCHAR(20) NOT NULL DEFAULT 'pending'"
            );

            return;
        }

        Schema::table('loans', function (Blueprint $table) {
            $table->string('status', 20)->default('pending')->change();
        });
    }

    public function down(): void
    {
        // Narrowing it again would throw away withdrawn applications, so this
        // is deliberately left alone.
    }
};
