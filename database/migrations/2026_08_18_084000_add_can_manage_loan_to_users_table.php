<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'can_manage_loan')) {
                $table->boolean('can_manage_loan')
                    ->default(false)
                    ->after('can_manage_salary');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'can_manage_loan')) {
                $table->dropColumn('can_manage_loan');
            }
        });
    }
};
