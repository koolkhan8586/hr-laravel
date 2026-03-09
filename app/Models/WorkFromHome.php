<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkFromHome extends Model
{

protected $table = "work_from_home";

protected $fillable = [
'user_id',
'start_date',
'end_date',
'reason'
];

}
