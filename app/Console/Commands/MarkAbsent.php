<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class MarkAbsent extends Command
{
    protected $signature = 'attendance:mark-absent';
    protected $description = 'Mark absent employees';

    public function handle()
    {
        $today = now()->toDateString();

        $employees = User::where('role','employee')->get();

        foreach($employees as $emp){

            $exists = Attendance::where('user_id',$emp->id)
                ->whereDate('clock_in',$today)
                ->exists();

            if(!$exists){
                Attendance::create([
                    'user_id'=>$emp->id,
                    'clock_in'=>Carbon::parse($today.' 00:00:00'),
                    'status'=>'absent'
                ]);
            }
        }

        $this->info('Absent marked successfully');
    }
}
