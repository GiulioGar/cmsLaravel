<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class PanelControl extends Model
{
    use HasFactory;
    protected $table = 't_panel_control';

    // Disattiva il comportamento di default di Eloquent per updated_at e created_at
    public $timestamps = false;

    // Campi assegnabili massivamente
    protected $fillable = [
        'sur_id', 'abilitati', 'contatti', 'red_panel', 'complete_int', 'complete_ext', 'complete',
        'sur_date', 'red_surv', 'prj', 'stato', 'last_update', 'end_field', 'sex_target',
        'age1_target', 'age2_target', 'description', 'filtrati', 'quota_full', 'incomplete',
        'panel_interno', 'panel_esterno', 'contatti_totali', 'abilitati_aggiornati', 'goal',
        'panel', 'giorni_rimanenti', 'durata', 'costo', 'paese', 'cliente', 'tipologia', 'bytes'
    ];

    // Campi di tipo datetime
    protected $dates = ['sur_date', 'last_update', 'end_field'];

       // Calcola i giorni in field
       public function giorniInField()
       {
           return Carbon::parse($this->sur_date)->diffInDays(Carbon::now());
       }
}
