<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Str;

class ReferralController extends Controller
{
    public function index()
    {
        $referrals = DB::table('t_user_info as info')
            ->join('t_user_info as ref', function ($join) {
                $join->on('ref.user_id', '=', 'info.provenienza')
                    ->where('ref.active', '=', 1);
            })
            ->whereNotNull('info.provenienza')
            ->where('info.active', '=', 1)
            ->groupBy(
                'ref.user_id',
                'ref.email',
                'ref.home_phone',
                'ref.id_bacheca'
            )
            ->orderByRaw('(COALESCE(ref.home_phone, 0) - COALESCE(ref.id_bacheca, 0)) DESC')
            ->select([
                'ref.user_id as ref_user_id',
                'ref.email as ref_email',
                DB::raw('COUNT(info.user_id) as inviti'),
                DB::raw('SUM(CASE WHEN info.active = 1 THEN 1 ELSE 0 END) as iscritti'),
                DB::raw('SUM(CASE WHEN info.actions > 0 THEN 1 ELSE 0 END) as attivi'),
                DB::raw('COALESCE(ref.home_phone, 0) as bonus_maturato'),
                DB::raw('COALESCE(ref.id_bacheca, 0) as bonus_pagato'),
                DB::raw('(COALESCE(ref.home_phone, 0) - COALESCE(ref.id_bacheca, 0)) as bonus_rimanente'),
            ])
            ->get();

        $summary = [
            'inviti' => (int) $referrals->sum('inviti'),
            'iscritti' => (int) $referrals->sum('iscritti'),
            'attivi' => (int) $referrals->sum('attivi'),
            'maturato' => (int) $referrals->sum('bonus_maturato'),
            'pagato' => (int) $referrals->sum('bonus_pagato'),
            'rimanente' => (int) $referrals->sum('bonus_rimanente'),
            'referrers' => (int) $referrals->count(),
        ];

        /*
         * Contatori dedicati tabella portaamico
         * Query singola aggregata
         */
        $inviteCounters = DB::table('portaamico')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as new_count')
            ->selectRaw('SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as exported_count')
            ->first();

        return view('referral.index', [
            'referrals' => $referrals,
            'summary' => $summary,
            'inviteCounters' => [
                'total' => (int) ($inviteCounters->total ?? 0),
                'new' => (int) ($inviteCounters->new_count ?? 0),
                'exported' => (int) ($inviteCounters->exported_count ?? 0),
            ],
        ]);
    }

     public function detail($refUserId)
    {
        $referrer = DB::table('t_user_info')
            ->select('user_id', 'email')
            ->where('user_id', $refUserId)
            ->first();

        if (!$referrer) {
            return response()->json([
                'success' => false,
                'message' => 'Referrer non trovato.',
            ], 404);
        }

        $referrerEmail = (string) ($referrer->email ?? '');

        $invitedUsers = DB::table('t_user_info')
            ->select([
                'user_id',
                'email',
                'reg_date',
                'active',
                'actions',
            ])
            ->where('provenienza', $refUserId)
            ->where('active', 1)
            ->orderByDesc('reg_date')
            ->get()
            ->map(function ($user) use ($referrerEmail) {
                $emailCheck = $this->evaluateReferralEmail(
                    (string) ($user->email ?? ''),
                    $referrerEmail
                );

                $user->email_check = $emailCheck;

                return $user;
            });

        $html = view('referral.partials.detail', [
            'referrer' => $referrer,
            'invitedUsers' => $invitedUsers,
        ])->render();

        return response()->json([
            'success' => true,
            'html' => $html,
        ]);
    }

