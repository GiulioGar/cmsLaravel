<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PanelQualityController extends Controller
{
    public function index()
    {
        // ── Tab Panelisti ─────────────────────────────────────────────────────
        // Niente JOIN diretto con t_user_info in fase di aggregazione: la collation di
        // t_user_quality.uid (utf8mb4_unicode_ci) non combacia con t_user_info.user_id
        // (latin1_swedish_ci), quindi MySQL non può usare la PK e fa uno scan incrociato
        // dell'intera t_user_info (~95k righe) per ogni riga aggregata — costava ~25s.
        // Si aggrega prima da sola (veloce, con indice su uid), poi si recuperano i nomi
        // con una whereIn mirata sui soli uid risultanti.
        $panelisti = DB::table('t_user_quality as uq')
            ->selectRaw("
                uq.uid,
                COUNT(*)                                                                     AS interviste,
                ROUND(AVG(uq.quality_score), 1)                                             AS score_medio,
                SUM(CASE WHEN uq.quality_tier = 'regolare' THEN 1 ELSE 0 END)              AS regolari,
                SUM(CASE WHEN uq.quality_tier = 'incerta'  THEN 1 ELSE 0 END)              AS incerte,
                SUM(CASE WHEN uq.quality_tier = 'anomala'  THEN 1 ELSE 0 END)              AS anomale,
                MAX(uq.computed_at)                                                          AS ultima_val
            ")
            ->whereNotNull('uq.quality_score')
            ->where('uq.panel', 'Interactive')
            ->groupBy('uq.uid')
            ->orderByRaw('AVG(uq.quality_score) ASC')
            ->get();

        $nomiByUid = DB::table('t_user_info')
            ->whereIn('user_id', $panelisti->pluck('uid'))
            ->select('user_id', 'first_name', 'second_name', 'points')
            ->get()
            ->keyBy('user_id');

        // Filtro difensivo: un uid senza corrispondenza in t_user_info non può essere
        // un vero panelista Interactive (t_user_info.user_id è varchar(10), niente
        // GUID esterni può starci). Copre i casi in cui detectPanel() etichetta
        // erroneamente "Interactive" per assenza del campo pan= nel file .sre.
        $panelisti = $panelisti->filter(fn ($p) => $nomiByUid->has($p->uid))->values();

        $malusByUid = DB::table('t_quality_malus')
            ->whereIn('uid', $panelisti->pluck('uid'))
            ->selectRaw('uid, COUNT(*) as malus_count')
            ->groupBy('uid')
            ->get()
            ->keyBy('uid');

        $panelisti->each(function ($p) use ($nomiByUid, $malusByUid) {
            $ui = $nomiByUid->get($p->uid);
            $p->full_name   = trim(($ui->first_name ?? '') . ' ' . ($ui->second_name ?? ''));
            $p->bytes       = (int) ($ui->points ?? 0);
            $p->malus_count = (int) (optional($malusByUid->get($p->uid))->malus_count ?? 0);
        });

        // Tutti i panelisti (già ordinati per score ASC) — paginati lato client a 30/pagina
        $panelistiTable = $panelisti;

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
            ->where('uq.panel', 'Interactive')
            ->groupBy('uq.prj', 'uq.sid', 'pc.description', 'pc.stato', 'pc.panel_interno', 'pc.panel_esterno', 'fp.name')
            ->orderByRaw('AVG(uq.quality_score) ASC')
            ->get();

        // ── Tab Ricerche — senza dati qualità (filtrate per anno) ────────────
        // panel_interno è numerico: > 0 indica ricerche con panel Interactive abilitato.
        // Anti-join LEFT JOIN + WHERE NULL ottimizzato da MySQL.
        $annoSenzaDati = (int) request('anno_senza_dati', now()->year);

        $anniDisponibili = DB::table('t_panel_control')
            ->selectRaw('DISTINCT YEAR(sur_date) as anno')
            ->whereNotNull('sur_date')
            ->where('complete', '>', 0)
            ->orderByDesc('anno')
            ->pluck('anno');

        // Tutte le ricerche con completamenti ma senza dati qualità (Interactive + Esterno unificati).
        // complete_int/complete_ext vengono passati alla blade per data-int/data-ext — filtro lato client.
        $ricerceSenzaDati = DB::table('t_panel_control as pc')
            ->leftJoin('t_user_quality as uq', function ($join) {
                $join->on('pc.sur_id', '=', 'uq.sid')
                     ->on('pc.prj', '=', 'uq.prj');
            })
            ->leftJoin('t_fornitoripanel as fp', 'pc.panel', '=', 'fp.panel_code')
            ->selectRaw('pc.prj, pc.sur_id, pc.description, pc.stato, pc.complete, pc.complete_int, pc.complete_ext, pc.goal, pc.sur_date, fp.name AS panel_nome_esterno')
            ->whereNull('uq.id')
            ->where('pc.complete', '>', 0)
            ->whereRaw('YEAR(pc.sur_date) = ?', [$annoSenzaDati])
            ->orderByDesc('pc.sur_date')
            ->get();

        // ── Tab Panel Esterni — media per panel + dettaglio per ricerca ──────
        $panelEsterniRollup = DB::table('t_user_quality as uq')
            ->selectRaw("
                uq.panel,
                COUNT(DISTINCT CONCAT(uq.prj, '|', uq.sid))                      AS ricerche,
                COUNT(*)                                                         AS interviste,
                ROUND(AVG(uq.quality_score), 1)                                  AS score_medio,
                SUM(CASE WHEN uq.quality_tier = 'regolare' THEN 1 ELSE 0 END)   AS regolari,
                SUM(CASE WHEN uq.quality_tier = 'incerta'  THEN 1 ELSE 0 END)   AS incerte,
                SUM(CASE WHEN uq.quality_tier = 'anomala'  THEN 1 ELSE 0 END)   AS anomale,
                MAX(uq.computed_at)                                               AS ultima_val
            ")
            ->whereNotNull('uq.quality_score')
            ->where('uq.panel', '!=', 'Interactive')
            ->groupBy('uq.panel')
            ->orderByRaw('AVG(uq.quality_score) ASC')
            ->get();

        $panelEsterniPerRicerca = DB::table('t_user_quality as uq')
            ->leftJoin('t_panel_control as pc', function ($join) {
                $join->on('uq.sid', '=', 'pc.sur_id')
                     ->on('uq.prj', '=', 'pc.prj');
            })
            ->selectRaw("
                uq.prj,
                uq.sid,
                uq.panel,
                pc.description,
                pc.stato,
                COUNT(*)                                                         AS interviste_valutate,
                ROUND(AVG(uq.quality_score), 1)                                  AS score_medio,
                SUM(CASE WHEN uq.quality_tier = 'regolare' THEN 1 ELSE 0 END)   AS regolari,
                SUM(CASE WHEN uq.quality_tier = 'incerta'  THEN 1 ELSE 0 END)   AS incerte,
                SUM(CASE WHEN uq.quality_tier = 'anomala'  THEN 1 ELSE 0 END)   AS anomale,
                MAX(uq.computed_at)                                               AS ultima_val
            ")
            ->whereNotNull('uq.quality_score')
            ->where('uq.panel', '!=', 'Interactive')
            ->groupBy('uq.prj', 'uq.sid', 'uq.panel', 'pc.description', 'pc.stato')
            ->orderByRaw('AVG(uq.quality_score) ASC')
            ->get();

        return view('panelQuality', compact(
            'globalStats',
            'pctAnomali',
            'panelisti',
            'panelistiTable',
            'ricercheConDati',
            'ricerceSenzaDati',
            'annoSenzaDati',
            'anniDisponibili',
            'panelEsterniRollup',
            'panelEsterniPerRicerca'
        ));
    }

    public function exportPanelisti(Request $request)
    {
        $scoreMin = is_numeric($request->query('score_min')) ? (float) $request->query('score_min') : 0;
        $scoreMax = is_numeric($request->query('score_max')) ? (float) $request->query('score_max') : 100;
        $minInterviste = is_numeric($request->query('min_interviste')) ? (int) $request->query('min_interviste') : 0;

        $panelisti = DB::table('t_user_quality as uq')
            ->selectRaw('uq.uid, ROUND(AVG(uq.quality_score), 1) AS score_medio')
            ->whereNotNull('uq.quality_score')
            ->where('uq.panel', 'Interactive')
            ->groupBy('uq.uid')
            ->havingRaw('AVG(uq.quality_score) BETWEEN ? AND ? AND COUNT(*) >= ?', [$scoreMin, $scoreMax, $minInterviste])
            ->orderByRaw('AVG(uq.quality_score) ASC')
            ->get();

        $infoByUid = DB::table('t_user_info')
            ->whereIn('user_id', $panelisti->pluck('uid'))
            ->select('user_id', 'first_name', 'second_name', 'email')
            ->get()
            ->keyBy('user_id');

        $filename = 'panelisti_qualita_' . $scoreMin . '-' . $scoreMax . '.csv';

        return response()->streamDownload(function () use ($panelisti, $infoByUid) {
            // Righe scritte a mano (non fputcsv) per evitare le virgolette che PHP
            // aggiunge automaticamente ai campi contenenti spazi (es. nomi composti).
            $out = fopen('php://output', 'w');
            fwrite($out, "user_id;first_name;email\r\n");

            foreach ($panelisti as $p) {
                $ui = $infoByUid->get($p->uid);
                // Uid senza corrispondenza in t_user_info: non è un panelista Interactive
                // reale (vedi filtro difensivo analogo nella tabella Panelisti).
                if (!$ui) {
                    continue;
                }
                $nome = trim(($ui->first_name ?? '') . ' ' . ($ui->second_name ?? ''));
                fwrite($out, $p->uid . ';' . $nome . ';' . ($ui->email ?? '') . "\r\n");
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
