@extends('layouts.main')

@section('content')
<link rel="stylesheet" href="{{ asset('css/premiPanel.css') }}">

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Premi Panel</h2>
            <p class="text-muted mb-0">Gestione richieste premio Amazon e Paypal</p>
        </div>
    </div>

<div class="card mb-4 filter-card">
    <div class="card-body">
        <div class="filter-toolbar d-flex flex-wrap align-items-start justify-content-between gap-4">
            <div class="filter-group">
                <div class="filter-group-label">Tipologia premio</div>
                <div class="filter-pills filter-pills-type" id="typeFilterGroup">
                    <button
                        type="button"
                        class="filter-pill type-pill amazon-pill {{ $type === 'amazon' ? 'active' : '' }}"
                        data-filter-type="amazon"
                    >
                        <i class="bi bi-gift me-2"></i>Amazon
                    </button>

                    <button
                        type="button"
                        class="filter-pill type-pill paypal-pill {{ $type === 'paypal' ? 'active' : '' }}"
                        data-filter-type="paypal"
                    >
                        <i class="bi bi-paypal me-2"></i>Paypal
                    </button>
                </div>
            </div>

            <div class="filter-group">
                <div class="filter-group-label">Stato pagamento</div>
                <div class="filter-pills filter-pills-status" id="statusFilterGroup">
                    <button
                        type="button"
                        class="filter-pill status-pill {{ $status === '0' ? 'active' : '' }}"
                        data-filter-status="0"
                    >
                        <i class="bi bi-clock-history me-2"></i>Da pagare
                    </button>

                    <button
                        type="button"
                        class="filter-pill status-pill {{ $status === '1' ? 'active' : '' }}"
                        data-filter-status="1"
                    >
                        <i class="bi bi-check-circle me-2"></i>Pagato
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-9" id="premiPanelTableWrapper">
        @include('partials.premiPanelTable', ['richieste' => $richieste, 'type' => $type])
    </div>

<div class="col-lg-3" id="premiPanelSummaryWrapper">
    @include('partials.premiPanelSummary', ['type' => $type, 'status' => $status, 'sidebar' => $sidebar])
</div>
</div>

</div>

<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 2000">
    <div id="toastContainer"></div>
</div>


 {{-- MODALE NOTA PAYPAL --}}

 <div class="modal fade" id="modalPaypalNote" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h6 class="modal-title">
                    <i class="bi bi-journal-text me-2"></i>Aggiungi nota Paypal
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <form id="formPaypalNote">
                    @csrf
                    <input type="hidden" id="paypalNoteRewardId" value="">

                    <div class="mb-3">
                        <label for="paypalNoteText" class="form-label">Nota</label>
                        <textarea
                            id="paypalNoteText"
                            class="form-control"
                            rows="4"
                            maxlength="255"
                            placeholder="Inserisci una nota per questa richiesta Paypal"
                            required
                        ></textarea>
                        <div class="form-text">Massimo 255 caratteri.</div>
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Annulla</button>
                <button type="button" class="btn btn-primary" id="btnSavePaypalNote">
                    <i class="bi bi-save me-1"></i> Salva nota
                </button>
            </div>
        </div>
    </div>
</div>

{{-- MODALE PAGAMENTO MASSIVO AMAZON --}}

<div class="modal fade" id="modalAmazonBulkPay" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header amazon-modal-header text-white">
                <h6 class="modal-title">
                    <i class="bi bi-gift-fill me-2"></i>Pagamento massivo premi Amazon
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div id="amazonBulkPayModalContentWrapper">
                @include('partials.amazonBulkPayModalContent', ['sidebar' => $sidebar])
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Chiudi</button>

                <button
                    type="button"
                    class="btn btn-amazon-bulk-confirm"
                    id="btnConfirmAmazonBulkPay"
                    {{ !($sidebar['amazon_bulk_pay_available'] ?? false) ? 'disabled' : '' }}
                    data-amazon-bulk-available="{{ ($sidebar['amazon_bulk_pay_available'] ?? false) ? '1' : '0' }}"
                >
                    <i class="bi bi-check2-circle me-1"></i> Paga
                </button>
            </div>
        </div>
    </div>
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
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
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

