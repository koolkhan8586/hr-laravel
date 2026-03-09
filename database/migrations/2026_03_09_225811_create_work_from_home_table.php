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
        Schema::create('work_from_home', function (Blueprint $table) {

            $table->id();

            // employee who requested WFH
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // date of WFH
            $table->date('date');

            // reason for WFH
            $table->text('reason')->nullable();

            // approval status
            $table->string('status')->default('pending');
            // pending | approved | rejected

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_from_home');
    }
};
