<?php

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
    $today = \Carbon\Carbon::today('Asia/Karachi')->toDateString();

    $attendances = \App\Models\Attendance::whereDate('date', $today)->get();

    $total = \App\Models\User::where('role','employee')->count();

    $present = $attendances->where('status','present')->count();
    $late = $attendances->where('status','late')->count();
    $absent = $total - $attendances->count();

    $body = "Daily Attendance Summary\n\n";
    $body .= "Date: $today\n\n";
    $body .= "Total Employees: $total\n";
    $body .= "Present: $present\n";
    $body .= "Late: $late\n";
    $body .= "Absent: $absent\n\n";
    $body .= "Employee Breakdown:\n\n";

    foreach ($attendances as $record) {
        $body .= $record->user->name . " - " . ucfirst($record->status) . "\n";
    }

    \Mail::raw($body, function ($message) {
        $message->to('hr@uolcc.edu.pk')
                ->subject('Daily Attendance Summary');
    });

    $this->info('Daily summary email sent successfully.');
}
}
