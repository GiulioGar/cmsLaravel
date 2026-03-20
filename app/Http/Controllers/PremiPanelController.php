<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PremiPanelController extends Controller
{

public function index(Request $request)
{
    $type = strtolower($request->get('type', 'amazon'));
    $status = $request->get('status', '0');

    $data = $this->buildPanelData($type, $status);

    return view('premiPanel', $data);
}

public function data(Request $request)
{
    $type = strtolower($request->get('type', 'amazon'));
    $status = $request->get('status', '0');

    $data = $this->buildPanelData($type, $status);

return response()->json([
    'success' => true,
    'table_html' => view('partials.premiPanelTable', $data)->render(),
    'summary_html' => view('partials.premiPanelSummary', $data)->render(),
    'amazon_modal_html' => view('partials.amazonBulkPayModalContent', $data)->render(),
    'amazon_bulk_pay_available' => $data['sidebar']['amazon_bulk_pay_available'] ?? false,
]);
}

public function summary(Request $request)
{
    $type = strtolower($request->get('type', 'amazon'));
    $status = $request->get('status', '0');

    $data = $this->buildPanelData($type, $status);

    return response()->json([
        'success' => true,
        'summary_html' => view('partials.premiPanelSummary', $data)->render(),
    ]);
}

private function buildPanelData(string $type = 'amazon', string $status = '0'): array
{
    $prizeKeyword = $type === 'paypal' ? 'Paypal' : 'Amazon';

    $richieste = DB::table('t_user_history as h')
        ->join('t_user_info as u', 'u.user_id', '=', 'h.user_id')
        ->select([
            'h.id',
            'h.user_id',
            'h.event_info',
            'h.event_date',
            'h.prev_level',
            'h.new_level',
            'h.pagato',
            'h.giorno_paga',
            'h.codice2',
            'h.ip',
            'u.email',
            'u.paypalEmail',
        ])
        ->where('h.event_type', 'withdraw')
        ->where('h.pagato', $status)
        ->where('u.active', 1) // 👈 AGGIUNTO QUI
        ->where('h.event_info', 'like', '%' . $prizeKeyword . '%')
        ->orderBy('h.id', 'desc')
        ->get()
        ->map(function ($row) use ($type) {
            $row->requested_at = $row->event_date
                ? \Carbon\Carbon::parse($row->event_date)->format('d/m/Y H:i')
                : '-';

            $eventDate = $row->event_date ? \Carbon\Carbon::parse($row->event_date) : null;

            $row->giorni_attesa = $eventDate
                ? $eventDate->diffInDays(now())
                : null;

            $row->paid_at = $row->giorno_paga
                ? \Carbon\Carbon::parse($row->giorno_paga)->format('d/m/Y')
                : '-';

            $row->points_spesi = (int)(($row->prev_level ?? 0) - ($row->new_level ?? 0));
            $row->premio_valore = $this->extractPremioValue($row->event_info);
            $row->premio_label = $this->extractPremioLabel($row->event_info, $type);

            $row->contact_email = $type === 'paypal'
                ? ($row->paypalEmail ?: '-')
                : ($row->email ?: '-');

            return $row;
        });


            /*
        |--------------------------------------------------------------------------
        | SIDEBAR - RIEPILOGO GENERALE NON PAGATI
        |--------------------------------------------------------------------------
        */
        $pendingSummary = (object) [
                    'total_requests' => 0,
                    'total_2' => 0,
                    'total_5' => 0,
                    'total_10' => 0,
                    'total_20' => 0,
                    'total_value' => 0,
                    'missing_paypal_email' => 0,
                ];

                $pendingRows = $richieste->filter(function ($row) {
                    return (int) $row->pagato === 0;
                });

                $pendingSummary->total_requests = $pendingRows->count();
                $pendingSummary->total_2 = $pendingRows->where('premio_valore', 2)->count();
                $pendingSummary->total_5 = $pendingRows->where('premio_valore', 5)->count();
                $pendingSummary->total_10 = $pendingRows->where('premio_valore', 10)->count();
                $pendingSummary->total_20 = $pendingRows->where('premio_valore', 20)->count();

                $pendingSummary->total_value =
                    ($pendingSummary->total_2 * 2) +
                    ($pendingSummary->total_5 * 5) +
                    ($pendingSummary->total_10 * 10) +
                    ($pendingSummary->total_20 * 20);

                if ($type === 'paypal') {
                    $pendingSummary->missing_paypal_email = $pendingRows->filter(function ($row) {
                        return empty($row->paypalEmail);
                    })->count();
                }
        /*
        |--------------------------------------------------------------------------
        | SIDEBAR - PREMI AMAZON DISPONIBILI
        |--------------------------------------------------------------------------
        */
        $availableAmazonRaw = collect();

        if ($type === 'amazon') {
            $availableAmazonRaw = DB::table('t_premidb')
                ->selectRaw('valore, COUNT(*) as quantity')
                ->where('status', 'disponibile')
                ->whereIn('valore', [2, 5, 10, 20])
                ->groupBy('valore')
                ->orderBy('valore')
                ->get();
        }

        $availableAmazonMap = [
            2 => 0,
            5 => 0,
            10 => 0,
            20 => 0,
        ];

        foreach ($availableAmazonRaw as $row) {
            $availableAmazonMap[(int) $row->valore] = (int) $row->quantity;
        }

$availableAmazon = collect([
    [
        'label' => 'Buoni 2 euro',
        'value' => 2,
        'quantity' => $availableAmazonMap[2],
        'total' => $availableAmazonMap[2] * 2,
        'pending' => $pendingSummary->total_2,
        'is_available' => $pendingSummary->total_2 <= $availableAmazonMap[2],
    ],
    [
        'label' => 'Buoni 5 euro',
        'value' => 5,
        'quantity' => $availableAmazonMap[5],
        'total' => $availableAmazonMap[5] * 5,
        'pending' => $pendingSummary->total_5,
        'is_available' => $pendingSummary->total_5 <= $availableAmazonMap[5],
    ],
    [
        'label' => 'Buoni 10 euro',
        'value' => 10,
        'quantity' => $availableAmazonMap[10],
        'total' => $availableAmazonMap[10] * 10,
        'pending' => $pendingSummary->total_10,
        'is_available' => $pendingSummary->total_10 <= $availableAmazonMap[10],
    ],
    [
        'label' => 'Buoni 20 euro',
        'value' => 20,
        'quantity' => $availableAmazonMap[20],
        'total' => $availableAmazonMap[20] * 20,
        'pending' => $pendingSummary->total_20,
        'is_available' => $pendingSummary->total_20 <= $availableAmazonMap[20],
    ],
]);

$amazonBulkPayAvailable = true;

if ($type === 'amazon') {
    foreach ($availableAmazon as $item) {
        if (!$item['is_available']) {
            $amazonBulkPayAvailable = false;
            break;
        }
    }
}

        $availableAmazonTotal = $availableAmazon->sum('total');

            /*
        |--------------------------------------------------------------------------
        | SIDEBAR - COSTI ANNO CORRENTE
        |--------------------------------------------------------------------------
        */
        $currentYear = now()->year;

        $yearPaidSummary = DB::table('t_user_history as h')
            ->selectRaw("
                SUM(CASE WHEN h.pagato = 1 AND h.event_info LIKE '%2 euro%' AND YEAR(h.giorno_paga) = ? THEN 1 ELSE 0 END) as paid_2,
                SUM(CASE WHEN h.pagato = 1 AND h.event_info LIKE '%5 euro%' AND YEAR(h.giorno_paga) = ? THEN 1 ELSE 0 END) as paid_5,
                SUM(CASE WHEN h.pagato = 1 AND h.event_info LIKE '%10 euro%' AND YEAR(h.giorno_paga) = ? THEN 1 ELSE 0 END) as paid_10,
                SUM(CASE WHEN h.pagato = 1 AND h.event_info LIKE '%20 euro%' AND YEAR(h.giorno_paga) = ? THEN 1 ELSE 0 END) as paid_20
            ", [$currentYear, $currentYear, $currentYear, $currentYear])
            ->where('h.event_type', 'withdraw')
            ->where('h.event_info', 'like', '%' . $prizeKeyword . '%')
            ->first();

        $yearPaidSummary = (object) [
            'paid_2' => (int) ($yearPaidSummary->paid_2 ?? 0),
            'paid_5' => (int) ($yearPaidSummary->paid_5 ?? 0),
            'paid_10' => (int) ($yearPaidSummary->paid_10 ?? 0),
            'paid_20' => (int) ($yearPaidSummary->paid_20 ?? 0),
        ];

        $yearPaidSummary->paid_total_value =
            ($yearPaidSummary->paid_2 * 2) +
            ($yearPaidSummary->paid_5 * 5) +
            ($yearPaidSummary->paid_10 * 10) +
            ($yearPaidSummary->paid_20 * 20);

        /*
        |--------------------------------------------------------------------------
        | SIDEBAR - ACQUISTATI (solo Amazon)
        |--------------------------------------------------------------------------
        */
        $yearPurchasedSummary = (object) [
            'buy_2' => 0,
            'buy_5' => 0,
            'buy_10' => 0,
            'buy_20' => 0,
            'buy_total_value' => 0,
        ];

        if ($type === 'amazon') {

            $purchasedRows = DB::table('t_premidb')
                ->select(['valore', 'scadenza'])
                ->whereIn('valore', [2, 5, 10, 20])
                ->get();

            $buyMap = [
                2 => 0,
                5 => 0,
                10 => 0,
                20 => 0,
            ];

            foreach ($purchasedRows as $row) {
                $expiryYear = null;

                if (!empty($row->scadenza)) {
                    $expiryYear = (int) substr((string) $row->scadenza, -4);
                    $expiryYear = $expiryYear - 10;
                }

                if ((string) $expiryYear === (string) $currentYear) {
                    $buyMap[(int) $row->valore] = $buyMap[(int) $row->valore] + 1;
                }
            }

            $yearPurchasedSummary = (object) [
                'buy_2' => $buyMap[2],
                'buy_5' => $buyMap[5],
                'buy_10' => $buyMap[10],
                'buy_20' => $buyMap[20],
                'buy_total_value' => ($buyMap[2] * 2) + ($buyMap[5] * 5) + ($buyMap[10] * 10) + ($buyMap[20] * 20),
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | SIDEBAR - STORICO PAGATI TOTALE
        |--------------------------------------------------------------------------
        */
        $historyTotals = DB::table('t_user_history as h')
            ->selectRaw("
                SUM(CASE WHEN h.pagato = 1 AND h.event_info LIKE '%2 euro%' THEN 1 ELSE 0 END) as total_2,
                SUM(CASE WHEN h.pagato = 1 AND h.event_info LIKE '%5 euro%' THEN 1 ELSE 0 END) as total_5,
                SUM(CASE WHEN h.pagato = 1 AND h.event_info LIKE '%10 euro%' THEN 1 ELSE 0 END) as total_10,
                SUM(CASE WHEN h.pagato = 1 AND h.event_info LIKE '%20 euro%' THEN 1 ELSE 0 END) as total_20
            ")
            ->where('h.event_type', 'withdraw')
            ->where('h.event_info', 'like', '%' . $prizeKeyword . '%')
            ->first();

        $historyTotals = (object) [
            'total_2' => (int) ($historyTotals->total_2 ?? 0),
            'total_5' => (int) ($historyTotals->total_5 ?? 0),
            'total_10' => (int) ($historyTotals->total_10 ?? 0),
            'total_20' => (int) ($historyTotals->total_20 ?? 0),
        ];

        $historyTotals->grand_total =
            $historyTotals->total_2 +
            $historyTotals->total_5 +
            $historyTotals->total_10 +
            $historyTotals->total_20;

        $historyTotals->percent_2 = $historyTotals->grand_total > 0 ? round(($historyTotals->total_2 / $historyTotals->grand_total) * 100) : 0;
        $historyTotals->percent_5 = $historyTotals->grand_total > 0 ? round(($historyTotals->total_5 / $historyTotals->grand_total) * 100) : 0;
        $historyTotals->percent_10 = $historyTotals->grand_total > 0 ? round(($historyTotals->total_10 / $historyTotals->grand_total) * 100) : 0;
        $historyTotals->percent_20 = $historyTotals->grand_total > 0 ? round(($historyTotals->total_20 / $historyTotals->grand_total) * 100) : 0;

return [
    'type' => $type,
    'status' => $status,
    'richieste' => $richieste,
    'sidebar' => [
        'pending_summary' => $pendingSummary,
        'available_amazon' => $availableAmazon,
        'available_amazon_total' => $availableAmazonTotal,
        'amazon_bulk_pay_available' => $amazonBulkPayAvailable,
        'year_paid_summary' => $yearPaidSummary,
        'year_purchased_summary' => $yearPurchasedSummary,
        'history_totals' => $historyTotals,
        'current_year' => $currentYear,
    ],
    ];
}

private function extractPremioLabel($eventInfo, $type)
{
    $eventInfo = trim((string) $eventInfo);

    if ($type === 'paypal') {
        if (stripos($eventInfo, '5 euro') !== false) {
            return 'Paypal 5€';
        }

        if (stripos($eventInfo, '10 euro') !== false) {
            return 'Paypal 10€';
        }

        return 'Paypal';
    }

    if (stripos($eventInfo, '2 euro') !== false) {
        return 'Amazon 2€';
    }

    if (stripos($eventInfo, '5 euro') !== false) {
        return 'Amazon 5€';
    }

    if (stripos($eventInfo, '10 euro') !== false) {
        return 'Amazon 10€';
    }

    if (stripos($eventInfo, '20 euro') !== false) {
        return 'Amazon 20€';
    }

    return 'Amazon';
}

private function extractPremioValue($eventInfo)
{
    $eventInfo = trim((string) $eventInfo);

    if (preg_match('/(\d+)\s*euro/i', $eventInfo, $matches)) {
        return (int) $matches[1];
    }

    return null;
}

public function payPaypal(Request $request, $id)
{
    try {
        $updated = DB::table('t_user_history')
            ->where('id', $id)
            ->where('event_type', 'withdraw')
            ->where('pagato', 0)
            ->where('event_info', 'like', '%Paypal%')
            ->update([
                'pagato' => 1,
                'giorno_paga' => now()->format('Y-m-d H:i:s'),
            ]);

        if (!$updated) {
            return response()->json([
                'success' => false,
                'message' => 'Richiesta non trovata o già pagata.',
            ], 404);
        }

        $row = DB::table('t_user_history as h')
            ->join('t_user_info as u', 'u.user_id', '=', 'h.user_id')
            ->select([
                'h.id',
                'h.user_id',
                'h.event_info',
                'h.event_date',
                'h.prev_level',
                'h.new_level',
                'h.pagato',
                'h.giorno_paga',
                'h.codice2',
                'h.ip',
                'u.email',
                'u.paypalEmail',
            ])
            ->where('h.id', $id)
            ->first();

        if (!$row) {
            return response()->json([
                'success' => false,
                'message' => 'Impossibile ricaricare la richiesta aggiornata.',
            ], 404);
        }

        $paidAt = $row->giorno_paga
            ? \Carbon\Carbon::parse($row->giorno_paga)->format('d/m/Y')
            : '-';

        $value = $this->extractPremioValue($row->event_info);

            return response()->json([
                'success' => true,
                'message' => 'Richiesta Paypal segnata come pagata.',
                'row' => [
                    'id' => $row->id,
                    'paid_at' => $paidAt,
                    'value' => $value,
                    'missing_paypal_email' => empty($row->paypalEmail) ? 1 : 0,
                ],
            ]);
    } catch (\Exception $e) {
        Log::error('Errore pagamento Paypal premiPanel: ' . $e->getMessage());

        return response()->json([
            'success' => false,
            'message' => 'Errore durante l\'aggiornamento del pagamento.',
        ], 500);
    }
}


public function deleteReward(Request $request, $id)
{
    try {
        $result = DB::transaction(function () use ($id) {

            $reward = DB::table('t_user_history')
                ->where('id', $id)
                ->where('event_type', 'withdraw')
                ->lockForUpdate()
                ->first();

            if (!$reward) {
                return [
                    'success' => false,
                    'message' => 'Richiesta premio non trovata.',
                    'status' => 404,
                ];
            }

            $user = DB::table('t_user_info')
                ->where('user_id', $reward->user_id)
                ->lockForUpdate()
                ->first();

            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Utente collegato alla richiesta non trovato.',
                    'status' => 404,
                ];
            }

            $pointsToRestore = (int)(($reward->prev_level ?? 0) - ($reward->new_level ?? 0));
            if ($pointsToRestore < 0) {
                $pointsToRestore = 0;
            }

            // Ripristino punti utente
            if ($pointsToRestore > 0) {
                DB::table('t_user_info')
                    ->where('user_id', $reward->user_id)
                    ->update([
                        'points' => DB::raw('points + ' . $pointsToRestore),
                    ]);
            }

            $releasedAmazonCode = false;

            // Se Amazon già assegnato, rimetti il codice disponibile
            if (
                !empty($reward->codice2) &&
                stripos((string) $reward->event_info, 'Amazon') !== false
            ) {
                DB::table('t_premidb')
                    ->where('codice', $reward->codice2)
                    ->update([
                        'status' => 'disponibile',
                        'pagamento' => null,
                        'user' => null,
                    ]);

                $releasedAmazonCode = true;
            }

            DB::table('t_user_history')
                ->where('id', $reward->id)
                ->delete();

            $value = $this->extractPremioValue($reward->event_info);
            $missingPaypalEmail = 0;

            if (stripos((string) $reward->event_info, 'Paypal') !== false) {
                $paypalEmail = DB::table('t_user_info')
                    ->where('user_id', $reward->user_id)
                    ->value('paypalEmail');

                $missingPaypalEmail = empty($paypalEmail) ? 1 : 0;
            }

            return [
                'success' => true,
                'message' => 'Richiesta premio eliminata correttamente.',
                'status' => 200,
                'payload' => [
                    'id' => $reward->id,
                    'user_id' => $reward->user_id,
                    'points_restored' => $pointsToRestore,
                    'value' => (int) ($value ?? 0),
                    'released_amazon_code' => $releasedAmazonCode ? 1 : 0,
                    'missing_paypal_email' => $missingPaypalEmail,
                    'was_paid' => (int) ($reward->pagato ?? 0),
                ],
            ];
        });

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'payload' => $result['payload'] ?? null,
        ], $result['status']);
    } catch (\Exception $e) {
        Log::error('Errore delete reward premiPanel: ' . $e->getMessage());

        return response()->json([
            'success' => false,
            'message' => 'Errore durante l\'eliminazione della richiesta premio.',
        ], 500);
    }
}

public function savePaypalNote(Request $request, $id)
{
    $validated = $request->validate([
        'note' => 'required|string|max:255',
    ]);

    try {
        $updated = DB::table('t_user_history')
            ->where('id', $id)
            ->where('event_type', 'withdraw')
            ->where('event_info', 'like', '%Paypal%')
            ->where(function ($query) {
                $query->whereNull('codice2')
                      ->orWhere('codice2', '');
            })
            ->update([
                'codice2' => trim($validated['note']),
            ]);

        if (!$updated) {
            return response()->json([
                'success' => false,
                'message' => 'Nota non salvabile: richiesta non trovata o nota già presente.',
            ], 409);
        }

        $row = DB::table('t_user_history')
            ->select(['id', 'codice2'])
            ->where('id', $id)
            ->first();

        if (!$row) {
            return response()->json([
                'success' => false,
                'message' => 'Richiesta non trovata dopo il salvataggio.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Nota salvata correttamente.',
            'row' => [
                'id' => $row->id,
                'note' => $row->codice2,
            ],
        ]);
    } catch (\Exception $e) {
        Log::error('Errore salvataggio nota Paypal premiPanel: ' . $e->getMessage());

        return response()->json([
            'success' => false,
            'message' => 'Errore durante il salvataggio della nota.',
        ], 500);
    }
}

public function bulkPayAmazon(Request $request)
{
    try {
        $result = DB::transaction(function () {
            $paymentDate = now()->format('Y-m-d H:i:s');
            $fileDate = now()->format('dmy');

            $rewards = DB::table('t_user_history as h')
                ->join('t_user_info as u', 'u.user_id', '=', 'h.user_id')
                ->select([
                    'h.id',
                    'h.user_id',
                    'h.event_info',
                    'h.codice2',
                    'u.email',
                ])
                ->where('h.event_type', 'withdraw')
                ->where('h.pagato', 0)
                ->where('h.event_info', 'like', '%Amazon%')
                ->orderBy('h.event_date', 'asc')
                ->lockForUpdate()
                ->get();

            if ($rewards->isEmpty()) {
                return [
                    'success' => false,
                    'status' => 404,
                    'message' => 'Nessuna richiesta Amazon non pagata da elaborare.',
                ];
            }

            $groupedRewards = [
                2 => collect(),
                5 => collect(),
                10 => collect(),
                20 => collect(),
            ];

            foreach ($rewards as $reward) {
                $value = (int) $this->extractPremioValue($reward->event_info);

                if (!in_array($value, [2, 5, 10, 20], true)) {
                    return [
                        'success' => false,
                        'status' => 422,
                        'message' => 'Trovata una richiesta Amazon con valore non riconosciuto.',
                    ];
                }

                $groupedRewards[$value]->push($reward);
            }

            $availableCoupons = [
                2 => DB::table('t_premidb')
                    ->where('status', 'disponibile')
                    ->where('valore', 2)
                    ->orderBy('id', 'asc')
                    ->lockForUpdate()
                    ->get(),
                5 => DB::table('t_premidb')
                    ->where('status', 'disponibile')
                    ->where('valore', 5)
                    ->orderBy('id', 'asc')
                    ->lockForUpdate()
                    ->get(),
                10 => DB::table('t_premidb')
                    ->where('status', 'disponibile')
                    ->where('valore', 10)
                    ->orderBy('id', 'asc')
                    ->lockForUpdate()
                    ->get(),
                20 => DB::table('t_premidb')
                    ->where('status', 'disponibile')
                    ->where('valore', 20)
                    ->orderBy('id', 'asc')
                    ->lockForUpdate()
                    ->get(),
            ];

            foreach ([2, 5, 10, 20] as $value) {
                if ($groupedRewards[$value]->count() > $availableCoupons[$value]->count()) {
                    return [
                        'success' => false,
                        'status' => 409,
                        'message' => 'Codici Amazon insufficienti per completare il pagamento massivo.',
                    ];
                }
            }

            $csvLines = [];
            $csvLines[] = 'uid;email;valore;codice';

            foreach ([2, 5, 10, 20] as $value) {
                $rewardGroup = $groupedRewards[$value];
                $couponGroup = $availableCoupons[$value];

                foreach ($rewardGroup as $index => $reward) {
                    $coupon = $couponGroup[$index];

                    DB::table('t_user_history')
                        ->where('id', $reward->id)
                        ->update([
                            'codice2' => $coupon->codice,
                            'giorno_paga' => $paymentDate,
                            'pagato' => 1,
                        ]);

                    DB::table('t_premidb')
                        ->where('id', $coupon->id)
                        ->update([
                            'status' => 'pagato',
                            'pagamento' => $paymentDate,
                            'user' => $reward->user_id,
                        ]);

                    $csvLines[] = sprintf(
                        '%s;%s;%s;%s',
                        trim((string) $reward->user_id),
                        trim((string) ($reward->email ?? '')),
                        $value . ' euro',
                        trim((string) $coupon->codice)
                    );
                }
            }

            $filename = 'premiAmazon_' . $fileDate . '.csv';
            $relativePath = 'premi_exports/' . $filename;

            Storage::disk('public')->put($relativePath, implode("\n", $csvLines));

            return [
                'success' => true,
                'status' => 200,
                'message' => 'Pagamento massivo Amazon completato correttamente.',
                'filename' => $filename,
                'download_url' => route('premi.panel.download.export', ['filename' => $filename]),
                'processed_count' => $rewards->count(),
            ];
        });

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'filename' => $result['filename'] ?? null,
            'download_url' => $result['download_url'] ?? null,
            'processed_count' => $result['processed_count'] ?? 0,
        ], $result['status']);
    } catch (\Exception $e) {
        Log::error('Errore bulkPayAmazon premiPanel: ' . $e->getMessage());

        return response()->json([
            'success' => false,
            'message' => 'Errore durante il pagamento massivo Amazon.',
        ], 500);
    }
}

