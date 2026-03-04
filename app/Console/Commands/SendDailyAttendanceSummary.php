<?php

namespace App\Console\Commands;

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;   // 👈 ADD THIS LINE

class SendDailyAttendanceSummary extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-daily-attendance-summary';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
{
    $today = Carbon::now('Asia/Karachi')->toDateString();

    $employees = User::where('role','employee')->get();

    foreach ($employees as $employee) {

        $attendance = Attendance::where('user_id',$employee->id)
            ->whereDate('clock_in',$today)
            ->first();

        $message = "Daily Attendance Summary\n\nDate: ".$today."\n";

        if ($attendance) {
            $message .= "Status: ".$attendance->status."\n";
            $message .= "Total Hours: ".$attendance->total_hours;
        } else {
            $message .= "Status: Absent";
        }

        Mail::raw($message, function ($mail) use ($employee) {
            $mail->to($employee->email)
                 ->subject('Daily Attendance Summary');
        });
    }
}
}
