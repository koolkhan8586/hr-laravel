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

    /** Employees whose single office_location_id still points here. */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /** Employees held to this office for marking attendance. */
    public function assignedUsers()
    {
        return $this->belongsToMany(User::class, 'office_location_user')
            ->withTimestamps();
    }
}
