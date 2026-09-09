<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QualityMalus extends Model
{
    protected $table = 't_quality_malus';

    protected $fillable = [
        'uid',
        'quality_score_snapshot',
        'valore',
        'motivazione',
        'assigned_by',
        'email_sent',
        'email_sent_at',
    ];

    protected $casts = [
        'email_sent'    => 'boolean',
        'email_sent_at' => 'datetime',
    ];
}
