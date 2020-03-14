<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UnknownDeviceLogin extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Agent to notify of
     *
     * @var array
     */
    public $agent;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->agent = $agent;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('NR Flow Unknown Device Login')
            ->view('emails.unknown-device');
    }
}
