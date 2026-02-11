<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leaves', function (Blueprint $table) {

            $table->id();

            $table->foreignId('user_id')
                  ->constrained()
                  ->onDelete('cascade');

            // Leave Type
            $table->enum('type', [
                'annual',
                'sick',
                'without_pay'
            ]);

            // Leave Dates
            $table->date('start_date');
            $table->date('end_date');

            // Full or Half Day
            $table->enum('duration', ['full', 'half'])
                  ->default('full');

            // Supports 0.5 for half day
            $table->decimal('days', 4, 2);

            // Leave Reason
            $table->text('reason')->nullable();

            // Status
            $table->enum('status', [
                'pending',
                'approved',
                'rejected'
            ])->default('pending');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leaves');
    }
};

