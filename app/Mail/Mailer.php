<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class Mailer extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $address="ignore@batcave.io";
        $name="Ignore Me";
        $subject = "Kryptonite Found";

        return $this->view('emails.index')
                    ->from($address,$name)
                    ->cc($address,$name)
                    ->bcc($address,$name)
                    ->replyTo($address,$name)
                    ->subject($subject);
    }
}
