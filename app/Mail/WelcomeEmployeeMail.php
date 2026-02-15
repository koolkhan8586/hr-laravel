<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class WelcomeEmployeeMail extends Mailable
{
    public $user;
    public $password;

    public function __construct($user, $password)
    {
        $this->user = $user;
        $this->password = $password;
    }

    public function build()
    {
        return $this->subject('Welcome to HR Portal')
            ->view('emails.welcome-employee');
    }
}
