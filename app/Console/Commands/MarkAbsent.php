<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class MarkAbsent extends Command
{
    protected $signature = 'attendance:mark-absent';
    protected $description = 'Mark employees absent if not clocked in by 11:45 AM';

    public function handle()
    {
        $now = Carbon::now('Asia/Karachi');

        // Only run after 11:45 AM
        if ($now->lt(Carbon::createFromTime(11, 45, 0, 'Asia/Karachi'))) {
            $this->info('Too early to mark absent.');
            return;
        }

        $today = $now->toDateString();

        // Get all users (adjust if you have role system)
        $users = User::all();

        foreach ($users as $user) {

            $alreadyMarked = Attendance::where('user_id', $user->id)
                ->whereDate('clock_in', $today)
                ->exists();

            if (!$alreadyMarked) {

                Attendance::create([
                    'user_id' => $user->id,
                    'clock_in' => null,
                    'clock_out' => null,
                    'status' => 'absent'
                ]);

                $this->info("Marked absent: {$user->name}");
            }
        }

        $this->info('Absent marking completed.');
    }
}
