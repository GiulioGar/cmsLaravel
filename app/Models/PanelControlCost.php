<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PanelControlCost extends Model
{
    protected $table = 't_panel_control_costs';

    protected $fillable = [
        'prj', 'sur_id', 'panel_name', 'cpi', 'costo_totale', 'referente', 'note',
    ];
}
