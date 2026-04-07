@extends('layouts.main')

@section('content')
<link rel="stylesheet" href="{{ asset('css/referral.css') }}">

<div class="container-fluid mt-4">
        @if(session('referral_export_error'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            {{ session('referral_export_error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Chiudi"></button>
        </div>
    @endif
    <div class="referral-page-hero mb-4">
        <div class="referral-page-hero-content">
            <div class="referral-page-kicker">
                <i class="bi bi-person-plus me-2"></i>Referral program
            </div>

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h2 class="mb-1 referral-page-title">Invita un amico</h2>
                    <p class="text-muted mb-0">
                        Gestione referral, bonus maturati e controllo invitati
                    </p>
                </div>

                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <a
                        href="{{ route('referral.export.new') }}"
                        class="btn btn-sm btn-outline-primary"
                    >
                        <i class="bi bi-download me-1"></i>Scarica nuovi inviti
                    </a>

                    <a
                        href="{{ route('referral.export.all') }}"
                        class="btn btn-sm btn-outline-secondary"
                    >
                        <i class="bi bi-file-earmark-spreadsheet me-1"></i>Scarica tutti gli inviti
                    </a>
                    <a
                        href="{{ route('referral.export.report') }}"
                        class="btn btn-sm btn-outline-dark"
                    >
                        <i class="bi bi-table me-1"></i>Scarica report bonus
                    </a>
                    <button
                        type="button"
                        class="btn btn-sm btn-primary"
                        id="btnRecalculateMaturato"
                    >
                        <i class="bi bi-arrow-repeat me-1"></i>Aggiorna maturato
                    </button>

                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3 col-xl-2">
            <div class="card referral-counter-card h-100">
                <div class="card-body text-center">
                    <div class="counter-label">Referrer</div>
                    <div class="counter-value" id="summary-referrers">{{ $summary['referrers'] ?? 0 }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-xl-2">
            <div class="card referral-counter-card h-100">
                <div class="card-body text-center">
                    <div class="counter-label">Inviti referral</div>
                    <div class="counter-value" id="summary-inviti">{{ $summary['inviti'] ?? 0 }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-xl-2">
            <div class="card referral-counter-card h-100">
                <div class="card-body text-center">
                    <div class="counter-label">Iscritti</div>
                    <div class="counter-value" id="summary-iscritti">{{ $summary['iscritti'] ?? 0 }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-xl-2">
            <div class="card referral-counter-card h-100">
                <div class="card-body text-center">
                    <div class="counter-label">Attivi</div>
                    <div class="counter-value" id="summary-attivi">{{ $summary['attivi'] ?? 0 }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-xl-2">
            <div class="card referral-counter-card h-100">
                <div class="card-body text-center">
                    <div class="counter-label">Maturato</div>
                    <div class="counter-value" id="summary-maturato">{{ $summary['maturato'] ?? 0 }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-xl-2">
            <div class="card referral-counter-card counter-warning h-100">
                <div class="card-body text-center">
                    <div class="counter-label">Pagato</div>
                    <div class="counter-value" id="summary-pagato">{{ $summary['pagato'] ?? 0 }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card referral-counter-card counter-danger h-100">
                <div class="card-body text-center">
                    <div class="counter-label">Da assegnare</div>
                    <div class="counter-value" id="summary-rimanente">{{ $summary['rimanente'] ?? 0 }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card referral-counter-card h-100">
                <div class="card-body text-center">
                    <div class="counter-label">Nuovi inviti email</div>
                    <div class="counter-value">{{ $inviteCounters['new'] ?? 0 }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card referral-counter-card h-100">
                <div class="card-body text-center">
                    <div class="counter-label">Inviti email totali</div>
                    <div class="counter-value">{{ $inviteCounters['total'] ?? 0 }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card referral-card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5 class="mb-1 d-flex align-items-center gap-2">
                    <span class="referral-table-header-icon">
                        <i class="bi bi-table"></i>
                    </span>
                    <span>Elenco bonus referral</span>
                </h5>
                <small class="text-muted">
                    Vista aggregata degli utenti invitanti con bonus maturati e residui
                </small>
            </div>

            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="referral-table-meta-chip">
                    <i class="bi bi-people me-1"></i>{{ $summary['referrers'] ?? 0 }} referrer
                </span>

                <button
                    type="button"
                    class="btn btn-sm btn-primary"
                    id="btnAssignSelected"
                    disabled
                >
                    <i class="bi bi-award me-1"></i>Assegna selezionati
                </button>
            </div>
        </div>

        <div class="card-body">
            <div class="referral-toolbar mb-3 d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div class="d-flex align-items-center flex-wrap gap-2">
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-danger referral-filter-btn active"
                        id="filter-all"
                        data-filter-mode="all"
                    >
                        Tutti
                    </button>

                    <button
                        type="button"
                        class="btn btn-sm btn-outline-warning referral-filter-btn"
                        id="filter-pending"
                        data-filter-mode="pending"
                    >
                        Solo da assegnare
                    </button>

                    <button
                        type="button"
                        class="btn btn-sm btn-outline-secondary referral-filter-btn"
                        id="filter-suspect"
                        data-filter-mode="suspect"
                    >
                        Solo sospetti
                    </button>
                </div>

                <div class="referral-toolbar-meta">
                    <span class="referral-table-meta-chip">
                        <i class="bi bi-funnel me-1"></i>
                        <span id="referral-visible-count">{{ $summary['referrers'] ?? 0 }}</span> visibili
                    </span>
                </div>
            </div>

            <div class="table-responsive">
                <table id="referral-table" class="table table-hover align-middle w-100 mb-0">
                    <thead>
                        <tr>
                            <th style="width: 40px;">
                                <input type="checkbox" id="referral-check-all">
                            </th>
                            <th>User ID</th>
                            <th>Email</th>
                            <th>Inviti</th>
                            <th>Iscritti</th>
                            <th>Attivi</th>
                            <th>Maturato</th>
                            <th>Pagato</th>
                            <th>Rimanente</th>
                            <th style="width: 180px;">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($referrals as $row)
                            <tr
                                id="referral-row-{{ $row->ref_user_id }}"
                                class="referral-table-row {{ (int) $row->bonus_rimanente > 0 ? 'referral-row-pending' : '' }}"
                                data-ref="{{ $row->ref_user_id }}"
                                data-rimanente="{{ (int) $row->bonus_rimanente }}"
                                data-has-suspect="0"
                            >
                                <td class="text-center">
                                    <input
                                        type="checkbox"
                                        class="referral-row-check"
                                        value="{{ $row->ref_user_id }}"
                                        {{ (int) $row->bonus_rimanente > 0 ? '' : 'disabled' }}
                                    >
                                </td>

                                <td>
                                    <div class="referral-user-cell">
                                        <div class="referral-cell-main">
                                            <a href="{{ route('user.profile', ['user_id' => $row->ref_user_id]) }}" target="_blank">
                                                {{ $row->ref_user_id }}
                                            </a>
                                        </div>
                                        <div class="referral-cell-sub">Utente invitante</div>
                                    </div>
                                </td>

                                <td>
                                    <div class="referral-email-cell">
                                        <div class="referral-cell-main">{{ $row->ref_email ?: '-' }}</div>
                                        <div class="referral-cell-sub">Email panel</div>
                                    </div>
                                </td>

                                <td class="referral-col-inviti">{{ (int) $row->inviti }}</td>
                                <td class="referral-col-iscritti">{{ (int) $row->iscritti }}</td>
                                <td class="referral-col-attivi">{{ (int) $row->attivi }}</td>
                                <td class="referral-col-maturato">{{ (int) $row->bonus_maturato }}</td>
                                <td class="referral-col-pagato">{{ (int) $row->bonus_pagato }}</td>

                                <td class="referral-col-rimanente" data-order="{{ (int) $row->bonus_rimanente }}">
                                    @if((int) $row->bonus_rimanente > 0)
                                        <span class="badge badge-soft-danger referral-rimanente-badge">
                                            {{ (int) $row->bonus_rimanente }} da assegnare
                                        </span>
                                    @else
                                        <span class="badge badge-soft-success referral-rimanente-badge">
                                            Pagato
                                        </span>
                                    @endif
                                </td>

                                <td>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-primary btn-open-referral-detail"
                                            data-ref="{{ $row->ref_user_id }}"
                                        >
                                            Dettaglio
                                        </button>

                                        @if((int) $row->bonus_rimanente > 0)
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-primary btn-assign-one"
                                                data-ref="{{ $row->ref_user_id }}"
                                            >
                                                Assegna
                                            </button>
                                        @else
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-light"
                                                disabled
                                            >
                                                Pagato
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">
                                    Nessun referral trovato
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="referralDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title">Dettaglio referral</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
            </div>
            <div class="modal-body" id="referralDetailModalBody">
                <div class="text-center text-muted py-4">
                    Seleziona un referral.
                </div>
            </div>
        </div>
    </div>
</div>

<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 2000">
    <div id="referralToastContainer"></div>
</div>
@endsection

@section('scripts')
<script>
function showReferralToast(message, type = 'success') {
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

    const container = document.getElementById('referralToastContainer');
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
    let referralTable = null;
    let isDetailLoading = false;
    let currentOpenRef = null;
    let currentFilterMode = 'all';

    const detailModalEl = document.getElementById('referralDetailModal');
    const detailModalBody = document.getElementById('referralDetailModalBody');
    const detailModalInstance = new bootstrap.Modal(detailModalEl);

    const detailUrlBase = @json(url('/referral'));
    const removeUrl = @json(route('referral.remove'));
    const assignBonusUrl = @json(route('referral.assignBonus'));
    const recalculateMaturatoUrl = @json(route('referral.recalculateMaturato'));


    function initReferralTable() {
        if (!window.jQuery || !$('#referral-table').length) {
            return;
        }

        if ($.fn.DataTable.isDataTable('#referral-table')) {
            $('#referral-table').DataTable().destroy();
        }

        referralTable = $('#referral-table').DataTable({
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            order: [[8, 'desc']],
            autoWidth: false,
            responsive: false,
            language: {
                emptyTable: 'Nessun referral trovato',
                search: 'Cerca:',
                lengthMenu: 'Mostra _MENU_ righe',
                info: 'Visualizzazione da _START_ a _END_ di _TOTAL_ righe',
                infoEmpty: 'Nessun dato disponibile',
                zeroRecords: 'Nessun risultato corrispondente',
                paginate: {
                    first: 'Inizio',
                    last: 'Fine',
                    next: '>',
                    previous: '<'
                }
            },
            columnDefs: [
                { orderable: false, targets: [0, 9] },
                { className: 'text-center', targets: [0, 3, 4, 5, 6, 7, 8] }
            ],
            drawCallback: function () {
                updateVisibleCount();
            }
        });
    }

        function updateVisibleCount() {
        const visibleCountEl = document.getElementById('referral-visible-count');

        if (!visibleCountEl || !referralTable) {
            return;
        }

        visibleCountEl.textContent = referralTable.rows({ filter: 'applied' }).count();
    }

       function setActiveReferralFilterButton(mode) {
        document.querySelectorAll('.referral-filter-btn').forEach(button => {
            button.classList.toggle('active', button.dataset.filterMode === mode);
        });
    }

    function applyReferralFilters() {
        if (!referralTable) {
            return;
        }

        $.fn.dataTable.ext.search = $.fn.dataTable.ext.search.filter(fn => !fn._referralCustomFilter);

        const filterFn = function(settings, data, dataIndex) {
            if (settings.nTable.id !== 'referral-table') {
                return true;
            }

            const rowNode = referralTable.row(dataIndex).node();
            if (!rowNode) {
                return true;
            }

            const rimanente = parseInt(rowNode.dataset.rimanente || '0', 10);
            const hasSuspect = String(rowNode.dataset.hasSuspect || '0') === '1';

            if (currentFilterMode === 'pending') {
                return rimanente > 0;
            }

            if (currentFilterMode === 'suspect') {
                return hasSuspect;
            }

            return true;
        };

        filterFn._referralCustomFilter = true;
        $.fn.dataTable.ext.search.push(filterFn);

        referralTable.draw(false);
    }

    function refreshBulkAssignState() {
        const checkedRows = document.querySelectorAll('.referral-row-check:checked');
        const bulkButton = document.getElementById('btnAssignSelected');
        const checkAll = document.getElementById('referral-check-all');
        const enabledChecks = document.querySelectorAll('.referral-row-check:not(:disabled)');

        if (bulkButton) {
            bulkButton.disabled = checkedRows.length === 0;
        }

        if (checkAll) {
            if (enabledChecks.length === 0) {
                checkAll.checked = false;
                checkAll.indeterminate = false;
                return;
            }

            checkAll.checked = checkedRows.length === enabledChecks.length;
            checkAll.indeterminate = checkedRows.length > 0 && checkedRows.length < enabledChecks.length;
        }
    }

    function updateRowData(ref, rowData) {
        const row = document.getElementById(`referral-row-${ref}`);

        if (!row) {
            return;
        }

        const newRimanente = parseInt(rowData.bonus_rimanente || 0, 10);
        const newPagato = parseInt(rowData.bonus_pagato || 0, 10);

        row.dataset.rimanente = String(newRimanente);
        row.classList.toggle('referral-row-pending', newRimanente > 0);

        const invitiCell = row.querySelector('.referral-col-inviti');
        const iscrittiCell = row.querySelector('.referral-col-iscritti');
        const attiviCell = row.querySelector('.referral-col-attivi');
        const maturatoCell = row.querySelector('.referral-col-maturato');
        const pagatoCell = row.querySelector('.referral-col-pagato');
        const rimanenteCell = row.querySelector('.referral-col-rimanente');
        const checkbox = row.querySelector('.referral-row-check');
        const actionCell = row.querySelector('td:last-child');

        if (typeof rowData.inviti !== 'undefined' && invitiCell) {
            invitiCell.textContent = rowData.inviti;
        }

        if (typeof rowData.iscritti !== 'undefined' && iscrittiCell) {
            iscrittiCell.textContent = rowData.iscritti;
        }

        if (typeof rowData.attivi !== 'undefined' && attiviCell) {
            attiviCell.textContent = rowData.attivi;
        }

        if (typeof rowData.bonus_maturato !== 'undefined' && maturatoCell) {
            maturatoCell.textContent = rowData.bonus_maturato;
        }

        if (typeof rowData.bonus_pagato !== 'undefined' && pagatoCell) {
            pagatoCell.textContent = newPagato;
        }

        if (rimanenteCell) {
            rimanenteCell.setAttribute('data-order', newRimanente);

            if (newRimanente > 0) {
                rimanenteCell.innerHTML = `
                    <span class="badge badge-soft-danger referral-rimanente-badge">
                        ${newRimanente} da assegnare
                    </span>
                `;
            } else {
                rimanenteCell.innerHTML = `
                    <span class="badge badge-soft-success referral-rimanente-badge">
                        Pagato
                    </span>
                `;
            }
        }

        if (checkbox) {
            checkbox.checked = false;
            checkbox.disabled = newRimanente <= 0;
        }

        if (actionCell) {
            const detailButton = `
                <button
                    type="button"
                    class="btn btn-sm btn-outline-primary btn-open-referral-detail"
                    data-ref="${ref}"
                >
                    Dettaglio
                </button>
            `;

            const assignButton = newRimanente > 0
                ? `
                    <button
                        type="button"
                        class="btn btn-sm btn-primary btn-assign-one"
                        data-ref="${ref}"
                    >
                        Assegna
                    </button>
                `
                : `
                    <button
                        type="button"
                        class="btn btn-sm btn-light"
                        disabled
                    >
                        Pagato
                    </button>
                `;

            actionCell.innerHTML = `<div class="d-flex gap-2 flex-wrap">${detailButton}${assignButton}</div>`;
        }

        if (referralTable) {
            referralTable.row(row).invalidate().draw(false);
        }

        refreshBulkAssignState();
        updateVisibleCount();
    }

    function assignBonusToRefs(refs) {
        if (!Array.isArray(refs) || refs.length === 0) {
            showReferralToast('Nessun utente selezionato.', 'warning');
            return;
        }

        fetch(assignBonusUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                user_ids: refs
            })
        })
        .then(async response => {
            let data = null;

            try {
                data = await response.json();
            } catch (e) {
                throw new Error('Risposta non valida dal server.');
            }

            if (!response.ok || !data.success) {
                if (response.status === 422 && data.errors) {
                    const firstError = Object.values(data.errors).flat()[0];
                    throw new Error(firstError || 'Dati non validi.');
                }

                throw new Error(data.message || 'Errore durante l’assegnazione bonus.');
            }

            (data.updated_rows || []).forEach(item => {
                updateRowData(item.ref_user_id, {
                    bonus_pagato: item.bonus_pagato,
                    bonus_rimanente: item.bonus_rimanente
                });
            });

            const pagatoSummaryEl = document.getElementById('summary-pagato');
            const rimanenteSummaryEl = document.getElementById('summary-rimanente');

            if (pagatoSummaryEl) {
                pagatoSummaryEl.textContent = parseInt(pagatoSummaryEl.textContent || '0', 10) + parseInt(data.total_assigned || '0', 10);
            }

            if (rimanenteSummaryEl) {
                rimanenteSummaryEl.textContent = Math.max(
                    0,
                    parseInt(rimanenteSummaryEl.textContent || '0', 10) - parseInt(data.total_assigned || '0', 10)
                );
            }

            const checkAll = document.getElementById('referral-check-all');
            if (checkAll) {
                checkAll.checked = false;
                checkAll.indeterminate = false;
            }

            showReferralToast(data.message || 'Bonus assegnato correttamente.', 'success');
        })
        .catch(error => {
            showReferralToast(error.message || 'Errore durante l’assegnazione bonus.', 'error');
        });
    }

    function recalculateMaturato() {
        return fetch(recalculateMaturatoUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({})
        })
        .then(async response => {
            let data = null;

            try {
                data = await response.json();
            } catch (e) {
                throw new Error('Risposta non valida dal server.');
            }

            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Errore durante il ricalcolo del maturato.');
            }

            (data.updated_rows || []).forEach(item => {
                updateRowData(item.ref_user_id, {
                    inviti: item.inviti,
                    iscritti: item.iscritti,
                    attivi: item.attivi,
                    bonus_maturato: item.bonus_maturato,
                    bonus_pagato: item.bonus_pagato,
                    bonus_rimanente: item.bonus_rimanente
                });
            });

            const maturatoSummaryEl = document.getElementById('summary-maturato');
            const pagatoSummaryEl = document.getElementById('summary-pagato');
            const rimanenteSummaryEl = document.getElementById('summary-rimanente');

            if (maturatoSummaryEl) {
                maturatoSummaryEl.textContent = parseInt(data.totals?.maturato || '0', 10);
            }

            if (pagatoSummaryEl) {
                pagatoSummaryEl.textContent = parseInt(data.totals?.pagato || '0', 10);
            }

            if (rimanenteSummaryEl) {
                rimanenteSummaryEl.textContent = parseInt(data.totals?.rimanente || '0', 10);
            }

            showReferralToast(data.message || 'Maturato referral aggiornato correttamente.', 'success');

            setTimeout(() => {
                window.location.reload();
            }, 700);

            return data;
        })
        .catch(error => {
            showReferralToast(error.message || 'Errore durante il ricalcolo del maturato.', 'error');
            throw error;
        });
    }

    function openReferralDetail(ref) {
        if (!ref || isDetailLoading) {
            return;
        }

        isDetailLoading = true;
        currentOpenRef = ref;

        detailModalBody.innerHTML = `
            <div class="text-center text-muted py-4">
                Caricamento dettaglio...
            </div>
        `;

        detailModalInstance.show();

        fetch(`${detailUrlBase}/${ref}/detail`, {
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

            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Errore durante il caricamento del dettaglio.');
            }

            detailModalBody.innerHTML = data.html;

            const mainRow = document.getElementById(`referral-row-${ref}`);
            const suspectRows = detailModalBody.querySelectorAll('tr[data-is-suspect="1"]');

            if (mainRow) {
                const hasSuspect = suspectRows.length > 0;
                mainRow.dataset.hasSuspect = hasSuspect ? '1' : '0';
                mainRow.classList.toggle('referral-row-suspect', hasSuspect);
            }

            applyReferralFilters();
            isDetailLoading = false;
        })
        .catch(error => {
            isDetailLoading = false;

            detailModalBody.innerHTML = `
                <div class="alert alert-danger mb-0">
                    ${error.message || 'Errore durante il caricamento del dettaglio.'}
                </div>
            `;
        });
    }

    document.addEventListener('click', (event) => {
        const detailBtn = event.target.closest('.btn-open-referral-detail');

        if (detailBtn) {
            const ref = detailBtn.dataset.ref || '';

            if (!ref) {
                showReferralToast('Referral non valido.', 'error');
                return;
            }

            if (isDetailLoading) {
                showReferralToast('Attendi il caricamento in corso.', 'warning');
                return;
            }

            openReferralDetail(ref);
            return;
        }

        const assignOneBtn = event.target.closest('.btn-assign-one');

        if (assignOneBtn) {
            const ref = assignOneBtn.dataset.ref || '';
            const row = document.getElementById(`referral-row-${ref}`);

            if (!ref || !row) {
                showReferralToast('Utente non valido.', 'error');
                return;
            }

            const rim = parseInt(row.dataset.rimanente || '0', 10);

            if (rim <= 0) {
                showReferralToast('Nessun bonus da assegnare.', 'warning');
                return;
            }

            if (!confirm(`Assegnare ${rim} punti a ${ref}?`)) {
                return;
            }

            assignBonusToRefs([ref]);
            return;
        }

        const removeBtn = event.target.closest('.btn-remove-referral');

        if (!removeBtn) {
            return;
        }

        const row = removeBtn.closest('tr');
        const wrapper = document.getElementById('referralDetailWrapper');

        if (!row || !wrapper) {
            showReferralToast('Dati referral non trovati.', 'error');
            return;
        }

        const uid = row.dataset.uid || '';
        const ref = wrapper.dataset.ref || '';
        const actions = parseInt(row.dataset.actions || '0', 10);

        if (!uid || !ref) {
            showReferralToast('Dati referral mancanti.', 'error');
            return;
        }

        if (!confirm(`Rimuovere ${uid} dal referral ${ref}?`)) {
            return;
        }

        removeBtn.disabled = true;
        removeBtn.textContent = 'Rimozione...';

        fetch(removeUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                uid: uid,
                ref: ref
            })
        })
        .then(async response => {
            let data = null;

            try {
                data = await response.json();
            } catch (e) {
                throw new Error('Risposta non valida dal server.');
            }

            if (!response.ok || !data.success) {
                if (response.status === 422 && data.errors) {
                    const firstError = Object.values(data.errors).flat()[0];
                    throw new Error(firstError || 'Dati non validi.');
                }

                throw new Error(data.message || 'Errore durante la rimozione del referral.');
            }

            row.remove();

            const remainingSuspects = detailModalBody.querySelectorAll('tr[data-is-suspect="1"]').length;
            const mainRow = document.getElementById(`referral-row-${ref}`);

            if (mainRow) {
                const hasSuspect = remainingSuspects > 0;
                mainRow.dataset.hasSuspect = hasSuspect ? '1' : '0';
                mainRow.classList.toggle('referral-row-suspect', hasSuspect);
            }

            updateRowData(ref, data.updated_row);

            const invitiSummaryEl = document.getElementById('summary-inviti');
            const iscrittiSummaryEl = document.getElementById('summary-iscritti');
            const attiviSummaryEl = document.getElementById('summary-attivi');
            const maturatoSummaryEl = document.getElementById('summary-maturato');
            const rimanenteSummaryEl = document.getElementById('summary-rimanente');

            if (invitiSummaryEl) {
                invitiSummaryEl.textContent = Math.max(0, parseInt(invitiSummaryEl.textContent || '0', 10) - 1);
            }

            if (iscrittiSummaryEl) {
                iscrittiSummaryEl.textContent = Math.max(0, parseInt(iscrittiSummaryEl.textContent || '0', 10) - 1);
            }

            if (actions > 0 && attiviSummaryEl) {
                attiviSummaryEl.textContent = Math.max(0, parseInt(attiviSummaryEl.textContent || '0', 10) - 1);
            }

            if (maturatoSummaryEl) {
                maturatoSummaryEl.textContent = Math.max(
                    0,
                    parseInt(maturatoSummaryEl.textContent || '0', 10) - parseInt(data.delta_bonus || '0', 10)
                );
            }

            if (rimanenteSummaryEl) {
                rimanenteSummaryEl.textContent = Math.max(
                    0,
                    parseInt(rimanenteSummaryEl.textContent || '0', 10) - parseInt(data.delta_bonus || '0', 10)
                );
            }

            const detailMessageEl = document.getElementById('referralDetailMessage');
            if (detailMessageEl) {
                detailMessageEl.innerHTML = `
                    <div class="alert alert-success py-2 mb-0">
                        Referral rimosso correttamente.
                    </div>
                `;
            }

            const tbody = document.querySelector('#referral-detail-table tbody');
            if (tbody && tbody.querySelectorAll('tr').length === 0) {
                detailModalBody.innerHTML = `
                    <div class="alert alert-info mb-0">
                        Nessun invitato attivo trovato per questo referrer.
                    </div>
                `;
            }

            refreshBulkAssignState();
            applyReferralFilters();
            updateVisibleCount();

            showReferralToast(data.message || 'Referral rimosso correttamente.', 'success');
        })
        .catch(error => {
            removeBtn.disabled = false;
            removeBtn.textContent = 'Rimuovi dal referral';
            showReferralToast(error.message || 'Errore durante la rimozione.', 'error');
        });
    });

    const checkAllEl = document.getElementById('referral-check-all');
    const bulkAssignBtn = document.getElementById('btnAssignSelected');
    const recalculateMaturatoBtn = document.getElementById('btnRecalculateMaturato');

    if (checkAllEl) {
        checkAllEl.addEventListener('change', () => {
            document.querySelectorAll('.referral-row-check:not(:disabled)').forEach(check => {
                check.checked = checkAllEl.checked;
            });

            refreshBulkAssignState();
        });
    }

    document.addEventListener('change', (event) => {
        if (!event.target.classList.contains('referral-row-check')) {
            return;
        }

        refreshBulkAssignState();
    });

    if (bulkAssignBtn) {
        bulkAssignBtn.addEventListener('click', () => {
            const refs = Array.from(document.querySelectorAll('.referral-row-check:checked'))
                .map(el => el.value)
                .filter(Boolean);

            if (refs.length === 0) {
                showReferralToast('Seleziona almeno un utente.', 'warning');
                return;
            }

            let total = 0;

            refs.forEach(ref => {
                const row = document.getElementById(`referral-row-${ref}`);
                if (!row) {
                    return;
                }

                total += parseInt(row.dataset.rimanente || '0', 10);
            });

            if (!confirm(`Assegnare ${total} punti complessivi a ${refs.length} utenti?`)) {
                return;
            }

            assignBonusToRefs(refs);
        });
    }

    if (recalculateMaturatoBtn) {
        recalculateMaturatoBtn.addEventListener('click', () => {
            if (!confirm('Vuoi ricalcolare il maturato referral per tutti i referrer attivi?')) {
                return;
            }

            const originalHtml = recalculateMaturatoBtn.innerHTML;
            recalculateMaturatoBtn.disabled = true;
            recalculateMaturatoBtn.innerHTML = '<i class="bi bi-arrow-repeat me-1"></i>Aggiornamento...';

            recalculateMaturato()
                .finally(() => {
                    recalculateMaturatoBtn.disabled = false;
                    recalculateMaturatoBtn.innerHTML = originalHtml;
                });
        });
    }

    document.querySelectorAll('.referral-filter-btn').forEach(button => {
        button.addEventListener('click', () => {
            const nextMode = button.dataset.filterMode || 'all';

            if (nextMode === currentFilterMode) {
                return;
            }

            currentFilterMode = nextMode;
            setActiveReferralFilterButton(currentFilterMode);
            applyReferralFilters();
        });
    });

    initReferralTable();
    refreshBulkAssignState();
    setActiveReferralFilterButton(currentFilterMode);
    applyReferralFilters();
    updateVisibleCount();

    detailModalEl.addEventListener('hidden.bs.modal', () => {
        currentOpenRef = null;
        isDetailLoading = false;

        detailModalBody.innerHTML = `
            <div class="text-center text-muted py-4">
                Seleziona un referral.
            </div>
        `;
    });
});
</script>
@endsection
