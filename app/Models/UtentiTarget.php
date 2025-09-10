<?php
// app/Models/UtentiTarget.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UtentiTarget extends Model
{
    protected $table = 'utenti_target';
    public $timestamps = false;
    // colonne: id, uid, target_id, target_name
}
