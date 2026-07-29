<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxSlab extends Model
{
    protected $fillable = [
        'from_amount',
        'to_amount',
        'fixed_amount',
        'percentage',
        'is_active',
    ];

    protected $casts = [
        'from_amount'  => 'float',
        'to_amount'    => 'float',
        'fixed_amount' => 'float',
        'percentage'   => 'float',
        'is_active'    => 'boolean',
    ];

    /**
     * FBR salaried income tax slabs for FY 2026-2027, on yearly income.
     * [from, to (null = no limit), fixed amount, rate %]
     */
    public const FBR_2026_27 = [
        [0,       600000,  0,       0],
        [600000,  1200000, 0,       1],
        [1200000, 2200000, 6000,    11],
        [2200000, 3200000, 116000,  20],
        [3200000, 4100000, 316000,  25],
        [4100000, 5600000, 541000,  29],
        [5600000, 7000000, 976000,  32],
        [7000000, null,    1424000, 35],
    ];

    /**
     * Replace the configured slabs with a preset.
     */
    public static function loadPreset(array $preset): int
    {
        static::query()->delete();

        foreach ($preset as [$from, $to, $fixed, $rate]) {
            static::create([
                'from_amount'  => $from,
                'to_amount'    => $to,
                'fixed_amount' => $fixed,
                'percentage'   => $rate,
                'is_active'    => true,
            ]);
        }

        return count($preset);
    }

    /**
     * Are the configured slabs written against yearly or monthly income?
     */
    public static function basis(): string
    {
        return AppSetting::get('tax_basis', 'annual') === 'monthly'
            ? 'monthly'
            : 'annual';
    }

    public static function activeSlabs()
    {
        return static::where('is_active', true)
            ->orderBy('from_amount')
            ->get();
    }

    /**
     * Tax due on an income expressed in the same basis as the slabs.
     */
    public static function taxFor(float $income): float
    {
        if ($income <= 0) {
            return 0.0;
        }

        foreach (static::activeSlabs() as $slab) {

            $withinLower = $income > $slab->from_amount;
            $withinUpper = is_null($slab->to_amount) || $income <= $slab->to_amount;

            if ($withinLower && $withinUpper) {
                $tax = $slab->fixed_amount
                    + (($income - $slab->from_amount) * $slab->percentage / 100);

                return round(max(0, $tax), 2);
            }
        }

        return 0.0;
    }

    /**
     * Tax for a full year of taxable income, honouring the configured basis.
     */
    public static function annualTaxFor(float $annualIncome): float
    {
        if ($annualIncome <= 0) {
            return 0.0;
        }

        if (static::basis() === 'monthly') {
            // Slabs describe a month, so tax a month and scale up.
            return round(static::taxFor($annualIncome / 12) * 12, 2);
        }

        return static::taxFor($annualIncome);
    }

    /**
     * Monthly tax for a monthly taxable income, honouring the configured basis.
     */
    public static function monthlyTaxFor(float $monthlyIncome): float
    {
        if ($monthlyIncome <= 0) {
            return 0.0;
        }

        if (static::basis() === 'monthly') {
            return static::taxFor($monthlyIncome);
        }

        // Slabs are annual: annualise, tax, then spread back over the year.
        return round(static::taxFor($monthlyIncome * 12) / 12, 2);
    }
}
