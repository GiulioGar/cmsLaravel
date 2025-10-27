<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PanelController extends Controller
{
    /**
     * Vista principale Gestione Utenti.
     */
    public function index()
    {
        return view('panelUsers');
    }


public function getData(Request $request)
{
    $start  = (int) $request->input('start', 0);
    $length = (int) $request->input('length', 50);
    $search = trim($request->input('search.value', ''));

    // ===============================
    // Subquery aggregata attività cache
    // ===============================
$cacheSub = DB::table('t_user_activity_cache')
    ->select(
        'uid',
        'inviti',
        DB::raw('click AS attivita'),
        DB::raw('ROUND(CASE WHEN inviti > 0 THEN (click / inviti * 100) ELSE 0 END) AS partecipazione')
    );

    // ===============================
    // Subquery ultima attività da t_user_history
    // ===============================
    $historySub = DB::table('t_user_history')
        ->select('user_id', DB::raw('MAX(event_date) AS last_event'))
        ->groupBy('user_id');

    // ===============================
    // Query principale
    // ===============================
    $query = DB::table('t_user_info as u')
        ->leftJoinSub($cacheSub, 'c', function ($join) {
            $join->on('c.uid', '=', 'u.user_id');
        })
        ->leftJoinSub($historySub, 'h', function ($join) {
            $join->on('h.user_id', '=', 'u.user_id');
        })
        ->select(
            'u.user_id',
            'u.email',
            'u.birth_date',
            'u.reg_date',
            DB::raw('COALESCE(c.inviti, 0) as inviti'),
            DB::raw('COALESCE(c.attivita, 0) as attivita'),
            DB::raw('COALESCE(c.partecipazione, 0) as partecipazione'),
            DB::raw('h.last_event as last_event')
        )
        ->where('u.active', 1);

    if ($search !== '') {
        $query->where(function ($q) use ($search) {
            $q->where('u.user_id', 'like', "%{$search}%")
              ->orWhere('u.email', 'like', "%{$search}%");
        });
    }

    $recordsTotal = DB::table('t_user_info')->where('active', 1)->count();
    $recordsFiltered = $query->count();

    $users = $query
        ->orderByDesc(DB::raw('COALESCE(c.attivita, 0)'))
        ->offset($start)
        ->limit($length)
        ->get();

    // ===============================
    // Calcoli aggiuntivi in PHP
    // ===============================
    $data = $users->map(function ($user) {
        $now = \Carbon\Carbon::now();

        // Età
        $user->eta = $user->birth_date
            ? \Carbon\Carbon::parse($user->birth_date)->age
            : null;

        // Email valida
        $user->email_valida = preg_match('/^[^@\s]+@[^@\s]+\.[^@\s]+$/', $user->email);

        // 🔹 Calcolo anzianità iscrizione
        $user->anzianita = null;
        if (!empty($user->reg_date)) {
            $reg = \Carbon\Carbon::parse($user->reg_date);
            $diffMesi = $reg->diffInMonths($now);
            if ($diffMesi <= 3) $user->anzianita = '0-3 mesi';
            elseif ($diffMesi <= 6) $user->anzianita = '3-6 mesi';
            elseif ($diffMesi <= 11) $user->anzianita = '6-11 mesi';
            elseif ($diffMesi <= 23) $user->anzianita = '1 anno';
            elseif ($diffMesi <= 35) $user->anzianita = '2 anni';
            elseif ($diffMesi <= 47) $user->anzianita = '3 anni';
            elseif ($diffMesi <= 59) $user->anzianita = '4-5 anni';
            elseif ($diffMesi <= 119) $user->anzianita = '6-9 anni';
            else $user->anzianita = '10 anni +';
        }

        // 🔹 Calcolo ultima attività
        $user->ultima_attivita = '-';
        if (!empty($user->last_event)) {
            $event = \Carbon\Carbon::parse($user->last_event);
            $diffGiorni = $event->diffInDays($now);
            $diffMesi = $event->diffInMonths($now);
            $diffAnni = $event->diffInYears($now);

            if ($diffGiorni < 31) {
                $user->ultima_attivita = $diffGiorni === 0 ? 'Oggi' : "{$diffGiorni} giorni fa";
            } elseif ($diffMesi < 12) {
                $user->ultima_attivita = "{$diffMesi} mesi fa";
            } else {
                $user->ultima_attivita = "{$diffAnni} anni fa";
            }
        }

        return $user;
    });

    // ===============================
    // Output per DataTables
    // ===============================
    return response()->json([
        'draw' => intval($request->input('draw')),
        'recordsTotal' => $recordsTotal,
        'recordsFiltered' => $recordsFiltered,
        'data' => $data,
    ]);
}




public function refreshActivityCache()
{
    try {
        // 1️⃣ Svuota la cache precedente
        DB::table('t_user_activity_cache')->truncate();

        // 2️⃣ Rigenera i dati aggregati
        DB::statement("
 INSERT INTO t_user_activity_cache (uid, inviti, click, complete_millebytes, sospese, non_target, updated_at)
            SELECT
                uid,
                COUNT(*) AS inviti,
                SUM(CASE WHEN iid != -1 THEN 1 ELSE 0 END) AS click,
                SUM(CASE WHEN status = 3 THEN 1 ELSE 0 END) AS complete_millebytes,
                SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) AS sospese,
                SUM(CASE WHEN status IN (4,5) THEN 1 ELSE 0 END) AS non_target,
                NOW()
            FROM t_respint
            GROUP BY uid
        ");

        // 3️⃣ Aggiorna anche il campo 'actions' in t_user_info per ciascun utente
        DB::statement("
            UPDATE t_user_info AS u
            INNER JOIN (
                SELECT
                    uid,
                    SUM(CASE WHEN iid != -1 THEN 1 ELSE 0 END) AS attivita
                FROM t_respint
                GROUP BY uid
            ) AS r ON r.uid = u.user_id
            SET u.actions = r.attivita
        ");

        return response()->json([
            'success' => true,
            'message' => 'Cache e campo "actions" aggiornati correttamente.'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Errore: ' . $e->getMessage()
        ]);
    }
}

public function getAnnualPanelInfo($anno)
{
    $anno = (int) $anno;
    $oggi = now();
    $mesi = [
        1 => 'Gennaio', 2 => 'Febbraio', 3 => 'Marzo', 4 => 'Aprile',
        5 => 'Maggio', 6 => 'Giugno', 7 => 'Luglio', 8 => 'Agosto',
        9 => 'Settembre', 10 => 'Ottobre', 11 => 'Novembre', 12 => 'Dicembre'
    ];

    $result = [];

    foreach ($mesi as $num => $nome) {
        if ($anno === $oggi->year && $num > $oggi->month) {
            continue; // non mostra mesi futuri
        }

        // Ricerche, IR medio, contatti
        $panelData = DB::table('t_panel_control')
            ->selectRaw('COUNT(*) as ricerche, AVG(red_panel) as ir_medio, SUM(contatti) as contatti')
            ->where('panel', 1)
            ->whereYear('sur_date', $anno)
            ->whereMonth('sur_date', $num)
            ->first();

        // Attivi (t_user_history)
        $attivi = DB::table('t_user_history')
            ->selectRaw('COUNT(DISTINCT CONCAT(user_id, "-", event_type)) as total')
            ->whereYear('event_date', $anno)
            ->whereMonth('event_date', $num)
            ->value('total');

        // Registrati (t_user_info)
        $registrati = DB::table('t_user_info')
            ->whereYear('reg_date', $anno)
            ->whereMonth('reg_date', $num)
            ->count();

        $result[] = [
            'mese' => $nome,
            'ricerche' => (int) ($panelData->ricerche ?? 0),
            'ir_medio' => $panelData->ir_medio ? round($panelData->ir_medio, 1) . '%' : '-',
            'contatti' => (int) ($panelData->contatti ?? 0),
            'attivi' => (int) ($attivi ?? 0),
            'registrati' => (int) $registrati,
        ];
    }

    return response()->json($result);
}

public function exportUsers(Request $request)
{
    $mode = $request->input('mode', 'uid'); // uid o email
    $rawInput = trim($request->input('values', ''));
    $fields = $request->input('fields', []); // array di campi selezionati

    if (empty($rawInput)) {
        return response()->json(['success' => false, 'message' => 'Nessun valore inserito.']);
    }

    $identifiers = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $rawInput)));

    // Query base
    $query = DB::table('t_user_info')
        ->select('user_id', 'email', 'first_name', 'second_name', 'birth_date', 'province_id', 'reg', 'area');

    if ($mode === 'email') {
        $query->whereIn('email', $identifiers);
    } else {
        $query->whereIn('user_id', $identifiers);
    }

    $users = $query->get();

    // Ricodifiche
    $provinceMap = $this->getProvinceMap();
    $regionMap = $this->getRegionMap();
    $areaMap = $this->getAreaMap();

    $rows = [];
    foreach ($users as $u) {
        $row = [
            'user_id' => $u->user_id,
            'email' => $u->email,
        ];

        if (in_array('nome', $fields)) {
            $row['nome'] = trim(($u->first_name ?? '') . ' ' . ($u->second_name ?? ''));
        }

        if (in_array('eta', $fields)) {
            $row['età'] = $u->birth_date ? \Carbon\Carbon::parse($u->birth_date)->age : '';
        }

        if (in_array('provincia', $fields)) {
            $row['provincia'] = $provinceMap[$u->province_id] ?? '';
        }

        if (in_array('regione', $fields)) {
            $row['regione'] = $regionMap[$u->reg] ?? '';
        }

        if (in_array('area', $fields)) {
            $row['area'] = $areaMap[$u->area] ?? '';
        }

        $rows[] = $row;
    }

    // Genera CSV temporaneo
    $filename = 'export_utenti_' . date('Ymd_His') . '.csv';
    $handle = fopen('php://temp', 'r+');
    fputcsv($handle, array_keys($rows[0] ?? []));

    foreach ($rows as $r) {
        fputcsv($handle, $r);
    }

    rewind($handle);
    $csv = stream_get_contents($handle);
    fclose($handle);

    return response($csv)
        ->header('Content-Type', 'text/csv')
        ->header('Content-Disposition', "attachment; filename=\"$filename\"");
}

