<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Who put the attendance on the day, not just what device it came from.
 *
 * The Manual Mark form and the Edit screen write a clock-in with no position,
 * no device and no trace of the person who typed it, so an employee who was
 * never at work could be marked present and nothing anywhere said so. These
 * columns say how a row got there and which user did it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {

            // self | manual. Blank on anything recorded before this existed.
            if (! Schema::hasColumn('attendances', 'marked_via')) {
                $table->string('marked_via', 20)->nullable()->after('device_agent');
            }

            // The admin who entered it by hand.
            if (! Schema::hasColumn('attendances', 'marked_by')) {
                $table->unsignedBigInteger('marked_by')->nullable()->after('marked_via');
            }

            // The last admin who changed the times on it, and when.
            if (! Schema::hasColumn('attendances', 'edited_by')) {
                $table->unsignedBigInteger('edited_by')->nullable()->after('marked_by');
            }

            if (! Schema::hasColumn('attendances', 'edited_at')) {
                $table->timestamp('edited_at')->nullable()->after('edited_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            foreach (['marked_via', 'marked_by', 'edited_by', 'edited_at'] as $column) {
                if (Schema::hasColumn('attendances', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
