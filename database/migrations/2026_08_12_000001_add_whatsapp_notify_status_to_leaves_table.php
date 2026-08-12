<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            $table->string('whatsapp_notify_status')->nullable()->after('decided_by_email');
            $table->timestamp('whatsapp_notified_at')->nullable()->after('whatsapp_notify_status');
        });
    }

    public function down(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            $table->dropColumn(['whatsapp_notify_status', 'whatsapp_notified_at']);
        });
    }
};
