@extends('layouts.main')

@section('content')

{{-- 🎨 STILI PERSONALIZZATI --}}
<style>
    /* ======== Card generale ======== */
    .card {
        border: none;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        transition: all 0.3s ease-in-out;
        background: #fff;
    }

    .card:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 18px rgba(0,0,0,0.12);
    }

    /* ======== Header moderno con gradiente ======== */
    .card-header {
        border: none;
        font-weight: 600;
        letter-spacing: 0.3px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.8rem 1rem;
    }

    .header-primary {
        background: linear-gradient(90deg, #007bff, #00bfff);
        color: white;
    }

    .header-info {
        background: linear-gradient(90deg, #17a2b8, #00c4cc);
        color: white;
    }

    .header-secondary {
        background: linear-gradient(90deg, #6c757d, #868e96);
        color: white;
    }

    .card-header h5, .card-header h6 {
        margin: 0;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    /* ======== Bottoni ======== */
    .btn-modern {
        border-radius: 20px;
        font-weight: 600;
        transition: all 0.2s ease;
    }

    .btn-modern:hover {
        transform: translateY(-1px);
        opacity: 0.9;
    }

    /* ======== Tabelle ======== */
    table th {
        font-size: 0.85rem;
        text-transform: uppercase;
        font-weight: 600;
        background-color: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
    }

    table td {
        vertical-align: middle;
        font-size: 0.9rem;
    }

    /* ======== Badge anno ======== */
    #annoTitle {
        background: rgba(255,255,255,0.2);
        border-radius: 6px;
        font-size: 0.8rem;
        font-weight: 600;
        padding: 3px 8px;
    }

    /* ======== Loader ======== */
    #loaderPanelInfo {
        color: #6c757d;
    }

    /* ======== Animazioni icone ======== */
    .card-header i {
        font-size: 1.1rem;
        opacity: 0.9;
    }

    /* ======== Responsive tweaks ======== */
    @media (max-width: 992px) {
        .card {
            margin-bottom: 1rem;
        }
    }


    /* Migliora compattezza tabella */
    #usersTable {
        font-size: 0.8rem;
        line-height: 1.2;
        white-space: nowrap; /* evita ritorni a capo */
    }

    #usersTable td, #usersTable th {
        padding: 4px 6px !important;
        vertical-align: middle;
    }

    /* Tronca testi troppo lunghi con ellissi */
    #usersTable td {
        max-width: 160px;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Header più compatto */
    .card-header {
        padding: 0.6rem 0.8rem;
    }

    /* Colore alternato per righe */
    #usersTable tbody tr:nth-child(even) {
        background-color: #f9f9f9;
    }

    /* Etichette badge più piccole */
    .badge {
        font-size: 0.7rem;
        padding: 3px 6px;
    }

    /* Colonna % con badge centrato */
    td.text-center {
        text-align: center;
    }

</style>

<div class="container-fluid mt-4">
    <div class="row g-4">

        {{-- ================== COLONNA SINISTRA ================== --}}
        <div class="col-lg-8">

            {{-- HEADER UTENTI --}}
            <div class="card mb-4">
<div class="card-header header-primary d-flex justify-content-between align-items-center">
    <h5 class="mb-0"><i class="bi bi-people-fill"></i> Gestione Utenti Panel</h5>
    <button id="btnUpdateActivity" class="btn btn-sm btn-light text-primary fw-bold shadow-sm">
        <i class="bi bi-arrow-repeat me-1"></i> Aggiorna dati
    </button>
