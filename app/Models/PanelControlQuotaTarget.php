<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PanelControlQuotaTarget extends Model
{
    protected $table = 't_panel_control_quota_targets';

    public function panelControl()
    {
        return $this->belongsTo(PanelControl::class, 'panel_control_id', 'id');
    }

    protected $fillable = [
        'panel_control_id',
        'sur_id',
        'prj',
        'gender',
        'age_min',
        'age_max',
        'area',
        'target_percent',
        'target_value',
        'quota_status_name',
        'quota_dimension',
        'quota_label',
        'quota_status_id',
        'enabled',
    ];
}
