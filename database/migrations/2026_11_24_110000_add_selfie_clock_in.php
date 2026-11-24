<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A photo taken at clock-in, for the few employees it is switched on for.
 *
 * Location and device say where a phone was and which phone it was. Neither
 * says who was holding it. A photo taken live, at the moment of clock-in and
 * saved beside the position and the device, is the one thing that does, and
 * an admin can look at it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'selfie_required')) {
                $table->boolean('selfie_required')->default(false)->after('attendance_device_exempt');
            }
        });

        Schema::table('attendances', function (Blueprint $table) {
            if (! Schema::hasColumn('attendances', 'selfie_path')) {
                $table->string('selfie_path', 255)->nullable()->after('edited_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'selfie_required')) {
                $table->dropColumn('selfie_required');
            }
        });

        Schema::table('attendances', function (Blueprint $table) {
            if (Schema::hasColumn('attendances', 'selfie_path')) {
                $table->dropColumn('selfie_path');
            }
        });
    }
};