</div>

                <div class="card-body bg-light">
                    <table id="usersTable" class="table table-hover table-bordered align-middle mb-0">
                        <thead class="table-primary text-center">
                            <tr>
                                <th>UID</th>
                                <th>Email</th>
                                <th>Età</th>
                                <th>Inviti</th>
                                <th>Attività</th>
                                <th> %</th>
                                <th>Iscrizione</th>
                                <th>Ultima Azione</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>

        {{-- ================== COLONNA DESTRA ================== --}}
        <div class="col-lg-4">

            {{-- INFO GENERALI PANEL --}}
            <div class="card mb-4">
                <div class="card-header header-info">
                    <h6>
                        <i class="bi bi-bar-chart-fill"></i> Info Generali Panel
                        <span id="annoTitle" class="ms-2"></span>
                    </h6>
                    <select id="annoSelect" class="form-select form-select-sm w-auto text-dark border-0 shadow-sm">
                        @for ($i = now()->year; $i >= now()->year - 10; $i--)
                            <option value="{{ $i }}">{{ $i }}</option>
                        @endfor
                    </select>
                </div>

                <div class="card-body p-2">
                    <div id="loaderPanelInfo" class="text-center my-3" style="display:none;">
                        <div class="spinner-border text-info" role="status" style="width: 2rem; height: 2rem;">
                            <span class="visually-hidden">Caricamento...</span>
                        </div>
                        <p class="mt-2 mb-0 text-muted small">Caricamento dati...</p>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm table-striped table-hover align-middle mb-0 text-center" id="panelInfoTable">
                            <thead>
                                <tr>
                                    <th>Mese</th>
                                    <th>Ricerche</th>
                                    <th>IR medio</th>
                                    <th>Contatti</th>
                                    <th>Attivi</th>
                                    <th>Registrati</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- RICERCA UTENTI --}}
            <div class="card mb-4">
                <div class="card-header header-secondary">
                    <h6><i class="bi bi-search"></i> Ricerca Utenti</h6>
                </div>

                <div class="card-body p-3 small">
                    {{-- Tipo ricerca --}}
                    <div class="mb-3">
                        <label class="form-label fw-bold"><i class="bi bi-funnel me-1"></i> Tipo di ricerca</label>
                        <select id="searchMode" class="form-select form-select-sm shadow-sm">
                            <option value="uid" selected>UID</option>
                            <option value="email">Email</option>
                        </select>
                    </div>

                    {{-- Textarea --}}
                    <div class="mb-3">
                        <label id="searchPlaceholder" class="form-label text-muted">Inserisci UID, uno per riga</label>
                        <textarea id="searchValues" class="form-control form-control-sm shadow-sm" rows="4"></textarea>
                    </div>

                    {{-- Campi da estrarre --}}
                    <div class="border-top pt-2 mt-3">
                        <label class="form-label fw-bold mb-2">
                            <i class="bi bi-list-check me-1"></i> Campi da estrarre
                        </label>
                        <div class="row g-1">
                            <div class="col-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="nome" id="fNome">
                                    <label for="fNome" class="form-check-label">Nome</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="eta" id="fEta">
                                    <label for="fEta" class="form-check-label">Età</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="provincia" id="fProvincia">
                                    <label for="fProvincia" class="form-check-label">Provincia</label>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="regione" id="fRegione">
                                    <label for="fRegione" class="form-check-label">Regione</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="area" id="fArea">
                                    <label for="fArea" class="form-check-label">Area</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Bottone Download --}}
                    <div class="text-center mt-4">
                        <button id="downloadCsv" class="btn btn-success btn-modern px-3 shadow-sm">
                            <i class="bi bi-file-earmark-spreadsheet-fill me-1"></i> Download CSV
                        </button>
                    </div>
                </div>
            </div>

