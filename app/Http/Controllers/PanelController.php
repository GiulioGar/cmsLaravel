<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;


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
    // QUERY BASE: t_user_info + t_user_activity
    // ===============================
    $query = DB::table('t_user_info as u')
        ->leftJoin('t_user_activity as a', 'a.uid', '=', 'u.user_id')
        ->select(
            'u.user_id',
            'u.email',
            'u.birth_date',
            'u.reg_date',
            DB::raw('IFNULL(a.invites_count, 0) AS inviti'),
            DB::raw('IFNULL(a.completes_count, 0) + IFNULL(a.screenouts_count, 0) + IFNULL(a.quotafull_count, 0) AS attivita'),
            DB::raw('CASE
                        WHEN IFNULL(a.invites_count,0) > 0
                        THEN ROUND(((IFNULL(a.completes_count,0) + IFNULL(a.screenouts_count,0) + IFNULL(a.quotafull_count,0)) / a.invites_count) * 100, 1)
                        ELSE 0
                     END AS partecipazione'),
            DB::raw('a.last_update AS ultima_attivita')
        )
        ->where('u.active', 1);

    // ===============================
    // FILTRO DI RICERCA
    // ===============================
    if ($search !== '') {
        $query->where(function ($q) use ($search) {
            $q->where('u.user_id', 'like', "%{$search}%")
              ->orWhere('u.email', 'like', "%{$search}%");
        });
    }

    // ===============================
    // PAGINAZIONE E ORDINAMENTO
    // ===============================
    $recordsTotal = DB::table('t_user_info')->where('active', 1)->count();
    $recordsFiltered = $query->count();

    $users = $query
        ->orderByDesc(DB::raw('a.last_update'))
        ->offset($start)
        ->limit($length)
        ->get();

    // ===============================
    // ELABORAZIONI SECONDARIE (es. età, validità email, anzianità)
    // ===============================
    $data = $users->map(function ($user) {
        $now = \Carbon\Carbon::now();

        // Età
        $user->eta = $user->birth_date
            ? \Carbon\Carbon::parse($user->birth_date)->age
            : null;

        // Email valida
        $user->email_valida = preg_match('/^[^@\s]+@[^@\s]+\.[^@\s]+$/', $user->email);

        // Anzianità iscrizione (in mesi)
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

        return $user;
    });

    // ===============================
    // OUTPUT per DataTables
    // ===============================
    return response()->json([
        'draw' => intval($request->input('draw')),
        'recordsTotal' => $recordsTotal,
        'recordsFiltered' => $recordsFiltered,
        'data' => $data,
    ]);
}

