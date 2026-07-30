<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tax_sheets', function (Blueprint $table) {
            // False means the yearly salary tracks the salary sheet and is
            // refreshed from it. True means someone typed their own figure,
            // which is then left alone.
            $table->boolean('salary_overridden')->default(false)->after('annual_salary');
        });
    }

    public function down(): void
    {
        Schema::table('tax_sheets', function (Blueprint $table) {
            $table->dropColumn('salary_overridden');
        });
    }
};
