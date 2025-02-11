<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PanelControl extends Model
{
    // Se la tabella non segue la convenzione "nome_modello" al plurale
    protected $table = 't_panel_control';

    // Se la primary key non è "id", specificarla
    // protected $primaryKey = 'nome_della_chiave_primaria';

    // Se non usi i campi created_at / updated_at, disabilita i timestamps
    // public $timestamps = false;

    // Se vuoi rendere mass-assignable certe colonne:
    // protected $fillable = ['colonna1','colonna2','ecc'];
}
