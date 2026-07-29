<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Which salary sheet the employee belongs to (Teachers / Staff)
            $table->string('salary_category')->default('staff')->after('role');

            // Bank details used by the bank sheet (ANNEXURE-A)
            $table->string('bank_account_no')->nullable()->after('salary_category');
            $table->string('new_account_no')->nullable()->after('bank_account_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'salary_category',
                'bank_account_no',
                'new_account_no',
            ]);
        });
    }
};
