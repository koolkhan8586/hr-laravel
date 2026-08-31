<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medical_insurances', function (Blueprint $table) {
            $table->string('total_formula', 200)->nullable()->after('total_amount');
        });
    }

    public function down(): void
    {
        Schema::table('medical_insurances', function (Blueprint $table) {
            $table->dropColumn('total_formula');
        });
    }
};
