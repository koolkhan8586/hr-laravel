<?php

namespace App\Mail;

use App\Models\Leave;
use App\Models\LeaveBalance;
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
    public ?float $remainingBalance;

    public function __construct(Leave $leave, string $recipientEmail)
    {
        $this->leave = $leave;
        $this->recipientEmail = $recipientEmail;

        $this->remainingBalance = $leave->type === 'annual'
            ? (LeaveBalance::where('user_id', $leave->user_id)->value('remaining_leaves') ?? 0)
            : null;

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
