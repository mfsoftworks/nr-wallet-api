<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class KnownDeviceLogin extends Mailable
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
    public function __construct(array $agent)
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
        return $this->subject('NR Flow New Login')
            ->view('emails.known-device');
    }
}
