<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TicketsController extends Controller
{
    public function index()
    {
        $tickets = DB::table('t_user_tickets as t')
            ->leftJoin('t_user_info as ui', 'ui.user_id', '=', 't.user_id')
            ->select([
                't.ticket_id',
                't.user_id',
                't.created_at',
                't.last_update',
                't.category',
                't.status',
                'ui.email',
            ])
           ->orderBy('t.status', 'asc')
            ->orderByDesc('t.ticket_id')
            ->get();

        $ticketCounters = [
            'new' => $tickets->where('status', 0)->count(),
            'working' => $tickets->where('status', 1)->count(),
            'closed' => $tickets->where('status', 2)->count(),
        ];

        return view('tickets.index', [
            'tickets' => $tickets,
            'ticketCounters' => $ticketCounters,
        ]);
    }

    public function detail($ticketId)
    {
        $ticket = DB::table('t_user_tickets as t')
            ->leftJoin('t_user_info as ui', 'ui.user_id', '=', 't.user_id')
            ->select([
                't.ticket_id',
                't.user_id',
                't.created_at',
                't.last_update',
                't.category',
                't.description',
                't.reply',
                't.status',
                'ui.first_name',
                'ui.second_name',
                'ui.email',
                'ui.active',
                'ui.confirm',
                'ui.points',
            ])
            ->where('t.ticket_id', $ticketId)
            ->first();

        if (!$ticket) {
            return response()->json([
                'success' => false,
                'message' => 'Ticket non trovato.',
            ], 404);
        }

        $withdraws = DB::table('t_user_history')
            ->select([
                'event_date',
                'codice2 as premio',
                'giorno_paga',
            ])
            ->where('user_id', $ticket->user_id)
            ->where('event_type', 'withdraw')
            ->orderByDesc('event_date')
            ->get();

        $suggestedReplies = $this->buildSuggestedReplies($ticket);

        $html = view('tickets.partials.detail', [
            'ticket' => $ticket,
            'withdraws' => $withdraws,
            'suggestedReplies' => $suggestedReplies,
            'userProfileUrl' => route('user.profile', ['user_id' => $ticket->user_id]),
        ])->render();

        return response()->json([
            'success' => true,
            'html' => $html,
        ]);
    }

public function update(Request $request, $ticketId)
{
    $validated = $request->validate([
        'reply' => ['nullable', 'string'],
        'status' => ['required', 'integer', 'in:0,1,2'],
    ]);

    $now = now();

    $updated = DB::table('t_user_tickets')
        ->where('ticket_id', $ticketId)
        ->update([
            'reply' => $validated['reply'] ?? null,
            'status' => (int) $validated['status'],
            'last_update' => $now,
        ]);

    if ($updated === 0) {
        $ticketExists = DB::table('t_user_tickets')
            ->where('ticket_id', $ticketId)
            ->exists();

        if (!$ticketExists) {
            return response()->json([
                'success' => false,
                'message' => 'Ticket non trovato.',
            ], 404);
        }
    }

    $status = (int) $validated['status'];

    $statusMap = [
        0 => [
            'label' => 'Da leggere',
            'badge_class' => 'badge-soft-secondary',
            'row_class' => 'ticket-row-new',
        ],
        1 => [
            'label' => 'In lavorazione',
            'badge_class' => 'badge-soft-warning',
            'row_class' => 'ticket-row-working',
        ],
        2 => [
            'label' => 'Chiuso',
            'badge_class' => 'badge-soft-success',
            'row_class' => 'ticket-row-closed',
        ],
    ];

    $meta = $statusMap[$status];

    return response()->json([
        'success' => true,
        'message' => 'Ticket aggiornato correttamente.',
        'ticket_id' => (int) $ticketId,
        'status' => $status,
        'status_label' => $meta['label'],
        'status_badge_class' => $meta['badge_class'],
        'status_badge_html' => '<span class="badge ' . $meta['badge_class'] . '">' . e($meta['label']) . '</span>',
        'row_class' => $meta['row_class'],
        'last_update' => $now->format('Y-m-d H:i:s'),
    ]);
}

    private function buildSuggestedReplies($ticket)
    {
        $ticketTopic = $this->detectTicketTopic(
            (string) ($ticket->category ?? ''),
            (string) ($ticket->description ?? '')
        );

        $candidates = DB::table('t_user_tickets as t')
            ->leftJoin('t_user_info as ui', 'ui.user_id', '=', 't.user_id')
            ->select([
                't.ticket_id',
                't.category',
                't.description',
                't.reply',
                't.last_update',
                'ui.first_name as source_first_name',
                'ui.second_name as source_second_name',
                'ui.email as source_email',
            ])
            ->where('t.ticket_id', '<>', $ticket->ticket_id)
            ->where('t.status', 2)
            ->whereNotNull('t.reply')
            ->where('t.reply', '<>', '')
            ->orderByDesc('t.last_update')
            ->limit(60)
            ->get();

        if ($candidates->isEmpty()) {
            $fallbackResults = $this->getFallbackRepliesByTopic($ticketTopic)
                ->map(function ($text) use ($ticket) {
                    $rendered = $this->renderSuggestedReplyTemplate($text, $ticket);

                    return [
                        'source_ticket_id' => null,
                        'title' => 'Suggerimento standard',
                        'reply' => $rendered,
                        'preview' => Str::limit($rendered, 160),
                        'score' => 999,
                        'last_update' => null,
                        'topic' => 'fallback',
                    ];
                })
                ->take(2)
                ->values();

            return $fallbackResults;
        }

        $currentDescriptionTokens = $this->extractMeaningfulTokens($ticket->description ?? '');
        $currentFullName = trim(($ticket->first_name ?? '') . ' ' . ($ticket->second_name ?? ''));

        $results = collect();

        foreach ($candidates as $candidate) {
            $candidateTopic = $this->detectTicketTopic(
                (string) ($candidate->category ?? ''),
                (string) ($candidate->description ?? '') . ' ' . (string) ($candidate->reply ?? '')
            );

            if (!$this->isReplyCompatibleWithTopic($ticketTopic, $candidateTopic)) {
                continue;
            }

            if ($this->containsSensitiveReplyData((string) $candidate->reply)) {
                continue;
            }

            $sanitizedTemplate = $this->sanitizeSuggestedReplyTemplate($candidate->reply, [
                'source_first_name' => $candidate->source_first_name ?? '',
                'source_second_name' => $candidate->source_second_name ?? '',
                'source_full_name' => trim(($candidate->source_first_name ?? '') . ' ' . ($candidate->source_second_name ?? '')),
                'source_email' => $candidate->source_email ?? '',
                'source_ticket_id' => $candidate->ticket_id ?? null,
                'current_first_name' => $ticket->first_name ?? '',
                'current_second_name' => $ticket->second_name ?? '',
                'current_full_name' => $currentFullName,
                'current_email' => $ticket->email ?? '',
                'current_user_id' => $ticket->user_id ?? '',
            ]);

            if ($sanitizedTemplate === '') {
                continue;
            }

            if ($this->containsSensitiveReplyData($sanitizedTemplate)) {
                continue;
            }

            $renderedReply = $this->renderSuggestedReplyTemplate($sanitizedTemplate, $ticket);

            if (mb_strlen(trim($renderedReply)) < 30) {
                continue;
            }

            if ($this->containsSensitiveReplyData($renderedReply, true)) {
                continue;
            }

            $candidateDescriptionTokens = $this->extractMeaningfulTokens($candidate->description ?? '');
            $commonTokensCount = count(array_intersect($currentDescriptionTokens, $candidateDescriptionTokens));

            $score = 0;

            if ($ticketTopic !== 'generic' && $candidateTopic === $ticketTopic) {
                $score += 80;
            } elseif ($ticketTopic === 'generic' && $candidateTopic === 'generic') {
                $score += 25;
            }

            $score += min($commonTokensCount * 8, 40);

            if ((string) ($candidate->category ?? '') === (string) ($ticket->category ?? '')) {
                $score += 15;
            }

            $replyLength = mb_strlen($renderedReply);
            if ($replyLength >= 80 && $replyLength <= 900) {
                $score += 15;
            } elseif ($replyLength >= 40 && $replyLength <= 1200) {
                $score += 8;
            }

            $results->push([
                'source_ticket_id' => (int) $candidate->ticket_id,
                'title' => 'Risposta simile da ticket #' . (int) $candidate->ticket_id,
                'reply' => $renderedReply,
                'preview' => Str::limit(trim(preg_replace('/\s+/u', ' ', $renderedReply)), 160),
                'score' => $score,
                'last_update' => $candidate->last_update,
                'topic' => $candidateTopic,
            ]);
        }

$historicalResults = $results
    ->filter(function ($item) use ($ticketTopic) {
        if ($ticketTopic === 'generic') {
            return true;
        }

        return ($item['topic'] ?? 'generic') === $ticketTopic;
    })
    ->unique('reply')
    ->sortByDesc(function ($item) {
        return sprintf('%05d_%s', $item['score'], $item['last_update']);
    })
    ->take(2)
    ->values();



$fallbackResults = $this->getFallbackRepliesByTopic($ticketTopic)
    ->map(function ($text) use ($ticket) {
        $rendered = $this->renderSuggestedReplyTemplate($text, $ticket);

        return [
            'source_ticket_id' => null,
            'title' => 'Suggerimento standard',
            'reply' => $rendered,
            'preview' => Str::limit($rendered, 160),
            'score' => 999,
            'last_update' => null,
            'topic' => 'fallback',
        ];
    })
    ->take(2)
    ->values();

return $fallbackResults
    ->merge($historicalResults)
    ->unique('reply')
    ->take(5)
    ->values();
    }

        private function detectTicketTopic($category, $text)
    {
        $haystack = mb_strtolower(trim($category . ' ' . $text));

        $topics = [
            'login_accesso' => [
                'credenziali', 'password', 'login', 'accesso', 'accedere', 'entrare',
                'account bloccato', 'reset password', 'non riesco ad entrare',
            ],
            'premi_pagamenti' => [
                'premio', 'premi', 'paypal', 'amazon', 'pagamento', 'pagato',
                'buono', 'voucher', 'codice premio', 'codice amazon',
            ],
            'punti_bonus' => [
                'punti', 'bonus', 'saldo punti', 'credito punti',
            ],
            'inviti_referral' => [
                'invita', 'invito', 'inviti', 'referral', 'amico', 'amici',
            ],
            'profilo_email' => [
                'profilo', 'email', 'mail', 'anagrafica', 'modifica email',
                'cambio email', 'dati profilo',
            ],
        ];

        foreach ($topics as $topic => $keywords) {
            foreach ($keywords as $keyword) {
                if (mb_strpos($haystack, $keyword) !== false) {
                    return $topic;
                }
            }
        }

        return 'generic';
    }

    private function isReplyCompatibleWithTopic($ticketTopic, $candidateTopic)
    {
        if ($ticketTopic === 'generic') {
            return true;
        }

        return $ticketTopic === $candidateTopic;
    }

    private function containsSensitiveReplyData($text, $allowCurrentPlaceholdersResolved = false)
    {
        $text = (string) $text;

        if (trim($text) === '') {
            return true;
        }

        $patterns = [
            '/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/iu',               // email
            '/\b[A-Z0-9]{4,}(?:-[A-Z0-9]{4,}){1,}\b/iu',                 // codici tipo ABCD-EFGH
            '/\b(?:codice|voucher|coupon|buono)\s*[:\-]?\s*[A-Z0-9\-]{6,}\b/iu',
            '/\bpaypal\b/iu',                                            // troppo specifico per suggerimenti riusabili
            '/\bamazon\b/iu',                                            // troppo specifico per suggerimenti riusabili
            '/\b[0-9]{3,}\s*punti\b/iu',                                 // punti specifici
            '/\b\d+(?:[.,]\d{1,2})?\s*euro\b/iu',                        // importi specifici
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text)) {
                return true;
            }
        }

        if (!$allowCurrentPlaceholdersResolved) {
            // nomi propri molto semplici dopo "ciao"
            if (preg_match('/\bciao\s+[A-ZÀ-Ú][a-zà-ú]{2,}\b/iu', $text)) {
                return true;
            }
        }

        return false;
    }

    private function getFallbackRepliesByTopic($topic)
{
    $fallbacks = [

        'login_accesso' => [
            'Ciao {first_name}, verifica di utilizzare le credenziali corrette. Se il problema persiste prova a effettuare il reset della password tramite la funzione "Password dimenticata".',
            'Ciao {first_name}, ti consiglio di provare a reimpostare la password. Se continui ad avere problemi di accesso facci sapere e verifichiamo noi.',
            'Ciao {first_name}, assicurati che email e password siano corrette. In caso contrario puoi recuperare l’accesso tramite il reset password.',
        ],

        'premi_pagamenti' => [
            'Ciao {first_name}, il tuo premio è in fase di elaborazione. Riceverai aggiornamenti non appena sarà completato.',
            'Ciao {first_name}, i tempi di gestione dei premi possono variare. Ti chiediamo di attendere, ti aggiorneremo appena possibile.',
            'Ciao {first_name}, abbiamo preso in carico la richiesta relativa al premio. Riceverai comunicazione non appena sarà disponibile.',
        ],

        'punti_bonus' => [
            'Ciao {first_name}, i punti vengono aggiornati automaticamente dal sistema dopo le attività completate. Se noti anomalie facci sapere.',
            'Ciao {first_name}, il saldo punti può richiedere un aggiornamento. Verifica più tardi oppure contattaci se il problema persiste.',
        ],

        'inviti_referral' => [
            'Ciao {first_name}, puoi invitare amici tramite l’apposita sezione del sito. Troverai tutte le informazioni nella tua area personale.',
            'Ciao {first_name}, il sistema inviti è disponibile nella tua dashboard. Se hai difficoltà facci sapere.',
        ],

        'profilo_email' => [
            'Ciao {first_name}, puoi aggiornare i tuoi dati accedendo alla sezione profilo del sito.',
            'Ciao {first_name}, se hai bisogno di modificare l’email o altri dati puoi farlo dalla tua area personale.',
        ],

        'generic' => [
            'Ciao {first_name}, abbiamo preso in carico la tua richiesta. Ti risponderemo nel più breve tempo possibile.',
            'Ciao {first_name}, grazie per averci contattato. Stiamo verificando la tua segnalazione.',
        ],
    ];

    return collect($fallbacks[$topic] ?? $fallbacks['generic']);
}

    private function sanitizeSuggestedReplyTemplate($reply, array $context = [])
    {
        $text = trim((string) $reply);

        if ($text === '') {
            return '';
        }

        $text = preg_replace("/\r\n|\r/u", "\n", $text);

        // Email generiche
        $text = preg_replace('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/iu', '{email}', $text);
            // Importi e punti troppo specifici: meglio non proporli
        $text = preg_replace('/\b\d+(?:[.,]\d{1,2})?\s*euro\b/iu', '{amount}', $text);
        $text = preg_replace('/\b[0-9]{3,}\s*punti\b/iu', '{points}', $text);

        // Codici alfanumerici strutturati
        $text = preg_replace('/\b[A-Z0-9]{4,}(?:-[A-Z0-9]{4,}){1,}\b/iu', '{code}', $text);

        // User ID correnti/storici se presenti nel testo
        foreach ([
            $context['current_user_id'] ?? '',
        ] as $userId) {
            $userId = trim((string) $userId);
            if ($userId !== '') {
                $text = str_replace($userId, '{user_id}', $text);
            }
        }

        // Nomi completi / nome singolo
        $replaceMap = [
            trim((string) ($context['source_full_name'] ?? '')) => '{full_name}',
            trim((string) ($context['current_full_name'] ?? '')) => '{full_name}',
            trim((string) ($context['source_first_name'] ?? '')) => '{first_name}',
            trim((string) ($context['current_first_name'] ?? '')) => '{first_name}',
        ];

        foreach ($replaceMap as $needle => $replacement) {
            if ($needle === '' || mb_strlen($needle) < 2) {
                continue;
            }

            $pattern = '/\b' . preg_quote($needle, '/') . '\b/iu';
            $text = preg_replace($pattern, $replacement, $text);
        }

        // Ticket id espressi nel testo
        if (!empty($context['source_ticket_id'])) {
            $sourceTicketId = (string) $context['source_ticket_id'];
            $text = preg_replace('/ticket\s*#?\s*' . preg_quote($sourceTicketId, '/') . '/iu', 'ticket #{ticket_id}', $text);
            $text = str_replace('#' . $sourceTicketId, '#{ticket_id}', $text);
        }

        // Compattazione spazi e righe vuote eccessive
        $text = preg_replace("/\n{3,}/u", "\n\n", $text);
        $text = trim($text);

        return $text;
    }

    private function renderSuggestedReplyTemplate($template, $ticket)
    {
        $fullName = trim(($ticket->first_name ?? '') . ' ' . ($ticket->second_name ?? ''));

        $replacements = [
            '{first_name}' => trim((string) ($ticket->first_name ?? '')) !== '' ? $ticket->first_name : 'utente',
            '{full_name}' => $fullName !== '' ? $fullName : 'utente',
            '{email}' => trim((string) ($ticket->email ?? '')) !== '' ? $ticket->email : '{email}',
            '{user_id}' => trim((string) ($ticket->user_id ?? '')) !== '' ? $ticket->user_id : '{user_id}',
            '{category}' => trim((string) ($ticket->category ?? '')) !== '' ? $ticket->category : '{category}',
            '{ticket_id}' => (string) $ticket->ticket_id,

            // placeholder troppo specifici: lasciamoli testuali o neutrali
            '{amount}' => 'l’importo previsto',
            '{points}' => 'i punti disponibili',
            '{code}' => 'il codice previsto',
        ];

        return strtr($template, $replacements);
    }

    private function extractMeaningfulTokens($text)
    {
        $text = mb_strtolower((string) $text);
        $text = preg_replace('/[^a-z0-9àèéìòù_\-\s]/iu', ' ', $text);
        $parts = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);

        $stopWords = [
            'ciao', 'buongiorno', 'salve', 'grazie', 'ticket', 'problema',
            'richiesta', 'utente', 'email', 'sono', 'della', 'dello', 'degli',
            'delle', 'che', 'per', 'con', 'una', 'uno', 'del', 'alla', 'alle',
            'agli', 'allo', 'nel', 'nella', 'nelle', 'il', 'lo', 'la', 'un',
            'ho', 'hai', 'abbiamo', 'avete', 'sono', 'come', 'puo', 'puoi',
        ];

        $tokens = [];

        foreach ($parts as $part) {
            if (mb_strlen($part) < 3) {
                continue;
            }

            if (in_array($part, $stopWords, true)) {
                continue;
            }

            $tokens[] = $part;
        }

        return array_values(array_unique($tokens));
    }

}
