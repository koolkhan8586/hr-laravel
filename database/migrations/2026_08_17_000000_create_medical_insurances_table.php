<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_insurances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('year');

            // Premium for the month. LSAF and the employee each take half.
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('lsaf_portion', 12, 2)->default(0);
            $table->decimal('employee_portion', 12, 2)->default(0);

            $table->timestamps();

            $table->unique(['user_id', 'month', 'year']);
            $table->index(['year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_insurances');
    }
};
