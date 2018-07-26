<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class PasswordReset extends Mailable
{
    use Queueable, SerializesModels;

    public $userName;
    public $url;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($userName, $url)
    {
        $this->userName = $userName;
        $this->url = $url;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('mail.password_reset')
            ->subject('Cambio de Contraseña - ADOM');
    }
}
