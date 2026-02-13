<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {

            $table->id();

            $table->foreignId('user_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->decimal('amount', 12, 2);
            $table->integer('installments');

            $table->decimal('monthly_deduction', 12, 2)->default(0);
            $table->decimal('remaining_balance', 12, 2)->default(0);

            $table->enum('status', ['pending','approved','rejected'])
                  ->default('pending');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
