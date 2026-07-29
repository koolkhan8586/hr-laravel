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
        Schema::table('salaries', function (Blueprint $table) {
            // "Extra Load" earning column on the salary sheet
            $table->decimal('extra_load', 12, 2)->default(0)->after('basic_salary');

            // "Amount" column on the salary sheet: the part paid by cheque
            // instead of bank transfer. Excluded from the bank sheet.
            $table->decimal('cheque_amount', 12, 2)->default(0)->after('net_salary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('salaries', function (Blueprint $table) {
            $table->dropColumn(['extra_load', 'cheque_amount']);
        });
    }
};
