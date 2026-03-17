<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Carbon\Carbon;

class PanelUsersController extends Controller
{

    public function index()
    {
        $cutoffDate = now()->subMonths(18);

        /*
        * 1) Totale panel attivo/confermato
        */
        $totalePanel = DB::table('t_user_info')
            ->where('active', 1)
            ->where('confirm', 1)
            ->count();

        $totalePanelUomo = DB::table('t_user_info')
            ->where('active', 1)
            ->where('confirm', 1)
            ->where('gender', 1)
            ->count();

        $totalePanelDonna = DB::table('t_user_info')
            ->where('active', 1)
            ->where('confirm', 1)
            ->where('gender', 2)
            ->count();

        /*
        * 2) Utenti attivi ultimi 18 mesi
        */
        $totaleAttivi18Mesi = DB::table('t_user_info as u')
            ->where('u.active', 1)
            ->where('u.confirm', 1)
            ->whereExists(function ($query) use ($cutoffDate) {
                $query->select(DB::raw(1))
                    ->from('t_user_history as h')
                    ->whereColumn('h.user_id', 'u.user_id')
                    ->where('h.event_date', '>=', $cutoffDate);
            })
            ->count();

        $totaleAttivi18MesiUomo = DB::table('t_user_info as u')
            ->where('u.active', 1)
            ->where('u.confirm', 1)
            ->where('u.gender', 1)
            ->whereExists(function ($query) use ($cutoffDate) {
                $query->select(DB::raw(1))
                    ->from('t_user_history as h')
                    ->whereColumn('h.user_id', 'u.user_id')
                    ->where('h.event_date', '>=', $cutoffDate);
            })
            ->count();

        $totaleAttivi18MesiDonna = DB::table('t_user_info as u')
            ->where('u.active', 1)
            ->where('u.confirm', 1)
            ->where('u.gender', 2)
            ->whereExists(function ($query) use ($cutoffDate) {
                $query->select(DB::raw(1))
                    ->from('t_user_history as h')
                    ->whereColumn('h.user_id', 'u.user_id')
                    ->where('h.event_date', '>=', $cutoffDate);
            })
            ->count();

        /*
        * 3) Percentuali
        */
        $percentualeAttivi18Mesi = ($totalePanel > 0)
            ? round(($totaleAttivi18Mesi / $totalePanel) * 100, 1)
            : 0;

        $percentualeAttivi18MesiUomo = ($totalePanelUomo > 0)
            ? round(($totaleAttivi18MesiUomo / $totalePanelUomo) * 100, 1)
            : 0;

        $percentualeAttivi18MesiDonna = ($totalePanelDonna > 0)
            ? round(($totaleAttivi18MesiDonna / $totalePanelDonna) * 100, 1)
            : 0;

        $annoSelezionato = now()->year;
        $mesi = $this->buildPanelStatsByYear($annoSelezionato);

        $anniDisponibili = DB::table('t_panel_control')
            ->selectRaw("DISTINCT YEAR(sur_date) as anno")
            ->whereNotNull('sur_date')
            ->orderByDesc('anno')
            ->pluck('anno');

        return view('panelUsers', compact(
            'totalePanel',
            'totalePanelUomo',
            'totalePanelDonna',
            'totaleAttivi18Mesi',
            'totaleAttivi18MesiUomo',
            'totaleAttivi18MesiDonna',
            'percentualeAttivi18Mesi',
            'percentualeAttivi18MesiUomo',
            'percentualeAttivi18MesiDonna',
            'mesi',
            'annoSelezionato',
            'anniDisponibili'
        ));
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

    public function getData(Request $request)
    {
        /*
         * Subquery inviti
         */
        $invitesSub = DB::table('t_user_invites')
            ->select(
                'user_id',
                DB::raw('COALESCE(invites, 0) as invites')
            );

        /*
         * Subquery attività + partecipazione + ultima azione
         */
$historySub = DB::table('t_user_history')
    ->select(
        'user_id',
        DB::raw("
            SUM(
                CASE
                    WHEN event_type NOT IN ('subscribe', 'unsubscribe')
                    THEN 1
                    ELSE 0
                END
            ) as activity_count
        "),
        DB::raw("
            SUM(
                CASE
                    WHEN event_type IN (
                        'interview_quotafull',
                        'interview_complete',
                        'interview_screenout',
                        'interview_complete_cint'
                    )
                    THEN 1
                    ELSE 0
                END
            ) as interview_count
        "),
        DB::raw('MAX(id) as last_history_id'),
        DB::raw('MAX(event_date) as last_event_date')
    )
    ->groupBy('user_id');

        /*
         * Query principale
         */
$query = DB::table('t_user_info as u')
    ->where('u.confirm', 1)
    ->where('u.active', 1)
    ->leftJoinSub($invitesSub, 'inv', function ($join) {
        $join->on('u.user_id', '=', 'inv.user_id');
    })
    ->leftJoinSub($historySub, 'h', function ($join) {
        $join->on('u.user_id', '=', 'h.user_id');
    })
    ->select([
        'u.user_id',
        'u.email',
        'u.birth_date',
        'u.reg_date',
        DB::raw('COALESCE(inv.invites, 0) as invites'),
        DB::raw('COALESCE(h.activity_count, 0) as activity_count'),
        DB::raw('COALESCE(h.interview_count, 0) as interview_count'),
        DB::raw('h.last_event_date as last_event_date'),
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
            ->editColumn('activity_count', function ($row) {
                $count = (int) $row->activity_count;

                if ($count <= 0) {
                    return '<span class="pu-badge pu-badge-muted">0</span>';
                }

                if ($count < 5) {
                    return '<span class="pu-badge pu-badge-low">'.$count.'</span>';
                }

                if ($count < 20) {
                    return '<span class="pu-badge pu-badge-mid">'.$count.'</span>';
                }

                return '<span class="pu-badge pu-badge-high">'.$count.'</span>';
            })
            ->addColumn('partecipazione', function ($row) {
                $invites = (int) $row->invites;
                $interviews = (int) $row->interview_count;

                if ($invites <= 0) {
                    return '<span class="pu-badge pu-badge-muted">0%</span>';
                }

                $percentage = round(($interviews / $invites) * 100, 1);

                if ($percentage < 20) {
                    $class = 'pu-badge-danger';
                } elseif ($percentage < 50) {
                    $class = 'pu-badge-warn';
                } else {
                    $class = 'pu-badge-success';
                }

                return '<span class="pu-badge '.$class.'">'.$percentage.'%</span>';
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
            ->editColumn('last_event_date', function ($row) {
                if (empty($row->last_event_date)) {
                    return 'N.D.';
                }

                try {
                    return Carbon::parse($row->last_event_date)->format('d/m/Y');
                } catch (\Exception $e) {
                    return 'N.D.';
                }
            })
        ->rawColumns(['user_id', 'email', 'activity_count', 'partecipazione'])
        ->make(true);
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
        'fields.*' => 'in:nome,eta,provincia,regione,area',
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
        'fields.*' => 'in:nome,eta,provincia,regione,area',
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
        1 => 'Alessandria', 2 => 'Crotone', 3 => 'Aosta', 4 => 'Arezzo', 5 => 'Ascoli Piceno',
        // ... (lascia tutta la tua lista così com'è)
        105 => 'Barletta-Andria-Trani'
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
     * 1) Totale utenti attivi/confermati
     */
    $totalActives = DB::table('t_user_info')
        ->where('active', 1)
        ->where('confirm', 1)
        ->count();

    /*
     * 2) Subquery actions e ultima azione
     */
    $historyStats = DB::table('t_user_history')
        ->select(
            'user_id',
            DB::raw("
                SUM(
                    CASE
                        WHEN event_type NOT IN ('subscribe', 'unsubscribe')
                        THEN 1
                        ELSE 0
                    END
                ) as actions_count
            "),
            DB::raw('MAX(event_date) as last_event_date')
        )
        ->groupBy('user_id');

    /*
     * 3) Query utenti base
     */
    $users = DB::table('t_user_info as u')
        ->leftJoinSub($historyStats, 'h', function ($join) {
            $join->on('u.user_id', '=', 'h.user_id');
        })
        ->where('u.active', 1)
        ->where('u.confirm', 1)
        ->where(function ($query) use ($cutoffDate) {
            $query
                // Caso A: nessuna azione, ma registrato prima della soglia
                ->where(function ($q) use ($cutoffDate) {
                    $q->whereNull('h.last_event_date')
                      ->whereDate('u.reg_date', '<=', $cutoffDate);
                })
                // Caso B: ultima azione più vecchia della soglia
                ->orWhere(function ($q) use ($cutoffDate) {
                    $q->whereNotNull('h.last_event_date')
                      ->whereDate('h.last_event_date', '<=', $cutoffDate);
                });
        })
        ->select([
            'u.user_id',
            'u.email',
            'u.reg_date',
            DB::raw('COALESCE(h.actions_count, 0) as actions_count'),
            'h.last_event_date',
        ])
        ->get();

    $inactiveCount = 0;
    $abandonersCount = 0;

    foreach ($users as $user) {
        if ((int)$user->actions_count === 0) {
            $inactiveCount++;
        } else {
            $abandonersCount++;
        }
    }

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

    $historyStats = DB::table('t_user_history')
        ->select(
            'user_id',
            DB::raw("
                SUM(
                    CASE
                        WHEN event_type NOT IN ('subscribe', 'unsubscribe')
                        THEN 1
                        ELSE 0
                    END
                ) as actions_count
            "),
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
                DB::raw('COALESCE(h.actions_count, 0) as actions_count'),
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

}
