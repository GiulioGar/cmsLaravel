<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BanNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $motivazione;
    public $nome;
    public $uid;

    public function __construct(string $motivazione, string $nome, string $uid)
    {
        $this->motivazione = $motivazione;
        $this->nome        = $nome;
        $this->uid         = $uid;
    }

    public function build()
    {
        return $this->subject('Comunicazione sospensione account Club Millebytes')
            ->view('emails.ban-notification');
    }
}
