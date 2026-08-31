<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Staff extends Model
{
    protected $fillable = [
        'user_id',
        'employee_id',
        'department',
        'designation',
        'salary',
        'joining_date',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return $this->status !== 'inactive';
    }

    public function hasLeft(): bool
    {
        return $this->status === 'inactive';
    }
}
