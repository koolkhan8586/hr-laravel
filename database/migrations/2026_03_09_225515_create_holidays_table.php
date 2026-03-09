<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();

            $table->string('title'); // Holiday name
            $table->date('date'); // Holiday date

            $table->boolean('for_all')->default(true); 
            // true = all employees
            // false = specific employee

            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            // if specific employee holiday

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};
