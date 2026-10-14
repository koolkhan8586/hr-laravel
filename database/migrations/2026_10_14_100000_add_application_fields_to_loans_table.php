<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What an employee's own loan application needs to carry.
 *
 * Until now a loan row only held the money. An application also has to say
 * what it is for, when the employee wants the deductions to start, who
 * decided it and when, and whether the approvers were told about it - none of
 * which had anywhere to live.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {

            if (! Schema::hasColumn('loans', 'purpose')) {
                $table->text('purpose')->nullable()->after('status');
            }

            // When the employee would like the salary deductions to begin.
            if (! Schema::hasColumn('loans', 'deduction_start_month')) {
                $table->date('deduction_start_month')->nullable()->after('purpose');
            }

            if (! Schema::hasColumn('loans', 'requested_at')) {
                $table->timestamp('requested_at')->nullable()->after('deduction_start_month');
            }

            if (! Schema::hasColumn('loans', 'decided_by')) {
                $table->string('decided_by')->nullable()->after('requested_at');
            }

            // dashboard or whatsapp, so a decision taken from a WhatsApp link
            // can be told apart from one taken in the panel.
            if (! Schema::hasColumn('loans', 'decided_via')) {
                $table->string('decided_via', 20)->nullable()->after('decided_by');
            }

            if (! Schema::hasColumn('loans', 'decided_at')) {
                $table->timestamp('decided_at')->nullable()->after('decided_via');
            }

            if (! Schema::hasColumn('loans', 'decision_remarks')) {
                $table->string('decision_remarks')->nullable()->after('decided_at');
            }

            if (! Schema::hasColumn('loans', 'whatsapp_notify_status')) {
                $table->string('whatsapp_notify_status', 20)->nullable()->after('decision_remarks');
            }

            if (! Schema::hasColumn('loans', 'whatsapp_notified_at')) {
                $table->timestamp('whatsapp_notified_at')->nullable()->after('whatsapp_notify_status');
            }
        });

        /*
        | The status column was created as enum('pending','approved','rejected')
        | but the ledger code has always written 'closed' and 'completed' to it
        | when a loan is paid off. On MySQL that is either a truncation warning
        | or an outright error depending on strict mode, so the column has to
        | admit the values the application actually uses.
        */
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            Schema::getConnection()->statement(
                "ALTER TABLE loans MODIFY status
                 ENUM('pending','approved','rejected','closed','completed')
                 NOT NULL DEFAULT 'pending'"
            );
        }
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            foreach ([
                'purpose',
                'deduction_start_month',
                'requested_at',
                'decided_by',
                'decided_via',
                'decided_at',
                'decision_remarks',
                'whatsapp_notify_status',
                'whatsapp_notified_at',
            ] as $column) {
                if (Schema::hasColumn('loans', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
