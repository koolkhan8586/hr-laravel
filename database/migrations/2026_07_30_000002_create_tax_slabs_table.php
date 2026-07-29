<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_slabs', function (Blueprint $table) {
            $table->id();

            // Income band this rule applies to. to_amount null = no upper limit.
            $table->decimal('from_amount', 14, 2)->default(0);
            $table->decimal('to_amount', 14, 2)->nullable();

            // Tax = fixed_amount + (income - from_amount) * percentage / 100
            $table->decimal('fixed_amount', 14, 2)->default(0);
            $table->decimal('percentage', 5, 2)->default(0);

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_slabs');
    }
};
