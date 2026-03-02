<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveBalance extends Model
{
    protected $fillable = [
        'user_id',
        'opening_balance' => 'float',
    'used_leaves' => 'float',
    'remaining_leaves' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
