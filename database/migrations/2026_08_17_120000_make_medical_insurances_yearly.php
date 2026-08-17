<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('medical_insurances')) {
            return;
        }

        if (! Schema::hasColumn('medical_insurances', 'month')) {
            return;
        }

        // One yearly row per employee: keep the largest premium, then the
        // latest month, so a figure already typed is not thrown away.
        $idsToDelete = [];
        $seen        = [];

        $rows = DB::table('medical_insurances')
            ->orderByDesc('total_amount')
            ->orderByDesc('month')
            ->orderByDesc('id')
            ->get();

        foreach ($rows as $row) {
            $key = $row->user_id.'-'.$row->year;

            if (isset($seen[$key])) {
                $idsToDelete[] = $row->id;
            } else {
                $seen[$key] = true;
            }
        }

        if ($idsToDelete) {
            DB::table('medical_insurances')->whereIn('id', $idsToDelete)->delete();
        }

        Schema::table('medical_insurances', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'month', 'year']);
            $table->dropIndex(['year', 'month']);
        });

        Schema::table('medical_insurances', function (Blueprint $table) {
            $table->dropColumn('month');
        });

        Schema::table('medical_insurances', function (Blueprint $table) {
            $table->unique(['user_id', 'year']);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('medical_insurances')) {
            return;
        }

        if (Schema::hasColumn('medical_insurances', 'month')) {
            return;
        }

        Schema::table('medical_insurances', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'year']);
        });

        Schema::table('medical_insurances', function (Blueprint $table) {
            $table->unsignedTinyInteger('month')->default(1)->after('user_id');
        });

        Schema::table('medical_insurances', function (Blueprint $table) {
            $table->unique(['user_id', 'month', 'year']);
            $table->index(['year', 'month']);
        });
    }
};
