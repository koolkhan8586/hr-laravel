<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\Leave;

class LeaveApproved extends Notification
{
    use Queueable;

    protected $leave;

    public function __construct(Leave $leave)
    {
        $this->leave = $leave;
    }

    public function via(object $notifiable): array
    {
        return ['mail']; // send email
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Leave Has Been Approved')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Your leave request has been approved.')
            ->line('Type: ' . ucfirst($this->leave->type))
            ->line('From: ' . $this->leave->start_date)
            ->line('To: ' . $this->leave->end_date)
            ->line('Days Deducted: ' . $this->leave->calculated_days)
            ->line('Remaining Balance: ' . $notifiable->annual_leave_balance)
            ->action('View Leaves', url('/leave'))
            ->line('Thank you.');
    }
}
