<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{

protected $fillable = [

'title',
'start_date',
'end_date',
'for_all'

];

/*
|--------------------------------------------------------------------------
| Users Assigned To Holiday
|--------------------------------------------------------------------------
*/

public function users()
{
    return $this->belongsToMany(User::class,'holiday_users');
}

}
