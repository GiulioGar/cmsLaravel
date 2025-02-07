<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    // Specifica il nome della tabella
    protected $table = 't_users';

    // Imposta la chiave primaria
    protected $primaryKey = 'id';

    // Se la tabella non gestisce i campi created_at e updated_at
    public $timestamps = false;

    // Campi assegnabili in massa
    protected $fillable = ['name', 'password', 'roles'];
}
