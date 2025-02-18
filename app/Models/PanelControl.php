<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PanelControl extends Model
{
    protected $table = 't_panel_control';

    // Disattiva il comportamento di default di Eloquent per updated_at e created_at
    public $timestamps = false;

    // Altri settaggi...
}
