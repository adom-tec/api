<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class CreateUserMail extends Mailable
{
    use Queueable, SerializesModels;
    
    public $username;
    public $password;
    public $email;
    public $loginUrl;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($username, $email, $password, $loginUrl)
    {
        $this->username = $username;
        $this->password = $password;
        $this->email = $email;
        $this->loginUrl = $loginUrl;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('mail.create_user')
            ->subject('Cuenta creada - ADOM');
    }
}
