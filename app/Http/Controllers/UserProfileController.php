<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UserProfileController extends Controller
{
    /**
     * Mostra il profilo utente completo.
     */
    public function show(Request $request, $uid)
{
    // ===============================
    // 1️⃣ DATI BASE (t_user_info)
    // ===============================
    $user = DB::table('t_user_info')
        ->where('user_id', $uid)
        ->first();

    if (!$user) {
        abort(404, 'Utente non trovato');
    }

   // ===============================
// ⚡ ATTIVITÀ — Lettura dalla cache
// ===============================
$cache = DB::table('t_user_activity_cache')->where('uid', $uid)->first();

if ($cache) {
    $inviti = $cache->inviti;
    $click = $cache->click;
    $completeMillebytes = $cache->complete_millebytes;
    $sospese = $cache->sospese;
    $nonTarget = $cache->non_target;
} else {
    // Se cache mancante, fallback rapido con query singola (solo 1 utente)
    $cache = DB::table('t_respint')
        ->selectRaw("
            COUNT(*) as inviti,
            SUM(CASE WHEN iid != -1 THEN 1 ELSE 0 END) as click,
            SUM(CASE WHEN status = 3 THEN 1 ELSE 0 END) as complete_millebytes,
            SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as sospese,
            SUM(CASE WHEN status IN (4,5) THEN 1 ELSE 0 END) as non_target
        ")
        ->where('uid', $uid)
        ->first();

    $inviti = $cache->inviti ?? 0;
    $click = $cache->click ?? 0;
    $completeMillebytes = $cache->complete_millebytes ?? 0;
    $sospese = $cache->sospese ?? 0;
    $nonTarget = $cache->non_target ?? 0;
}



$completeTotali = $completeMillebytes ;

$partecipazione = $inviti > 0 ? round(($click / $inviti) * 100) : 0;

$ultimaAttivita = DB::table('t_user_history')
    ->where('user_id', $uid)
    ->max('event_date');


    // ===============================
    // 3️⃣ PREMI
    // ===============================
    $premi = DB::table('t_user_history')
        ->where('user_id', $uid)
        ->where('event_type', 'withdraw')
        ->orderByDesc('event_date')
        ->get();

    $premiPagati = $premi->where('pagato', 1)->count();
    $premiDaPagare = $premi->where('pagato', 0)->count();
    $premiTotali = $premi->count();

    // =====================================================
    // ✅ 4️⃣ BLOCCO NUOVO: GESTIONE "MOSTRA TUTTO" STORICO
    // =====================================================
    $showAll = $request->query('full', 0);

    $storicoQuery = DB::table('t_user_history')
        ->where('user_id', $uid)
        ->orderByDesc('event_date');

    // Se non richiesto "Mostra tutto", limitiamo a 20 record
    if (!$showAll) {
        $storicoQuery->limit(20);
    }

    $storico = $storicoQuery->get()->map(function ($item) {
        $diff = ($item->new_level ?? 0) - ($item->prev_level ?? 0);
        $item->delta = $diff;
        return $item;
    });

    // ===============================
    // 5️⃣ RETURN ALLA VIEW
    // ===============================
    return view('userProfile', [
        'user' => $user,
        'attivita' => [
            'inviti' => $inviti,
            'click' => $click,
            'complete_millebytes' => $completeMillebytes,
            'complete_totali' => $completeTotali,
            'sospese' => $sospese,
            'non_target' => $nonTarget,
            'partecipazione' => $partecipazione,
            'ultima_attivita' => $ultimaAttivita,
        ],
        'premi' => [
            'lista' => $premi,
            'pagati' => $premiPagati,
            'da_pagare' => $premiDaPagare,
            'totali' => $premiTotali,
        ],
        'storico' => $storico,
    ]);
}

}
