<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveApprovalEmail extends Model
{
    protected $fillable = [
        'name',
        'email',
    ];
}
