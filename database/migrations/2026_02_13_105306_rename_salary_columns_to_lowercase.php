<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameSalaryColumnsToLowercase extends Migration
{
    public function up(): void
    {
        Schema::table('salaries', function (Blueprint $table) {
            $table->renameColumn('Others', 'others');
            $table->renameColumn('Eidi', 'eidi');
        });
    }

    public function down(): void
    {
        Schema::table('salaries', function (Blueprint $table) {
            $table->renameColumn('others', 'Others');
            $table->renameColumn('eidi', 'Eidi');
        });
    }
}
