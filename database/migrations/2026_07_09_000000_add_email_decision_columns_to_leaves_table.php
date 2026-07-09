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
        Schema::table('leaves', function (Blueprint $table) {
            $table->string('decided_via')->nullable()->after('status');
            $table->string('decided_by_email')->nullable()->after('decided_via');
        });

        Schema::table('leave_transactions', function (Blueprint $table) {
            $table->dropForeign(['processed_by']);
            $table->unsignedBigInteger('processed_by')->nullable()->change();
            $table->foreign('processed_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            $table->dropColumn(['decided_via', 'decided_by_email']);
        });

        Schema::table('leave_transactions', function (Blueprint $table) {
            $table->dropForeign(['processed_by']);
            $table->unsignedBigInteger('processed_by')->nullable(false)->change();
            $table->foreign('processed_by')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
