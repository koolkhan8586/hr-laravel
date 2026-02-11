<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            $table->enum('duration_type', ['full_day', 'half_day'])
                  ->default('full_day');

            $table->enum('half_day_type', ['morning', 'afternoon'])
                  ->nullable();

            $table->decimal('calculated_days', 5, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            $table->dropColumn([
                'duration_type',
                'half_day_type',
                'calculated_days'
            ]);
        });
    }
};
