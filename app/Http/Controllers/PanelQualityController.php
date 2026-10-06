<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PanelQualityController extends Controller
{
    public function index()
    {
        $pd = $this->panelistiData();

        // Tab Panelisti: 958 righe renderizzate tutte insieme pesavano ~2.5MB da sole
        // (il grosso del peso dell'intera pagina, più di tab 2-4 messe insieme) ed è
        // l'unica tab visibile da subito — quindi qui si manda solo la prima pagina
        // (30 righe, stesso pageSize della paginazione client-side), il resto arriva
        // in background via tabPanelistiFull() e sostituisce il tbody (vedi JS).
        $panelistiPreview = $pd['panelistiTable']->take(30)->values();

        // Tab 2-4: il contenuto completo (righe tabella) si carica via AJAX al primo
        // click (vedi tabRicerche/tabPanelEsterni/tabDuplicati + lazy load in JS).
        // Qui servono solo i conteggi per i badge in cima — query già leggere dopo
        // il fix di collation (vedi panelControlLookup), quindi va bene ricalcolarle
        // sia qui (per il numero) sia nell'endpoint lazy (per il rendering).
        $countRicercheConDati  = $this->ricercheConDatiData()->count();
        $annoSenzaDati         = (int) request('anno_senza_dati', now()->year);
        $countRicerceSenzaDati = $this->ricerceSenzaDatiData($annoSenzaDati)->count();
        $countPanelEsterni     = $this->panelEsterniRollupData()->count();

        // Duplicati: il blocco "Analisi gruppi" è mostrato subito (nascosto via CSS
        // finché non si apre la tab), quindi resta eager — è comunque economico
        // (~35ms) dopo il fix di buildDuplicatiData(). Solo la tabella dettaglio
        // per-UID (tab-duplicati) è deferita via lazy load.
        $dupData            = $this->buildDuplicatiData();
        $nUidDuplicati      = $dupData['nUidDuplicati'];
        $nRicercheDuplicati = $dupData['nRicercheDuplicati'];
        $gruppiSospetti     = $dupData['gruppiSospetti'];
        $nGruppi            = count($gruppiSospetti);
        $nAltoRischio       = count(array_filter($gruppiSospetti, fn ($g) => $g['risk'] === 'alto'));

        return view('panelQuality', [
            'globalStats'           => $pd['globalStats'],
            'pctAnomali'            => $pd['pctAnomali'],
            'panelisti'             => $pd['panelisti'],
            'panelistiTable'        => $panelistiPreview,
            'countRicercheConDati'  => $countRicercheConDati,
            'countRicerceSenzaDati' => $countRicerceSenzaDati,
            'countPanelEsterni'     => $countPanelEsterni,
            'nUidDuplicati'         => $nUidDuplicati,
            'nRicercheDuplicati'    => $nRicercheDuplicati,
            'gruppiSospetti'        => $gruppiSospetti,
            'nGruppi'               => $nGruppi,
            'nAltoRischio'          => $nAltoRischio,
        ]);
    }

    public function tabRicerche(Request $request)
    {
        $ricercheConDati  = $this->ricercheConDatiData();
        $annoSenzaDati    = (int) $request->get('anno_senza_dati', now()->year);
        $ricerceSenzaDati = $this->ricerceSenzaDatiData($annoSenzaDati);
        $anniDisponibili  = $this->anniDisponibiliRicerche();

        return view('panelQuality.tabs.ricerche', compact(
            'ricercheConDati', 'ricerceSenzaDati', 'annoSenzaDati', 'anniDisponibili'
        ));
    }

    public function tabPanelEsterni()
    {
        $panelEsterniRollup     = $this->panelEsterniRollupData();
        $panelEsterniPerRicerca = $this->panelEsterniPerRicercaData();

        return view('panelQuality.tabs.panel-esterni', compact(
            'panelEsterniRollup', 'panelEsterniPerRicerca'
        ));
    }

    public function tabPanelistiFull()
    {
        $pd = $this->panelistiData();

        return view('panelQuality.tabs.panelisti-rows', ['panelistiTable' => $pd['panelistiTable']]);
    }

    public function tabDuplicati()
    {
        $dupData = $this->buildDuplicatiData();

        return view('panelQuality.tabs.duplicati', [
            'duplicati'          => $dupData['duplicati'],
            'nUidDuplicati'      => $dupData['nUidDuplicati'],
            'nRicercheDuplicati' => $dupData['nRicercheDuplicati'],
        ]);
    }

    /**
     * Dettaglio analisi di un gruppo sospetto (modale "Utenti del gruppo"): per ogni
     * membro raccoglie i segnali che aiutano a capire se è davvero la stessa persona
     * (IP di prelievo condivisi, stessa data di nascita, stessa città, registrazioni
     * ravvicinate) — stesso indice 1-based di exportGruppi().
     */
    public function groupDetail(Request $request)
    {
        $gruppoParam = $request->query('gruppo');
        if (!is_numeric($gruppoParam)) {
            return response()->json(['error' => 'Parametro gruppo mancante.'], 422);
        }

        $gruppiSospetti = $this->buildDuplicatiData()['gruppiSospetti'];
        $idx = ((int) $gruppoParam) - 1;
        if (!isset($gruppiSospetti[$idx])) {
            return response()->json(['error' => 'Gruppo non trovato.'], 404);
        }

        $group = $gruppiSospetti[$idx];
        $uids = array_column($group['membri'], 'uid');

        // IP di prelievo (t_user_history.ip è popolato solo sugli eventi 'withdraw';
        // niente indice su user_id ma il costo è irrilevante per un gruppo di poche
        // decine di uid, ed è una query on-demand al click, non a carico pagina).
        $ipRows = DB::table('t_user_history')
            ->whereIn('user_id', $uids)
            ->whereNotNull('ip')
            ->where('ip', '!=', '')
            ->orderBy('event_date')
            ->get(['user_id', 'ip', 'event_date']);

        // Raggruppa per uid+ip (un utente può prelevare più volte dallo stesso IP:
        // conta come un solo "IP distinto", con prima/ultima data e occorrenze).
        $ipOccurrencesByUid = [];
        $uidsByIp = [];
        foreach ($ipRows as $r) {
            $ipOccurrencesByUid[$r->user_id][$r->ip][] = $r->event_date;
            $uidsByIp[$r->ip][$r->user_id] = true;
        }

        // IP condivisi da 2+ membri del gruppo: un colore fisso per IP (ciclico sulla
        // stessa palette usata per gli avatar in tab Panelisti), così lo stesso colore
        // nelle righe di membri diversi salta all'occhio come "stesso IP".
        $palette = ['#3b82f6', '#8b5cf6', '#10b981', '#f59e0b', '#ef4444', '#ec4899', '#14b8a6', '#f97316'];
        $ipColor = [];
        $sharedIpUids = [];
        $colorIdx = 0;
        foreach ($uidsByIp as $ip => $uidsForIp) {
            if (count($uidsForIp) < 2) {
                continue;
            }
            $ipColor[$ip] = $palette[$colorIdx % count($palette)];
            $colorIdx++;
            foreach ($uidsForIp as $u => $_) {
                $sharedIpUids[$u] = true;
            }
        }

        $nameByUid = array_column($group['membri'], 'name', 'uid');

        // Un badge per IP distinto (non uno per ogni prelievo): etichetta "N volte"
        // se riutilizzato più volte dallo stesso utente, colorato+tooltip "con chi"
        // se condiviso con altri membri del gruppo, grigio neutro altrimenti.
        $ipsByUid = [];
        foreach ($ipOccurrencesByUid as $uid => $ipsForUid) {
            foreach ($ipsForUid as $ip => $dates) {
                $partners = array_values(array_diff(array_keys($uidsByIp[$ip] ?? []), [$uid]));
                $ipsByUid[$uid][] = [
                    'ip'       => $ip,
                    'count'    => count($dates),
                    'first'    => min($dates),
                    'last'     => max($dates),
                    'shared'   => isset($ipColor[$ip]),
                    'color'    => $ipColor[$ip] ?? null,
                    'partners' => array_map(fn ($p) => $p . ($nameByUid[$p] ?? null ? ' (' . $nameByUid[$p] . ')' : ''), $partners),
                ];
            }
            // IP più recente per primo.
            usort($ipsByUid[$uid], fn ($a, $b) => $b['last'] <=> $a['last']);
        }

        $groupBy = function (array $members, callable $keyFn) {
            $buckets = [];
            foreach ($members as $m) {
                $key = $keyFn($m);
                if ($key === null || $key === '') {
                    continue;
                }
                $buckets[$key][] = $m['uid'];
            }
            return array_filter($buckets, fn ($uids) => count($uids) >= 2);
        };

        $uidToFlag = function (array $buckets) {
            $map = [];
            foreach ($buckets as $uids) {
                foreach ($uids as $u) {
                    $map[$u] = true;
                }
            }
            return $map;
        };

        $sharedBirthUids  = $uidToFlag($groupBy($group['membri'], fn ($m) => $m['birth_date']));
        $sharedCityUids   = $uidToFlag($groupBy($group['membri'], fn ($m) => $m['city'] ? trim(mb_strtolower($m['city'])) : null));
        $sharedRegDayUids = $uidToFlag($groupBy($group['membri'], fn ($m) => $m['reg_date'] ? substr($m['reg_date'], 0, 10) : null));

        $members = array_map(function ($m) use ($ipsByUid, $sharedIpUids, $sharedBirthUids, $sharedCityUids, $sharedRegDayUids) {
            $m['ips']          = $ipsByUid[$m['uid']] ?? [];
            $m['flag_ip']      = isset($sharedIpUids[$m['uid']]);
            $m['flag_birth']   = isset($sharedBirthUids[$m['uid']]);
            $m['flag_city']    = isset($sharedCityUids[$m['uid']]);
            $m['flag_reg_day'] = isset($sharedRegDayUids[$m['uid']]);
            return $m;
        }, $group['membri']);

        return view('panelQuality.tabs.group-detail', [
            'members'  => $members,
            'risk'     => $group['risk'],
            'gruppoIdx' => (int) $gruppoParam,
        ]);
    }

    // ── Tab Panelisti ─────────────────────────────────────────────────────────
    private function panelistiData(): array
    {
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

        return [
            'panelisti'      => $panelisti,
            'panelistiTable' => $panelistiTable,
            'globalStats'    => $globalStats,
            'pctAnomali'     => $pctAnomali,
        ];
    }

    // ── Lookup condiviso t_panel_control/t_fornitoripanel ───────────────────────
    // Niente JOIN diretto con t_user_quality: t_panel_control è latin1_swedish_ci
    // (vs utf8mb4_unicode_ci di uq) e non ha indici secondari oltre alla PK — un
    // JOIN forzerebbe uno scan incrociato ALL×ALL (misurato ~1.6s anche con poche
    // migliaia di righe, e peggiora come N×M al crescere dei dati). Si passano qui
    // solo i (prj,sid) già noti (da un'aggregazione fatta su uq da sola) e si
    // recupera il resto con un whereIn mirato.
    private function panelControlLookup(\Illuminate\Support\Collection $prjSidPairs): \Illuminate\Support\Collection
    {
        $prjSidPairs = $prjSidPairs->unique(fn ($p) => $p[0] . '|' . $p[1])->values();
        if ($prjSidPairs->isEmpty()) {
            return collect();
        }

        $placeholders = implode(',', array_fill(0, $prjSidPairs->count(), '(?, ?)'));
        $bindings = $prjSidPairs->flatMap(fn ($p) => $p)->all();

        return DB::table('t_panel_control as pc')
            ->leftJoin('t_fornitoripanel as fp', 'pc.panel', '=', 'fp.panel_code')
            ->select('pc.prj', 'pc.sur_id', 'pc.description', 'pc.stato', 'pc.panel_interno', 'pc.panel_esterno', 'fp.name as panel_nome_esterno')
            ->whereRaw("(pc.prj, pc.sur_id) IN ({$placeholders})", $bindings)
            ->get()
            ->keyBy(fn ($pc) => $pc->prj . '|' . $pc->sur_id);
    }

    // ── Tab Ricerche — con dati qualità ──────────────────────────────────────────
    private function ricercheConDatiData(): \Illuminate\Support\Collection
    {
        $agg = DB::table('t_user_quality as uq')
            ->selectRaw("
                uq.prj,
                uq.sid,
                COUNT(*)                                                         AS interviste_valutate,
                AVG(uq.quality_score)                                            AS score_medio_raw,
                SUM(CASE WHEN uq.quality_tier = 'regolare' THEN 1 ELSE 0 END)   AS regolari,
                SUM(CASE WHEN uq.quality_tier = 'incerta'  THEN 1 ELSE 0 END)   AS incerte,
                SUM(CASE WHEN uq.quality_tier = 'anomala'  THEN 1 ELSE 0 END)   AS anomale,
                MAX(uq.computed_at)                                               AS ultima_val
            ")
            ->whereNotNull('uq.quality_score')
            ->where('uq.panel', 'Interactive')
            ->groupBy('uq.prj', 'uq.sid')
            ->get();

        $pcByPrjSid = $this->panelControlLookup($agg->map(fn ($r) => [$r->prj, $r->sid]));

        return $agg->map(function ($r) use ($pcByPrjSid) {
            $pc = $pcByPrjSid->get($r->prj . '|' . $r->sid);
            $r->description       = $pc->description ?? null;
            $r->stato              = $pc->stato ?? null;
            $r->panel_interno      = $pc->panel_interno ?? null;
            $r->panel_esterno      = $pc->panel_esterno ?? null;
            $r->panel_nome_esterno = $pc->panel_nome_esterno ?? null;
            // number_format (non round) per mantenere lo stesso formato a 1 decimale
            // fisso di ROUND() lato SQL (es. "100.0", non "100").
            $r->score_medio        = number_format((float) $r->score_medio_raw, 1);
            return $r;
        })->sortBy('score_medio_raw')->values();
    }

    // ── Tab Ricerche — senza dati qualità (filtrate per anno) ───────────────────
    private function anniDisponibiliRicerche(): \Illuminate\Support\Collection
    {
        return DB::table('t_panel_control')
            ->selectRaw('DISTINCT YEAR(sur_date) as anno')
            ->whereNotNull('sur_date')
            ->where('complete', '>', 0)
            ->orderByDesc('anno')
            ->pluck('anno');
    }

    private function ricerceSenzaDatiData(int $annoSenzaDati): \Illuminate\Support\Collection
    {
        // panel_interno è numerico: > 0 indica ricerche con panel Interactive abilitato.
        // Anti-join LEFT JOIN + WHERE NULL ottimizzato da MySQL.
        // complete_int/complete_ext vengono passati alla blade per data-int/data-ext — filtro lato client.
        return DB::table('t_panel_control as pc')
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
    }

    // ── Tab Panel Esterni — media per panel + dettaglio per ricerca ─────────────
    private function panelEsterniRollupData(): \Illuminate\Support\Collection
    {
        return DB::table('t_user_quality as uq')
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
    }

    private function panelEsterniPerRicercaData(): \Illuminate\Support\Collection
    {
        // Stesso fix di ricercheConDatiData(): aggrega uq da sola (gruppo include anche uq.panel).
        $agg = DB::table('t_user_quality as uq')
            ->selectRaw("
                uq.prj,
                uq.sid,
                uq.panel,
                COUNT(*)                                                         AS interviste_valutate,
                AVG(uq.quality_score)                                            AS score_medio_raw,
                SUM(CASE WHEN uq.quality_tier = 'regolare' THEN 1 ELSE 0 END)   AS regolari,
                SUM(CASE WHEN uq.quality_tier = 'incerta'  THEN 1 ELSE 0 END)   AS incerte,
                SUM(CASE WHEN uq.quality_tier = 'anomala'  THEN 1 ELSE 0 END)   AS anomale,
                MAX(uq.computed_at)                                               AS ultima_val
            ")
            ->whereNotNull('uq.quality_score')
            ->where('uq.panel', '!=', 'Interactive')
            ->groupBy('uq.prj', 'uq.sid', 'uq.panel')
            ->get();

        $pcByPrjSid = $this->panelControlLookup($agg->map(fn ($r) => [$r->prj, $r->sid]));

        return $agg->map(function ($r) use ($pcByPrjSid) {
            $pc = $pcByPrjSid->get($r->prj . '|' . $r->sid);
            $r->description = $pc->description ?? null;
            $r->stato        = $pc->stato ?? null;
            $r->score_medio  = number_format((float) $r->score_medio_raw, 1);
            return $r;
        })->sortBy('score_medio_raw')->values();
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

    private function buildDuplicatiData(): array
    {
        // Niente JOIN diretto con t_panel_control: stesso mismatch di collation già
        // visto in index() (t_panel_similar è utf8mb4_unicode_ci, t_panel_control è
        // latin1_swedish_ci e senza indice su prj/sur_id) — oggi costa poco perché
        // t_panel_similar ha poche righe, ma cresce come N×M con le segnalazioni.
        // Si recupera ps da sola, poi le description mirate sui soli (prj,sid) trovati.
        $duplicatiRaw = DB::table('t_panel_similar as ps')
            ->select('ps.uid', 'ps.prj', 'ps.sid', 'ps.similar_to', 'ps.flagged_at')
            ->orderByDesc('ps.flagged_at')
            ->get();

        $prjSidPairs = $duplicatiRaw->map(fn ($d) => [$d->prj, $d->sid])
            ->unique(fn ($p) => $p[0] . '|' . $p[1])
            ->values();

        $descByPrjSid = collect();
        if ($prjSidPairs->isNotEmpty()) {
            $placeholders = implode(',', array_fill(0, $prjSidPairs->count(), '(?, ?)'));
            $bindings = $prjSidPairs->flatMap(fn ($p) => $p)->all();

            $descByPrjSid = DB::table('t_panel_control')
                ->select('prj', 'sur_id', 'description')
                ->whereRaw("(prj, sur_id) IN ({$placeholders})", $bindings)
                ->get()
                ->keyBy(fn ($pc) => $pc->prj . '|' . $pc->sur_id);
        }

        // Aggrega per uid: segnalazioni count, ricerche coinvolte, conteggio simile_a
        $byUid = [];
        foreach ($duplicatiRaw as $d) {
            $uid = $d->uid;
            if (!isset($byUid[$uid])) {
                $byUid[$uid] = [
                    'uid'          => $uid,
                    'full_name'    => null,
                    'email'        => null,
                    'active'       => null,
                    'segnalazioni' => 0,
                    'ricerche'     => [], // 'PRJ/SID' => description
                    'simile_a'     => [], // uid => count
                    'ultima'       => $d->flagged_at,
                ];
            }
            $byUid[$uid]['segnalazioni']++;
            $rKey = $d->prj . '/' . $d->sid;
            $byUid[$uid]['ricerche'][$rKey] = optional($descByPrjSid->get($d->prj . '|' . $d->sid))->description ?? $rKey;
            foreach (array_filter(array_map('trim', explode(';', $d->similar_to))) as $other) {
                $byUid[$uid]['simile_a'][$other] = ($byUid[$uid]['simile_a'][$other] ?? 0) + 1;
            }
            if ($d->flagged_at > $byUid[$uid]['ultima']) {
                $byUid[$uid]['ultima'] = $d->flagged_at;
            }
        }

        $nomiDuplicati = DB::table('t_user_info')
            ->whereIn('user_id', array_keys($byUid))
            ->select('user_id', 'first_name', 'second_name', 'email', 'active', 'city', 'birth_date', 'reg_date')
            ->get()
            ->keyBy('user_id');

        foreach ($byUid as $uid => &$row) {
            $ui = $nomiDuplicati->get($uid);
            $row['full_name']  = $ui ? trim(($ui->first_name ?? '') . ' ' . ($ui->second_name ?? '')) : null;
            $row['email']      = $ui ? ($ui->email ?? null) : null;
            $row['active']     = $ui ? (int) $ui->active : null;
            $row['city']       = $ui ? ($ui->city ?? null) : null;
            $row['birth_date'] = $ui ? ($ui->birth_date ?? null) : null;
            $row['reg_date']   = $ui ? ($ui->reg_date ?? null) : null;
            arsort($row['simile_a']); // ordina per occorrenze desc
        }
        unset($row);

        usort($byUid, fn ($a, $b) => $b['segnalazioni'] - $a['segnalazioni']);

        $duplicati          = collect(array_values($byUid));
        $nUidDuplicati      = $duplicati->count();
        $nRicercheDuplicati = $duplicatiRaw->map(fn ($d) => $d->prj . '|' . $d->sid)->unique()->count();

        // ── Componenti connesse (BFS) — gruppi "probabilmente stessa persona" ──
        // Il grafo è già simmetrico (record speculari in t_panel_similar).
        $byUidMap = array_column(iterator_to_array($duplicati), null, 'uid');
        $visitati = [];
        $gruppiSospetti = [];

        foreach (array_keys($byUidMap) as $startUid) {
            if (isset($visitati[$startUid])) continue;
            $membri = [];
            $coda   = [$startUid];
            while (!empty($coda)) {
                $node = array_shift($coda);
                if (isset($visitati[$node])) continue;
                $visitati[$node] = true;
                $membri[] = $node;
                foreach (array_keys($byUidMap[$node]['simile_a'] ?? []) as $vicino) {
                    if (!isset($visitati[$vicino]) && isset($byUidMap[$vicino])) {
                        $coda[] = $vicino;
                    }
                }
            }
            if (count($membri) < 2) continue;

            $ricercheGruppo  = [];
            $maxRipetizioni  = 0;
            $segnTotali      = 0;
            $membriDettaglio = [];
            foreach ($membri as $uid) {
                $row = $byUidMap[$uid];
                $segnTotali += $row['segnalazioni'];
                foreach ($row['ricerche'] as $k => $v) { $ricercheGruppo[$k] = $v; }
                foreach ($row['simile_a'] as $cnt) { if ($cnt > $maxRipetizioni) $maxRipetizioni = $cnt; }
                $membriDettaglio[] = [
                    'uid'        => $uid,
                    'name'       => $row['full_name'],
                    'email'      => $row['email'],
                    'active'     => $row['active'],
                    'city'       => $row['city'],
                    'birth_date' => $row['birth_date'],
                    'reg_date'   => $row['reg_date'],
                ];
            }

            $gruppiSospetti[] = [
                'membri'         => $membriDettaglio,
                'size'           => count($membri),
                'ricerche'       => $ricercheGruppo,
                'segnalazioni'   => $segnTotali,
                'max_rip'        => $maxRipetizioni,
                'risk'           => $maxRipetizioni >= 2 ? 'alto' : 'medio',
            ];
        }

        usort($gruppiSospetti, function ($a, $b) {
            if ($a['risk'] !== $b['risk']) return $a['risk'] === 'alto' ? -1 : 1;
            return $b['size'] - $a['size'];
        });

        return [
            'duplicati'          => $duplicati,
            'nUidDuplicati'      => $nUidDuplicati,
            'nRicercheDuplicati' => $nRicercheDuplicati,
            'gruppiSospetti'     => $gruppiSospetti,
        ];
    }

    public function exportGruppi(Request $request)
    {
        $gruppiSospetti = $this->buildDuplicatiData()['gruppiSospetti'];

        $gruppoParam = $request->query('gruppo');
        $filename    = 'gruppi_sospetti_duplicati.csv';

        if (is_numeric($gruppoParam)) {
            $idx = ((int) $gruppoParam) - 1;
            $gruppiSospetti = isset($gruppiSospetti[$idx]) ? [$idx => $gruppiSospetti[$idx]] : [];
            $filename = 'gruppo_sospetto_' . (int) $gruppoParam . '.csv';
        }

        return response()->streamDownload(function () use ($gruppiSospetti) {
            $out = fopen('php://output', 'w');
            fwrite($out, "Gruppo;Rischio;uid;email;nome\r\n");

            foreach ($gruppiSospetti as $gi => $g) {
                $rischio = $g['risk'] === 'alto' ? 'Alto' : 'Medio';
                foreach ($g['membri'] as $m) {
                    fwrite($out, ($gi + 1) . ';' . $rischio . ';' . $m['uid'] . ';' . ($m['email'] ?? '') . ';' . ($m['name'] ?? '') . "\r\n");
                }
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