{{-- UTENTI INATTIVI 3+ ANNI --}}
<div class="card mb-4 shadow-sm border-0">
    <div class="card-header header-secondary d-flex align-items-center justify-content-between">
        <h6 class="mb-0"><i class="bi bi-clock-history"></i> Utenti inattivi da 3+ anni</h6>
        <div class="d-flex gap-2">
            <button id="btnRefreshInactive" class="btn btn-sm btn-light text-secondary shadow-sm">
                <i class="bi bi-arrow-clockwise"></i>
            </button>
        </div>
    </div>

    <div class="card-body text-center py-4 position-relative">
        {{-- Loader --}}
        <div id="inactiveCountLoader" class="text-muted small" style="display:none;">
            <div class="spinner-border text-secondary mb-2" style="width:1.8rem;height:1.8rem;"></div>
            <div>Calcolo in corso...</div>
        </div>

        {{-- Contenuto principale --}}
        <div id="inactiveCountBox" class="d-none">
            <div class="mb-3">
                <div class="display-6 fw-bold text-danger mb-1" id="inactiveTotalValue">0</div>
                <div class="text-muted small">utenti totali inattivi / abandoners (≥ 3 anni)</div>
            </div>

            {{-- Split numerico --}}
            <div class="row justify-content-center mb-3">
                <div class="col-5 border-end">
                    <div class="fw-bold text-danger fs-5" id="inactiveCountValue">0</div>
                    <div class="small text-muted">Inattivi<br>(0 actions)</div>
                </div>
                <div class="col-5">
                    <div class="fw-bold text-warning fs-5" id="abandonersCountValue">0</div>
                    <div class="small text-muted">Abandoners<br>(>0 actions)</div>
                </div>
            </div>

            {{-- Barra complessiva --}}
            <div class="progress mx-auto mb-2" style="height: 10px; width: 80%;">
                <div id="inactivePercentBar" class="progress-bar bg-danger" role="progressbar" style="width: 0%;"></div>
            </div>

            <div class="text-muted small mb-3">
                <span id="inactivePercentValue">0%</span> su <span id="inactiveTotalActives">0</span> utenti attivi
            </div>

            <div class="d-flex justify-content-center gap-2">
                <button id="btnShowInactiveList" class="btn btn-sm btn-outline-danger shadow-sm">
                    <i class="bi bi-person-x"></i> Mostra Inattivi
                </button>
                <button id="btnShowAbandonersList" class="btn btn-sm btn-outline-warning shadow-sm">
                    <i class="bi bi-person-dash"></i> Mostra Abandoners
                </button>
            </div>
        </div>
    </div>
</div>









        </div>
        {{-- ====== FINE COLONNA DESTRA ====== --}}
    </div>
</div>



{{-- 🔹 Modal elenco utenti inattivi --}}
<div class="modal fade" id="modalInactiveList" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-secondary text-white">
        <h6 class="modal-title"><i class="bi bi-list-ul me-1"></i> Elenco utenti inattivi da 3+ anni</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-0">
        <div id="inactiveListLoader" class="text-center my-4">
          <div class="spinner-border text-secondary" style="width: 2rem; height: 2rem;"></div>
          <p class="mt-2 text-muted small">Caricamento in corso...</p>
        </div>
        <div class="table-responsive">
          <table class="table table-sm table-striped table-hover align-middle mb-0 text-center" id="inactiveUsersTable" style="display:none;">
            <thead class="table-light">
              <tr>
                <th>Data Registrazione</th>
                <th>User ID</th>
                <th>Email</th>
                <th>Provenienza</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
          <i class="bi bi-x-circle"></i> Chiudi
        </button>
      </div>
    </div>
  </div>
</div>



@endsection

{{-- 🔹 Toasts Bootstrap per messaggi dinamici --}}
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 2000;">
  <div id="toastContainer"></div>
</div>

<script>
/**
 * Mostra un toast dinamico.
 * @param {string} message - Testo del messaggio
 * @param {string} type - success | error | warning | info
 */
function showToast(message, type = 'success') {
    const toastId = 'toast-' + Date.now();
    let bgClass = 'bg-success text-white';
    if (type === 'error') bgClass = 'bg-danger text-white';
    else if (type === 'warning') bgClass = 'bg-warning text-dark';
    else if (type === 'info') bgClass = 'bg-info text-white';

    const toastHtml = `
      <div id="${toastId}" class="toast align-items-center ${bgClass} border-0 shadow mb-2" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
          <div class="toast-body fw-semibold">${message}</div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
      </div>
    `;
    const container = document.getElementById('toastContainer');
    container.insertAdjacentHTML('beforeend', toastHtml);

    const toastEl = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastEl, { delay: 3000 });
    toast.show();

    toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
}
</script>



@section('scripts')
<script>



