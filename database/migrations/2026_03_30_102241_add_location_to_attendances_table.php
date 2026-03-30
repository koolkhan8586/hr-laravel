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
    Schema::table('attendances', function (Blueprint $table) {

        if (!Schema::hasColumn('attendances', 'latitude')) {
            $table->decimal('latitude', 10, 7)->nullable();
        }

        if (!Schema::hasColumn('attendances', 'longitude')) {
            $table->decimal('longitude', 10, 7)->nullable();
        }

        if (!Schema::hasColumn('attendances', 'location_status')) {
            $table->string('location_status')->nullable();
        }

    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
{
    Schema::table('attendances', function (Blueprint $table) {

        if (Schema::hasColumn('attendances', 'latitude')) {
            $table->dropColumn('latitude');
        }

        if (Schema::hasColumn('attendances', 'longitude')) {
            $table->dropColumn('longitude');
        }

        if (Schema::hasColumn('attendances', 'location_status')) {
            $table->dropColumn('location_status');
        }

    });
}
};
