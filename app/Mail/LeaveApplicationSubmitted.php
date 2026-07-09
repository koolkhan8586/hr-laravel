<?php

namespace App\Mail;

use App\Models\Leave;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class LeaveApplicationSubmitted extends Mailable
{
    use Queueable, SerializesModels;

    public Leave $leave;
    public string $recipientEmail;
    public string $approveUrl;
    public string $rejectUrl;

    public function __construct(Leave $leave, string $recipientEmail)
    {
        $this->leave = $leave;
        $this->recipientEmail = $recipientEmail;

        $expiresAt = now()->addHours(72);

        $this->approveUrl = URL::temporarySignedRoute('leave.email.decision', $expiresAt, [
            'id' => $leave->id,
            'decision' => 'approve',
            'email' => $recipientEmail,
        ]);

        $this->rejectUrl = URL::temporarySignedRoute('leave.email.decision', $expiresAt, [
            'id' => $leave->id,
            'decision' => 'reject',
            'email' => $recipientEmail,
        ]);
    }

    public function build()
    {
        return $this->subject('New Leave Application Submitted')
            ->view('emails.leave_application_submitted');
    }
}
