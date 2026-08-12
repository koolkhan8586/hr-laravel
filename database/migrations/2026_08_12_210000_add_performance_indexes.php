<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            // FK already indexes user_id; these cover admin filters + overlap checks
            $table->index(['status', 'created_at'], 'leaves_status_created_at_index');
            $table->index(['user_id', 'status'], 'leaves_user_id_status_index');
            $table->index(['user_id', 'start_date', 'end_date'], 'leaves_user_date_range_index');
            $table->index('status', 'leaves_status_index');
        });

        Schema::table('leave_transactions', function (Blueprint $table) {
            $table->index('created_at', 'leave_transactions_created_at_index');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->index(['date', 'status'], 'attendances_date_status_index');
            $table->index('date', 'attendances_date_index');
        });

        Schema::table('salaries', function (Blueprint $table) {
            $table->index(['year', 'month'], 'salaries_year_month_index');
            $table->index(['user_id', 'year', 'month'], 'salaries_user_year_month_index');
            $table->index('is_posted', 'salaries_is_posted_index');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('role', 'users_role_index');
            if (Schema::hasColumn('users', 'mobile')) {
                $table->index('mobile', 'users_mobile_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            $table->dropIndex('leaves_status_created_at_index');
            $table->dropIndex('leaves_user_id_status_index');
            $table->dropIndex('leaves_user_date_range_index');
            $table->dropIndex('leaves_status_index');
        });

        Schema::table('leave_transactions', function (Blueprint $table) {
            $table->dropIndex('leave_transactions_created_at_index');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex('attendances_date_status_index');
            $table->dropIndex('attendances_date_index');
        });

        Schema::table('salaries', function (Blueprint $table) {
            $table->dropIndex('salaries_year_month_index');
            $table->dropIndex('salaries_user_year_month_index');
            $table->dropIndex('salaries_is_posted_index');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_role_index');
            if (Schema::hasColumn('users', 'mobile')) {
                $table->dropIndex('users_mobile_index');
            }
        });
    }
};
