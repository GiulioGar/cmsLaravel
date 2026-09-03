<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class PanelQualityController extends Controller
{
    public function index()
    {
        // ── Tab Panelisti ─────────────────────────────────────────────────────
        $panelisti = DB::table('t_user_quality as uq')
            ->leftJoin('t_user_info as ui', 'uq.uid', '=', 'ui.user_id')
            ->selectRaw("
                uq.uid,
                TRIM(CONCAT(COALESCE(ui.first_name,''), ' ', COALESCE(ui.second_name,''))) AS full_name,
                COUNT(*)                                                                     AS interviste,
                ROUND(AVG(uq.quality_score), 1)                                             AS score_medio,
                SUM(CASE WHEN uq.quality_tier = 'regolare' THEN 1 ELSE 0 END)              AS regolari,
                SUM(CASE WHEN uq.quality_tier = 'incerta'  THEN 1 ELSE 0 END)              AS incerte,
                SUM(CASE WHEN uq.quality_tier = 'anomala'  THEN 1 ELSE 0 END)              AS anomale,
                SUM(uq.cap_applied)                                                          AS cap_count,
                MAX(uq.computed_at)                                                          AS ultima_val
            ")
            ->whereNotNull('uq.quality_score')
            ->groupBy('uq.uid', 'ui.first_name', 'ui.second_name')
            ->orderByRaw('AVG(uq.quality_score) ASC')
            ->get();

        // ── KPI globali — derivati in PHP dalla collection già in memoria ─────
        $intervisteTotali = $panelisti->sum('interviste');
        $anomaleTotali    = $panelisti->sum('anomale');
        $scorePonderato   = $intervisteTotali > 0
            ? round($panelisti->sum(fn ($p) => $p->score_medio * $p->interviste) / $intervisteTotali, 1)
            : null;

        $globalStats = (object) [
            'panelisti_totali'  => $panelisti->count(),
            'interviste_totali' => $intervisteTotali,
            'score_medio'       => $scorePonderato,
            'anomale_totali'    => $anomaleTotali,
            'incerte_totali'    => $panelisti->sum('incerte'),
            'regolari_totali'   => $panelisti->sum('regolari'),
            'cap_totali'        => $panelisti->sum('cap_count'),
        ];

        $pctAnomali = $intervisteTotali > 0
            ? round($anomaleTotali / $intervisteTotali * 100, 1)
            : 0;

        // ── Tab Ricerche — con dati qualità ──────────────────────────────────
        $ricercheConDati = DB::table('t_user_quality as uq')
            ->leftJoin('t_panel_control as pc', function ($join) {
                $join->on('uq.sid', '=', 'pc.sur_id')
                     ->on('uq.prj', '=', 'pc.prj');
            })
            ->leftJoin('t_fornitoripanel as fp', 'pc.panel', '=', 'fp.panel_code')
            ->selectRaw("
                uq.prj,
                uq.sid,
                pc.description,
                pc.stato,
                pc.panel_interno,
                pc.panel_esterno,
                fp.name                                                           AS panel_nome_esterno,
                COUNT(*)                                                         AS interviste_valutate,
                ROUND(AVG(uq.quality_score), 1)                                  AS score_medio,
                SUM(CASE WHEN uq.quality_tier = 'regolare' THEN 1 ELSE 0 END)   AS regolari,
                SUM(CASE WHEN uq.quality_tier = 'incerta'  THEN 1 ELSE 0 END)   AS incerte,
                SUM(CASE WHEN uq.quality_tier = 'anomala'  THEN 1 ELSE 0 END)   AS anomale,
                MAX(uq.computed_at)                                               AS ultima_val
            ")
            ->whereNotNull('uq.quality_score')
            ->groupBy('uq.prj', 'uq.sid', 'pc.description', 'pc.stato', 'pc.panel_interno', 'pc.panel_esterno', 'fp.name')
            ->orderByRaw('AVG(uq.quality_score) ASC')
            ->get();

        // ── Tab Ricerche — senza dati qualità (filtrate per anno) ────────────
        // panel_interno è numerico: > 0 indica ricerche con panel Interactive abilitato.
        // Anti-join LEFT JOIN + WHERE NULL ottimizzato da MySQL.
        $annoSenzaDati = (int) request('anno_senza_dati', now()->year);

        $anniDisponibili = DB::table('t_panel_control')
            ->selectRaw('DISTINCT YEAR(sur_date) as anno')
            ->where('panel_interno', '>', 0)
            ->whereNotNull('sur_date')
            ->orderByDesc('anno')
            ->pluck('anno');

        $ricerceSenzaDati = DB::table('t_panel_control as pc')
            ->leftJoin('t_user_quality as uq', function ($join) {
                $join->on('pc.sur_id', '=', 'uq.sid')
                     ->on('pc.prj', '=', 'uq.prj');
            })
            ->leftJoin('t_fornitoripanel as fp', 'pc.panel', '=', 'fp.panel_code')
            ->selectRaw('pc.prj, pc.sur_id, pc.description, pc.stato, pc.complete, pc.goal, pc.sur_date, pc.panel_interno, pc.panel_esterno, fp.name AS panel_nome_esterno')
            ->whereNull('uq.id')
            ->where('pc.panel_interno', '>', 0)
            ->whereRaw('YEAR(pc.sur_date) = ?', [$annoSenzaDati])
            ->orderByDesc('pc.sur_date')
            ->get();

        return view('panelQuality', compact(
            'globalStats',
            'pctAnomali',
            'panelisti',
            'ricercheConDati',
            'ricerceSenzaDati',
            'annoSenzaDati',
            'anniDisponibili'
        ));
    }
}
