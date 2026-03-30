<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfficeLocation extends Model
{
    protected $fillable = [
        'name',
        'latitude',
        'longitude',
        'radius',
        'address'
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
