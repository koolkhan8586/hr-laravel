<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            if (!Schema::hasColumn('loans', 'loan_date')) {
                $table->date('loan_date')->nullable();
            }
            $table->integer('installments')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            if (Schema::hasColumn('loans', 'loan_date')) {
                $table->dropColumn('loan_date');
            }
            $table->integer('installments')->nullable(false)->change();
        });
    }
};
