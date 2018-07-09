<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Carbon\Carbon;

class ServiceAssigned extends Mailable
{
    use Queueable, SerializesModels;

    public $professionalName;
    public $service;
    public $InitialDate;
    public $FinalDate;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($professionalName, $service)
    {
        $this->professionalName = $professionalName;
        $this->service = $service;
        $this->InitialDate = Carbon::createFromFormat('Y-m-d', $service->InitialDate)->format('d/m/Y');
        $this->FinalDate = Carbon::createFromFormat('Y-m-d', $service->FinalDate)->format('d/m/Y');
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('mail.service_assigned')
            ->subject('Nuevo servicio asignado – ADOM');
    }
}
