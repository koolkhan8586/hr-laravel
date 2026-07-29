<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_sheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Tax year the working belongs to
            $table->integer('year');

            // Yearly salary & wages the tax is worked out on. Seeded from the
            // salary sheet but editable, because payroll may know better.
            $table->decimal('annual_salary', 14, 2)->default(0);

            // Manual correction applied to the year's payable tax
            $table->decimal('tax_adjustment', 14, 2)->default(0);

            $table->timestamps();

            $table->unique(['user_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_sheets');
    }
};
