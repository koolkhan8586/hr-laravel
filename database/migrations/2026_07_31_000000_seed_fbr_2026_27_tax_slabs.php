<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * FBR salaried slabs for FY 2026-2027, on yearly income.
     * Held literally here so this migration keeps working regardless of
     * how the preset on the model changes in future years.
     */
    private array $slabs = [
        // [from, to, fixed amount, rate %]
        [0,       600000,  0,       0],
        [600000,  1200000, 0,       1],
        [1200000, 2200000, 6000,    11],
        [2200000, 3200000, 116000,  20],
        [3200000, 4100000, 316000,  25],
        [4100000, 5600000, 541000,  29],
        [5600000, 7000000, 976000,  32],
        [7000000, null,    1424000, 35],
    ];

    public function up(): void
    {
        // Don't touch slabs that have already been set up by hand.
        if (DB::table('tax_slabs')->exists()) {
            return;
        }

        $now = now();

        foreach ($this->slabs as [$from, $to, $fixed, $rate]) {
            DB::table('tax_slabs')->insert([
                'from_amount'  => $from,
                'to_amount'    => $to,
                'fixed_amount' => $fixed,
                'percentage'   => $rate,
                'is_active'    => true,
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);
        }

        // These bands are yearly figures.
        if (!DB::table('app_settings')->where('key', 'tax_basis')->exists()) {
            DB::table('app_settings')->insert([
                'key'        => 'tax_basis',
                'value'      => 'annual',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        foreach ($this->slabs as [$from, $to, $fixed, $rate]) {
            DB::table('tax_slabs')
                ->where('from_amount', $from)
                ->where('fixed_amount', $fixed)
                ->where('percentage', $rate)
                ->delete();
        }
    }
};
