<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'annual_leave_balance',   // ✅ ADD THIS
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function loans()
{
    return $this->hasMany(Loan::class);
}

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'annual_leave_balance' => 'float',   // ✅ ADD THIS
        ];
    }
}
