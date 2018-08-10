<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class CancelVisits extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */

    public $professionalName;
    public $docPatient;
    public $patientName;
    public $service;
    public $cantVisits;
    public $motive;

    public function __construct($professionalName, $docPatient, $patientName, $service, $cantVisits, $motive)
    {
        $this->professionalName = $professionalName;
        $this->docPatient = $docPatient;
        $this->patientName = $patientName;
        $this->service = $service;
        $this->cantVisits = $cantVisits;
        $this->motive = $motive;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('mail.cancel_visits')
            ->subject('CANCELACION Servicio – ' . $this->professionalName);
    }
}
