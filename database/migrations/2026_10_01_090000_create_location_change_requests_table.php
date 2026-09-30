<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * An employee asking to mark attendance somewhere else.
 *
 * They cannot change their own locations - that would undo the restriction -
 * so they ask, and an admin decides. Keeping the request means both sides can
 * see what was asked, when, and what was decided.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('location_change_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('office_location_id')->constrained()->cascadeOnDelete();

            $table->text('reason')->nullable();
            $table->string('status')->default('pending'); // pending | approved | rejected

            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->text('decision_note')->nullable();

            // Whether approving added the office or replaced what they had.
            $table->string('applied_as')->nullable(); // added | replaced

            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('location_change_requests');
    }
};
