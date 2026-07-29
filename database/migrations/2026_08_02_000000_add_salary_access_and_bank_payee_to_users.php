<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Lets a non-admin work on the salary screens.
            $table->boolean('can_manage_salary')->default(false)->after('salary_category');

            // Credit this employee's pay into another employee's account
            // on the bank sheet (used when they have no account of their own).
            $table->unsignedBigInteger('bank_payee_id')->nullable()->after('bank_account_no');
            $table->foreign('bank_payee_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['bank_payee_id']);
            $table->dropColumn(['can_manage_salary', 'bank_payee_id']);
        });
    }
};
