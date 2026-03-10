<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_from_home', function (Blueprint $table) {

            $table->dropColumn('date');

        });
    }

    public function down(): void
    {
        Schema::table('work_from_home', function (Blueprint $table) {

            $table->date('date')->nullable();

        });
    }
};
