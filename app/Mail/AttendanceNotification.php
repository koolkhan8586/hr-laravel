<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class AttendanceNotification extends Mailable
{
    public $type;
    public $attendance;

    public function __construct($type, $attendance)
    {
        $this->type = $type;
        $this->attendance = $attendance;
    }

    public function build()
    {
        return $this->subject('Attendance ' . $this->type)
            ->view('emails.attendance');
    }
}