public function downloadExport($filename)
{
    $filename = basename($filename);
    $relativePath = 'premi_exports/' . $filename;

    if (!Storage::disk('public')->exists($relativePath)) {
        abort(404, 'File export non trovato.');
    }

    return Storage::disk('public')->download($relativePath, $filename, [
        'Content-Type' => 'text/csv; charset=UTF-8',
    ]);
}

public function exportPaypalMissingEmail(Request $request)
{
    $status = (string) $request->get('status', '0');

    $rows = DB::table('t_user_history as h')
        ->join('t_user_info as u', 'u.user_id', '=', 'h.user_id')
        ->select([
            'h.user_id',
            'u.email',
        ])
        ->where('h.event_type', 'withdraw')
        ->where('h.pagato', $status)
        ->where('h.event_info', 'like', '%Paypal%')
        ->where('u.active', 1)
        ->where(function ($query) {
            $query->whereNull('u.paypalEmail')
                  ->orWhere('u.paypalEmail', '');
        })
        ->orderBy('h.id', 'desc')
        ->distinct()
        ->get();

    $lines = [];
    $lines[] = 'uid;email;';

    foreach ($rows as $row) {
        $lines[] = sprintf(
            '%s;%s;',
            trim((string) $row->user_id),
            trim((string) ($row->email ?? ''))
        );
    }

    $filename = 'paypal_email_non_disponibili_' . now()->format('dmy') . '.csv';
    $content = implode("\n", $lines);

    return response($content, 200, [
        'Content-Type' => 'text/csv; charset=UTF-8',
        'Content-Disposition' => 'attachment; filename="' . $filename . '"',
    ]);
}

}
