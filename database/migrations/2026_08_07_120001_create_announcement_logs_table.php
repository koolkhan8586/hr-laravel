<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcement_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sent_by')->constrained('users')->cascadeOnDelete();
            $table->string('subject')->nullable();
            $table->text('message');
            $table->boolean('via_whatsapp')->default(false);
            $table->boolean('via_email')->default(false);
            $table->string('audience')->default('all'); // all | selected
            $table->json('user_ids')->nullable();
            $table->unsignedInteger('whatsapp_sent')->default(0);
            $table->unsignedInteger('whatsapp_failed')->default(0);
            $table->unsignedInteger('email_sent')->default(0);
            $table->unsignedInteger('email_failed')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_logs');
    }
};