$(document).ready(function () {
    let table = $('#usersTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("panel.users.data") }}',
            type: 'GET',
            dataSrc: function (json) {
                console.log('✅ JSON ricevuto', json);
                return json.data;
            }
        },
columns: [
   {
  data: 'user_id',
  title: 'UID',
  render: function(data, type, row) {
      return `<a target="_blank" href="/panel/user/${data}" class="fw-bold text-primary text-decoration-none">
                <i class="bi bi-person-fill me-1"></i>${data}
              </a>`;
  }
},
    {
        data: 'email',
        title: 'Email',
        render: function (data, type, row) {
            if (!row.email_valida) {
                return `<span style="color:red;font-weight:bold;">${data}</span>`;
            }
            return data;
        }
    },
    { data: 'eta', title: 'Età', className: 'text-center', defaultContent: '-' },
    {
    data: 'anzianita',
    title: 'Anni Iscrizione',
    className: 'text-center',
    render: function(data) {
        if (!data) return '-';
        let color = '#6c757d'; // default
        if (data.includes('0-3')) color = '#00bcd4';
        else if (data.includes('3-6')) color = '#4caf50';
        else if (data.includes('6-11')) color = '#8bc34a';
        else if (data.includes('1 anno')) color = '#cddc39';
        else if (data.includes('2 anni')) color = '#ffc107';
        else if (data.includes('3 anni')) color = '#ff9800';
        else if (data.includes('4-5')) color = '#ff5722';
        else if (data.includes('6-9')) color = '#9c27b0';
        else if (data.includes('10')) color = '#f44336';
        return `<span class="badge" style="background:${color};">${data}</span>`;
    }
},
{
    data: 'ultima_attivita',
    title: 'Ultima Azione',
    className: 'text-center',
    defaultContent: '-',
    render: function (data, type, row) {
        if (!data) return '-';
        // Rimuove eventuali frazioni di secondo e formato compatto
        return data.replace(/\.\d+$/, '').replace('T', ' ').substring(0, 16);
    }
},
    { data: 'inviti', title: 'Inviti', className: 'text-center' },
    { data: 'attivita', title: 'Attività', className: 'text-center' },
    { data: 'partecipazione', title: '%', className: 'text-center' }
],

order: [[5, 'desc']],
columnDefs: [
    { orderable: false, targets: [0,1,2,3,4,5,6] },
],


        pageLength: 40,
        responsive: true,
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/it-IT.json'
        }
    });
});


// =====================
// 🔄 AGGIORNA DATI PANEL + ACTIONS (con toasts)
// =====================
$('#btnUpdateActivity').on('click', function() {
    if (!confirm('Vuoi aggiornare i dati del panel? L’operazione può richiedere alcuni minuti.')) return;

    const btn = $(this);
    btn.prop('disabled', true).html('<i class="bi bi-hourglass-split me-1"></i> Aggiornamento in corso...');

    // STEP 1️⃣ - Aggiorna attività
    $.get('{{ route("panel.users.update.activity") }}')
        .done(function(resp) {
            if (resp.success) {
                showToast('Aggiornamento attività completato, aggiorno azioni...', 'info');

                // STEP 2️⃣ - Aggiorna campo actions
                $.get('{{ route("panel.users.update.actions") }}')
                    .done(function(resp2) {
                        if (resp2.success) {
                            showToast('✅ ' + resp2.message, 'success');
                            $('#usersTable').DataTable().ajax.reload(null, false);
                        } else {
                            showToast('⚠️ ' + (resp2.message || 'Aggiornamento actions non riuscito.'), 'warning');
                        }
                    })
                    .fail(function(xhr) {
                        showToast('❌ Errore update actions: ' + xhr.statusText, 'error');
                    })
                    .always(function() {
                        btn.prop('disabled', false).html('<i class="bi bi-arrow-repeat me-1"></i> Aggiorna dati');
                    });
            } else {
                showToast('⚠️ ' + (resp.message || 'Aggiornamento attività non riuscito.'), 'warning');
                btn.prop('disabled', false).html('<i class="bi bi-arrow-repeat me-1"></i> Aggiorna dati');
            }
        })
        .fail(function(xhr) {
            showToast('❌ Errore update attività: ' + xhr.statusText, 'error');
            btn.prop('disabled', false).html('<i class="bi bi-arrow-repeat me-1"></i> Aggiorna dati');
        });
});




