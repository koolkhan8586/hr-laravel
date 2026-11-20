<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Let a leave transaction say what actually happened to the balance.
 *
 * The column was created as enum('approved','rejected','adjustment'), but a
 * half day charged by the system writes "deducted". On MySQL that insert is
 * refused - and it is refused after the balance has already been reduced,
 * because the balance is written first. So the day came off the employee's
 * leave and nothing appeared on the Leave Transaction Report to say it had:
 * the one screen that is supposed to prove the balance moved.
 *
 * Made a plain string, the same as the attendance and loan status columns,
 * because the application already knows the words it uses and the next one
 * should not need a migration.
 *
 * The rows that were lost are rebuilt below.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            Schema::getConnection()->statement(
                "ALTER TABLE leave_transactions MODIFY action VARCHAR(20) NOT NULL DEFAULT 'approved'"
            );
        } else {
            Schema::table('leave_transactions', function (Blueprint $table) {
                $table->string('action', 20)->default('approved')->change();
            });
        }

        $this->rebuildMissingRows();
    }

    public function down(): void
    {
        // Narrowing it again would refuse the rows already written, so this
        // is deliberately left alone.
    }

    /**
     * Write the transaction rows the refused inserts never left behind.
     *
     * Only for half days the system charged against annual leave that have
     * no row at all. The balances are worked out the way the application
     * works them out everywhere else - the opening balance less the approved
     * annual leave up to and including that day - so they line up with the
     * balance the employee has now.
     */
    private function rebuildMissingRows(): void
    {
        if (! Schema::hasColumn('leaves', 'auto_reason')) {
            return;
        }

        $charged = DB::table('leaves')
            ->whereNotNull('auto_reason')
            ->where('type', 'annual')
            ->where('status', 'approved')
            ->whereNotIn('id', DB::table('leave_transactions')->select('leave_id'))
            ->orderBy('user_id')
            ->orderBy('start_date')
            ->get();

        foreach ($charged as $leave) {

            $opening = DB::table('leave_balances')
                ->where('user_id', $leave->user_id)
                ->value('opening_balance');

            if ($opening === null) {
                continue;
            }

            // Everything approved and annual up to this day, this one last.
            $usedToHere = DB::table('leaves')
                ->where('user_id', $leave->user_id)
                ->where('type', 'annual')
                ->where('status', 'approved')
                ->whereDate('start_date', '<=', $leave->start_date)
                ->sum('calculated_days');

            $after  = round($opening - $usedToHere, 2);
            $before = round($after + $leave->calculated_days, 2);

            DB::table('leave_transactions')->insert([
                'user_id'        => $leave->user_id,
                'leave_id'       => $leave->id,
                'days'           => $leave->calculated_days,
                'balance_before' => $before,
                'balance_after'  => $after,
                'action'         => 'deducted',
                'processed_by'   => null,
                'created_at'     => $leave->auto_charged_at ?? $leave->created_at,
                'updated_at'     => now(),
            ]);
        }
    }
};
