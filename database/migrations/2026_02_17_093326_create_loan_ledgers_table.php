<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLoanLedgersTable extends Migration
{
    public function up(): void
    {
        Schema::create('loan_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->decimal('amount', 12,2);
            $table->string('type'); // opening, loan, deduction, adjustment
            $table->text('remarks')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_ledgers');
    }
}
