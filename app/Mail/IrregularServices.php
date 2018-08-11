<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class IrregularServices extends Mailable
{
    use Queueable, SerializesModels;

    public $date;
    public $countServices;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($date, $countServices)
    {
        $this->date = $date;
        $this->countServices = $countServices;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('mail.irregular_services')
            ->subject('Servicios Irregulares - ADOM');
    }
}
