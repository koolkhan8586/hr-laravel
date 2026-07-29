<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalaryColumn extends Model
{
    protected $fillable = [
        'name',
        'type',
        'applies_to',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Active columns that belong on a given sheet, in display order.
     */
    public static function forCategory(string $category)
    {
        return static::where('is_active', true)
            ->whereIn('applies_to', ['both', $category])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public function isEarning(): bool
    {
        return $this->type === 'earning';
    }
}
