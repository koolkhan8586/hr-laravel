<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_attendance_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->time('shift_start')->nullable();
            $table->string('mobile')->nullable();
            $table->string('chat_id')->nullable();
            $table->text('message')->nullable();
            $table->string('status')->default('sent'); // sent | failed
            $table->text('response')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_attendance_reminders');
    }
};
