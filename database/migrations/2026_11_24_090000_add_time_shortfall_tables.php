<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Time owed, charged once a month instead of half a day at a time.
 *
 * Three things are needed for that:
 *
 *  - a record of what was charged for which month, so running the job twice
 *    cannot charge twice and the employee can be shown the figure;
 *  - a balance transaction that does not have to belong to a leave, because
 *    a month's missed time is not a day off - writing one as a leave would
 *    put a fictitious absence on a day the employee actually worked, and the
 *    attendance report would then show it;
 *  - a line on that transaction saying what it was for, since without a
 *    leave behind it there is nothing else on the report to explain it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_shortfall_charges', function (Blueprint $table) {

            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // The first of the month this covers.
            $table->date('month');

            // What was counted, kept in minutes so the figure on the screen
            // can always be checked against the days it was turned into.
            $table->unsignedInteger('minutes')->default(0);
            $table->unsignedInteger('late_minutes')->default(0);
            $table->unsignedInteger('early_minutes')->default(0);
            $table->unsignedInteger('missing_minutes')->default(0);

            // What it came to, and how it was paid for.
            $table->decimal('days_charged', 5, 2)->default(0);
            $table->decimal('days_from_balance', 5, 2)->default(0);
            $table->decimal('days_without_pay', 5, 2)->default(0);

            $table->timestamp('charged_at')->nullable();

            $table->timestamps();

            // One charge per employee per month, whatever else happens.
            $table->unique(['user_id', 'month'], 'shortfall_user_month_unique');
        });

        /*
        | A balance deduction without a leave behind it.
        |
        | SQLite cannot alter a column in place, so the table is rebuilt; on
        | MySQL the column is simply changed. Both end up with leave_id
        | nullable and a note to explain a row that has none.
        */
        if (! Schema::hasColumn('leave_transactions', 'note')) {
            Schema::table('leave_transactions', function (Blueprint $table) {
                $table->string('note', 255)->nullable()->after('action');
            });
        }

        self::makeLeaveIdNullable();
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_shortfall_charges');

        if (Schema::hasColumn('leave_transactions', 'note')) {
            Schema::table('leave_transactions', function (Blueprint $table) {
                $table->dropColumn('note');
            });
        }

        // leave_id is left nullable: rows written while it was may still be
        // there, and narrowing it again would refuse them.
    }

    /**
     * Let a balance transaction stand on its own, with no leave behind it.
     */
    protected static function makeLeaveIdNullable(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {

            /*
            | Rebuilding the table is how SQLite changes a column, and the
            | rebuild has to put back everything the old table carried or it
            | quietly loses it. Both matter here:
            |
            |  - leave_id cascades on delete, which is how taking back an
            |    auto-charged half day removes its transaction too;
            |  - processed_by is "set null", not cascade, so deleting an
            |    admin does not delete the history of what they approved.
            |
            | The three indexes go back on as well.
            */
            DB::statement('PRAGMA foreign_keys = OFF');

            Schema::create('leave_transactions_rebuilt', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('leave_id')->nullable()->constrained()->cascadeOnDelete();
                $table->decimal('days', 8, 2);
                $table->decimal('balance_before', 8, 2);
                $table->decimal('balance_after', 8, 2);
                $table->string('action')->default('approved');
                $table->string('note', 255)->nullable();
                $table->foreignId('processed_by')->nullable()
                    ->constrained('users')->nullOnDelete();
                $table->timestamps();
            });

            DB::statement('
                INSERT INTO leave_transactions_rebuilt
                    (id, user_id, leave_id, days, balance_before, balance_after, action, note, processed_by, created_at, updated_at)
                SELECT
                    id, user_id, leave_id, days, balance_before, balance_after, action, note, processed_by, created_at, updated_at
                FROM leave_transactions
            ');

            Schema::drop('leave_transactions');

            Schema::rename('leave_transactions_rebuilt', 'leave_transactions');

            Schema::table('leave_transactions', function (Blueprint $table) {
                $table->index('action', 'leave_transactions_action_index');
                $table->index('created_at', 'leave_transactions_created_at_index');
                $table->index(['user_id', 'created_at'], 'leave_transactions_user_created_index');
            });

            DB::statement('PRAGMA foreign_keys = ON');

            return;
        }

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement('ALTER TABLE leave_transactions MODIFY leave_id BIGINT UNSIGNED NULL');
        }
    }
};
