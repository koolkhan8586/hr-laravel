<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('loan_ledgers', 'salary_id')) {
            Schema::table('loan_ledgers', function (Blueprint $table) {
                $table->foreignId('salary_id')->nullable()->constrained()->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('loan_ledgers', 'salary_id')) {
            Schema::table('loan_ledgers', function (Blueprint $table) {
                $table->dropConstrainedForeignId('salary_id');
            });
        }
    }
};
