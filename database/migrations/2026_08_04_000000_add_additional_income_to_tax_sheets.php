<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tax_sheets', function (Blueprint $table) {
            // Monthly extra taxable income on top of salary & wages. Held
            // monthly because that is how payroll knows it; annualised when
            // the tax is worked out.
            $table->decimal('additional_income', 14, 2)->default(0)->after('annual_salary');
        });
    }

    public function down(): void
    {
        Schema::table('tax_sheets', function (Blueprint $table) {
            $table->dropColumn('additional_income');
        });
    }
};
