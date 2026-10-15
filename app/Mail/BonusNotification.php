<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BonusNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $valore;
    public $nome;
    public $uid;

    public function __construct(int $valore, string $nome, string $uid)
    {
        $this->valore = $valore;
        $this->nome   = $nome;
        $this->uid    = $uid;
    }

    public function build()
    {
        return $this->subject('Il tuo bonus di benvenuto Millebytes')
            ->view('emails.bonus-notification');
    }
}
