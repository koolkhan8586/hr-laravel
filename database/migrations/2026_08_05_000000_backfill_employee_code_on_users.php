<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * employee_code was never mass-assignable, so staff created through the
     * admin screens ended up with the code on their staff record but not on
     * the user. Anything reading users.employee_code - the salary sheet, the
     * tax sheet, the bank sheet - showed a blank. Copy it across.
     */
    public function up(): void
    {
        if (!Schema::hasTable('staff') || !Schema::hasColumn('users', 'employee_code')) {
            return;
        }

        DB::table('staff')
            ->join('users', 'users.id', '=', 'staff.user_id')
            ->where(function ($q) {
                $q->whereNull('users.employee_code')
                  ->orWhere('users.employee_code', '');
            })
            ->whereNotNull('staff.employee_id')
            ->where('staff.employee_id', '!=', '')
            ->select('staff.user_id', 'staff.employee_id')
            ->orderBy('staff.user_id')
            ->get()
            ->each(function ($row) {
                // Skip anything that would collide with an existing code.
                $taken = DB::table('users')
                    ->where('employee_code', $row->employee_id)
                    ->exists();

                if (!$taken) {
                    DB::table('users')
                        ->where('id', $row->user_id)
                        ->update(['employee_code' => $row->employee_id]);
                }
            });
    }

    public function down(): void
    {
        // Leaves the copied codes in place; removing them would lose data.
    }
};
