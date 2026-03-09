<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('holidays', function (Blueprint $table) {

            $table->date('start_date')->after('title');
            $table->date('end_date')->after('start_date');

            $table->dropColumn('date');

        });
    }

    public function down(): void
    {
        Schema::table('holidays', function (Blueprint $table) {

            $table->date('date');
            $table->dropColumn(['start_date','end_date']);

        });
    }
};
