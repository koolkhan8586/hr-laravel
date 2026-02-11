<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            $table->decimal('days', 5, 2)->change();
            $table->decimal('calculated_days', 5, 2)->change();
        });
    }

    public function down(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            $table->integer('days')->change();
            $table->integer('calculated_days')->change();
        });
    }
};
