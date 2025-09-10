<?php
// app/Models/UserInfo.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class UserInfo extends Model
{
    protected $table = 't_user_info';
    public $timestamps = false;

    // user_id è varchar(10) -> chiave string
    protected $primaryKey = 'user_id';
    public $incrementing = false;
    protected $keyType = 'string';

    /** Attivi + confermati */
    public function scopeAttiviEConfermati(Builder $q): Builder
    {
        return $q->where('confirm', 1)->where('active', 1);
    }

    /** Filtro sesso: accetta ['Uomo','Donna'] o [1,2] */
    public function scopeSesso(Builder $q, ?array $sessi): Builder
    {
        if (!$sessi || !count($sessi)) return $q; // se non specificato: nessun filtro
        $map = ['Uomo'=>1,'Donna'=>2,'1'=>1,'2'=>2];
        $vals = array_values(array_unique(array_filter(array_map(function($v) use ($map){
            $v = trim((string)$v);
            return $map[$v] ?? null;
        }, $sessi))));
        return count($vals) ? $q->whereIn('gender', $vals) : $q;
    }

    /** Età tra min/max: se assenti -> default 18-99 */
    public function scopeEta(Builder $q, ?int $min, ?int $max): Builder
    {
        $min = $min ?? 18;
        $max = $max ?? 99;

        // birth_date è varchar: assumo formato YYYY-MM-DD
        $expr = "TIMESTAMPDIFF(YEAR, STR_TO_DATE(birth_date, '%Y-%m-%d'), CURDATE())";
        return $q->whereRaw("$expr BETWEEN ? AND ?", [$min, $max]);
    }

    /** Regioni (colonna: reg) */
    public function scopeRegioni(Builder $q, ?array $regions): Builder
    {
        return ($regions && count($regions)) ? $q->whereIn('reg', $regions) : $q;
    }

    /** Aree (colonna: area) */
    public function scopeAree(Builder $q, ?array $areas): Builder
    {
        return ($areas && count($areas)) ? $q->whereIn('area', $areas) : $q;
    }

    /** Province (colonna: province_id) */
    public function scopeProvince(Builder $q, ?array $prov): Builder
    {
        return ($prov && count($prov)) ? $q->whereIn('province_id', $prov) : $q;
    }

    /** Escludi utenti già presenti in t_respint per una data ricerca (sid = sur_id corrente) */
    public function scopeNonGiaRespintiPerRicerca(Builder $q, string $surId, string $userKey = 'user_id'): Builder
    {
        return $q->whereNotExists(function($sub) use ($surId, $userKey) {
            $sub->select(DB::raw(1))
                ->from('t_respint as r')
                ->whereColumn("r.uid", "t_user_info.$userKey")
                ->where('r.sid', $surId);
        });
    }

    /** Escludi utenti respinti in ricerche pregresse: sid IN (...) e status <> 0 */
    public function scopeEscludiRicerche(Builder $q, array $sids, string $userKey = 'user_id'): Builder
    {
        if (!count($sids)) return $q;
        return $q->whereNotExists(function($sub) use ($sids, $userKey) {
            $sub->select(DB::raw(1))
                ->from('t_respint as r2')
                ->whereColumn("r2.uid", "t_user_info.$userKey")
                ->whereIn('r2.sid', $sids)
                ->where('r2.status', '<>', 0);
        });
    }

    /** Join + filtro su target_id nella tabella utenti_target */
    public function scopeTarget(Builder $q, ?int $targetId, string $userKey = 'user_id'): Builder
    {
        if (!$targetId) return $q;
        // join su utenti_target.uid = t_user_info.user_id (o uid se usi quello)
        return $q->join('utenti_target as ut', "ut.uid", "=", "t_user_info.$userKey")
                 ->where('ut.target_id', $targetId);
    }
}
