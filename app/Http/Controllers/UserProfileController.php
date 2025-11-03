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
        // ⚡ ATTIVITÀ — conteggio filtrato
        // ===============================
        // Recupera tutti i sid validi da t_panel_control (panel=1)
        $sidValidi = DB::table('t_panel_control')
            ->where('panel', 1)
            ->pluck('sur_id')
            ->toArray();

        // Query principale: contiamo solo se SID è valido o PRJ = CINTPANEL
        $stats = DB::table('t_respint')
            ->selectRaw("
                COUNT(*) as inviti,
                SUM(CASE WHEN iid != -1 THEN 1 ELSE 0 END) as click,
                SUM(CASE WHEN status = 3 THEN 1 ELSE 0 END) as complete_millebytes,
                SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as sospese,
                SUM(CASE WHEN status IN (4,5) THEN 1 ELSE 0 END) as non_target
            ")
            ->where('uid', $uid)
            ->where(function ($q) use ($sidValidi) {
                $q->whereIn('sid', $sidValidi)
                  ->orWhere('prj_name', 'CINTPANEL');
            })
            ->first();

        // Conteggi per origine inviti
        $countByOrigin = DB::table('t_respint')
            ->selectRaw("
                SUM(CASE WHEN prj_name = 'CINTPANEL' THEN 1 ELSE 0 END) as cint_inviti,
                SUM(CASE WHEN prj_name != 'CINTPANEL' THEN 1 ELSE 0 END) as millebytes_inviti
            ")
            ->where('uid', $uid)
            ->where(function ($q) use ($sidValidi) {
                $q->whereIn('sid', $sidValidi)
                  ->orWhere('prj_name', 'CINTPANEL');
            })
            ->first();

        $inviti = $stats->inviti ?? 0;
        $click = $stats->click ?? 0;
        $completeMillebytes = $stats->complete_millebytes ?? 0;
        $sospese = $stats->sospese ?? 0;
        $nonTarget = $stats->non_target ?? 0;

        $completeTotali = $completeMillebytes;
        $partecipazione = $inviti > 0 ? round(($click / $inviti) * 100) : 0;

        $cintInviti = $countByOrigin->cint_inviti ?? 0;
        $millebytesInviti = $countByOrigin->millebytes_inviti ?? 0;

        // ===============================
        // 🕓 Ultima attività
        // ===============================
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

// ===============================
// 4️⃣ STORICO ATTIVITÀ
// ===============================
$showAll = $request->query('full', 0);

$storicoQuery = DB::table('t_user_history')
    ->where('user_id', $uid)
    ->orderByDesc('event_date');

if (!$showAll) {
    $storicoQuery->limit(30);
}

$storico = $storicoQuery->get()->map(function ($item) {
    $diff = ($item->new_level ?? 0) - ($item->prev_level ?? 0);
    $item->bytes = $diff;

    // Divide info solo se contiene virgole
    $info = [];
    if (!empty($item->event_info) && str_contains($item->event_info, ',')) {
        $info = explode(',', $item->event_info);
    } elseif (!empty($item->event_info) && str_contains($item->event_info, '|')) {
        $info = explode('|', $item->event_info);
    }

$item->iid = trim(str_replace(['(',')'], '', $info[0] ?? '-'));
$item->sid = trim($info[1] ?? '-');
$item->prj = trim(str_replace(['(',')'], '', $info[2] ?? '-'));

    $item->tipologia = '-';
    $item->evento_label = '-';
    $item->evento_color = 'secondary';
    $item->evento_icon = 'bi-question-circle';

    switch ($item->event_type) {
        case 'interview_screenout':
            $item->evento_label = 'SCREENOUT';
            $item->evento_color = 'danger';
            $item->evento_icon = 'bi-emoji-frown';
            $item->tipologia = 'Sondaggio Interactive';
            break;

        case 'interview_complete':
            $item->evento_label = 'COMPLETATA';
            $item->evento_color = 'success'; // verde standard
            $item->evento_icon = 'bi-emoji-smile';
            $item->tipologia = 'Sondaggio Interactive';
            break;

        case 'interview_quotafull':
            $item->evento_label = 'QUOTAFULL';
            $item->evento_color = 'warning';
            $item->evento_icon = 'bi-emoji-neutral';
            $item->tipologia = 'Sondaggio Interactive';
            break;

        case 'interview_complete_cint':
            $item->evento_label = 'COMPLETATA';
            $item->evento_color = 'success-dark'; // verde più scuro per CINT
            $item->evento_icon = 'bi-emoji-laughing';
            $item->tipologia = 'Sondaggio CINT';
            break;

        case 'withdraw':
            $item->evento_label = 'PREMIO';
            $item->evento_color = 'info-light'; // azzurro più chiaro
            $item->evento_icon = 'bi-gift';
            $item->tipologia = $item->event_info ?? 'Premio';
            break;

        case 'Bonus':
            $item->evento_label = 'BONUS';
            $item->evento_color = 'primary';
            $item->evento_icon = 'bi-plus-circle';
            $item->tipologia = $item->event_info ?? 'Bonus';
            $item->bytes = $diff;
            break;

        case 'Malus':
            $item->evento_label = 'MALUS';
            $item->evento_color = 'orange';
            $item->evento_icon = 'bi-emoji-angry';
            $item->tipologia = $item->event_info ?? 'Malus';
            $item->bytes = ($item->prev_level ?? 0) - ($item->new_level ?? 0);
            break;
    }

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
                'cint_inviti' => $cintInviti,
                'millebytes_inviti' => $millebytesInviti,
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