function highlightRow(row) {
    if (!row) {
        return;
    }

    row.classList.add('row-updated');

    setTimeout(() => {
        row.classList.remove('row-updated');
    }, 1500);
}

function initTooltips(scope = document) {
    const tooltipElements = scope.querySelectorAll('[data-bs-toggle="tooltip"]');

    tooltipElements.forEach(el => {
        const existingTooltip = bootstrap.Tooltip.getInstance(el);
        if (existingTooltip) {
            existingTooltip.dispose();
        }

        new bootstrap.Tooltip(el);
    });
}

document.addEventListener('DOMContentLoaded', () => {
    let currentType = @json($type);
    let currentStatus = @json($status);

    const premiPanelDataUrl = @json(route('premi.panel.data'));
const premiPanelSummaryUrl = @json(route('premi.panel.summary'));
const premiPanelBulkAmazonUrl = @json(route('premi.panel.amazon.bulk.pay'));
const premiPanelDeleteBaseUrl = @json(url('/premi-panel'));
const premiPanelPaypalPayBaseUrl = @json(url('/premi-panel/paypal'));
const premiPanelPaypalNoteBaseUrl = @json(url('/premi-panel/paypal'));

    function initPremiTable() {
        if (!window.jQuery || !$('#premi-panel-table').length) {
            return;
        }

        if ($.fn.DataTable.isDataTable('#premi-panel-table')) {
            $('#premi-panel-table').DataTable().destroy();
        }

        $('#premi-panel-table').DataTable({
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            order: [],
            scrollX: false,
            autoWidth: false,
            responsive: false,
            language: {
                emptyTable: 'Non sono presenti richieste',
                search: 'Cerca:',
                lengthMenu: 'Mostra _MENU_ richieste',
                info: 'Visualizzazione da _START_ a _END_ di _TOTAL_ richieste',
                infoEmpty: 'Nessuna richiesta disponibile',
                paginate: {
                    first: 'Inizio',
                    last: 'Fine',
                    next: '>',
                    previous: '<'
                }
            },
            columnDefs: currentType === 'paypal'
                ? [
                    { orderable: false, targets: [5, 6, 7, 8] },
                    { className: 'text-center', targets: [1, 4, 5, 6, 7, 8] }
                ]
                : [
                    { orderable: false, targets: [5, 6, 7] },
                    { className: 'text-center', targets: [1, 4, 5, 6, 7] }
                ]
        });
    }

    function setActiveFilterButton(selector, value, dataAttr) {
        document.querySelectorAll(selector).forEach(button => {
            button.classList.toggle('active', button.dataset[dataAttr] === value);
        });
    }

    function refreshSummaryPanel() {
        const summaryWrapper = document.getElementById('premiPanelSummaryWrapper');
        if (!summaryWrapper) {
            return Promise.resolve();
        }

        summaryWrapper.classList.add('loading');

        return fetch(`${premiPanelSummaryUrl}?type=${encodeURIComponent(currentType)}&status=${encodeURIComponent(currentStatus)}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
            },
        })
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                throw new Error('Errore caricamento summary');
            }

            summaryWrapper.innerHTML = data.summary_html;
            summaryWrapper.classList.remove('loading');
            initTooltips(summaryWrapper);
        })
        .catch(() => {
            summaryWrapper.classList.remove('loading');
            showToast('Errore durante l\'aggiornamento del riepilogo.', 'error');
        });
    }


function bindTableDelegatedActions() {
    const tableWrapper = document.getElementById('premiPanelTableWrapper');

    if (!tableWrapper) {
        return;
    }

    if (tableWrapper.dataset.bound === '1') {
        return;
    }

    tableWrapper.dataset.bound = '1';

    tableWrapper.addEventListener('click', (event) => {
        const paypalPayBtn = event.target.closest('.btn-pay-paypal');
        const deleteBtn = event.target.closest('.btn-delete-reward');
        const addNoteBtn = event.target.closest('.btn-add-note');
        const readNoteBtn = event.target.closest('.btn-read-note');

        if (paypalPayBtn) {
            const id = paypalPayBtn.dataset.id;

            if (!confirm('Confermi il pagamento di questa richiesta Paypal?')) {
                return;
            }

            paypalPayBtn.disabled = true;
            paypalPayBtn.innerHTML = '<i class="bi bi-hourglass-split"></i>';

            fetch(`${premiPanelPaypalPayBaseUrl}/${id}/pay`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
            })
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    paypalPayBtn.disabled = false;
                    paypalPayBtn.innerHTML = '<i class="bi bi-cash-coin"></i>';
                    showToast(data.message || 'Errore durante il pagamento.', 'error');
                    return;
                }

                const row = document.getElementById(`reward-row-${id}`);
                if (!row) {
                    showToast(data.message, 'success');
                    refreshSummaryPanel();
                    return;
                }

                const statusCell = row.querySelector('.payment-status-cell');
                const actionCell = row.querySelector('.payment-action-cell');

                if (statusCell) {
                    statusCell.innerHTML = `
                        <span class="action-icon success" title="Pagato">
                            <i class="bi bi-check-circle-fill"></i>
                        </span>
                    `;
                }

                if (actionCell) {
                    actionCell.innerHTML = `
                        <span class="badge badge-soft-success">
                            ${data.row.paid_at}
                        </span>
                    `;
                }

                reloadPremiPanelData()
                    .then(() => {
                        showToast(data.message, 'success');
                    })
                    .catch(() => {
                        showToast('Pagamento completato, ma errore nell’aggiornamento della schermata.', 'warning');
                    });

            })
            .catch(() => {
                paypalPayBtn.disabled = false;
                paypalPayBtn.innerHTML = '<i class="bi bi-cash-coin"></i>';
                showToast('Errore di connessione.', 'error');
            });

            return;
        }

        if (deleteBtn) {
            const id = deleteBtn.dataset.id;

            if (!confirm('Confermi l\'eliminazione della richiesta premio? I punti utente verranno ripristinati.')) {
                return;
            }

            deleteBtn.disabled = true;
            deleteBtn.innerHTML = '<i class="bi bi-hourglass-split"></i>';

            fetch(`${premiPanelDeleteBaseUrl}/${id}/delete`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
            })
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    deleteBtn.disabled = false;
                    deleteBtn.innerHTML = '<i class="bi bi-trash"></i>';
                    showToast(data.message || 'Errore durante l\'eliminazione.', 'error');
                    return;
                }

                const row = document.getElementById(`reward-row-${id}`);
                if (row) {
                    if ($.fn.DataTable.isDataTable('#premi-panel-table')) {
                        const table = $('#premi-panel-table').DataTable();
                        table.row($(row)).remove().draw(false);
                    } else {
                        row.remove();
                    }
                }

                refreshSummaryPanel();

                let extraMessage = '';

                if (parseInt(data.payload.points_restored || 0, 10) > 0) {
                    extraMessage += ` Punti ripristinati: ${data.payload.points_restored}.`;
                }

                if (parseInt(data.payload.released_amazon_code || 0, 10) === 1) {
                    extraMessage += ' Codice Amazon rimesso disponibile.';
                }

                showToast((data.message || 'Richiesta eliminata.') + extraMessage, 'success');
            })
            .catch(() => {
                deleteBtn.disabled = false;
                deleteBtn.innerHTML = '<i class="bi bi-trash"></i>';
                showToast('Errore di connessione.', 'error');
            });

            return;
        }

        if (addNoteBtn) {
            const rewardId = addNoteBtn.dataset.id;
            const modalEl = document.getElementById('modalPaypalNote');

            if (!modalEl) {
                return;
            }

            document.getElementById('paypalNoteRewardId').value = rewardId;
            document.getElementById('paypalNoteText').value = '';

            const modal = new bootstrap.Modal(modalEl);
            modal.show();
            return;
        }

        if (readNoteBtn) {
            return;
        }
    });
}

    function reloadPremiPanelData()
    {
        const tableWrapper = document.getElementById('premiPanelTableWrapper');
        const summaryWrapper = document.getElementById('premiPanelSummaryWrapper');

        if (tableWrapper) {
            tableWrapper.classList.add('loading');
        }

        if (summaryWrapper) {
            summaryWrapper.classList.add('loading');
        }

        return fetch(`${premiPanelDataUrl}?type=${encodeURIComponent(currentType)}&status=${encodeURIComponent(currentStatus)}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
            },
        })
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                if (tableWrapper) {
                    tableWrapper.classList.remove('loading');
                }

                if (summaryWrapper) {
                    summaryWrapper.classList.remove('loading');
                }

                showToast('Errore durante il caricamento dati.', 'error');
                return;
            }

            if (tableWrapper) {
                tableWrapper.innerHTML = data.table_html;
                tableWrapper.classList.remove('loading');
            }

            if (summaryWrapper) {
                summaryWrapper.innerHTML = data.summary_html;
                summaryWrapper.classList.remove('loading');
            }

            const amazonModalContentWrapper = document.getElementById('amazonBulkPayModalContentWrapper');
            if (amazonModalContentWrapper && typeof data.amazon_modal_html !== 'undefined') {
                amazonModalContentWrapper.innerHTML = data.amazon_modal_html;
            }

            const btnConfirmAmazonBulkPay = document.getElementById('btnConfirmAmazonBulkPay');
            if (btnConfirmAmazonBulkPay && typeof data.amazon_bulk_pay_available !== 'undefined') {
                btnConfirmAmazonBulkPay.disabled = !data.amazon_bulk_pay_available;
                btnConfirmAmazonBulkPay.setAttribute(
                    'data-amazon-bulk-available',
                    data.amazon_bulk_pay_available ? '1' : '0'
                );
                btnConfirmAmazonBulkPay.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Paga';
            }

            initPremiTable();
            bindTableDelegatedActions();
            bindAmazonBulkPayButton();
            bindAmazonBulkConfirmButton();
            initTooltips();
        })
        .catch(() => {
            if (tableWrapper) {
                tableWrapper.classList.remove('loading');
            }

            if (summaryWrapper) {
                summaryWrapper.classList.remove('loading');
            }

            showToast('Errore di connessione durante il caricamento dati.', 'error');
        });
    }

    function bindFilterButtons() {
        document.querySelectorAll('[data-filter-type]').forEach(button => {
            button.addEventListener('click', () => {
                const nextType = button.dataset.filterType;

                if (nextType === currentType) {
                    return;
                }

                currentType = nextType;
                setActiveFilterButton('[data-filter-type]', currentType, 'filterType');
                reloadPremiPanelData();
            });
        });

        document.querySelectorAll('[data-filter-status]').forEach(button => {
            button.addEventListener('click', () => {
                const nextStatus = button.dataset.filterStatus;

                if (nextStatus === currentStatus) {
                    return;
                }

                currentStatus = nextStatus;
                setActiveFilterButton('[data-filter-status]', currentStatus, 'filterStatus');
                reloadPremiPanelData();
            });
        });
    }

    const btnSavePaypalNote = document.getElementById('btnSavePaypalNote');

    if (btnSavePaypalNote) {
        btnSavePaypalNote.addEventListener('click', () => {
            const rewardId = document.getElementById('paypalNoteRewardId').value;
            const note = document.getElementById('paypalNoteText').value.trim();

            if (!rewardId || !note) {
                showToast('Inserisci una nota valida.', 'warning');
                return;
            }

            btnSavePaypalNote.disabled = true;
            btnSavePaypalNote.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> Salvataggio...';

            fetch(`${premiPanelPaypalNoteBaseUrl}/${rewardId}/note`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ note: note })
            })
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    btnSavePaypalNote.disabled = false;
                    btnSavePaypalNote.innerHTML = '<i class="bi bi-save me-1"></i> Salva nota';
                    showToast(data.message || 'Errore durante il salvataggio della nota.', 'error');
                    return;
                }

                const row = document.getElementById(`reward-row-${rewardId}`);
                if (row) {
                    const noteCell = row.querySelector('.reward-note-cell');

                    if (noteCell) {
                        const safeNote = (data.row.note || '').replace(/"/g, '&quot;');

                        noteCell.innerHTML = `
                            <button
                                type="button"
                                class="note-action note-action-read btn-read-note"
                                data-id="${data.row.id}"
                                data-note="${safeNote}"
                                data-bs-toggle="tooltip"
                                data-bs-placement="top"
                                title="${safeNote}"
                            >
                                <span class="note-action-icon">
                                    <i class="bi bi-eye"></i>
                                </span>
                                <span class="note-action-label">Leggi</span>
                            </button>
                        `;
                    }

                    highlightRow(row);
                }

                const modalEl = document.getElementById('modalPaypalNote');
                const modalInstance = bootstrap.Modal.getInstance(modalEl);
                modalInstance?.hide();

                document.getElementById('paypalNoteRewardId').value = '';
                document.getElementById('paypalNoteText').value = '';

                btnSavePaypalNote.disabled = false;
                btnSavePaypalNote.innerHTML = '<i class="bi bi-save me-1"></i> Salva nota';

                initTooltips();

                showToast(data.message, 'success');
            })
            .catch(() => {
                btnSavePaypalNote.disabled = false;
                btnSavePaypalNote.innerHTML = '<i class="bi bi-save me-1"></i> Salva nota';
                showToast('Errore di connessione.', 'error');
            });
        });
    }

    function bindAmazonBulkPayButton() {
        const btnOpenAmazonBulkPayModal = document.getElementById('btnOpenAmazonBulkPayModal');

        if (!btnOpenAmazonBulkPayModal) {
            return;
        }

        btnOpenAmazonBulkPayModal.addEventListener('click', () => {
            const modalEl = document.getElementById('modalAmazonBulkPay');

            if (!modalEl) {
                return;
            }

            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        });
    }

