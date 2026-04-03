@php
    $fullName = trim(($ticket->first_name ?? '') . ' ' . ($ticket->second_name ?? ''));
    $fullName = $fullName !== '' ? $fullName : 'Utente senza nominativo';

    $statusLabel = 'Da leggere';
    $statusBadgeClass = 'badge-soft-secondary';
    $statusIcon = 'bi-envelope';

    if ((int) $ticket->status === 1) {
        $statusLabel = 'In lavorazione';
        $statusBadgeClass = 'badge-soft-warning';
        $statusIcon = 'bi-hourglass-split';
    } elseif ((int) $ticket->status === 2) {
        $statusLabel = 'Chiuso';
        $statusBadgeClass = 'badge-soft-success';
        $statusIcon = 'bi-check-circle';
    }
@endphp

<div class="ticket-detail-wrapper ticket-detail-modern">
    <div class="ticket-detail-hero mb-4">
        <div class="ticket-detail-hero-main">
            <div class="ticket-detail-kicker">
                <i class="bi bi-life-preserver me-2"></i>Dettaglio ticket
            </div>

            <div class="d-flex align-items-center flex-wrap gap-2 mb-2">
                <h4 class="mb-0">#{{ $ticket->ticket_id }}</h4>

                <span class="badge {{ $statusBadgeClass }} ticket-status-pill" id="ticket-detail-main-status-badge">
                    <i class="bi {{ $statusIcon }} me-1"></i>{{ $statusLabel }}
                </span>

                <span class="badge ticket-category-badge">
                    <i class="bi bi-tag me-1"></i>{{ $ticket->category }}
                </span>
            </div>

            <div class="ticket-detail-meta-row">
                <span class="ticket-meta-chip">
                    <i class="bi bi-calendar-event me-2"></i>
                    <strong>Creato:</strong>&nbsp;{{ $ticket->created_at }}
                </span>

                <span class="ticket-meta-chip">
                    <i class="bi bi-clock-history me-2"></i>
                    <strong>Ultimo aggiornamento:</strong>&nbsp;
                    <span id="ticket-detail-last-update">{{ $ticket->last_update }}</span>
                </span>
            </div>
        </div>
    </div>

        <div class="ticket-detail-grid ticket-detail-grid-2col">

        <div class="ticket-grid-col ticket-grid-col-half">
            <div class="card ticket-detail-card ticket-section-card ticket-section-user h-100">
                <div class="card-header ticket-section-header">
                    <span class="ticket-section-icon">
                        <i class="bi bi-person-circle"></i>
                    </span>
                    <strong>Utente</strong>
                </div>

                <div class="card-body">
                    <div class="ticket-user-topbar">
                        <div class="ticket-user-identity">
                            <div class="ticket-user-name">{{ $fullName }}</div>
                            <div class="ticket-user-line">
                                <i class="bi bi-envelope me-2"></i>{{ $ticket->email ?: '-' }}
                            </div>
                            <div class="ticket-user-line">
                                <i class="bi bi-hash me-2"></i>User ID: {{ $ticket->user_id }}
                            </div>
                            <div class="ticket-user-line">
                                <i class="bi bi-star me-2"></i>Punti attuali:
                                <strong>{{ (int) ($ticket->points ?? 0) }}</strong>
                            </div>
                        </div>

                        <div class="ticket-user-actions">
                            <a
                                href="{{ $userProfileUrl }}"
                                target="_blank"
                                rel="noopener"
                                class="btn btn-sm btn-outline-primary"
                            >
                                <i class="bi bi-box-arrow-up-right me-1"></i>Apri profilo
                            </a>
                        </div>
                    </div>

                    <div class="ticket-user-badges mt-3">
                        @if((int) ($ticket->active ?? 0) === 1)
                            <span class="badge bg-success">
                                <i class="bi bi-check-circle me-1"></i>Attivo
                            </span>
                        @else
                            <span class="badge bg-danger">
                                <i class="bi bi-slash-circle me-1"></i>Inattivo
                            </span>
                        @endif

                        @if((int) ($ticket->confirm ?? 0) === 1)
                            <span class="badge bg-info text-dark">
                                <i class="bi bi-patch-check me-1"></i>Email confermata
                            </span>
                        @else
                            <span class="badge bg-secondary">
                                <i class="bi bi-exclamation-circle me-1"></i>Email non confermata
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="ticket-grid-col ticket-grid-col-half">
            <div class="card ticket-detail-card ticket-section-card h-100">
                <div class="card-header ticket-section-header">
                    <span class="ticket-section-icon">
                        <i class="bi bi-gift"></i>
                    </span>
                    <strong>Premi richiesti</strong>
                </div>

                <div class="card-body p-0">
                    @if($withdraws->isNotEmpty())
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0 ticket-detail-table-modern">
                                <thead class="table-light">
                                    <tr>
                                        <th>Data richiesta</th>
                                        <th>Premio</th>
                                        <th>Giorno paga</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($withdraws as $withdraw)
                                        <tr>
                                            <td>{{ $withdraw->event_date }}</td>
                                            <td>{{ $withdraw->premio ?: '-' }}</td>
                                            <td>{{ $withdraw->giorno_paga ?: '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="ticket-empty-state">
                            <i class="bi bi-inbox me-2"></i>Nessun premio richiesto
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="ticket-grid-col ticket-grid-col-full">
            <div class="card ticket-detail-card ticket-section-card">
                <div class="card-header ticket-section-header">
                    <span class="ticket-section-icon">
                        <i class="bi bi-chat-left-text"></i>
                    </span>
                    <strong>Messaggio dell’utente</strong>
                </div>

                <div class="card-body ticket-message-body ticket-message-panel">
                    {{ $ticket->description }}
                </div>
            </div>
        </div>

        <div class="ticket-grid-col ticket-grid-col-full">
            <div class="card ticket-detail-card ticket-section-card">
                <div class="card-header ticket-section-header">
                    <span class="ticket-section-icon">
                        <i class="bi bi-lightning-charge"></i>
                    </span>
                    <strong>Risposte rapide suggerite</strong>
                </div>

                <div class="card-body">
                    @if(($suggestedReplies ?? collect())->isNotEmpty())
                        <div class="ticket-suggestions-list ticket-suggestions-grid">
                            @foreach($suggestedReplies as $suggestion)
                                <div class="ticket-suggestion-item">
                                    <div class="ticket-suggestion-head">
                                        <div class="ticket-suggestion-title">
                                            <strong>
                                                {{ $suggestion['title'] }}
                                                @if(empty($suggestion['source_ticket_id']))
                                                    <span class="badge bg-secondary ms-2">Standard</span>
                                                @endif
                                            </strong>
                                        </div>
                                    </div>

                                    <div
                                        class="ticket-suggestion-preview ticket-suggestion-preview-clamped"
                                        data-bs-toggle="tooltip"
                                        data-bs-placement="top"
                                        title="{{ $suggestion['reply'] }}"
                                    >
                                        {{ $suggestion['preview'] }}
                                    </div>

                                    <div class="d-flex flex-wrap gap-2 mt-3">
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-primary btn-use-suggested-reply"
                                            data-mode="replace"
                                            data-reply-b64="{{ base64_encode($suggestion['reply']) }}"
                                        >
                                            <i class="bi bi-arrow-repeat me-1"></i>Sostituisci
                                        </button>

                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-secondary btn-use-suggested-reply"
                                            data-mode="append"
                                            data-reply-b64="{{ base64_encode($suggestion['reply']) }}"
                                        >
                                            <i class="bi bi-plus-circle me-1"></i>Aggiungi
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="ticket-empty-state">
                            <i class="bi bi-search me-2"></i>Nessun suggerimento coerente disponibile per questo ticket.
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="ticket-grid-col ticket-grid-col-full">
            <div class="card ticket-detail-card ticket-section-card ticket-reply-card">
                <div class="card-header ticket-section-header">
                    <span class="ticket-section-icon">
                        <i class="bi bi-pencil-square"></i>
                    </span>
                    <strong>Risposta operatore</strong>
                </div>

                <div class="card-body">
                    <textarea
                        id="ticket-reply"
                        class="form-control ticket-reply-textarea mb-3"
                        rows="6"
                        placeholder="Scrivi qui la risposta..."
                        data-initial-value="{{ e($ticket->reply ?? '') }}"
                    >{{ $ticket->reply }}</textarea>

                    <div class="ticket-operator-actions ticket-operator-toolbar">
                        <div class="ticket-status-select-wrap">
                            <label for="ticket-status" class="ticket-inline-label">Stato ticket</label>
                            <select
                                id="ticket-status"
                                class="form-select"
                                style="max-width: 240px;"
                                data-initial-value="{{ (int) $ticket->status }}"
                            >
                                <option value="0" {{ (int) $ticket->status === 0 ? 'selected' : '' }}>Da leggere</option>
                                <option value="1" {{ (int) $ticket->status === 1 ? 'selected' : '' }}>In lavorazione</option>
                                <option value="2" {{ (int) $ticket->status === 2 ? 'selected' : '' }}>Chiuso</option>
                            </select>
                        </div>

                        <button
                            type="button"
                            class="btn btn-primary ticket-save-btn"
                            id="save-ticket-btn"
                            data-ticket-id="{{ $ticket->ticket_id }}"
                        >
                            <i class="bi bi-save me-1"></i>Salva modifiche
                        </button>
                    </div>

                    <div id="ticket-detail-message" class="mt-3"></div>
                </div>
            </div>
        </div>

    </div>
</div>
