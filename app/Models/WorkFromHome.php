<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkFromHome extends Model
{
    use HasFactory;

    protected $table = 'work_from_home';

    protected $fillable = [
        'user_id',
        'start_date',
        'end_date',
        'reason'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