function bindAmazonBulkConfirmButton() {
    const btnConfirmAmazonBulkPay = document.getElementById('btnConfirmAmazonBulkPay');

    if (!btnConfirmAmazonBulkPay) {
        return;
    }

    if (btnConfirmAmazonBulkPay.dataset.bound === '1') {
        return;
    }

    btnConfirmAmazonBulkPay.dataset.bound = '1';

    btnConfirmAmazonBulkPay.addEventListener('click', () => {
        const isAvailable = btnConfirmAmazonBulkPay.getAttribute('data-amazon-bulk-available') === '1';

        if (!isAvailable) {
            showToast('Codici Amazon insufficienti per completare il pagamento massivo.', 'error');
            return;
        }

        if (!confirm('Confermi il pagamento massivo di tutte le richieste Amazon non pagate?')) {
            return;
        }

        btnConfirmAmazonBulkPay.disabled = true;
        btnConfirmAmazonBulkPay.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> Elaborazione...';

        fetch(premiPanelBulkAmazonUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
        })
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                btnConfirmAmazonBulkPay.disabled = false;
                btnConfirmAmazonBulkPay.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Paga';
                showToast(data.message || 'Errore durante il pagamento massivo Amazon.', 'error');
                return;
            }

            const modalEl = document.getElementById('modalAmazonBulkPay');
            const modalInstance = bootstrap.Modal.getInstance(modalEl);
            modalInstance?.hide();

            btnConfirmAmazonBulkPay.disabled = false;
            btnConfirmAmazonBulkPay.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Paga';

            reloadPremiPanelData()
                .then(() => {
                    if (data.download_url) {
                        const tempLink = document.createElement('a');
                        tempLink.href = data.download_url;
                        tempLink.target = '_blank';
                        tempLink.rel = 'noopener';
                        document.body.appendChild(tempLink);
                        tempLink.click();
                        document.body.removeChild(tempLink);
                    }

                    showToast(`${data.message} (${data.processed_count} richieste elaborate)`, 'success');
                })
                .catch(() => {
                    showToast('Pagamento completato, ma errore nell’aggiornamento della schermata.', 'warning');
                });
        })
        .catch(() => {
            btnConfirmAmazonBulkPay.disabled = false;
            btnConfirmAmazonBulkPay.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Paga';
            showToast('Errore di connessione durante il pagamento massivo Amazon.', 'error');
        });
    });
}

initPremiTable();
bindTableDelegatedActions();
bindFilterButtons();
bindAmazonBulkPayButton();
bindAmazonBulkConfirmButton();
initTooltips();
});
</script>
@endsection
