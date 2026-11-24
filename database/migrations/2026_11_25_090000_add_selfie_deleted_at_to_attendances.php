<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * When a clock-in photo was deleted under the retention rule, so an old day
 * reads "deleted after 90 days" rather than "no photo was taken".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            if (! Schema::hasColumn('attendances', 'selfie_deleted_at')) {
                $table->timestamp('selfie_deleted_at')->nullable()->after('selfie_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            if (Schema::hasColumn('attendances', 'selfie_deleted_at')) {
                $table->dropColumn('selfie_deleted_at');
            }
        });
    }
};
