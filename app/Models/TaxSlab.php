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
