<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveBalance extends Model
{
    protected $fillable = [
        'user_id',
        'opening_balance',
        'used_leaves',
        'remaining_leaves'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
