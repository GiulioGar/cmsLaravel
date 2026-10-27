<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Carbon\Carbon;

class PanelUsersController extends Controller
{

        public function index()
        {
            $annoSelezionato = now()->year;

            $anniDisponibili = DB::table('t_panel_control')
                ->selectRaw("DISTINCT YEAR(sur_date) as anno")
                ->whereNotNull('sur_date')
                ->orderByDesc('anno')
                ->pluck('anno');

            return view('panelUsers', compact(
                'annoSelezionato',
                'anniDisponibili'
            ));
        }

/**
 * Sincronizza t_user_info.actions con le nuove righe di t_user_history
 * accumulate dall'ultimo click. Esclude eventi di sistema/registrazione
 * (coerente con l'esclusione usata in getInactiveSummary() e nel modulo
 * Recruitment). Scrittura a blocchi con LOW_PRIORITY per non bloccare
 * a lungo le altre pagine che leggono t_user_info/t_user_history (MyISAM,
 * lock a livello di tabella).
 */
public function syncActions(Request $request)
{
    $lock = Cache::lock('panelusers_actions_sync', 120);

    if (!$lock->get()) {
        return response()->json([
            'success' => false,
            'message' => 'Una sincronizzazione è già in corso, riprova in un minuto.',
        ], 409);
    }

    try {
        $startedAt = microtime(true);

        $excludedEvents = [
            'BAN', 'MALUS QUALITA', 'Malus', 'PREMIO REVOCATO', 'PREMIO RIPRISTINATO', 'livelli_rimossi',
            'subscribe', 'unsubscribe',
        ];

        $state = DB::table('t_recruitment_actions_sync')->first();
        $currentMaxId = (int) (DB::table('t_user_history')->max('id') ?? 0);

        if (!$state) {
            DB::table('t_recruitment_actions_sync')->insert([
                'last_history_id' => $currentMaxId,
                'last_run_at' => now(),
                'last_run_rows' => 0,
                'last_run_users' => 0,
                'last_run_duration_ms' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'initialized' => true,
                'partial' => false,
                'message' => 'Sincronizzazione inizializzata. Il conteggio storico è già aggiornato (caricamento manuale appena effettuato); da ora le nuove attività verranno sincronizzate ad ogni click.',
                'rows_read' => 0,
                'users_updated' => 0,
                'duration_ms' => round((microtime(true) - $startedAt) * 1000),
            ]);
        }

        $lastId = (int) $state->last_history_id;

        if ($currentMaxId <= $lastId) {
            return response()->json([
                'success' => true,
                'initialized' => false,
                'partial' => false,
                'message' => 'Nessuna nuova attività da sincronizzare: tutto è già aggiornato.',
                'rows_read' => 0,
                'users_updated' => 0,
                'duration_ms' => round((microtime(true) - $startedAt) * 1000),
            ]);
        }

        $chunkSize = 5000;
        $maxRowsPerRun = 300000; // tetto di sicurezza per non far scadere la richiesta HTTP
        $placeholders = implode(',', array_fill(0, count($excludedEvents), '?'));

        $cursor = $lastId;
        $processedRows = 0;

        while ($cursor < $currentMaxId && $processedRows < $maxRowsPerRun) {
            $chunkEnd = min($cursor + $chunkSize, $currentMaxId);

            DB::statement("
                UPDATE LOW_PRIORITY t_user_info u
                INNER JOIN (
                    SELECT user_id, COUNT(*) as cnt
                    FROM t_user_history
                    WHERE id > ? AND id <= ?
                      AND event_type NOT IN ($placeholders)
                    GROUP BY user_id
                ) h ON h.user_id = u.user_id
                SET u.actions = u.actions + h.cnt
            ", array_merge([$cursor, $chunkEnd], $excludedEvents));

            $processedRows += ($chunkEnd - $cursor);
            $cursor = $chunkEnd;

            // avanzamento salvato ad ogni blocco: se qualcosa si interrompe, si riparte da qui
            DB::table('t_recruitment_actions_sync')->update([
                'last_history_id' => $cursor,
                'updated_at' => now(),
            ]);
        }

        $summary = DB::table('t_user_history')
            ->where('id', '>', $lastId)
            ->where('id', '<=', $cursor)
            ->whereNotIn('event_type', $excludedEvents)
            ->selectRaw('COUNT(*) as rows_count, COUNT(DISTINCT user_id) as users_count')
            ->first();

        $durationMs = round((microtime(true) - $startedAt) * 1000);
        $partial = $cursor < $currentMaxId;

        DB::table('t_recruitment_actions_sync')->update([
            'last_run_at' => now(),
            'last_run_rows' => (int) $summary->rows_count,
            'last_run_users' => (int) $summary->users_count,
            'last_run_duration_ms' => $durationMs,
        ]);

        return response()->json([
            'success' => true,
            'initialized' => false,
            'partial' => $partial,
            'message' => $partial
                ? "Sincronizzazione parziale: elaborate {$summary->rows_count} righe di attività, {$summary->users_count} utenti aggiornati. Clicca di nuovo per continuare con il resto."
                : "Sincronizzazione completata: {$summary->rows_count} righe di attività elaborate, {$summary->users_count} utenti aggiornati.",
            'rows_read' => (int) $summary->rows_count,
            'users_updated' => (int) $summary->users_count,
            'duration_ms' => $durationMs,
        ]);
    } finally {
        $lock->release();
    }
}

public function getPanelStats(Request $request)
{
    $anno = $request->input('anno', now()->year);

    $mesi = $this->buildPanelStatsByYear($anno);

    return response()->json([
        'success' => true,
        'mesi' => array_values($mesi),
    ]);
}

public function getActiveSummary()
{
    $cutoffDate = now()->subMonths(18);

    /*
     * Subquery: utenti con almeno un evento negli ultimi 18 mesi
     * DISTINCT evita duplicati per user_id
     */
    $recentActiveUsers = DB::table('t_user_history')
        ->select('user_id')
        ->where('event_date', '>=', $cutoffDate)
        ->distinct();

    /*
     * Una sola query:
     * - prende tutti gli utenti attivi/confermati
     * - marca con flag chi ha avuto attività negli ultimi 18 mesi
     */
    $users = DB::table('t_user_info as u')
        ->leftJoinSub($recentActiveUsers, 'ra', function ($join) {
            $join->on('u.user_id', '=', 'ra.user_id');
        })
        ->where('u.active', 1)
        ->where('u.confirm', 1)
        ->select([
            'u.gender',
            DB::raw('CASE WHEN ra.user_id IS NULL THEN 0 ELSE 1 END as is_active_18'),
        ])
        ->get();

    /*
     * Conteggi in collection
     */
    $totalePanel = $users->count();

    $totalePanelUomo = $users->where('gender', 1)->count();
    $totalePanelDonna = $users->where('gender', 2)->count();

    $totaleAttivi18Mesi = $users->where('is_active_18', 1)->count();

    $totaleAttivi18MesiUomo = $users
        ->where('gender', 1)
        ->where('is_active_18', 1)
        ->count();

    $totaleAttivi18MesiDonna = $users
        ->where('gender', 2)
        ->where('is_active_18', 1)
        ->count();

    $percentualeAttivi18Mesi = ($totalePanel > 0)
        ? round(($totaleAttivi18Mesi / $totalePanel) * 100, 1)
        : 0;

    $percentualeAttivi18MesiUomo = ($totalePanelUomo > 0)
        ? round(($totaleAttivi18MesiUomo / $totalePanelUomo) * 100, 1)
        : 0;

    $percentualeAttivi18MesiDonna = ($totalePanelDonna > 0)
        ? round(($totaleAttivi18MesiDonna / $totalePanelDonna) * 100, 1)
        : 0;

    return response()->json([
        'success' => true,
        'totalePanel' => $totalePanel,
        'totalePanelUomo' => $totalePanelUomo,
        'totalePanelDonna' => $totalePanelDonna,
        'totaleAttivi18Mesi' => $totaleAttivi18Mesi,
        'totaleAttivi18MesiUomo' => $totaleAttivi18MesiUomo,
        'totaleAttivi18MesiDonna' => $totaleAttivi18MesiDonna,
        'percentualeAttivi18Mesi' => $percentualeAttivi18Mesi,
        'percentualeAttivi18MesiUomo' => $percentualeAttivi18MesiUomo,
        'percentualeAttivi18MesiDonna' => $percentualeAttivi18MesiDonna,
    ]);
}


public function getData(Request $request)
{
    /*
     * Inviti già aggregati per utente
     */
    $invitesAgg = DB::table('t_user_invites')
        ->select(
            'user_id',
            DB::raw('COALESCE(invites, 0) as invites')
        );

    /*
     * Query principale super leggera:
     * niente history, niente last_event_date, niente activity_count
     */
    $query = DB::table('t_user_info as u')
        ->where('u.confirm', 1)
        ->leftJoinSub($invitesAgg, 'inv', function ($join) {
            $join->on('u.user_id', '=', 'inv.user_id');
        })
        ->select([
            'u.user_id',
            'u.email',
            'u.birth_date',
            'u.reg_date',
            'u.active',
            DB::raw('COALESCE(inv.invites, 0) as invites'),
        ]);

    return DataTables::of($query)
        ->filterColumn('user_id', function ($query, $keyword) {
            $query->where('u.user_id', 'like', "%{$keyword}%");
        })
        ->filterColumn('email', function ($query, $keyword) {
            $query->where('u.email', 'like', "%{$keyword}%");
        })
        ->editColumn('user_id', function ($row) {
            $uid = e($row->user_id);
            $url = route('user.profile', ['user_id' => $row->user_id]);

            return '<a target="_blank" href="' . $url . '"
                        class="fw-bold text-primary text-decoration-none d-inline-flex align-items-center gap-1"
                        style="white-space: nowrap;">
                        <i class="bi bi-person-fill"></i><span>' . $uid . '</span>
                    </a>';
        })
        ->editColumn('email', function ($row) {
            $email = e($row->email);
            $url = route('user.profile', ['user_id' => $row->user_id]);

            return '<a target="_blank" href="' . $url . '"
                        class="text-dark text-decoration-none"
                        style="white-space: nowrap;">
                        ' . $email . '
                    </a>';
        })
        ->editColumn('birth_date', function ($row) {
            if (empty($row->birth_date)) {
                return 'N.D.';
            }

            try {
                return Carbon::parse($row->birth_date)->age;
            } catch (\Exception $e) {
                return 'N.D.';
            }
        })
        ->editColumn('reg_date', function ($row) {
            if (empty($row->reg_date)) {
                return 'N.D.';
            }

            try {
                $regDate = Carbon::parse($row->reg_date);
                $now = now();

                $months = $regDate->diffInMonths($now);
                $years = $regDate->diffInYears($now);

                if ($months < 12) {
                    return $months . ' mesi';
                }

                return $years . ' anni';
            } catch (\Exception $e) {
                return 'N.D.';
            }
        })
        ->addColumn('stato', function ($row) {
            $class = $this->getUserStatoBadgeClass($row->active);
            $label = e($this->getUserStatoLabel($row->active));

            return '<span class="pu-status-badge ' . $class . '">' . $label . '</span>';
        })
        ->rawColumns(['user_id', 'email', 'stato'])
        ->make(true);
}

private function getUserStatoLabel($active): string
{
    switch ((int) $active) {
        case 1:
            return 'Attivo';
        case 0:
            return 'Non attivo';
        case 8:
            return 'Bannato';
        case 9:
            return 'Cancellato';
        default:
            return 'Sconosciuto';
    }
}

private function getUserStatoBadgeClass($active): string
{
    switch ((int) $active) {
        case 1:
            return 'pu-status-active';
        case 0:
            return 'pu-status-inactive';
        case 8:
            return 'pu-status-banned';
        case 9:
            return 'pu-status-cancelled';
        default:
            return 'pu-status-other';
    }
}


private function buildPanelStatsByYear($annoSelezionato)
{
    /*
     * 1) Ricerche + IR medio + Contatti da t_panel_control
     */
    $panelStats = DB::table('t_panel_control')
        ->selectRaw("
            MONTH(sur_date) as mese,
            COUNT(*) as ricerche,
            ROUND(AVG(red_panel),1) as ir_medio,
            ROUND(AVG(contatti),0) as contatti
        ")
        ->whereYear('sur_date', $annoSelezionato)
        ->groupByRaw("MONTH(sur_date)")
        ->get()
        ->keyBy('mese');

    /*
     * 2) Attivi (utenti unici con almeno un evento nel mese)
     */
    $attiviStats = DB::table('t_user_history')
        ->selectRaw("
            MONTH(event_date) as mese,
            COUNT(DISTINCT user_id) as attivi
        ")
        ->whereYear('event_date', $annoSelezionato)
        ->groupByRaw("MONTH(event_date)")
        ->get()
        ->keyBy('mese');

    /*
     * 3) Registrati
     */
    $registratiStats = DB::table('t_user_info')
        ->selectRaw("
            MONTH(reg_date) as mese,
            COUNT(*) as registrati
        ")
        ->whereYear('reg_date', $annoSelezionato)
        ->groupByRaw("MONTH(reg_date)")
        ->get()
        ->keyBy('mese');

    /*
     * 4) Merge dati per 12 mesi
     */
    $mesi = [];

    for ($m = 1; $m <= 12; $m++) {
        $mesi[$m] = [
            'mese' => $m,
            'mese_nome' => ucfirst(\Carbon\Carbon::create()->locale('it')->month($m)->translatedFormat('F')),
            'ricerche' => $panelStats[$m]->ricerche ?? 0,
            'ir_medio' => $panelStats[$m]->ir_medio ?? 0,
            'contatti' => $panelStats[$m]->contatti ?? 0,
            'attivi' => $attiviStats[$m]->attivi ?? 0,
            'registrati' => $registratiStats[$m]->registrati ?? 0,
        ];
    }

    return $mesi;
}

private function buildUserSearchQuery(array $values, string $mode)
{
    $query = DB::table('t_user_info')
        ->select([
            'user_id',
            'email',
            'first_name',
            'birth_date',
            'province_id',
            'reg',
            'area',
            'active',
        ]);

    if ($mode === 'email') {
        $query->whereIn('email', $values);
    } else {
        $query->whereIn('user_id', $values);
    }

    return $query;
}

public function searchPreview(Request $request)
{
    $request->validate([
        'mode' => 'required|in:uid,email',
        'values' => 'required|string',
        'fields' => 'nullable|array',
        'fields.*' => 'in:nome,eta,provincia,regione,area,stato',
    ]);

    $mode = $request->input('mode');
    $fields = $request->input('fields', []);

        $decodeLocation = $request->input('decode_location') == 1;

        $provinceMap = $decodeLocation ? $this->getProvinceMap() : [];
        $regionMap = $decodeLocation ? $this->getRegionMap() : [];
        $areaMap = $decodeLocation ? $this->getAreaMap() : [];

    // valori puliti, uno per riga
    $values = preg_split('/\r\n|\r|\n/', $request->input('values'));
    $values = array_map('trim', $values);
    $values = array_filter($values, function ($v) {
        return $v !== '';
    });
    $values = array_values(array_unique($values));

    if (empty($values)) {
        return response()->json([
            'success' => true,
            'columns' => [],
            'rows' => [],
            'count' => 0,
        ]);
    }

    $users = $this->buildUserSearchQuery($values, $mode)->get();

$rows = $users->map(function ($user) use ($fields, $decodeLocation, $provinceMap, $regionMap, $areaMap) {
        $row = [
            'UID' => $user->user_id,
            'Email' => $user->email,
        ];

        if (in_array('nome', $fields)) {
            $row['Nome'] = $user->first_name ?? '';
        }

        if (in_array('eta', $fields)) {
            $eta = 'N.D.';
            if (!empty($user->birth_date)) {
                try {
                    $eta = Carbon::parse($user->birth_date)->age;
                } catch (\Exception $e) {
                    $eta = 'N.D.';
                }
            }
            $row['Età'] = $eta;
        }

        if (in_array('provincia', $fields)) {
            if ($decodeLocation) {
                $row['Provincia'] = $provinceMap[$user->province_id] ?? 'N.D.';
            } else {
                $row['Provincia'] = $user->province_id ?? '';
            }
        }

        if (in_array('regione', $fields)) {
            if ($decodeLocation) {
                $row['Regione'] = $regionMap[$user->reg] ?? 'N.D.';
            } else {
                $row['Regione'] = $user->reg ?? '';
            }
        }

if (in_array('area', $fields)) {
    if ($decodeLocation) {
        $row['Area'] = $areaMap[$user->area] ?? 'N.D.';
    } else {
        $row['Area'] = $user->area ?? '';
    }
}

        if (in_array('stato', $fields)) {
            $row['Stato'] = $this->getUserStatoLabel($user->active);
        }

        return $row;
    })->values();

    $columns = !empty($rows) ? array_keys($rows[0]) : ['UID', 'Email'];

    return response()->json([
        'success' => true,
        'columns' => $columns,
        'rows' => $rows,
        'count' => count($rows),
    ]);
}

public function searchDownload(Request $request)
{
    $request->validate([
        'mode' => 'required|in:uid,email',
        'values' => 'required|string',
        'fields' => 'nullable|array',
        'fields.*' => 'in:nome,eta,provincia,regione,area,stato',
    ]);

    $mode = $request->input('mode');
    $fields = $request->input('fields', []);

    $decodeLocation = $request->input('decode_location') == 1;
    $provinceMap = $decodeLocation ? $this->getProvinceMap() : [];
    $regionMap = $decodeLocation ? $this->getRegionMap() : [];
    $areaMap = $decodeLocation ? $this->getAreaMap() : [];

    $values = preg_split('/\r\n|\r|\n/', $request->input('values'));
    $values = array_map('trim', $values);
    $values = array_filter($values, function ($v) {
        return $v !== '';
    });
    $values = array_values(array_unique($values));

    $users = collect();
    if (!empty($values)) {
        $users = $this->buildUserSearchQuery($values, $mode)->get();
    }

    $fileName = 'panel_users_search_' . now()->format('Ymd_His') . '.csv';

    $response = new StreamedResponse(function () use ($users, $fields, $decodeLocation, $provinceMap, $regionMap, $areaMap) {
  $handle = fopen('php://output', 'w');

    $headers = ['UID', 'Email'];

    if (in_array('nome', $fields)) $headers[] = 'Nome';
    if (in_array('eta', $fields)) $headers[] = 'Età';
    if (in_array('provincia', $fields)) $headers[] = 'Provincia';
    if (in_array('regione', $fields)) $headers[] = 'Regione';
    if (in_array('area', $fields)) $headers[] = 'Area';
    if (in_array('stato', $fields)) $headers[] = 'Stato';

    fputcsv($handle, $headers, ';');

    foreach ($users as $user) {

        $row = [
            $user->user_id,
            $user->email,
        ];

        if (in_array('nome', $fields)) {
            $row[] = $user->first_name ?? '';
        }

        if (in_array('eta', $fields)) {
            $eta = 'N.D.';
            if (!empty($user->birth_date)) {
                try {
                    $eta = \Carbon\Carbon::parse($user->birth_date)->age;
                } catch (\Exception $e) {}
            }
            $row[] = $eta;
        }

        if (in_array('provincia', $fields)) {
            $row[] = $decodeLocation
                ? ($provinceMap[$user->province_id] ?? 'N.D.')
                : ($user->province_id ?? '');
        }

        if (in_array('regione', $fields)) {
            $row[] = $decodeLocation
                ? ($regionMap[$user->reg] ?? 'N.D.')
                : ($user->reg ?? '');
        }

        if (in_array('area', $fields)) {
            $row[] = $decodeLocation
                ? ($areaMap[$user->area] ?? 'N.D.')
                : ($user->area ?? '');
        }

        if (in_array('stato', $fields)) {
            $row[] = $this->getUserStatoLabel($user->active);
        }

        fputcsv($handle, $row, ';');
    }

    fclose($handle);
});

    $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
    $response->headers->set('Content-Disposition', 'attachment; filename="' . $fileName . '"');

    return $response;
}

private function getProvinceMap()
{
    return [
        1 => 'Alessandria',
        2 => 'Crotone',
        3 => 'Aosta',
        4 => 'Arezzo',
        5 => 'Ascoli',
        6 => 'Piceno',
        7 => 'Asti',
        8 => 'Avellino',
        9 => 'Bari',
        10 => 'Belluno',
        11 => 'Benevento',
        12 => 'Bergamo',
        13 => 'Biella',
        14 => 'Bologna',
        15 => 'Bolzano',
        16 => 'Brescia',
        17 => 'Brindisi',
        18 => 'Cagliari',
        19 => 'Caltanissetta',
        20 => 'Campobasso',
        21 => 'Caserta',
        22 => 'Catania',
        23 => 'Catanzaro',
        24 => 'Chieti',
        25 => 'Como',
        26 => 'Cosenza',
        27 => 'Cremona',
        29 => 'Cuneo',
        30 => 'Enna',
        31 => 'Ferrara',
        32 => 'Firenze',
        33 => 'Foggia',
        34 => "Forli'",
        35 => 'Frosinone',
        36 => 'Genova',
        37 => 'Gorizia',
        38 => 'Grosseto',
        39 => 'Imperia Isernia',
        40 => "L'Aquila",
        41 => 'La Spezia',
        42 => 'Latina',
        43 => 'Lecce',
        44 => 'Lecco',
        45 => 'Livorno',
        46 => 'Lodi',
        47 => 'Lucca',
        48 => 'Macerata',
        49 => 'Mantova',
        50 => 'Massa Carrara',
        51 => 'Matera',
        52 => 'Messina',
        53 => 'Milano',
        54 => 'Modena',
        55 => 'Napoli',
        56 => 'Novara',
        57 => 'Nuoro',
        58 => 'Oristano',
        59 => 'Padova',
        60 => 'Palermo',
        61 => 'Parma',
        62 => 'Pavia',
        63 => 'Perugia',
        64 => 'Pesaro e Urbino',
        65 => 'Pescara',
        66 => 'Piacenza',
        67 => 'Pisa',
        68 => 'Pistoia',
        69 => 'Pordenone',
        70 => 'Potenza',
        71 => 'Prato',
        72 => 'Ragusa',
        73 => 'Ravenna',
        74 => 'Reggio',
        75 => 'Calabria',
        76 => 'Reggio Emilia',
        77 => 'Rieti',
        78 => 'Rimini',
        79 => 'Roma',
        80 => 'Rovigo',
        81 => 'Salerno',
        82 => 'Sassari',
        83 => 'Savona',
        84 => 'Siena',
        85 => 'Siracusa',
        86 => 'Sondrio',
        87 => 'Taranto',
        88 => 'Teramo',
        89 => 'Terni',
        90 => 'Torino',
        91 => 'Trapani',
        92 => 'Trento',
        93 => 'Treviso',
        94 => 'Trieste',
        95 => 'Udine',
        96 => 'Varese',
        97 => 'Venezia',
        98 => 'Verbano-Cusio-Os',
        99 => 'Vercelli',
        100 => 'Verona',
        101 => 'Vibo Valentia',
        102 => 'Vicenza',
        103 => 'Viterbo',
        104 => 'Altro',
        105 => 'Fermo',
        106 => 'Barletta Andria Trani',
    ];
}

private function getRegionMap()
{
    return [
        1 => 'Abruzzo', 2 => 'Basilicata', 3 => 'Calabria', 4 => 'Campania',
        5 => 'Emilia-Romagna', 6 => 'Friuli-Venezia Giulia', 7 => 'Lazio',
        8 => 'Liguria', 9 => 'Lombardia', 10 => 'Marche',
        11 => 'Molise', 12 => 'Piemonte', 13 => 'Puglia',
        14 => 'Sardegna', 15 => 'Sicilia', 16 => 'Toscana',
        17 => 'Trentino-Alto Adige', 18 => 'Umbria',
        19 => "Valle d'Aosta", 20 => 'Veneto'
    ];
}

private function getAreaMap()
{
    return [
        1 => 'Nord-Ovest',
        2 => 'Nord-Est',
        3 => 'Centro',
        4 => 'Sud + Isole',
    ];
}

private function formatInactivityLabelFromDate($lastDate)
{
    if (!$lastDate) {
        return 'N.D.';
    }

    try {
        $date = \Carbon\Carbon::parse($lastDate);
        $months = $date->diffInMonths(now());
        $years = $date->diffInYears(now());

        if ($months < 12) {
            return $months . ' mesi';
        }

        return $years . ' anni';
    } catch (\Exception $e) {
        return 'N.D.';
    }
}


public function getInactiveSummary(Request $request)
{
    $years = (int) $request->input('years', 3);

    if (!in_array($years, [1, 2, 3])) {
        $years = 3;
    }

    $cutoffDate = now()->subYears($years);

    /*
     * Subquery aggregata history: solo ultima azione (serve una data, quindi
     * resta su t_user_history). Il conteggio azioni (Inattivo 0 vs Abandoner >0,
     * nessun arco temporale) viene letto direttamente da u.actions.
     */
    $historyStats = DB::table('t_user_history')
        ->select(
            'user_id',
            DB::raw('MAX(event_date) as last_event_date')
        )
        ->groupBy('user_id');

    /*
     * Una sola query:
     * - tutti gli utenti attivi/confermati
     * - join con history aggregata
     * - campi minimi necessari
     */
    $users = DB::table('t_user_info as u')
        ->leftJoinSub($historyStats, 'h', function ($join) {
            $join->on('u.user_id', '=', 'h.user_id');
        })
        ->where('u.active', 1)
        ->where('u.confirm', 1)
        ->select([
            'u.reg_date',
            DB::raw('COALESCE(u.actions, 0) as actions_count'),
            'h.last_event_date',
        ])
        ->get()
        ->map(function ($row) {
            $row->actions_count = (int) $row->actions_count;
            return $row;
        });

    /*
     * Totale utenti attivi/confermati
     */
    $totalActives = $users->count();

    /*
     * Filtra solo quelli che rientrano nella definizione di inattività
     */
    $inactiveUsers = $users->filter(function ($user) use ($cutoffDate) {
        // Caso A: nessuna azione, ma registrato prima della soglia
        if (empty($user->last_event_date)) {
            if (empty($user->reg_date)) {
                return false;
            }

            try {
                return Carbon::parse($user->reg_date)->lte($cutoffDate);
            } catch (\Exception $e) {
                return false;
            }
        }

        // Caso B: ultima azione più vecchia della soglia
        try {
            return Carbon::parse($user->last_event_date)->lte($cutoffDate);
        } catch (\Exception $e) {
            return false;
        }
    });

    /*
     * Split inattivi / abandoners in collection
     */
    $inactiveCount = $inactiveUsers->where('actions_count', 0)->count();
    $abandonersCount = $inactiveUsers->where('actions_count', '>', 0)->count();

    $totalInactive = $inactiveCount + $abandonersCount;

    $inactivePercent = ($totalActives > 0)
        ? round(($totalInactive / $totalActives) * 100, 1)
        : 0;

    return response()->json([
        'success' => true,
        'years' => $years,
        'totalActives' => $totalActives,
        'totalInactive' => $totalInactive,
        'inactiveCount' => $inactiveCount,
        'abandonersCount' => $abandonersCount,
        'inactivePercent' => $inactivePercent,
    ]);
}

public function getInactiveList(Request $request)
{
    $years = (int) $request->input('years', 3);
    $type = $request->input('type', 'inactive');

    $rows = $this->buildInactiveUsersCollection($years, $type);

    return response()->json([
        'success' => true,
        'type' => $type,
        'rows' => $rows,
        'count' => $rows->count(),
    ]);
}

private function buildInactiveUsersCollection(int $years, string $type)
{
    if (!in_array($years, [1, 2, 3])) {
        $years = 3;
    }

    if (!in_array($type, ['inactive', 'abandoner'])) {
        $type = 'inactive';
    }

    $cutoffDate = now()->subYears($years);

    // Serve solo l'ultima data di azione (arco temporale per la soglia di inattivita'):
    // il conteggio azioni per Inattivo/Abandoner viene letto da u.actions, nessuna data.
    $historyStats = DB::table('t_user_history')
        ->select(
            'user_id',
            DB::raw('MAX(event_date) as last_event_date')
        )
        ->groupBy('user_id');

    $users = DB::table('t_user_info as u')
        ->leftJoinSub($historyStats, 'h', function ($join) {
            $join->on('u.user_id', '=', 'h.user_id');
        })
        ->where('u.active', 1)
        ->where('u.confirm', 1)
        ->where(function ($query) use ($cutoffDate) {
            $query
                ->where(function ($q) use ($cutoffDate) {
                    $q->whereNull('h.last_event_date')
                      ->whereDate('u.reg_date', '<=', $cutoffDate);
                })
                ->orWhere(function ($q) use ($cutoffDate) {
                    $q->whereNotNull('h.last_event_date')
                      ->whereDate('h.last_event_date', '<=', $cutoffDate);
                });
        })
            ->select([
                'u.user_id',
                'u.email',
                'u.reg_date',
                'u.actions',
                'u.points',
                'u.provenienza',
                DB::raw('COALESCE(u.actions, 0) as actions_count'),
                'h.last_event_date',
            ])
        ->get();

    $rows = collect();

    foreach ($users as $user) {
        $actions = (int) $user->actions_count;
        $userType = ($actions === 0) ? 'Inattivo' : 'Abandoner';

        if ($type === 'inactive' && $actions !== 0) {
            continue;
        }

        if ($type === 'abandoner' && $actions === 0) {
            continue;
        }

        $referenceDate = $user->last_event_date ?: $user->reg_date;

        $inactivityLabel = 'N.D.';
        if (!empty($referenceDate)) {
            try {
                $date = \Carbon\Carbon::parse($referenceDate);
                $months = $date->diffInMonths(now());
                $yearsDiff = $date->diffInYears(now());

                if ($months < 12) {
                    $inactivityLabel = $months . ' mesi';
                } else {
                    $inactivityLabel = $yearsDiff . ' anni';
                }
            } catch (\Exception $e) {
                $inactivityLabel = 'N.D.';
            }
        }

            $rows->push([
                'uid' => $user->user_id,
                'email' => $user->email,
                'actions' => $user->actions ?? 0,
                'points' => $user->points ?? 0,
                'provenienza' => $user->provenienza ?? 'N.D.',
                'tipo' => $userType,
                'inattivita' => $inactivityLabel,
                'ultima_azione' => !empty($user->last_event_date)
                    ? \Carbon\Carbon::parse($user->last_event_date)->format('d/m/Y')
                    : 'Nessuna',
            ]);
    }

    return $rows->values();
}

public function downloadInactiveList(Request $request)
{
    $years = (int) $request->input('years', 3);
    $type = $request->input('type', 'inactive');

    $rows = $this->buildInactiveUsersCollection($years, $type);

    $fileName = 'panel_users_' . $type . '_' . now()->format('Ymd_His') . '.csv';

    $response = new \Symfony\Component\HttpFoundation\StreamedResponse(function () use ($rows) {
        $handle = fopen('php://output', 'w');

        fwrite($handle, "uid;email;actions;points;prov;type;periodo;lastAction\n");

        foreach ($rows as $row) {
            $line = [
                $row['uid'],
                $row['email'],
                $row['actions'],
                $row['points'],
                $row['provenienza'],
                $row['tipo'],
                $row['inattivita'],
                $row['ultima_azione'],
            ];

            // pulizia semplice per evitare ; e ritorni a capo nel CSV
            $line = array_map(function ($value) {
                $value = (string) $value;
                $value = str_replace(["\r", "\n", ";"], [' ', ' ', ','], $value);
                return trim($value);
            }, $line);

            fwrite($handle, implode(';', $line) . "\n");
        }

        fclose($handle);
    });

    $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
    $response->headers->set('Content-Disposition', 'attachment; filename="' . $fileName . '"');

    return $response;
}

public function disableInactiveUsers(Request $request)
{
    $years = (int) $request->input('years', 3);
    $type = $request->input('type', 'inactive');

    $rows = $this->buildInactiveUsersCollection($years, $type);

    $uids = $rows->pluck('uid')->filter()->values()->all();

    if (empty($uids)) {
        return response()->json([
            'success' => true,
            'updated' => 0,
        ]);
    }

    $updated = DB::table('t_user_info')
        ->whereIn('user_id', $uids)
        ->update([
            'active' => 0,
            'confirm' => 0,
            'datapriv_agreement' => 2,
        ]);

    return response()->json([
        'success' => true,
        'updated' => $updated,
    ]);
}

/*
 * Centro Controllo — verifiche automatiche sugli iscritti
 */

private function unconfirmedActiveZeroQuery()
{
    $since = now()->subMonths(6)->format('Y-m-d');

    return DB::table('t_user_info')
        ->where('active', 0)
        ->where('reg_date', '>=', $since);
}

private function implausibleAgeQuery()
{
    return DB::table('t_user_info')
        ->where('active', 1)
        ->whereNotNull('birth_date')
        ->where('birth_date', '<>', '0000-00-00')
        ->whereRaw('(TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) < 14 OR TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) > 85)');
}

/**
 * Verifica reale (DNS) se un dominio puo' ricevere posta: record MX, con
 * fallback su A (alcuni mail server minori non pubblicano MX e usano A).
 * Risultato cachato 7 giorni per dominio per non rifare il lookup ad ogni richiesta.
 */
private function domainHasValidMx($domain)
{
    return Cache::remember('email_domain_mx_' . $domain, now()->addDays(7), function () use ($domain) {
        return checkdnsrr($domain, 'MX') || checkdnsrr($domain, 'A');
    });
}

/**
 * Soglia oltre la quale un dominio e' considerato "diffuso" nel panel (quindi
 * affidabile di per se', indipendentemente dal DNS) per il controllo di similitudine.
 */
const EMAIL_DOMAIN_POPULAR_THRESHOLD = 30;

/**
 * Domini reali ma rari nel panel, scartati manualmente dal controllo di
 * similitudine perche' verificati come falsi positivi: pur essendo testualmente
 * vicini a un dominio popolare, sono servizi email esistenti e legittimi.
 */
const EMAIL_DOMAIN_SIMILARITY_EXCEPTIONS = [
    'mac.com', 'mail.com', 'mozmail.com', 'yopmail.com',
];

/**
 * Domini email del panel da segnalare, per due motivi indipendenti:
 *
 * 1. Nessun MX/A valido: oggettivamente incapaci di ricevere posta.
 * 2. Dominio raro (pochi utenti) molto simile a un dominio molto diffuso nel
 *    panel: tipico dei domini "typosquattati" (es. gnail.com) che i truffatori
 *    registrano apposta con un server di posta funzionante per intercettare i
 *    refusi di chi scrive male gmail.com - quindi passano il controllo DNS pur
 *    essendo quasi certamente un errore dell'utente.
 *
 * Il suggerimento di correzione usa sempre il dominio diffuso/valido piu' vicino
 * realmente presente nel database, mai una lista esterna.
 */
private function suspiciousEmailDomains()
{
    $domains = DB::table('t_user_info')
        ->where('active', 1)
        ->where('confirm', 1)
        ->whereNotNull('email')
        ->where('email', 'like', '%@%')
        ->selectRaw('LOWER(SUBSTRING_INDEX(email, "@", -1)) as domain, COUNT(*) as cnt')
        ->groupBy('domain')
        ->get();

    $counts = [];
    $validDomains = [];
    $noMxDomains = [];

    foreach ($domains as $row) {
        $domain = trim($row->domain);

        if ($domain === '' || strpos($domain, '.') === false) {
            continue;
        }

        $counts[$domain] = (int) $row->cnt;

        if ($this->domainHasValidMx($domain)) {
            $validDomains[] = $domain;
        } else {
            $noMxDomains[] = $domain;
        }
    }

    // Domini "diffusi": affidabili per il confronto di similitudine a prescindere dal DNS.
    $popularDomains = array_keys(array_filter($counts, function ($cnt) {
        return $cnt >= self::EMAIL_DOMAIN_POPULAR_THRESHOLD;
    }));

    $closestDomainAndDistance = function ($domain, array $candidates) {
        $bestMatch = null;
        $bestDistance = null;

        foreach ($candidates as $candidate) {
            if ($candidate === $domain) {
                continue;
            }

            $distance = levenshtein($domain, $candidate);

            if ($bestDistance === null || $distance < $bestDistance) {
                $bestDistance = $distance;
                $bestMatch = $candidate;
            }
        }

        return [$bestMatch, $bestDistance];
    };

    $suspicious = [];

    // 1. Nessun MX/A valido.
    foreach ($noMxDomains as $domain) {
        [$bestMatch, $bestDistance] = $closestDomainAndDistance($domain, $validDomains);

        $suspicious[$domain] = [
            'domain'    => $domain,
            'suggested' => ($bestDistance !== null && $bestDistance <= 3) ? $bestMatch : null,
            'count'     => $counts[$domain],
            // certo: nessun MX/A, il dominio non puo' ricevere posta.
            'severity'  => 'invalid',
        ];
    }

    // Nome prima del primo punto (es. "yahoo" da "yahoo.fr") dei domini popolari,
    // per riconoscere varianti nazionali legittime dello stesso provider (stesso
    // nome, TLD diverso: yahoo.fr/de/es, hotmail.de/es/fr... tutte con MX reale).
    $popularNames = array_unique(array_map(function ($d) {
        return strstr($d, '.', true);
    }, $popularDomains));

    // 2. Raro e molto simile a un dominio diffuso (typosquatting con DNS valido).
    foreach ($counts as $domain => $cnt) {
        if (isset($suspicious[$domain]) || in_array($domain, $popularDomains, true)) {
            continue;
        }

        $domainName = strstr($domain, '.', true);

        if (in_array($domainName, $popularNames, true) && in_array($domain, $validDomains, true)) {
            continue;
        }

        if (in_array($domain, self::EMAIL_DOMAIN_SIMILARITY_EXCEPTIONS, true)) {
            continue;
        }

        [$bestMatch, $bestDistance] = $closestDomainAndDistance($domain, $popularDomains);

        if ($bestDistance !== null && $bestDistance <= 2) {
            $suspicious[$domain] = [
                'domain'    => $domain,
                'suggested' => $bestMatch,
                'count'     => $cnt,
                // da verificare: il dominio ha DNS valido, potrebbe essere un provider
                // reale ma poco diffuso nel panel, non solo un refuso.
                'severity'  => 'suspect',
            ];
        }
    }

    return $suspicious;
}

public function getAnomaliesSummary()
{
    $suspiciousDomains = $this->suspiciousEmailDomains();

    return response()->json([
        'success' => true,
        'checks' => [
            'unconfirmed'       => $this->unconfirmedActiveZeroQuery()->count(),
            'implausible_age'   => $this->implausibleAgeQuery()->count(),
            'suspicious_email'  => array_sum(array_column($suspiciousDomains, 'count')),
        ],
    ]);
}

public function getUnconfirmedList()
{
    $rows = $this->unconfirmedActiveZeroQuery()
        ->select('user_id', 'email', 'reg_date', 'confirm')
        ->orderBy('reg_date', 'desc')
        ->limit(2000)
        ->get();

    return response()->json([
        'success' => true,
        'count' => $rows->count(),
        'rows' => $rows,
    ]);
}

public function getImplausibleAgeList()
{
    $rows = $this->implausibleAgeQuery()
        ->select('user_id', 'email', 'birth_date')
        ->orderBy('birth_date', 'asc')
        ->limit(2000)
        ->get()
        ->map(function ($row) {
            try {
                $row->age = Carbon::parse($row->birth_date)->age;
            } catch (\Exception $e) {
                $row->age = null;
            }

            return $row;
        });

    return response()->json([
        'success' => true,
        'count' => $rows->count(),
        'rows' => $rows,
    ]);
}

public function getSuspiciousEmailList()
{
    $suspiciousDomains = $this->suspiciousEmailDomains();

    if (empty($suspiciousDomains)) {
        return response()->json(['success' => true, 'count' => 0, 'rows' => []]);
    }

    $rows = DB::table('t_user_info')
        ->where('active', 1)
        ->where('confirm', 1)
        ->whereNotNull('email')
        ->where('email', 'like', '%@%')
        ->whereIn(DB::raw('LOWER(SUBSTRING_INDEX(email, "@", -1))'), array_keys($suspiciousDomains))
        ->select('user_id', 'email')
        ->orderBy('email')
        ->limit(2000)
        ->get()
        ->map(function ($row) use ($suspiciousDomains) {
            $domain = strtolower(substr(strrchr($row->email, '@'), 1));
            $local = substr($row->email, 0, strrpos($row->email, '@'));
            $suggested = $suspiciousDomains[$domain]['suggested'] ?? null;

            $row->domain = $domain;
            $row->suggested_email = $suggested ? ($local . '@' . $suggested) : $row->email;
            $row->severity = $suspiciousDomains[$domain]['severity'] ?? 'suspect';

            return $row;
        })
        ->sortBy(function ($row) {
            return $row->severity === 'invalid' ? 0 : 1;
        })
        ->values();

    return response()->json([
        'success' => true,
        'count' => $rows->count(),
        'rows' => $rows,
    ]);
}

public function updateEmail(Request $request, $user_id)
{
    $validated = $request->validate([
        'email' => 'required|email|max:255',
    ]);

    try {
        $user = DB::table('t_user_info')->where('user_id', $user_id)->first();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Utente non trovato.'], 404);
        }

        $newEmail = strtolower(trim($validated['email']));

        $exists = DB::table('t_user_info')
            ->where('email', $newEmail)
            ->where('user_id', '<>', $user_id)
            ->exists();

        if ($exists) {
            return response()->json(['success' => false, 'message' => 'Email gia\' in uso da un altro utente.'], 422);
        }

        DB::table('t_user_info')
            ->where('user_id', $user_id)
            ->update(['email' => $newEmail]);

        DB::table('t_user_history')->insert([
            'user_id'    => $user_id,
            'event_date' => now(),
            'event_type' => 'CORREZIONE EMAIL',
            'event_info' => 'Email corretta da ' . $user->email . ' a ' . $newEmail . ' (controllo dominio sospetto)',
            'prev_level' => $user->points ?? 0,
            'new_level'  => $user->points ?? 0,
        ]);

        return response()->json(['success' => true, 'message' => 'Email aggiornata correttamente.']);
    } catch (\Exception $e) {
        Log::error('Errore aggiornamento email: ' . $e->getMessage());

        return response()->json(['success' => false, 'message' => 'Errore durante il salvataggio.'], 500);
    }
}

public function updateBirthDate(Request $request, $user_id)
{
    $validated = $request->validate([
        'birth_date' => 'required|date|before:today',
    ]);

    try {
        $user = DB::table('t_user_info')->where('user_id', $user_id)->first();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Utente non trovato.'], 404);
        }

        DB::table('t_user_info')
            ->where('user_id', $user_id)
            ->update(['birth_date' => $validated['birth_date']]);

        DB::table('t_user_history')->insert([
            'user_id'    => $user_id,
            'event_date' => now(),
            'event_type' => 'MODIFICA DATA NASCITA',
            'event_info' => 'Data di nascita corretta da ' . ($user->birth_date ?? 'N.D.') . ' a ' . $validated['birth_date'] . ' (controllo età poco credibile)',
            'prev_level' => $user->points ?? 0,
            'new_level'  => $user->points ?? 0,
        ]);

        return response()->json(['success' => true, 'message' => 'Data di nascita aggiornata correttamente.']);
    } catch (\Exception $e) {
        Log::error('Errore aggiornamento data di nascita: ' . $e->getMessage());

        return response()->json(['success' => false, 'message' => 'Errore durante il salvataggio.'], 500);
    }
}

}
