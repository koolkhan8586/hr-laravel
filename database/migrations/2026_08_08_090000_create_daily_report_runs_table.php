<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per day for the daily WhatsApp attendance report.
 *
 * Without it the schedule fires once and, if WAHA happens to be down at that
 * minute, the day's report is simply lost with nobody the wiser. Keeping the
 * outcome lets the report be retried until it lands and lets Settings say
 * plainly whether today's report actually went out.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_report_runs', function (Blueprint $table) {
            $table->id();
            $table->date('report_date')->unique();
            $table->string('status')->default('pending'); // pending | sent | failed
            $table->unsignedInteger('attempts')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->text('last_error')->nullable();
            $table->boolean('manual')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_report_runs');
    }
};
