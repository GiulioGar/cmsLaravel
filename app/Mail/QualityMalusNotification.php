<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class QualityMalusNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $motivazione;
    public $valore;
    public $nome;

    public function __construct(string $motivazione, int $valore, string $nome)
    {
        $this->motivazione = $motivazione;
        $this->valore      = $valore;
        $this->nome        = $nome;
    }

    public function build()
    {
        return $this->subject('Comunicazione qualità intervista')
            ->view('emails.quality-malus');
    }
}