public function updateUserActivity()
{
    $startTime = microtime(true);
    Log::info('=== [updateUserActivity] Inizio aggiornamento ===');

    DB::transaction(function () {

        // 1️⃣ Legge il checkpoint corrente
        $ckpt = DB::table('t_user_activity_ckpt')->where('id', 1)->first();
        $lastInviteId  = (int) ($ckpt->last_invite_id ?? 0);
        $lastHistoryId = (int) ($ckpt->last_history_id ?? 0);

        Log::info('[updateUserActivity] Checkpoint attuale', [
            'last_invite_id'  => $lastInviteId,
            'last_history_id' => $lastHistoryId,
        ]);

        // 2️⃣ Conta nuovi record effettivamente da elaborare
        $newInvites = DB::table('t_abilitatipanel')->where('id', '>', $lastInviteId)->count();
        $newHistory = DB::table('t_user_history')->where('id', '>', $lastHistoryId)->count();

        Log::info('[updateUserActivity] Record da elaborare', [
            'newInvites' => $newInvites,
            'newHistory' => $newHistory,
        ]);

        // Se non c'è nulla di nuovo, esci subito
        if ($newInvites === 0 && $newHistory === 0) {
            Log::info('[updateUserActivity] Nessun nuovo record da elaborare. Uscita.');
            return;
        }

        // 3️⃣ AGGIORNAMENTO INVITI
        $startInviti = microtime(true);
        DB::statement("
            INSERT INTO t_user_activity (uid, invites_count)
            SELECT T.uid, SUM(T.inviti) AS invites_count
            FROM (
                -- 🔹 1) Inviti panel: t_abilitatipanel con SID presente in t_panel_control
                SELECT
                    a.uid,
                    COUNT(*) AS inviti
                FROM t_abilitatipanel a
                JOIN t_panel_control p ON p.sur_id = a.sid
                JOIN t_user_info u ON u.user_id = a.uid
                WHERE p.panel = 1
                  AND a.id > {$lastInviteId}
                GROUP BY a.uid

                UNION ALL

                -- 🔹 2) Inviti CINT: eventi con 'cint' nel tipo evento
                SELECT
                    h.user_id AS uid,
                    COUNT(*) AS inviti
                FROM t_user_history h
                JOIN t_user_info u ON u.user_id = h.user_id
                WHERE h.event_type LIKE '%cint%'
                  AND h.id > {$lastHistoryId}
                GROUP BY h.user_id
            ) AS T
            GROUP BY T.uid
            ON DUPLICATE KEY UPDATE
                invites_count = t_user_activity.invites_count + VALUES(invites_count)
        ");
        Log::info('[updateUserActivity] Inviti aggiornati in ' . round(microtime(true) - $startInviti, 2) . ' sec');

        // 4️⃣ AGGIORNAMENTO ATTIVITÀ
        $startAttivita = microtime(true);
        DB::statement("
            INSERT INTO t_user_activity (uid, completes_count, screenouts_count, quotafull_count, last_update)
            SELECT
                H.user_id,
                SUM(H.add_complete),
                SUM(H.add_screenout),
                SUM(H.add_quotafull),
                MAX(H.max_event_dt)
            FROM (
                -- 🔹 Eventi standard (Primis)
                SELECT
                    h.user_id,
                    SUM(h.event_type = 'interview_complete')  AS add_complete,
                    SUM(h.event_type = 'interview_screenout') AS add_screenout,
                    SUM(h.event_type = 'interview_quotafull') AS add_quotafull,
                    MAX(h.event_date) AS max_event_dt
                FROM t_user_history h
                JOIN t_user_info u ON u.user_id = h.user_id
                JOIN t_abilitatipanel a
                    ON a.uid = h.user_id
                    AND a.sid = REPLACE(REPLACE(SUBSTRING_INDEX(SUBSTRING_INDEX(h.event_info, ',', 2), ',', -1), '(', ''), ')', '')
                WHERE h.id > {$lastHistoryId}
                  AND h.event_type IN ('interview_complete','interview_screenout','interview_quotafull')
                GROUP BY h.user_id

                UNION ALL

                -- 🔹 Eventi CINT (non richiedono match con panel)
                SELECT
                    h.user_id,
                    SUM(h.event_type = 'interview_complete_cint')  AS add_complete,
                    SUM(h.event_type = 'interview_screenout_cint') AS add_screenout,
                    SUM(h.event_type = 'interview_quotafull_cint') AS add_quotafull,
                    MAX(h.event_date) AS max_event_dt
                FROM t_user_history h
                JOIN t_user_info u ON u.user_id = h.user_id
                WHERE h.id > {$lastHistoryId}
                  AND h.event_type IN (
                        'interview_complete_cint',
                        'interview_screenout_cint',
                        'interview_quotafull_cint'
                  )
                GROUP BY h.user_id
            ) AS H
            GROUP BY H.user_id
            ON DUPLICATE KEY UPDATE
                completes_count  = t_user_activity.completes_count  + VALUES(completes_count),
                screenouts_count = t_user_activity.screenouts_count + VALUES(screenouts_count),
                quotafull_count  = t_user_activity.quotafull_count  + VALUES(quotafull_count),
                last_update      = GREATEST(t_user_activity.last_update, VALUES(last_update))
        ");
        Log::info('[updateUserActivity] Attività aggiornate in ' . round(microtime(true) - $startAttivita, 2) . ' sec');

        // 5️⃣ AGGIORNA CHECKPOINT SOLO SE EFFETTIVAMENTE LAVORATO
        DB::statement("
            UPDATE t_user_activity_ckpt
            SET
                last_invite_id  = (SELECT IFNULL(MAX(id), last_invite_id) FROM t_abilitatipanel),
                last_history_id = (SELECT IFNULL(MAX(id), last_history_id) FROM t_user_history),
                updated_at      = NOW()
            WHERE id = 1
        ");
        Log::info('[updateUserActivity] Checkpoint aggiornato');
    }); // <-- chiusura DB::transaction

    $elapsed = round(microtime(true) - $startTime, 2);
    Log::info('=== [updateUserActivity] Fine aggiornamento (' . $elapsed . 's) ===');

    return response()->json([
        'success' => true,
        'message' => 'Aggiornamento completato in ' . $elapsed . ' secondi.'
    ]);
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