    public function removeReferral(Request $request)
    {
        $validated = $request->validate([
            'uid' => ['required', 'string', 'max:10'],
            'ref' => ['required', 'string', 'max:10'],
        ]);

        $uid = $validated['uid'];
        $ref = $validated['ref'];

        $result = DB::transaction(function () use ($uid, $ref) {
            $invitedUser = DB::table('t_user_info')
                ->select('user_id', 'provenienza', 'active', 'actions')
                ->where('user_id', $uid)
                ->where('provenienza', $ref)
                ->lockForUpdate()
                ->first();

            if (!$invitedUser) {
                return [
                    'success' => false,
                    'message' => 'Relazione referral non trovata.',
                    'status' => 404,
                ];
            }

            $referrer = DB::table('t_user_info')
                ->select('user_id', 'home_phone', 'id_bacheca')
                ->where('user_id', $ref)
                ->lockForUpdate()
                ->first();

            if (!$referrer) {
                return [
                    'success' => false,
                    'message' => 'Referrer non trovato.',
                    'status' => 404,
                ];
            }

            $delta = 0;

            if ((int) $invitedUser->active === 1) {
                $delta += 50;
            }

            if ((int) $invitedUser->actions > 0) {
                $delta += 250;
            }

            DB::table('t_user_info')
                ->where('user_id', $uid)
                ->where('provenienza', $ref)
                ->update([
                    'provenienza' => null,
                ]);

            $currentMaturato = (int) $referrer->home_phone;
            $currentPagato = (int) $referrer->id_bacheca;

            $newMaturato = max(0, $currentMaturato - $delta);
            $newPagato = min($currentPagato, $newMaturato);

            DB::table('t_user_info')
                ->where('user_id', $ref)
                ->update([
                    'home_phone' => $newMaturato,
                    'id_bacheca' => $newPagato,
                ]);

            $updatedAggregate = DB::table('t_user_info as info')
                ->join('t_user_info as referrer', 'referrer.user_id', '=', 'info.provenienza')
                ->where('info.provenienza', $ref)
                ->where('info.active', 1)
                ->select([
                    DB::raw('COUNT(info.user_id) as inviti'),
                    DB::raw('SUM(CASE WHEN info.active = 1 THEN 1 ELSE 0 END) as iscritti'),
                    DB::raw('SUM(CASE WHEN info.actions > 0 THEN 1 ELSE 0 END) as attivi'),
                ])
                ->first();

            $inviti = (int) ($updatedAggregate->inviti ?? 0);
            $iscritti = (int) ($updatedAggregate->iscritti ?? 0);
            $attivi = (int) ($updatedAggregate->attivi ?? 0);
            $rimanente = max(0, $newMaturato - $newPagato);

            return [
                'success' => true,
                'message' => 'Referral rimosso correttamente.',
                'delta_bonus' => $delta,
                'updated_row' => [
                    'ref_user_id' => $ref,
                    'inviti' => $inviti,
                    'iscritti' => $iscritti,
                    'attivi' => $attivi,
                    'bonus_maturato' => $newMaturato,
                    'bonus_pagato' => $newPagato,
                    'bonus_rimanente' => $rimanente,
                ],
            ];
        });

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], $result['status']);
        }

        return response()->json($result);
    }

    public function assignBonus(Request $request)
    {
        $validated = $request->validate([
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['required', 'string', 'max:10'],
        ]);

        $userIds = collect($validated['user_ids'])
            ->map(function ($value) {
                return trim((string) $value);
            })
            ->filter()
            ->unique()
            ->values();

        if ($userIds->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Nessun utente selezionato.',
            ], 422);
        }

        $updatedRows = [];
        $updatedCount = 0;
        $totalAssigned = 0;

        DB::transaction(function () use ($userIds, &$updatedRows, &$updatedCount, &$totalAssigned) {
            foreach ($userIds as $uid) {
                $user = DB::table('t_user_info')
                    ->select('user_id', 'home_phone', 'id_bacheca', 'points')
                    ->where('user_id', $uid)
                    ->lockForUpdate()
                    ->first();

                if (!$user) {
                    continue;
                }

                $maturato = (int) $user->home_phone;
                $pagato = (int) $user->id_bacheca;
                $points = (int) $user->points;

                $rim = $maturato - $pagato;

                if ($rim <= 0) {
                    continue;
                }

                $newPoints = $points + $rim;
                $newPagato = $pagato + $rim;

                DB::table('t_user_info')
                    ->where('user_id', $uid)
                    ->update([
                        'points' => $newPoints,
                        'id_bacheca' => $newPagato,
                    ]);

                DB::table('t_user_history')->insert([
                    'user_id' => $uid,
                    'event_date' => now(),
                    'event_type' => 'bonus_amico',
                    'event_info' => 'bonus referral',
                    'prev_level' => $points,
                    'new_level' => $newPoints,
                    'pagato' => 0,
                ]);

                $updatedRows[] = [
                    'ref_user_id' => $uid,
                    'bonus_pagato' => $newPagato,
                    'bonus_rimanente' => 0,
                    'assigned_points' => $rim,
                ];

                $updatedCount++;
                $totalAssigned += $rim;
            }
        });

        if ($updatedCount === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Nessun bonus da assegnare.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $updatedCount === 1
                ? 'Bonus assegnato correttamente.'
                : 'Bonus assegnato agli utenti selezionati.',
            'updated_count' => $updatedCount,
            'total_assigned' => $totalAssigned,
            'updated_rows' => $updatedRows,
        ]);
    }


    public function recalculateMaturato()
    {
        /*
         * Regola storica implicita ricostruita dal vecchio remove referral:
         * - +50 se invitato active = 1
         * - +250 se invitato con actions > 0
         *
         * Fonte:
         * ajax_bonus_remove_referral.php
         */

        $aggregates = DB::table('t_user_info as info')
            ->join('t_user_info as ref', function ($join) {
                $join->on('ref.user_id', '=', 'info.provenienza')
                    ->where('ref.active', '=', 1);
            })
            ->whereNotNull('info.provenienza')
            ->where('info.active', '=', 1)
            ->groupBy('ref.user_id')
            ->select([
                'ref.user_id as ref_user_id',
                DB::raw('(COUNT(info.user_id) * 50) + SUM(CASE WHEN info.actions > 0 THEN 250 ELSE 0 END) as new_maturato'),
            ])
            ->get();

        if ($aggregates->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'Nessun referral da aggiornare.',
                'updated_count' => 0,
                'totals' => [
                    'maturato' => 0,
                    'pagato' => 0,
                    'rimanente' => 0,
                ],
                'updated_rows' => [],
            ]);
        }

        $updatedRows = [];
        $updatedCount = 0;

               DB::transaction(function () use ($aggregates, &$updatedRows, &$updatedCount) {
            foreach ($aggregates as $item) {
                $refUserId = (string) $item->ref_user_id;
                $newMaturato = (int) $item->new_maturato;

                $referrer = DB::table('t_user_info')
                    ->select('user_id', 'home_phone', 'id_bacheca')
                    ->where('user_id', $refUserId)
                    ->lockForUpdate()
                    ->first();

                if (!$referrer) {
                    continue;
                }

                $currentPagato = (int) $referrer->id_bacheca;
                $newPagato = min($currentPagato, $newMaturato);

                DB::table('t_user_info')
                    ->where('user_id', $refUserId)
                    ->update([
                        'home_phone' => $newMaturato,
                        'id_bacheca' => $newPagato,
                    ]);

                $aggregateRow = DB::table('t_user_info as info')
                    ->join('t_user_info as ref', 'ref.user_id', '=', 'info.provenienza')
                    ->where('info.provenienza', $refUserId)
                    ->where('info.active', 1)
                    ->select([
                        DB::raw('COUNT(info.user_id) as inviti'),
                        DB::raw('SUM(CASE WHEN info.active = 1 THEN 1 ELSE 0 END) as iscritti'),
                        DB::raw('SUM(CASE WHEN info.actions > 0 THEN 1 ELSE 0 END) as attivi'),
                    ])
                    ->first();

                $updatedRows[] = [
                    'ref_user_id' => $refUserId,
                    'inviti' => (int) ($aggregateRow->inviti ?? 0),
                    'iscritti' => (int) ($aggregateRow->iscritti ?? 0),
                    'attivi' => (int) ($aggregateRow->attivi ?? 0),
                    'bonus_maturato' => $newMaturato,
                    'bonus_pagato' => $newPagato,
                    'bonus_rimanente' => max(0, $newMaturato - $newPagato),
                ];

                $updatedCount++;
            }
        });

        $totalMaturato = collect($updatedRows)->sum('bonus_maturato');
        $totalPagato = collect($updatedRows)->sum('bonus_pagato');
        $totalRimanente = collect($updatedRows)->sum('bonus_rimanente');

        return response()->json([
            'success' => true,
            'message' => 'Maturato referral aggiornato correttamente.',
            'updated_count' => $updatedCount,
            'totals' => [
                'maturato' => (int) $totalMaturato,
                'pagato' => (int) $totalPagato,
                'rimanente' => (int) $totalRimanente,
            ],
            'updated_rows' => $updatedRows,
        ]);
    }




    public function exportNewInvites()
    {
        return $this->exportInvitesByStatus(0, true);
    }

    public function exportAllInvites()
    {
        return $this->exportInvitesByStatus(null, false);
    }

        private function exportInvitesByStatus($status = null, bool $markAsExported = false)
    {
        $query = DB::table('portaamico as pa')
            ->join('t_user_info as ui', 'ui.user_id', '=', 'pa.uid_invitante')
            ->select([
                'pa.idPortaAmico',
                'pa.email_invitato',
                'ui.email as email_invitante',
                'ui.user_id as uid',
            ]);

        if ($status !== null) {
            $query->where('pa.status', $status);
        }

        $rows = $query
            ->orderBy('pa.idPortaAmico', 'asc')
            ->get();

        if ($rows->isEmpty()) {
            return redirect()
                ->route('referral.index')
                ->with('referral_export_error', 'Nessun dato da esportare.');
        }

        $idsToUpdate = $rows->pluck('idPortaAmico')
            ->map(function ($id) {
                return (int) $id;
            })
            ->unique()
            ->values()
            ->all();

        $filename = $status === 0
            ? 'porta_amico_nuovi_' . now()->format('Ymd_His') . '.csv'
            : 'porta_amico_tutti_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($rows, $markAsExported, $idsToUpdate) {
            $handle = fopen('php://output', 'w');

            /*
             * BOM UTF-8 per Excel
             */
            fwrite($handle, chr(239) . chr(187) . chr(191));

            fwrite($handle, "email_invitato;email_invitante;uid\n");

            foreach ($rows as $row) {
                $line = [
                    (string) ($row->email_invitato ?? ''),
                    (string) ($row->email_invitante ?? ''),
                    (string) ($row->uid ?? ''),
                ];

                fwrite($handle, implode(';', $line) . "\n");
            }

            fclose($handle);

            if ($markAsExported && !empty($idsToUpdate)) {
                DB::table('portaamico')
                    ->where('status', 0)
                    ->whereIn('idPortaAmico', $idsToUpdate)
                    ->update([
                        'status' => 1,
                    ]);
            }
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

        public function exportBonusReport(): StreamedResponse
    {
        $rows = DB::table('t_user_info as info')
            ->join('t_user_info as ref', function ($join) {
                $join->on('ref.user_id', '=', 'info.provenienza')
                    ->where('ref.active', '=', 1);
            })
            ->whereNotNull('info.provenienza')
            ->where('info.active', '=', 1)
            ->groupBy(
                'ref.user_id',
                'ref.email',
                'ref.home_phone',
                'ref.id_bacheca'
            )
            ->orderByRaw('(COALESCE(ref.home_phone, 0) - COALESCE(ref.id_bacheca, 0)) DESC')
            ->select([
                'ref.user_id as ref_user_id',
                'ref.email as ref_email',
                DB::raw('COUNT(info.user_id) as inviti'),
                DB::raw('SUM(CASE WHEN info.active = 1 THEN 1 ELSE 0 END) as iscritti'),
                DB::raw('SUM(CASE WHEN info.actions > 0 THEN 1 ELSE 0 END) as attivi'),
                DB::raw('COALESCE(ref.home_phone, 0) as bonus_maturato'),
                DB::raw('COALESCE(ref.id_bacheca, 0) as bonus_pagato'),
                DB::raw('(COALESCE(ref.home_phone, 0) - COALESCE(ref.id_bacheca, 0)) as bonus_rimanente'),
            ])
            ->get();

        $filename = 'bonus_referral_report_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');

            fwrite($handle, chr(239) . chr(187) . chr(191));
            fwrite($handle, "user_id;email;inviti;iscritti;attivi;bonus_maturato;bonus_pagato;bonus_rimanente\n");

            foreach ($rows as $row) {
                $line = [
                    (string) ($row->ref_user_id ?? ''),
                    (string) ($row->ref_email ?? ''),
                    (string) ((int) ($row->inviti ?? 0)),
                    (string) ((int) ($row->iscritti ?? 0)),
                    (string) ((int) ($row->attivi ?? 0)),
                    (string) ((int) ($row->bonus_maturato ?? 0)),
                    (string) ((int) ($row->bonus_pagato ?? 0)),
                    (string) ((int) ($row->bonus_rimanente ?? 0)),
                ];

                fwrite($handle, implode(';', $line) . "\n");
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

        private function evaluateReferralEmail(string $email, string $referrerEmail = ''): array
    {
        $email = trim($email);
        $lower = Str::lower($email);

        if ($email === '') {
            return [
                'status' => 'red',
                'label' => 'Email mancante',
                'is_valid' => false,
            ];
        }

        $blockedExact = [
            'test@example.com',
            'example@example.com',
            'admin@example.com',
        ];

        if (in_array($lower, $blockedExact, true)) {
            return [
                'status' => 'red',
                'label' => 'Placeholder',
                'is_valid' => false,
            ];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'status' => 'red',
                'label' => 'Formato non valido',
                'is_valid' => false,
            ];
        }

        $domain = Str::lower((string) substr(strrchr($email, '@'), 1));
        $local = Str::lower((string) strstr($email, '@', true));

        if (in_array($domain, ['example.com', 'example.net', 'example.org'], true)) {
            return [
                'status' => 'red',
                'label' => 'Dominio example.*',
                'is_valid' => false,
            ];
        }

        if (in_array($local, ['test', 'example'], true)) {
            return [
                'status' => 'red',
                'label' => 'Local sospetto',
                'is_valid' => false,
            ];
        }

        $tempDomains = [
            'mailinator.com',
            '10minutemail.com',
            'guerrillamail.com',
            'tempmail.com',
            'yopmail.com',
        ];

        if (in_array($domain, $tempDomains, true)) {
            return [
                'status' => 'red',
                'label' => 'Email temporanea',
                'is_valid' => false,
            ];
        }

        $referrerEmail = trim($referrerEmail);
        if ($referrerEmail !== '') {
            similar_text(Str::lower($email), Str::lower($referrerEmail), $percent);

            if ($percent >= 85) {
                return [
                    'status' => 'yellow',
                    'label' => 'Molto simile al referrer',
                    'is_valid' => true,
                ];
            }
        }

        if ($domain !== '' && function_exists('checkdnsrr') && !checkdnsrr($domain, 'MX')) {
            return [
                'status' => 'red',
                'label' => 'Dominio senza MX',
                'is_valid' => false,
            ];
        }

        return [
            'status' => 'green',
            'label' => 'OK',
            'is_valid' => true,
        ];
    }

}
