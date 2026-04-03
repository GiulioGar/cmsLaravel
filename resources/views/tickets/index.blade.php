@extends('layouts.main')

@section('content')
<link rel="stylesheet" href="{{ asset('css/tickets.css') }}">

<div class="container-fluid mt-4">
    <div class="tickets-page-hero mb-4">
        <div class="tickets-page-hero-content">
            <div class="tickets-page-kicker">
                <i class="bi bi-life-preserver me-2"></i>Support center
            </div>

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h2 class="mb-1 tickets-page-title">Gestione Tickets</h2>
                    <p class="text-muted mb-0">Consultazione, filtro e gestione operativa delle richieste utenti</p>
                </div>

                <div class="tickets-page-hero-badge">
                    <i class="bi bi-lightning-charge me-2"></i>Backoffice operativo
                </div>
            </div>
        </div>
    </div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card tickets-counter-card tickets-counter-card-compact counter-new h-100">
            <div class="card-body">
                <div class="tickets-counter-compact-row">
                    <div class="tickets-counter-compact-left">
                        <div class="counter-icon counter-icon-new">
                            <i class="bi bi-envelope"></i>
                        </div>
                        <div>
                            <div class="counter-label mb-0">Da leggere</div>
                            <div class="counter-subtext counter-subtext-compact">In attesa</div>
                        </div>
                    </div>

                    <div class="counter-value counter-value-compact" id="count-new">{{ $ticketCounters['new'] ?? 0 }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card tickets-counter-card tickets-counter-card-compact counter-working h-100">
            <div class="card-body">
                <div class="tickets-counter-compact-row">
                    <div class="tickets-counter-compact-left">
                        <div class="counter-icon counter-icon-working">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                        <div>
                            <div class="counter-label mb-0">In lavorazione</div>
                            <div class="counter-subtext counter-subtext-compact">Aperti</div>
                        </div>
                    </div>

                    <div class="counter-value counter-value-compact" id="count-working">{{ $ticketCounters['working'] ?? 0 }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card tickets-counter-card tickets-counter-card-compact counter-closed h-100">
            <div class="card-body">
                <div class="tickets-counter-compact-row">
                    <div class="tickets-counter-compact-left">
                        <div class="counter-icon counter-icon-closed">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <div>
                            <div class="counter-label mb-0">Chiusi</div>
                            <div class="counter-subtext counter-subtext-compact">Completati</div>
                        </div>
                    </div>

                    <div class="counter-value counter-value-compact" id="count-closed">{{ $ticketCounters['closed'] ?? 0 }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

        <div class="card tickets-filters-card shadow-sm mb-4">

        <div class="card-body tickets-filter-card-body">
            <div class="row g-3 align-items-end">
                <div class="col-lg-7">
                                        <div class="filter-group-label mb-2">
                        <i class="bi bi-funnel me-2"></i>Filtra per status
                    </div>
                    <div class="d-flex flex-wrap gap-2" id="ticketStatusFilters">
                        <button type="button" class="btn btn-sm btn-primary ticket-filter-btn active" data-status-filter="all">
                            Tutti
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary ticket-filter-btn" data-status-filter="0">
                            Da leggere
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-warning ticket-filter-btn" data-status-filter="1">
                            In lavorazione
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-success ticket-filter-btn" data-status-filter="2">
                            Chiusi
                        </button>
                    </div>
                </div>

                <div class="col-lg-3">
                                        <label for="ticketCategoryFilter" class="filter-group-label mb-2">
                        <i class="bi bi-tags me-2"></i>Categoria
                    </label>
                    <select id="ticketCategoryFilter" class="form-select">
                        <option value="all">Tutte le categorie</option>
                        @foreach($tickets->pluck('category')->filter()->unique()->sort()->values() as $category)
                            <option value="{{ $category }}">{{ $category }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-lg-2 text-lg-end">
                                  <div class="filter-group-label mb-2">
                        <i class="bi bi-eye me-2"></i>Ticket visibili
                    </div>
                    <div class="tickets-visible-count" id="tickets-visible-count">0</div>
                </div>
            </div>

            <div class="mt-3 d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-dark btn-sm tickets-next-btn" id="btnOpenNextUnread">
                    <i class="bi bi-arrow-right-circle me-1"></i>Apri prossimo da leggere
                </button>
            </div>
        </div>

    </div>

    <div class="card tickets-card shadow-sm">
<div class="card-header tickets-table-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h5 class="mb-1 d-flex align-items-center gap-2">
            <span class="tickets-table-header-icon">
                <i class="bi bi-table"></i>
            </span>
            <span>Elenco ticket</span>
        </h5>
        <small class="text-muted">Ricerca per ID, utente, email, categoria</small>
    </div>

    <div class="tickets-table-header-meta">
        <span class="tickets-table-meta-chip">
            <i class="bi bi-layout-text-window-reverse me-1"></i>Vista operativa
        </span>
    </div>
</div>

        <div class="card-body">
            <div class="table-responsive">
                <table id="tickets-table" class="table table-hover align-middle w-100 mb-0">
                    <thead>
                    <tr>
                        <th style="width: 90px;">Azione</th>
                        <th>ID</th>
                        <th>Status</th>
                        <th>User ID</th>
                        <th>Email</th>
                        <th>Creato</th>
                        <th>Ultimo aggiornamento</th>
                        <th>Categoria</th>
                    </tr>
                    </thead>
                    <tbody>
                        @forelse($tickets as $ticket)
                            @php
                                $rowClass = '';
                                $badgeClass = '';
                                $statusLabel = '';

                                if ((int) $ticket->status === 0) {
                                    $rowClass = 'ticket-row-new';
                                    $badgeClass = 'badge-soft-secondary';
                                    $statusLabel = 'Da leggere';
                                } elseif ((int) $ticket->status === 1) {
                                    $rowClass = 'ticket-row-working';
                                    $badgeClass = 'badge-soft-warning';
                                    $statusLabel = 'In lavorazione';
                                } elseif ((int) $ticket->status === 2) {
                                    $rowClass = 'ticket-row-closed';
                                    $badgeClass = 'badge-soft-success';
                                    $statusLabel = 'Chiuso';
                                }
                            @endphp

<tr
    id="ticket-row-{{ $ticket->ticket_id }}"
    class="{{ $rowClass }} ticket-table-row"
    data-ticket-id="{{ $ticket->ticket_id }}"
    data-status="{{ (int) $ticket->status }}"
    data-category="{{ $ticket->category }}"
>
    <td class="text-center">
<button
    type="button"
    class="btn btn-sm btn-outline-primary btn-open-ticket tickets-open-btn tickets-open-icon-btn"
    data-ticket-id="{{ $ticket->ticket_id }}"
    title="Apri dettaglio ticket"
    aria-label="Apri ticket {{ $ticket->ticket_id }}"
>
    <i class="bi bi-arrow-up-right"></i>
</button>
    </td>

    <td>
        <div class="ticket-id-cell">
            <span class="ticket-id-pill">#{{ $ticket->ticket_id }}</span>
        </div>
    </td>

    <td id="ticket-badge-{{ $ticket->ticket_id }}">
        <span class="badge {{ $badgeClass }}">
            {{ $statusLabel }}
        </span>
    </td>

    <td>
        <div class="ticket-userid-cell">
            <div class="ticket-cell-main">{{ $ticket->user_id }}</div>
            <div class="ticket-cell-sub">Utente panel</div>
        </div>
    </td>

    <td>
        <div class="ticket-email-cell">
            <div class="ticket-cell-main">{{ $ticket->email ?: '-' }}</div>
            <div class="ticket-cell-sub">Contatto</div>
        </div>
    </td>

    <td>
        <div class="ticket-datetime-cell">
            <div class="ticket-cell-main">{{ $ticket->created_at }}</div>
            <div class="ticket-cell-sub">Creazione</div>
        </div>
    </td>

    <td id="ticket-last-update-cell-{{ $ticket->ticket_id }}">
        <div class="ticket-datetime-cell">
            <div class="ticket-cell-main">{{ $ticket->last_update }}</div>
            <div class="ticket-cell-sub">Ultima attività</div>
        </div>
    </td>

    <td>
        <span class="ticket-category-chip">
            <i class="bi bi-tag me-1"></i>{{ $ticket->category }}
        </span>
    </td>
</tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    Nessun ticket trovato
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="ticketDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title">Dettaglio ticket</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
            </div>
            <div class="modal-body" id="ticketDetailModalBody">
                <div class="text-center text-muted py-4">
                    Seleziona un ticket.
                </div>
            </div>
        </div>
    </div>
</div>

<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 2000">
    <div id="toastContainer"></div>
</div>
@endsection




@section('scripts')
<script>
function showToast(message, type = 'success') {
    const toastId = 'toast-' + Date.now();
    const bgClass = type === 'error'
        ? 'bg-danger text-white'
        : type === 'warning'
            ? 'bg-warning text-dark'
            : 'bg-success text-white';

    const html = `
        <div id="${toastId}" class="toast align-items-center ${bgClass} border-0 mb-2" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body fw-semibold">${message}</div>
                <button type="button" class="btn-close ${type === 'warning' ? '' : 'btn-close-white'} me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;

    const container = document.getElementById('toastContainer');
    if (!container) {
        return;
    }

    container.insertAdjacentHTML('beforeend', html);

    const toastEl = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastEl, { delay: 2500 });
    toast.show();

    toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
}

document.addEventListener('DOMContentLoaded', () => {
    let currentStatusFilter = 'all';
    let currentCategoryFilter = 'all';
    let ticketsTable = null;

    let isTicketDetailLoading = false;
    let currentOpenTicketId = null;
    const ticketDetailModalInstance = new bootstrap.Modal(document.getElementById('ticketDetailModal'));

    function updateCounters() {
        let visibleCount = 0;

        document.querySelectorAll('#tickets-table tbody tr').forEach(row => {
            if (row.style.display === 'none') {
                return;
            }

            visibleCount++;
        });

        const visibleCountEl = document.getElementById('tickets-visible-count');
        if (visibleCountEl) {
            visibleCountEl.textContent = visibleCount;
        }
    }

    function setActiveTicketFilterButton(value) {
        document.querySelectorAll('.ticket-filter-btn').forEach(button => {
            const filterValue = button.dataset.statusFilter;
            const isActive = filterValue === value;

            button.classList.remove(
                'btn-primary',
                'btn-outline-primary',
                'btn-outline-secondary',
                'btn-outline-warning',
                'btn-outline-success'
            );

            if (isActive) {
                button.classList.add('btn-primary', 'active');
                return;
            }

            button.classList.remove('active');

            if (filterValue === '0') {
                button.classList.add('btn-outline-secondary');
            } else if (filterValue === '1') {
                button.classList.add('btn-outline-warning');
            } else if (filterValue === '2') {
                button.classList.add('btn-outline-success');
            } else {
                button.classList.add('btn-outline-primary');
            }
        });
    }

    function applyCombinedFilters() {
        if (!ticketsTable) {
            return;
        }

        $.fn.dataTable.ext.search = $.fn.dataTable.ext.search.filter(fn => !fn._ticketCombinedFilter);

        const combinedFilterFn = function(settings, data, dataIndex) {
            if (settings.nTable.id !== 'tickets-table') {
                return true;
            }

            const rowNode = ticketsTable.row(dataIndex).node();

            if (!rowNode) {
                return true;
            }

            const rowStatus = String(rowNode.dataset.status || '');
            const rowCategory = String(rowNode.dataset.category || '');

            const statusMatch = currentStatusFilter === 'all'
                ? true
                : rowStatus === String(currentStatusFilter);

            const categoryMatch = currentCategoryFilter === 'all'
                ? true
                : rowCategory === String(currentCategoryFilter);

            return statusMatch && categoryMatch;
        };

        combinedFilterFn._ticketCombinedFilter = true;
        $.fn.dataTable.ext.search.push(combinedFilterFn);

        ticketsTable.draw(false);
    }

    function bindTicketStatusFilters() {
        document.querySelectorAll('.ticket-filter-btn').forEach(button => {
            button.addEventListener('click', () => {
                const nextFilter = button.dataset.statusFilter || 'all';

                if (nextFilter === currentStatusFilter) {
                    return;
                }

                currentStatusFilter = nextFilter;
                setActiveTicketFilterButton(currentStatusFilter);
                applyCombinedFilters();
            });
        });
    }

    function bindTicketCategoryFilter() {
        const categorySelect = document.getElementById('ticketCategoryFilter');

        if (!categorySelect) {
            return;
        }

        categorySelect.addEventListener('change', () => {
            currentCategoryFilter = categorySelect.value || 'all';
            applyCombinedFilters();
        });
    }

        function openTicketById(ticketId) {
        const button = document.querySelector(`.btn-open-ticket[data-ticket-id="${ticketId}"]`);

        if (button) {
            button.click();
        }
    }

    function bindOpenNextUnreadButton() {
        const nextUnreadBtn = document.getElementById('btnOpenNextUnread');

        if (!nextUnreadBtn) {
            return;
        }

        nextUnreadBtn.addEventListener('click', () => {
            if (isTicketDetailLoading) {
                showToast('Attendi il caricamento del ticket in corso.', 'warning');
                return;
            }
            let targetTicketId = null;

            document.querySelectorAll('#tickets-table tbody tr').forEach(row => {
                if (targetTicketId !== null) {
                    return;
                }

                if (row.style.display === 'none') {
                    return;
                }

                if (String(row.dataset.status || '') !== '0') {
                    return;
                }

                targetTicketId = row.dataset.ticketId || null;
            });

            if (!targetTicketId) {
                showToast('Nessun ticket da leggere disponibile con i filtri attuali.', 'warning');
                return;
            }

            openTicketById(targetTicketId);
        });
    }

    function initTicketsTable() {
        if (!window.jQuery || !$('#tickets-table').length) {
            return;
        }

        if ($.fn.DataTable.isDataTable('#tickets-table')) {
            $('#tickets-table').DataTable().destroy();
        }

        ticketsTable = $('#tickets-table').DataTable({
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            order: [],
            autoWidth: false,
            responsive: false,
            language: {
                emptyTable: 'Nessun ticket trovato',
                search: 'Cerca:',
                lengthMenu: 'Mostra _MENU_ ticket',
                info: 'Visualizzazione da _START_ a _END_ di _TOTAL_ ticket',
                infoEmpty: 'Nessun ticket disponibile',
                zeroRecords: 'Nessun ticket corrispondente',
                paginate: {
                    first: 'Inizio',
                    last: 'Fine',
                    next: '>',
                    previous: '<'
                }
            },
            columnDefs: [
                { orderable: false, targets: [0, 2] },
                { className: 'text-center', targets: [0, 1, 2] }
            ],
            order: [],
            drawCallback: function () {
                updateCounters();
            }
        });

        updateCounters();
        applyCombinedFilters();
    }

    const ticketDetailModalEl = document.getElementById('ticketDetailModal');
    const ticketDetailModalBody = document.getElementById('ticketDetailModalBody');
    const ticketDetailUrlBase = @json(url('/tickets'));
    const ticketUpdateUrlBase = @json(url('/tickets'));

        function highlightRow(row) {
        if (!row) {
            return;
        }

        row.classList.add('row-updated');

        setTimeout(() => {
            row.classList.remove('row-updated');
        }, 1600);
    }

    function removeTicketRowStatusClasses(row) {
        if (!row) {
            return;
        }

        row.classList.remove('ticket-row-new', 'ticket-row-working', 'ticket-row-closed');
    }

        function clearActiveTicketRows() {
        document.querySelectorAll('.ticket-table-row.ticket-row-active').forEach(row => {
            row.classList.remove('ticket-row-active');
        });
    }

    function setActiveTicketRow(ticketId) {
        clearActiveTicketRows();

        const row = document.getElementById(`ticket-row-${ticketId}`);
        if (!row) {
            return;
        }

        row.classList.add('ticket-row-active');
    }

    function decodeBase64Utf8(value) {
        try {
            return decodeURIComponent(
                Array.prototype.map.call(atob(value), function (char) {
                    return '%' + ('00' + char.charCodeAt(0).toString(16)).slice(-2);
                }).join('')
            );
        } catch (error) {
            return '';
        }
    }

        function initTicketDetailTooltips(scope = document) {
        const tooltipElements = scope.querySelectorAll('[data-bs-toggle="tooltip"]');

        tooltipElements.forEach(el => {
            const existingTooltip = bootstrap.Tooltip.getInstance(el);
            if (existingTooltip) {
                existingTooltip.dispose();
            }

            new bootstrap.Tooltip(el);
        });
    }

    function bindSuggestedReplyActions() {
        const replyEl = document.getElementById('ticket-reply');
        const messageEl = document.getElementById('ticket-detail-message');

        if (!replyEl) {
            return;
        }

        document.querySelectorAll('.btn-use-suggested-reply').forEach(button => {
            if (button.dataset.bound === '1') {
                return;
            }

            button.dataset.bound = '1';

            button.addEventListener('click', () => {
                const mode = button.dataset.mode || 'replace';
                const replyB64 = button.dataset.replyB64 || '';
                const suggestedText = decodeBase64Utf8(replyB64).trim();

                if (!suggestedText) {
                    showToast('Risposta suggerita non valida.', 'error');
                    return;
                }

                if (mode === 'replace') {
                    const currentValue = replyEl.value.trim();

                    if (currentValue !== '') {
                        const confirmReplace = confirm('Vuoi sostituire completamente il testo attuale della risposta?');
                        if (!confirmReplace) {
                            return;
                        }
                    }

                    replyEl.value = suggestedText;
                } else {
                    replyEl.value = replyEl.value.trim() === ''
                        ? suggestedText
                        : replyEl.value.replace(/\s+$/u, '') + "\n\n" + suggestedText;
                }

                replyEl.focus();

                if (messageEl) {
                    messageEl.innerHTML = '<span class="text-muted">Risposta suggerita inserita nella textarea. Verifica sempre il testo prima di salvare.</span>';
                }

                showToast('Risposta suggerita inserita.', 'success');
            });
        });
    }


    function bindTicketDetailActions() {
        const saveBtn = document.getElementById('save-ticket-btn');

        if (!saveBtn || saveBtn.dataset.bound === '1') {
            return;
        }

        saveBtn.dataset.bound = '1';

        saveBtn.addEventListener('click', () => {
            const ticketId = saveBtn.dataset.ticketId;
            const replyEl = document.getElementById('ticket-reply');
            const statusEl = document.getElementById('ticket-status');
            const messageEl = document.getElementById('ticket-detail-message');

            if (!ticketId || !replyEl || !statusEl || !messageEl) {
                showToast('Elementi ticket non trovati.', 'error');
                return;
            }
            const initialReply = replyEl.dataset.initialValue ?? '';
            const initialStatus = statusEl.dataset.initialValue ?? '';
            const currentReply = replyEl.value;
            const currentStatus = statusEl.value;

            if (currentReply === initialReply && String(currentStatus) === String(initialStatus)) {
                messageEl.innerHTML = '<span class="ticket-save-message-warning">Nessuna modifica da salvare.</span>';
                showToast('Nessuna modifica da salvare.', 'warning');
                return;
            }

            const originalButtonHtml = saveBtn.innerHTML;

            saveBtn.disabled = true;
            saveBtn.innerHTML = 'Salvataggio...';
           messageEl.innerHTML = '<span class="text-muted">Salvataggio in corso...</span>';

            fetch(`${ticketUpdateUrlBase}/${ticketId}/update`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    reply: replyEl.value,
                    status: statusEl.value
                })
            })
            .then(async response => {
                let data = null;

                try {
                    data = await response.json();
                } catch (e) {
                    throw new Error('Risposta non valida dal server.');
                }

                if (!response.ok) {
                    if (response.status === 422 && data.errors) {
                        const firstError = Object.values(data.errors).flat()[0];
                        throw new Error(firstError || 'Dati non validi.');
                    }

                    throw new Error(data.message || 'Errore durante il salvataggio del ticket.');
                }

                if (!data.success) {
                    throw new Error(data.message || 'Errore durante il salvataggio del ticket.');
                }

        const row = document.getElementById(`ticket-row-${ticketId}`);
        const previousStatus = row ? parseInt(row.dataset.status || '-1', 10) : -1;
        const badgeCell = document.getElementById(`ticket-badge-${ticketId}`);
        const lastUpdateEl = document.getElementById('ticket-detail-last-update');
        const tableLastUpdateCell = document.getElementById(`ticket-last-update-cell-${ticketId}`);
        const mainStatusBadgeEl = document.getElementById('ticket-detail-main-status-badge');

                if (badgeCell) {
                    badgeCell.innerHTML = data.status_badge_html;
                }

                if (row) {
                    row.dataset.status = String(data.status);
                    removeTicketRowStatusClasses(row);
                    row.classList.add(data.row_class);
                    highlightRow(row);
                }

                                if (previousStatus !== -1 && previousStatus !== parseInt(data.status, 10)) {
                    const counterMap = {
                        0: 'count-new',
                        1: 'count-working',
                        2: 'count-closed'
                    };

                    const prevCounterEl = document.getElementById(counterMap[previousStatus]);
                    const nextCounterEl = document.getElementById(counterMap[parseInt(data.status, 10)]);

                    if (prevCounterEl) {
                        prevCounterEl.textContent = Math.max(0, parseInt(prevCounterEl.textContent || '0', 10) - 1);
                    }

                    if (nextCounterEl) {
                        nextCounterEl.textContent = parseInt(nextCounterEl.textContent || '0', 10) + 1;
                    }
                }


if (lastUpdateEl) {
    lastUpdateEl.textContent = data.last_update;
}

if (tableLastUpdateCell) {
    tableLastUpdateCell.textContent = data.last_update;
}

                if (mainStatusBadgeEl) {
                    mainStatusBadgeEl.className = `badge ${data.status_badge_class}`;
                    mainStatusBadgeEl.textContent = data.status_label;
                }

                if (ticketsTable && row) {
                    const dataTableRow = ticketsTable.row(row);

                    if (dataTableRow) {
                        const rowData = dataTableRow.data();

                        if (rowData && rowData.length >= 8) {
                            rowData[2] = data.status_badge_html;
                            rowData[6] = data.last_update;
                            dataTableRow.data(rowData);
                        }
                    }

                    ticketsTable.draw(false);
                } else {
                    updateCounters();
                }

                replyEl.dataset.initialValue = replyEl.value;
                statusEl.dataset.initialValue = String(data.status);
                messageEl.innerHTML = '<span class="ticket-save-message-success">Ticket aggiornato correttamente.</span>';
                showToast(data.message || 'Ticket aggiornato correttamente.', 'success');
            })
            .catch(error => {
                messageEl.innerHTML = `<span class="ticket-save-message-error">${error.message || 'Errore durante il salvataggio.'}</span>`;
                showToast(error.message || 'Errore durante il salvataggio.', 'error');
            })
            .finally(() => {
                saveBtn.disabled = false;
                saveBtn.innerHTML = originalButtonHtml;
            });
        });
    }

    document.addEventListener('click', (event) => {
        const openBtn = event.target.closest('.btn-open-ticket');

        if (!openBtn) {
            return;
        }

        const ticketId = openBtn.dataset.ticketId;

        if (isTicketDetailLoading) {
            showToast('Attendi il caricamento del ticket in corso.', 'warning');
            return;
        }

        if (currentOpenTicketId === ticketId) {
            ticketDetailModalInstance.show();
            return;
        }

        isTicketDetailLoading = true;
        currentOpenTicketId = ticketId;
                setActiveTicketRow(ticketId);

        if (!ticketId) {
            showToast('Ticket non valido.', 'error');
            return;
        }

        ticketDetailModalBody.innerHTML = `
            <div class="text-center text-muted py-4">
                Caricamento ticket...
            </div>
        `;

        ticketDetailModalInstance.show();

        ticketDetailModalEl.addEventListener('hidden.bs.modal', () => {
            currentOpenTicketId = null;
            isTicketDetailLoading = false;
            clearActiveTicketRows();

            ticketDetailModalBody.innerHTML = `
                <div class="text-center text-muted py-4">
                    Seleziona un ticket.
                </div>
            `;
        }, { once: true });

        fetch(`${ticketDetailUrlBase}/${ticketId}/detail`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
            },
        })
        .then(async response => {
            let data = null;

            try {
                data = await response.json();
            } catch (e) {
                throw new Error('Risposta non valida dal server.');
            }

            if (!response.ok) {
                throw new Error(data.message || 'Errore durante il caricamento del ticket.');
            }

            if (!data.success) {
                throw new Error(data.message || 'Errore durante il caricamento del ticket.');
            }

            ticketDetailModalBody.innerHTML = data.html;
            initTicketDetailTooltips(ticketDetailModalBody);
            bindSuggestedReplyActions();
            bindTicketDetailActions();
            isTicketDetailLoading = false;
        })
        .catch(error => {
            isTicketDetailLoading = false;

            ticketDetailModalBody.innerHTML = `
                <div class="alert alert-danger mb-0">
                    ${error.message || 'Errore durante il caricamento del ticket.'}
                </div>
            `;
        });
    });

    initTicketsTable();
    bindTicketStatusFilters();
    bindTicketCategoryFilter();
    bindOpenNextUnreadButton();
    bindTicketRowClick();
    setActiveTicketFilterButton(currentStatusFilter);

        function bindTicketRowClick() {
        const table = document.getElementById('tickets-table');

        if (!table || table.dataset.rowClickBound === '1') {
            return;
        }

        table.dataset.rowClickBound = '1';

        table.addEventListener('click', (event) => {
            const clickedOpenButton = event.target.closest('.btn-open-ticket');
            if (clickedOpenButton) {
                return;
            }

            const row = event.target.closest('.ticket-table-row');
            if (!row) {
                return;
            }

            const ticketId = row.dataset.ticketId;
            if (!ticketId) {
                return;
            }

            const openBtn = row.querySelector('.btn-open-ticket');
            if (openBtn) {
                openBtn.click();
            }
        });
    }


});
</script>
@endsection
