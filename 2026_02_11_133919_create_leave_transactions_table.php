<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_transactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('leave_id')->constrained()->onDelete('cascade');

            $table->decimal('days', 5, 2);
            $table->decimal('balance_before', 5, 2);
            $table->decimal('balance_after', 5, 2);

            $table->enum('action', ['approved', 'rejected', 'adjustment']);

            $table->foreignId('processed_by')
                  ->constrained('users')
                  ->onDelete('cascade');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_transactions');
    }
};
