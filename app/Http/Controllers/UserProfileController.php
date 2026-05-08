<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Log;

class UserProfileController extends Controller
{
    /**
     * Mostra il profilo utente completo.
     */
    public function show(Request $request, $uid)
{
    $start = microtime(true);

    if (config('app.debug')) {
        DB::enableQueryLog();
    }

    // ===============================
    // 1) DATI BASE UTENTE
    // ===============================
    $user = DB::table('t_user_info')
        ->where('user_id', $uid)
        ->first();

    if (!$user) {
        abort(404, 'Utente non trovato');
    }

    $provinceMap = $this->getProvinceMap();
    $user->province_name = $provinceMap[$user->province_id] ?? '-';

    $fullName = trim(($user->first_name ?? '') . ' ' . ($user->second_name ?? ''));
    $user->full_name = $fullName !== '' ? $fullName : $user->user_id;

    $user->gender_label = $this->getGenderLabel($user->gender);

    // ===============================
    // 2) ATTIVITÀ LIGHT
    // ===============================
    $userInvites = DB::table('t_user_invites')
        ->select(['user_id', 'invites', 'updated_at', 'last_rebuild'])
        ->where('user_id', $uid)
        ->first();

    $inviti = $userInvites->invites ?? 0;

    // ===============================
    // 3) ULTIMA ATTIVITÀ
    // ===============================
    $ultimaAttivita = DB::table('t_user_history')
        ->where('user_id', $uid)
        ->max('event_date');

    // ===============================
    // 4) PREMI
    // ===============================
    $premi = DB::table('t_user_history')
        ->select(['event_date', 'event_info', 'codice2', 'giorno_paga', 'pagato', 'ip'])
        ->where('user_id', $uid)
        ->where('event_type', 'withdraw')
        ->orderByDesc('event_date')
        ->get();

    $premiPagati = $premi->where('pagato', 1)->count();
    $premiDaPagare = $premi->where('pagato', 0)->count();
    $premiTotali = $premi->count();

    // ===============================
    // 5) STORICO ATTIVITÀ
    // ===============================
    $showAll = $request->query('full', 0);
    $storico = $this->buildStorico($uid, $showAll ? null : 30);

    $logData = [
        'uid' => $uid,
        'duration_sec' => round(microtime(true) - $start, 3),
    ];

    if (config('app.debug')) {
        $logData['queries'] = DB::getQueryLog();
    }

    Log::info('UserProfile show total time', $logData);

    // ===============================
    // 6) RETURN ALLA VIEW
    // ===============================
    return view('userProfile', [
        'user' => $user,
        'attivita' => [
            'inviti' => $inviti,
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


// ===============================
 /** FUNZIONI DI GESTIONE UTENTE **/
// ===============================

    public function deactivate($user_id)
    {
        try {
            DB::table('t_user_info')
                ->where('user_id', $user_id)
                ->update(['active' => 9, 'confirm' => 9]);

            return response()->json(['success' => true, 'message' => 'Utente disattivato correttamente.']);
        } catch (\Exception $e) {
            Log::error('Errore disattivazione utente: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Errore durante la disattivazione.']);
        }
    }

    /**
     * Elimina definitivamente un utente (rimozione riga)
     */
public function delete($user_id)
{
    try {
        DB::table('t_user_info')
            ->where('user_id', $user_id)
            ->update([
                'active' => 9,
                'confirm' => 9,
                'email' => DB::raw("CONCAT('deleted_', user_id, '_', UNIX_TIMESTAMP(), '@deleted.local')"),
                'paypalEmail' => null,
                'mobile_phone' => null,
                'home_phone' => null,
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Utente disattivato e dati di contatto rimossi.',
        ]);
    } catch (\Exception $e) {
        Log::error('Errore eliminazione logica utente: ' . $e->getMessage());

        return response()->json([
            'success' => false,
            'message' => 'Errore durante l\'eliminazione.',
        ]);
    }
}

    /**
     * Riattiva un utente (active=1, confirm=1)
     */
    public function activate($user_id)
    {
        try {
            DB::table('t_user_info')
                ->where('user_id', $user_id)
                ->update(['active' => 1, 'confirm' => 1]);

            return response()->json(['success' => true, 'message' => 'Utente riattivato correttamente.']);
        } catch (\Exception $e) {
            Log::error('Errore riattivazione utente: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Errore durante la riattivazione.']);
        }
    }

public function updateAnagrafica(Request $request, $user_id)
{
    $validated = $request->validate([
        'email' => 'required|email',
        'paypalEmail' => 'nullable|email',
    ]);

    try {
        DB::table('t_user_info')
            ->where('user_id', $user_id)
            ->update([
                'email' => $validated['email'],
                'paypalEmail' => $validated['paypalEmail'],
            ]);

        return response()->json(['success' => true, 'message' => 'Anagrafica aggiornata con successo.']);
    } catch (\Exception $e) {
        Log::error('Errore aggiornamento anagrafica: ' . $e->getMessage());
        return response()->json(['success' => false, 'message' => 'Errore durante il salvataggio.']);
    }
}


public function assignBonusMalus(Request $request, $user_id)
{
    $validated = $request->validate([
        'type' => 'required|string|in:Bonus,Malus,BONUS,MALUS',
        'motivation' => 'required|string|max:255',
        'value' => 'required|integer|min:1',
    ]);

    $type = ucfirst(strtolower(trim($validated['type'])));

    try {
        $result = DB::transaction(function () use ($user_id, $validated, $type) {
            $user = DB::table('t_user_info')
                ->where('user_id', $user_id)
                ->lockForUpdate()
                ->first();

            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Utente non trovato.',
                ];
            }

            $prev = (int) $user->points;
            $inputValue = (int) $validated['value'];

            if ($type === 'Bonus') {
                $new = $prev + $inputValue;

            } else {
                $new = max(0, $prev - $inputValue);

            }

            DB::table('t_user_info')
                ->where('user_id', $user_id)
                ->update([
                    'points' => $new,
                ]);

            DB::table('t_user_history')->insert([
                'user_id'    => $user_id,
                'event_date' => now(),
                'event_type' => $type,
                'event_info' => $validated['motivation'],
                'prev_level' => $prev,
                'new_level'  => $new,
            ]);

            return [
                'success' => true,
                'message' => $type . ' assegnato correttamente.',
                'points'  => $new,
            ];
        });

        if (!$result['success']) {
            return response()->json($result);
        }

        $storico = $this->buildStorico($user_id, 30);
        $storicoHtml = view('partials.userProfileStoricoRows', compact('storico'))->render();

        $result['storico_html'] = $storicoHtml;

        return response()->json($result);

    } catch (\Exception $e) {
        Log::error('Errore bonus/malus: ' . $e->getMessage());

        return response()->json([
            'success' => false,
            'message' => 'Errore durante l\'operazione.',
        ]);
    }
}

private function buildStorico($user_id, $limit = 30)
{
$storicoQuery = DB::table('t_user_history')
    ->select(['event_date', 'event_type', 'event_info', 'prev_level', 'new_level'])
    ->where('user_id', $user_id)
    ->orderByDesc('event_date');

    if (!is_null($limit)) {
        $storicoQuery->limit($limit);
    }

    return $storicoQuery->get()->map(function ($item) {
        $diff = (int)(($item->new_level ?? 0) - ($item->prev_level ?? 0));
        $item->bytes = $diff;

        // Divide info solo per eventi sondaggio, non per Bonus/Malus/Premi
        $info = [];

        $isInterviewEvent = in_array($item->event_type, [
            'interview_screenout',
            'interview_complete',
            'interview_quotafull',
            'interview_complete_cint',
        ], true);

        if ($isInterviewEvent && !empty($item->event_info)) {
            if (strpos($item->event_info, ',') !== false) {
                $info = explode(',', $item->event_info);
            } elseif (strpos($item->event_info, '|') !== false) {
                $info = explode('|', $item->event_info);
            }
        }

        $item->iid = trim(str_replace(['(', ')'], '', $info[0] ?? '-'));
        $item->sid = trim($info[1] ?? '-');
        $item->prj = trim(str_replace(['(', ')'], '', $info[2] ?? '-'));

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
                $item->evento_color = 'success';
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
                $item->evento_color = 'success-dark';
                $item->evento_icon = 'bi-emoji-laughing';
                $item->tipologia = 'Sondaggio CINT';
                break;

            case 'withdraw':
                $item->evento_label = 'PREMIO';
                $item->evento_color = 'info-light';
                $item->evento_icon = 'bi-gift';
                $item->tipologia = $item->event_info ?? 'Premio';
                break;

            case 'Bonus':
                $item->bytes = abs($diff);
                $item->evento_label = 'BONUS';
                $item->evento_color = 'primary';
                $item->evento_icon = 'bi-plus-circle';
                $item->tipologia = $item->event_info ?? 'Bonus';
                break;

            case 'Malus':
                $item->bytes = -abs($diff);
                $item->evento_label = 'MALUS';
                $item->evento_color = 'orange';
                $item->evento_icon = 'bi-emoji-angry';
                $item->tipologia = $item->event_info ?? 'Malus';
                break;
        }

        return $item;
    });
}

private function getGenderLabel($gender)
{
    switch ((int) $gender) {
        case 1:
            return 'Maschile';
        case 2:
            return 'Femminile';
        default:
            return '-';
    }
}

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


}
