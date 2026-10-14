<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Who is told about a loan application, and may decide it from WhatsApp.
 *
 * Entered by an admin, the same way leave approval numbers are, so the people
 * who sign loans off - the accountant, a manager, whoever - can be changed
 * without touching the code.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('loan_approvers')) {
            return;
        }

        Schema::create('loan_approvers', function (Blueprint $table) {

            $table->id();

            $table->string('name')->nullable();

            // WhatsApp number, in any local or international shape: WahaService
            // normalises it when sending.
            $table->string('mobile', 30)->unique();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_approvers');
    }
};