function loadPanelInfo(anno) {
    // Mostra loader
    $('#loaderPanelInfo').show();
    $('#panelInfoTable tbody').empty();
    $('#annoTitle').text(`Anno ${anno}`);

    $.get('{{ url("/panel/info-annuale") }}/' + anno)
        .done(function(data) {
            const tbody = $('#panelInfoTable tbody');
            tbody.empty();

            data.forEach(row => {
                tbody.append(`
                    <tr>
                        <td><strong>${row.mese}</strong></td>
                        <td>${row.ricerche}</td>
                        <td>${row.ir_medio}</td>
                        <td>${row.contatti}</td>
                        <td>${row.attivi}</td>
                        <td>${row.registrati}</td>
                    </tr>
                `);
            });
        })
        .fail(function() {
            alert('❌ Errore nel caricamento dei dati.');
        })
        .always(function() {
            // Nasconde il loader dopo 0.5s per un effetto più morbido
            setTimeout(() => $('#loaderPanelInfo').fadeOut(300), 500);
        });
}

// Cambio anno
$('#annoSelect').on('change', function() {
    const anno = $(this).val();
    loadPanelInfo(anno);
});

// Caricamento iniziale
$(document).ready(() => {
    const annoCorrente = $('#annoSelect').val();
    $('#annoTitle').text(`Anno ${annoCorrente}`);
    loadPanelInfo(annoCorrente);
});


// Cambia testo placeholder in base al tipo di ricerca
$('#searchMode').on('change', function() {
    const mode = $(this).val();
    $('#searchPlaceholder').text(
        mode === 'email'
            ? 'Inserisci email, una per riga'
            : 'Inserisci UID, uno per riga'
    );
});

// Download CSV
$('#downloadCsv').on('click', function() {
    const mode = $('#searchMode').val();
    const values = $('#searchValues').val();
    const fields = [];
    $('.form-check-input:checked').each(function() {
        fields.push($(this).val());
    });

    if (!values.trim()) {
        alert('⚠️ Inserisci almeno un valore da cercare.');
        return;
    }

    const form = $('<form>', {
        method: 'POST',
        action: '{{ route("panel.users.export") }}'
    });

    form.append('@csrf'.replace('@', '<input type="hidden" name="_token" value="{{ csrf_token() }}">'));
    form.append(`<input type="hidden" name="mode" value="${mode}">`);
    form.append(`<input type="hidden" name="values" value="${values.replace(/\n/g, '&#10;')}">`);
    fields.forEach(f => {
        form.append(`<input type="hidden" name="fields[]" value="${f}">`);
    });

    $('body').append(form);
    form.submit();
    form.remove();
});


// =======================
// 👥 INATTIVI + ABANDONERS SPLITTATI
// =======================
function loadInactiveUsers(showToastMessage = false) {
    $('#inactiveCountLoader').show();
    $('#inactiveCountBox').addClass('d-none');

    $.get('{{ route("panel.users.inactive.3y") }}')
        .done(function(resp) {
            if (resp.success) {
                const tot = resp.totale || 0;
                const attivi = resp.tot_attivi || 0;
                const percTot = resp.perc_totale || 0;

                $('#inactiveTotalValue').text(tot.toLocaleString());
                $('#inactiveTotalActives').text(attivi.toLocaleString());
                $('#inactivePercentValue').text(percTot + '%');
                $('#inactivePercentBar').css('width', percTot + '%');

                // Split dettagli
                $('#inactiveCountValue').text(resp.inattivi.toLocaleString());
                $('#abandonersCountValue').text(resp.abandoners.toLocaleString());

                $('#inactiveCountBox').removeClass('d-none');
            }
        })
        .always(() => $('#inactiveCountLoader').hide());
}




</script>
@endsection

