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
        ->select('uid', 'inviti', 'attivita', 'partecipazione');

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
        // Svuota la cache precedente
        DB::table('t_user_activity_cache')->truncate();

        // Rigenera i dati aggregati
        DB::statement("
            INSERT INTO t_user_activity_cache (uid, inviti, attivita, partecipazione)
            SELECT
                uid,
                COUNT(*) AS inviti,
                SUM(CASE WHEN iid != -1 THEN 1 ELSE 0 END) AS attivita,
                ROUND(SUM(CASE WHEN iid != -1 THEN 1 ELSE 0 END) / COUNT(*) * 100) AS partecipazione
            FROM t_respint
            GROUP BY uid
        ");

        return response()->json(['success' => true, 'message' => 'Cache aggiornata correttamente.']);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'message' => $e->getMessage()]);
    }
}


}
