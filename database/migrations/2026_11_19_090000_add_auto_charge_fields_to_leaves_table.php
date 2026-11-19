<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Half days the system charges on its own.
 *
 * Leaving early, or never marking a clock-out, now costs half a day. That
 * half day is a real leave - it comes off the balance, and off the pay when
 * there is no balance left - so it lives in the leaves table with everything
 * else. These columns say that the system wrote it and why, so it can be
 * told apart from a leave somebody applied for, found again rather than
 * charged twice, and taken back if the employee clocks out after all.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leaves', function (Blueprint $table) {

            if (! Schema::hasColumn('leaves', 'auto_reason')) {
                $table->string('auto_reason', 30)->nullable()->after('reason');
            }

            if (! Schema::hasColumn('leaves', 'auto_charged_at')) {
                $table->timestamp('auto_charged_at')->nullable()->after('auto_reason');
            }
        });

        Schema::table('leaves', function (Blueprint $table) {
            $table->index(['user_id', 'auto_reason'], 'leaves_user_auto_reason_index');
        });
    }

    public function down(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            $table->dropIndex('leaves_user_auto_reason_index');
        });

        Schema::table('leaves', function (Blueprint $table) {
            foreach (['auto_reason', 'auto_charged_at'] as $column) {
                if (Schema::hasColumn('leaves', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