// 🔹 Funzioni helper di ricodifica
private function getProvinceMap()
{
    return [
        1 => 'Alessandria', 2 => 'Crotone', 3 => 'Aosta', 4 => 'Arezzo', 5 => 'Ascoli Piceno', 6 => 'Piceno',
        7 => 'Asti', 8 => 'Avellino', 9 => 'Bari', 10 => 'Belluno', 11 => 'Benevento', 12 => 'Bergamo',
        13 => 'Biella', 14 => 'Bologna', 15 => 'Bolzano', 16 => 'Brescia', 17 => 'Brindisi', 18 => 'Cagliari',
        19 => 'Caltanissetta', 20 => 'Campobasso', 21 => 'Caserta', 22 => 'Catania', 23 => 'Catanzaro', 24 => 'Chieti',
        25 => 'Como', 26 => 'Cosenza', 27 => 'Cremona', 29 => 'Cuneo', 30 => 'Enna', 31 => 'Ferrara', 32 => 'Firenze',
        33 => 'Foggia', 34 => 'Forlì', 35 => 'Frosinone', 36 => 'Genova', 37 => 'Gorizia', 38 => 'Grosseto',
        39 => 'Imperia Isernia', 40 => "L'Aquila", 41 => 'La Spezia', 42 => 'Latina', 43 => 'Lecce', 44 => 'Lecco',
        45 => 'Livorno', 46 => 'Lodi', 47 => 'Lucca', 48 => 'Macerata', 49 => 'Mantova', 50 => 'Massa Carrara',
        51 => 'Matera', 52 => 'Messina', 53 => 'Milano', 54 => 'Modena', 55 => 'Napoli', 56 => 'Novara',
        57 => 'Nuoro', 58 => 'Oristano', 59 => 'Padova', 60 => 'Palermo', 61 => 'Parma', 62 => 'Pavia', 63 => 'Perugia',
        64 => 'Pesaro e Urbino', 65 => 'Pescara', 66 => 'Piacenza', 67 => 'Pisa', 68 => 'Pistoia', 69 => 'Pordenone',
        70 => 'Potenza', 71 => 'Prato', 72 => 'Ragusa', 73 => 'Ravenna', 74 => 'Reggio Calabria', 75 => 'Reggio Emilia',
        76 => 'Rieti', 77 => 'Rimini', 78 => 'Roma', 79 => 'Rovigo', 80 => 'Salerno', 81 => 'Sassari', 82 => 'Savona',
        83 => 'Siena', 84 => 'Siracusa', 85 => 'Sondrio', 86 => 'Taranto', 87 => 'Teramo', 88 => 'Terni',
        89 => 'Torino', 90 => 'Trapani', 91 => 'Trento', 92 => 'Treviso', 93 => 'Trieste', 94 => 'Udine',
        95 => 'Varese', 96 => 'Venezia', 97 => 'Verbano-Cusio-Ossola', 98 => 'Vercelli', 99 => 'Verona',
        100 => 'Vibo Valentia', 101 => 'Vicenza', 102 => 'Viterbo', 103 => 'Altro', 104 => 'Fermo', 105 => 'Barletta-Andria-Trani'
    ];
}

private function getRegionMap()
{
    return [
        1 => 'Abruzzo', 2 => 'Basilicata', 3 => 'Calabria', 4 => 'Campania', 5 => 'Emilia-Romagna',
        6 => 'Friuli-Venezia Giulia', 7 => 'Lazio', 8 => 'Liguria', 9 => 'Lombardia', 10 => 'Marche',
        11 => 'Molise', 12 => 'Piemonte', 13 => 'Puglia', 14 => 'Sardegna', 15 => 'Sicilia', 16 => 'Toscana',
        17 => 'Trentino-Alto Adige', 18 => 'Umbria', 19 => "Valle d'Aosta", 20 => 'Veneto'
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


}
