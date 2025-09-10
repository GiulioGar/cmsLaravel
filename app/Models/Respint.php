<?php
// app/Models/Respint.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Respint extends Model
{
    protected $table = 't_respint';
    public $timestamps = false;
    // colonne tipiche: uid, sid, status, ...
}
