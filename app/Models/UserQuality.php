<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserQuality extends Model
{
    protected $table = 't_user_quality';

    protected $fillable = [
        'prj',
        'sid',
        'iid',
        'uid',
        'panel',
        'quality_score',
        'quality_tier',
        'quality_risk_total',
        'cap_applied',
        'computed_at',
    ];

    protected $casts = [
        'cap_applied'  => 'boolean',
        'computed_at'  => 'datetime',
    ];
}
