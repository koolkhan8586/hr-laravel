<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salaries', function (Blueprint $table) {
            // { salary_column_id: amount } for admin-defined columns
            $table->json('custom_values')->nullable()->after('cheque_amount');
        });
    }

    public function down(): void
    {
        Schema::table('salaries', function (Blueprint $table) {
            $table->dropColumn('custom_values');
        });
    }
};
