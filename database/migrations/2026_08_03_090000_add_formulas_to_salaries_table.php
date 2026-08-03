<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Remembers how a figure was worked out.
 *
 * A cell holding "=6*87" stores 522 as the amount, exactly as before, plus the
 * expression that produced it. Everything downstream - bank sheet, tax sheet,
 * payslips, exports - keeps reading the plain number and is untouched.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salaries', function (Blueprint $table) {
            if (!Schema::hasColumn('salaries', 'formulas')) {
                $table->json('formulas')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('salaries', function (Blueprint $table) {
            if (Schema::hasColumn('salaries', 'formulas')) {
                $table->dropColumn('formulas');
            }
        });
    }
};
