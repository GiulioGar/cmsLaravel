<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class QualityMalusNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $motivazione;
    public $valore;
    public $nome;
    public $uid;

    public function __construct(string $motivazione, int $valore, string $nome, string $uid)
    {
        $this->motivazione = $motivazione;
        $this->valore      = $valore;
        $this->nome        = $nome;
        $this->uid         = $uid;
    }

    public function build()
    {
        return $this->subject('Comunicazione qualità interviste')
            ->view('emails.quality-malus');
    }
}
