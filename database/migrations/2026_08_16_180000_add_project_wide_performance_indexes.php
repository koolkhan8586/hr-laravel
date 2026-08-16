<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Project-wide performance indexes for common filters, date ranges,
 * payroll lookups, schedules, loans, and admin reports.
 *
 * Safe to re-run: skips indexes that already exist.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ------------------------------------------------------------------
        // Leaves
        // ------------------------------------------------------------------
        $this->addIndex('leaves', ['status', 'start_date', 'end_date'], 'leaves_status_date_range_index');
        $this->addIndex('leaves', ['type', 'status', 'start_date'], 'leaves_type_status_start_index');
        $this->addIndex('leaves', ['user_id', 'type', 'status'], 'leaves_user_type_status_index');
        $this->addIndex('leaves', 'whatsapp_notify_status', 'leaves_whatsapp_notify_status_index');
        $this->addIndex('leaves', 'decided_at', 'leaves_decided_at_index');
        $this->addIndex('leaves', 'created_at', 'leaves_created_at_index');

        // ------------------------------------------------------------------
        // Attendances
        // ------------------------------------------------------------------
        $this->addIndex('attendances', ['user_id', 'clock_in'], 'attendances_user_clock_in_index');
        $this->addIndex('attendances', 'clock_in', 'attendances_clock_in_index');
        $this->addIndex('attendances', 'status', 'attendances_status_index');
        $this->addIndex('attendances', 'is_late', 'attendances_is_late_index');

        // ------------------------------------------------------------------
        // Work from home
        // ------------------------------------------------------------------
        $this->addIndex('work_from_home', ['start_date', 'end_date'], 'wfh_start_end_date_index');
        $this->addIndex('work_from_home', ['user_id', 'start_date', 'end_date'], 'wfh_user_date_range_index');
        $this->addIndex('work_from_home', 'created_at', 'wfh_created_at_index');

        // ------------------------------------------------------------------
        // Holidays
        // ------------------------------------------------------------------
        $this->addIndex('holidays', ['start_date', 'end_date'], 'holidays_start_end_date_index');
        $this->addIndex('holidays', ['for_all', 'start_date', 'end_date'], 'holidays_for_all_date_range_index');
        $this->addIndex('holidays', 'start_date', 'holidays_start_date_index');
        $this->addUnique('holiday_users', ['holiday_id', 'user_id'], 'holiday_users_holiday_user_unique');

        // ------------------------------------------------------------------
        // Schedules
        // ------------------------------------------------------------------
        $this->addIndex('weekly_schedules', ['user_id', 'day_of_week'], 'weekly_schedules_user_day_index');
        $this->addIndex('weekly_schedules', ['day_of_week', 'shift_id'], 'weekly_schedules_day_shift_index');
        $this->addUnique('employee_schedules', ['user_id', 'date'], 'employee_schedules_user_date_unique');
        $this->addIndex('employee_schedules', 'date', 'employee_schedules_date_index');

        // ------------------------------------------------------------------
        // Loans
        // ------------------------------------------------------------------
        $this->addIndex('loans', ['user_id', 'status'], 'loans_user_status_index');
        $this->addIndex('loans', ['user_id', 'status', 'remaining_balance'], 'loans_user_status_balance_index');
        $this->addIndex('loans', ['user_id', 'remaining_balance'], 'loans_user_balance_index');
        $this->addIndex('loans', 'status', 'loans_status_index');
        $this->addIndex('loans', 'created_at', 'loans_created_at_index');

        $this->addIndex('loan_ledgers', ['loan_id', 'type'], 'loan_ledgers_loan_type_index');
        $this->addIndex('loan_ledgers', ['loan_id', 'created_at'], 'loan_ledgers_loan_created_index');
        $this->addIndex('loan_ledgers', 'type', 'loan_ledgers_type_index');
        $this->addIndex('loan_ledgers', 'created_at', 'loan_ledgers_created_at_index');

        $this->addIndex('loan_payments', ['loan_id', 'year', 'month'], 'loan_payments_loan_period_index');
        $this->addIndex('loan_payments', ['year', 'month'], 'loan_payments_year_month_index');

        // ------------------------------------------------------------------
        // Salaries / tax / sheet config
        // ------------------------------------------------------------------
        $this->addIndex('salaries', ['year', 'month', 'status'], 'salaries_year_month_status_index');
        $this->addIndex('salaries', 'status', 'salaries_status_index');
        $this->addIndex('salaries', 'posted_at', 'salaries_posted_at_index');
        $this->addIndex('salaries', 'created_at', 'salaries_created_at_index');

        $this->addIndex('tax_sheets', 'year', 'tax_sheets_year_index');
        $this->addIndex('salary_columns', ['is_active', 'applies_to', 'sort_order'], 'salary_columns_active_applies_sort_index');
        $this->addIndex('salary_columns', 'sort_order', 'salary_columns_sort_order_index');
        $this->addIndex('tax_slabs', ['is_active', 'from_amount'], 'tax_slabs_active_from_index');

        // ------------------------------------------------------------------
        // Users / staff
        // ------------------------------------------------------------------
        $this->addIndex('users', ['role', 'salary_category'], 'users_role_salary_category_index');
        $this->addIndex('users', ['role', 'tracks_attendance'], 'users_role_tracks_attendance_index');
        $this->addIndex('users', 'salary_category', 'users_salary_category_index');
        $this->addIndex('users', 'tracks_attendance', 'users_tracks_attendance_index');
        $this->addIndex('users', 'can_manage_salary', 'users_can_manage_salary_index');
        $this->addIndex('users', 'name', 'users_name_index');

        $this->addIndex('staff', 'department', 'staff_department_index');
        $this->addIndex('staff', 'status', 'staff_status_index');
        $this->addIndex('staff', ['status', 'department'], 'staff_status_department_index');
        $this->addIndex('staff', 'joining_date', 'staff_joining_date_index');

        // ------------------------------------------------------------------
        // Leave balances / transactions extras
        // ------------------------------------------------------------------
        $this->addUnique('leave_balances', ['user_id'], 'leave_balances_user_id_unique');
        $this->addIndex('leave_transactions', ['user_id', 'created_at'], 'leave_transactions_user_created_index');
        $this->addIndex('leave_transactions', 'action', 'leave_transactions_action_index');

        // ------------------------------------------------------------------
        // WhatsApp / announcements
        // ------------------------------------------------------------------
        $this->addIndex('daily_report_whatsapp_numbers', 'is_active', 'daily_report_wa_is_active_index');
        $this->addIndex('whatsapp_attendance_reminders', ['date', 'status'], 'wa_attendance_reminders_date_status_index');
        $this->addIndex('whatsapp_attendance_reminders', 'status', 'wa_attendance_reminders_status_index');
        $this->addIndex('announcement_logs', 'created_at', 'announcement_logs_created_at_index');
        $this->addIndex('announcement_logs', ['sent_by', 'created_at'], 'announcement_logs_sent_by_created_index');
    }

    public function down(): void
    {
        $indexes = [
            'leaves' => [
                'leaves_status_date_range_index',
                'leaves_type_status_start_index',
                'leaves_user_type_status_index',
                'leaves_whatsapp_notify_status_index',
                'leaves_decided_at_index',
                'leaves_created_at_index',
            ],
            'attendances' => [
                'attendances_user_clock_in_index',
                'attendances_clock_in_index',
                'attendances_status_index',
                'attendances_is_late_index',
            ],
            'work_from_home' => [
                'wfh_start_end_date_index',
                'wfh_user_date_range_index',
                'wfh_created_at_index',
            ],
            'holidays' => [
                'holidays_start_end_date_index',
                'holidays_for_all_date_range_index',
                'holidays_start_date_index',
            ],
            'holiday_users' => ['holiday_users_holiday_user_unique'],
            'weekly_schedules' => [
                'weekly_schedules_user_day_index',
                'weekly_schedules_day_shift_index',
            ],
            'employee_schedules' => [
                'employee_schedules_user_date_unique',
                'employee_schedules_date_index',
            ],
            'loans' => [
                'loans_user_status_index',
                'loans_user_status_balance_index',
                'loans_user_balance_index',
                'loans_status_index',
                'loans_created_at_index',
            ],
            'loan_ledgers' => [
                'loan_ledgers_loan_type_index',
                'loan_ledgers_loan_created_index',
                'loan_ledgers_type_index',
                'loan_ledgers_created_at_index',
            ],
            'loan_payments' => [
                'loan_payments_loan_period_index',
                'loan_payments_year_month_index',
            ],
            'salaries' => [
                'salaries_year_month_status_index',
                'salaries_status_index',
                'salaries_posted_at_index',
                'salaries_created_at_index',
            ],
            'tax_sheets' => ['tax_sheets_year_index'],
            'salary_columns' => [
                'salary_columns_active_applies_sort_index',
                'salary_columns_sort_order_index',
            ],
            'tax_slabs' => ['tax_slabs_active_from_index'],
            'users' => [
                'users_role_salary_category_index',
                'users_role_tracks_attendance_index',
                'users_salary_category_index',
                'users_tracks_attendance_index',
                'users_can_manage_salary_index',
                'users_name_index',
            ],
            'staff' => [
                'staff_department_index',
                'staff_status_index',
                'staff_status_department_index',
                'staff_joining_date_index',
            ],
            'leave_balances' => ['leave_balances_user_id_unique'],
            'leave_transactions' => [
                'leave_transactions_user_created_index',
                'leave_transactions_action_index',
            ],
            'daily_report_whatsapp_numbers' => ['daily_report_wa_is_active_index'],
            'whatsapp_attendance_reminders' => [
                'wa_attendance_reminders_date_status_index',
                'wa_attendance_reminders_status_index',
            ],
            'announcement_logs' => [
                'announcement_logs_created_at_index',
                'announcement_logs_sent_by_created_index',
            ],
        ];

        foreach ($indexes as $table => $names) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($names as $name) {
                $this->dropIndexIfExists($table, $name);
            }
        }
    }

    /**
     * @param  array<int, string>|string  $columns
     */
    private function addIndex(string $table, array|string $columns, string $name): void
    {
        if (! Schema::hasTable($table) || Schema::hasIndex($table, $name)) {
            return;
        }

        $cols = (array) $columns;
        foreach ($cols as $col) {
            if (! Schema::hasColumn($table, $col)) {
                return;
            }
        }

        Schema::table($table, function (Blueprint $blueprint) use ($cols, $name) {
            $blueprint->index($cols, $name);
        });
    }

    /**
     * @param  array<int, string>|string  $columns
     */
    private function addUnique(string $table, array|string $columns, string $name): void
    {
        if (! Schema::hasTable($table) || Schema::hasIndex($table, $name)) {
            return;
        }

        $cols = (array) $columns;
        foreach ($cols as $col) {
            if (! Schema::hasColumn($table, $col)) {
                return;
            }
        }

        try {
            Schema::table($table, function (Blueprint $blueprint) use ($cols, $name) {
                $blueprint->unique($cols, $name);
            });
        } catch (\Throwable $e) {
            // Skip if duplicate data prevents a unique index (e.g. dirty leave_balances).
            // Still add a non-unique index so lookups stay fast.
            $fallback = $name.'_idx';
            if (! Schema::hasIndex($table, $fallback)) {
                Schema::table($table, function (Blueprint $blueprint) use ($cols, $fallback) {
                    $blueprint->index($cols, $fallback);
                });
            }
        }
    }

    private function dropIndexIfExists(string $table, string $name): void
    {
        if (! Schema::hasIndex($table, $name)) {
            // Also drop any fallback non-unique index from addUnique()
            $fallback = $name.'_idx';
            if (Schema::hasIndex($table, $fallback)) {
                Schema::table($table, function (Blueprint $blueprint) use ($fallback) {
                    $blueprint->dropIndex($fallback);
                });
            }

            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($name) {
            $blueprint->dropIndex($name);
        });
    }
};
