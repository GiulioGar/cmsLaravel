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
</style>

<div class="container-fluid mt-4">
    <div class="row g-4">

        {{-- ================== COLONNA SINISTRA ================== --}}
        <div class="col-lg-8">

            {{-- HEADER UTENTI --}}
            <div class="card mb-4">
                <div class="card-header header-primary">
                    <h5><i class="bi bi-people-fill"></i> Gestione Utenti Panel</h5>
                    <button id="refreshCache" class="btn btn-sm btn-light text-primary btn-modern">
                        <i class="bi bi-arrow-clockwise me-1"></i> Aggiorna Attività
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
        </div>
        {{-- ====== FINE COLONNA DESTRA ====== --}}
    </div>
</div>
@endsection




@section('scripts')
<script>

$('#refreshCache').on('click', function() {
    if (confirm('Vuoi aggiornare i dati di attività? Questa operazione può richiedere alcuni secondi.')) {
        $(this).prop('disabled', true).text('⏳ Aggiornamento in corso...');
        $.post('{{ route("panel.users.refresh") }}', {_token: '{{ csrf_token() }}'}, function(resp) {
            if (resp.success) {
                alert('✅ ' + resp.message);
                $('#usersTable').DataTable().ajax.reload();
            } else {
                alert('❌ Errore: ' + resp.message);
            }
        }).always(() => {
            $('#refreshCache').prop('disabled', false).text('🔄 Aggiorna Attività');
        });
    }
});


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
{ data: 'ultima_attivita', title: 'Ultima Azione', className: 'text-center', defaultContent: '-' },
    { data: 'inviti', title: 'Inviti', className: 'text-center' },
    { data: 'attivita', title: 'Attività', className: 'text-center' },
    { data: 'partecipazione', title: '%', className: 'text-center' }
],

order: [[5, 'desc']],
columnDefs: [
    { orderable: false, targets: [0,1,2,3,4,5,6] },
],


        pageLength: 25,
        responsive: true,
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/it-IT.json'
        }
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



</script>
@endsection

