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
        Schema::create('office_locations', function (Blueprint $table) {
            $table->id();

            // 🔹 Location Details
            $table->string('name'); // Office name (e.g. Head Office, Branch 1)
            $table->decimal('latitude', 10, 7); // GPS latitude
            $table->decimal('longitude', 10, 7); // GPS longitude
            $table->integer('radius')->default(100); // Allowed radius in meters

            // 🔹 Optional (Good for future scaling)
            $table->text('address')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('office_locations');
    }
};
