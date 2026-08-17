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

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            $this->mysqlConvertToYearly();
            return;
        }

        Schema::table('medical_insurances', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('medical_insurances', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'month', 'year']);
            $table->dropIndex(['year', 'month']);
        });

        Schema::table('medical_insurances', function (Blueprint $table) {
            $table->dropColumn('month');
        });

        Schema::table('medical_insurances', function (Blueprint $table) {
            $table->unique(['user_id', 'year']);
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    /**
     * MySQL will not drop the (user_id, month, year) unique index while any
     * foreign key is using it (error 1553). Drop FKs by their real names,
     * then every non-primary index, then rebuild.
     */
    private function mysqlConvertToYearly(): void
    {
        $this->mysqlDropForeignKeys('medical_insurances');

        $indexNames = collect(DB::select('SHOW INDEX FROM `medical_insurances`'))
            ->pluck('Key_name')
            ->unique()
            ->reject(fn ($name) => $name === 'PRIMARY')
            ->values();

        foreach ($indexNames as $index) {
            $safe = str_replace('`', '', (string) $index);
            DB::statement("ALTER TABLE `medical_insurances` DROP INDEX `{$safe}`");
        }

        Schema::table('medical_insurances', function (Blueprint $table) {
            $table->dropColumn('month');
        });

        Schema::table('medical_insurances', function (Blueprint $table) {
            $table->unique(['user_id', 'year']);
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    private function mysqlDropForeignKeys(string $table): void
    {
        $foreignKeys = DB::select(
            'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND CONSTRAINT_TYPE = ?',
            [$table, 'FOREIGN KEY']
        );

        foreach ($foreignKeys as $fk) {
            $safe = str_replace('`', '', $fk->CONSTRAINT_NAME);
            DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$safe}`");
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('medical_insurances')) {
            return;
        }

        if (Schema::hasColumn('medical_insurances', 'month')) {
            return;
        }

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            $this->mysqlDropForeignKeys('medical_insurances');
        } else {
            Schema::table('medical_insurances', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
            });
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
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }
};
