@extends('layouts.main')

@section('content')

<link rel="stylesheet" href="{{ asset('css/panelUsers.css') }}">

<main class="content">
    <div class="container-fluid">

        <div class="row">
            {{-- COLONNA SINISTRA --}}
            <div class="col-lg-7">
                <div class="card panel-users-card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="mb-0">Utenti Panel</h4>
                        <small class="text-muted">Consultazione utenti, inviti, attività e partecipazione</small>
                    </div>
                </div>

                    <div class="card-body">
                        <table id="panel-users-table" class="table table-sm table-striped align-middle w-100">
                            <thead>
                                <tr>
                                    <th>UID</th>
                                    <th>Email</th>
                                    <th>Età</th>
                                    <th>Inviti</th>
                                    <th>Attività</th>
                                    <th>%</th>
                                    <th>Iscrizione</th>
                                    <th>Ultima Azione</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>

            {{-- COLONNA DESTRA --}}

{{-- COLONNA DESTRA --}}
<div class="col-lg-5">

    <div class="card panel-users-card panel-stats-card">
        <div class="card-header">
            <h4 class="mb-0">Utenti attivi ultimi 18 mesi</h4>
            <small class="text-muted">Utenti attivi e confermati con almeno un'azione negli ultimi 18 mesi</small>
        </div>

        <div class="card-body">

            {{-- CARD PRINCIPALE --}}
            <div class="pu-stat-hero">
                <div class="pu-stat-hero-top">
                    <div>
                        <div class="pu-stat-label">Totale attivi 18 mesi</div>
                        <div class="pu-stat-main">
                            {{ number_format($totaleAttivi18Mesi, 0, ',', '.') }}
                            <span class="pu-stat-main-sub">
                                / {{ number_format($totalePanel, 0, ',', '.') }}
                            </span>
                        </div>
                    </div>

                    <div class="pu-stat-percent">
                        {{ $percentualeAttivi18Mesi }}%
                    </div>
                </div>

                <div class="pu-progress-wrap mt-3">
                    <div class="pu-progress-track">
                        <div class="pu-progress-fill pu-progress-fill-total" style="width: {{ $percentualeAttivi18Mesi }}%;"></div>
                    </div>
                </div>
            </div>

            {{-- MINI CARD UOMO / DONNA --}}
            <div class="row mt-3">
                <div class="col-md-6 mb-3 mb-md-0">
                    <div class="pu-mini-stat pu-mini-stat-male">
                        <div class="pu-mini-head">
                            <span class="pu-mini-title">Uomini</span>
                            <span class="pu-mini-percent">{{ $percentualeAttivi18MesiUomo }}%</span>
                        </div>

                        <div class="pu-mini-value">
                            {{ number_format($totaleAttivi18MesiUomo, 0, ',', '.') }}
                            <span class="pu-mini-sub">
                                / {{ number_format($totalePanelUomo, 0, ',', '.') }}
                            </span>
                        </div>

                        <div class="pu-progress-wrap mt-2">
                            <div class="pu-progress-track">
                                <div class="pu-progress-fill pu-progress-fill-male" style="width: {{ $percentualeAttivi18MesiUomo }}%;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="pu-mini-stat pu-mini-stat-female">
                        <div class="pu-mini-head">
                            <span class="pu-mini-title">Donne</span>
                            <span class="pu-mini-percent">{{ $percentualeAttivi18MesiDonna }}%</span>
                        </div>

                        <div class="pu-mini-value">
                            {{ number_format($totaleAttivi18MesiDonna, 0, ',', '.') }}
                            <span class="pu-mini-sub">
                                / {{ number_format($totalePanelDonna, 0, ',', '.') }}
                            </span>
                        </div>

                        <div class="pu-progress-wrap mt-2">
                            <div class="pu-progress-track">
                                <div class="pu-progress-fill pu-progress-fill-female" style="width: {{ $percentualeAttivi18MesiDonna }}%;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <div class="card panel-users-card mt-4">
    <div class="card-header">
        <h4 class="mb-0">Ricerca Utenti</h4>
        <small class="text-muted">Ricerca multipla con anteprima e download CSV</small>
    </div>

    <div class="card-body p-3 small">

    {{-- RIGA 1 --}}
    <div class="row g-3 align-items-start">
        <div class="col-md-4">
            <label class="form-label fw-bold mb-2">Tipo di ricerca</label>
            <select id="searchMode" class="form-select form-select-sm shadow-sm">
                <option value="uid" selected>UID</option>
                <option value="email">Email</option>
            </select>
        </div>

        <div class="col-md-8">
            <label id="searchPlaceholder" class="form-label text-muted mb-2">
                Inserisci UID, uno per riga
            </label>
            <textarea id="searchValues" class="form-control form-control-sm shadow-sm search-values-box" rows="4"></textarea>
        </div>
    </div>

    {{-- RIGA 2 --}}
    <div class="row g-3 align-items-start mt-1">
        <div class="col-md-7">
            <label class="form-label fw-bold mb-2">Campi da estrarre</label>

            <div class="search-fields-grid">
                <div class="form-check">
                    <input class="form-check-input search-field" type="checkbox" value="nome" id="fNome">
                    <label for="fNome" class="form-check-label">Nome</label>
                </div>

                <div class="form-check">
                    <input class="form-check-input search-field" type="checkbox" value="eta" id="fEta">
                    <label for="fEta" class="form-check-label">Età</label>
                </div>

                <div class="form-check">
                    <input class="form-check-input search-field" type="checkbox" value="provincia" id="fProvincia">
                    <label for="fProvincia" class="form-check-label">Provincia</label>
                </div>

                <div class="form-check">
                    <input class="form-check-input search-field" type="checkbox" value="regione" id="fRegione">
                    <label for="fRegione" class="form-check-label">Regione</label>
                </div>

                <div class="form-check">
                    <input class="form-check-input search-field" type="checkbox" value="area" id="fArea">
                    <label for="fArea" class="form-check-label">Area</label>
                </div>
            </div>
        </div>

        <div class="col-md-5">
            <label class="form-label fw-bold mb-2">Azioni</label>

            <div class="d-grid gap-2">
                <button id="previewUsersBtn" class="btn btn-primary btn-sm">
                    Anteprima
                </button>

                <button id="downloadUsersCsvBtn" class="btn btn-success btn-sm">
                    Download CSV
                </button>
            </div>

            <div class="form-check mt-3">
                <input class="form-check-input" type="checkbox" id="decodeLocation">
                <label for="decodeLocation" class="form-check-label fw-semibold">
                    Ricodifica provincia / regione / area
                </label>
            </div>
        </div>
    </div>

    {{-- ANTEPRIMA (nascosta di default) --}}
    <div id="searchPreviewWrapper" class="mt-4 d-none">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <h6 class="mb-0">Anteprima risultati</h6>
            <small id="previewCount" class="text-muted"></small>
        </div>

        <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle mb-0" id="searchPreviewTable">
                <thead id="searchPreviewHead"></thead>
                <tbody id="searchPreviewBody">
                    <tr>
                        <td class="text-muted text-center py-4">Nessuna anteprima disponibile</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</div>

</div>

    {{-- CARD: STATISTICHE PANEL --}}
<div class="card panel-users-card panel-stats-card mt-4">

    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <h4 class="mb-0">Statistiche Panel</h4>
            <small class="text-muted">Andamento mensile</small>
        </div>

        <form method="GET">
            <select id="panel-stats-year" class="form-select form-select-sm">
                @foreach($anniDisponibili as $anno)
                    <option value="{{ $anno }}" {{ $anno == $annoSelezionato ? 'selected' : '' }}>
                        {{ $anno }}
                    </option>
                @endforeach
            </select>
        </form>
    </div>

    <div class="card-body p-0">

        <div class="table-responsive">
            <table class="table table-hover panel-stats-table mb-0">

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

                <tbody id="panel-stats-tbody">
                    @foreach($mesi as $mese)
                        <tr>
                            <td class="fw-bold text-capitalize">
                                {{ $mese['mese_nome'] }}
                            </td>

                            <td>{{ number_format($mese['ricerche'], 0, ',', '.') }}</td>

                            <td>
                                <span class="badge bg-light text-dark">
                                    {{ $mese['ir_medio'] }}%
                                </span>
                            </td>

                            <td>{{ number_format($mese['contatti'], 0, ',', '.') }}</td>

                            <td>
                                <span class="text-primary fw-bold">
                                    {{ number_format($mese['attivi'], 0, ',', '.') }}
                                </span>
                            </td>

                            <td>
                                <span class="text-success fw-bold">
                                    {{ number_format($mese['registrati'], 0, ',', '.') }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>

            </table>
        </div>

    </div>
</div>


<div class="card panel-users-card mt-4">
    <div class="card-header d-flex align-items-center justify-content-between">
        <div>
            <h4 class="mb-0">Utenti inattivi</h4>
            <small class="text-muted">Monitoraggio inattivi e abandoners</small>
        </div>

        <button id="btnRefreshInactive" class="btn btn-light btn-sm shadow-sm">
            Aggiorna
        </button>
    </div>

    <div class="card-body">

        <div class="row g-3 align-items-end">
            <div class="col-md-6">
                <label class="form-label fw-bold mb-2">Finestra inattività</label>
                <select id="inactiveYears" class="form-select form-select-sm">
                    <option value="1">Inattivo da 1 anno</option>
                    <option value="2">Inattivo da 2 anni</option>
                    <option value="3" selected>Inattivo da 3 anni o +</option>
                </select>
            </div>
        </div>

        <div id="inactiveCountLoader" class="text-center py-4 d-none">
            <div class="spinner-border text-secondary mb-2" style="width:1.8rem;height:1.8rem;"></div>
            <div class="text-muted small">Calcolo in corso...</div>
        </div>

        <div id="inactiveCountBox" class="d-none mt-4">
            <div class="text-center mb-3">
                <div class="display-6 fw-bold text-danger mb-1" id="inactiveTotalValue">0</div>
                <div class="text-muted small">utenti totali inattivi / abandoners</div>
            </div>

            <div class="row text-center mb-3">
                <div class="col-6 border-end">
                    <div class="fw-bold text-danger fs-4" id="inactiveCountValue">0</div>
                    <div class="small text-muted">Inattivi<br>(0 actions)</div>
                </div>
                <div class="col-6">
                    <div class="fw-bold text-warning fs-4" id="abandonersCountValue">0</div>
                    <div class="small text-muted">Abandoners<br>(>0 actions)</div>
                </div>
            </div>

            <div class="progress mb-2" style="height: 10px;">
                <div id="inactivePercentBar" class="progress-bar bg-danger" role="progressbar" style="width: 0%;"></div>
            </div>

            <div class="text-muted small mb-3 text-center">
                <span id="inactivePercentValue">0%</span> su <span id="inactiveTotalActives">0</span> utenti attivi
            </div>

            <div class="d-flex justify-content-center gap-2 flex-wrap">
                <button id="btnShowInactiveList" class="btn btn-outline-danger btn-sm">
                    Mostra Inattivi
                </button>
                <button id="btnShowAbandonersList" class="btn btn-outline-warning btn-sm">
                    Mostra Abandoners
                </button>
            </div>
        </div>

    </div>
</div>

</div>
{{-- COLONNA DESTRA FINE --}}

        </div>

    </div>
</main>

<div class="modal fade" id="inactiveUsersModal" tabindex="-1" aria-labelledby="inactiveUsersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">

            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="inactiveUsersModalLabel">Elenco utenti inattivi</h5>
                    <small id="inactiveUsersModalSubtitle" class="text-muted"></small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
            </div>

            <div class="modal-body">

                <div id="inactiveUsersLoader" class="text-center py-4 d-none">
                    <div class="spinner-border text-secondary mb-2" style="width:1.8rem;height:1.8rem;"></div>
                    <div class="text-muted small">Caricamento in corso...</div>
                </div>

                <div id="inactiveUsersTableWrap" class="d-none">
                    <div class="d-flex justify-content-end mb-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="btnScrollInactiveBottom">
                            Vai in fondo
                        </button>
                    </div>

                    <div class="table-responsive" id="inactiveUsersTableResponsive">
                        <table class="table table-sm table-bordered table-hover align-middle mb-0" id="inactiveUsersTable">
                            <thead>
                                <tr>
                        <th>UID</th>
                        <th>Email</th>
                        <th>Actions</th>
                        <th>Points</th>
                        <th>Provenienza</th>
                        <th>Tipo</th>
                        <th>Inattività</th>
                        <th>Ultima azione</th>
                                </tr>
                            </thead>
                            <tbody id="inactiveUsersTableBody"></tbody>
                        </table>

                        <div class="d-flex justify-content-end mt-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="btnScrollInactiveTop">
                            Torna in alto
                        </button>
                    </div>

                    </div>
                </div>

            </div>

            <div class="modal-footer d-flex justify-content-between">
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-success" id="btnDownloadInactiveCsv">
                        Download CSV
                    </button>

                    <button type="button" class="btn btn-danger" id="btnDisableInactiveUsers">
                        Disabilita utenti
                    </button>
                </div>

                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
            </div>

        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
$(document).ready(function() {

$('#panel-users-table').DataTable({
    processing: true,
    serverSide: true,
    ajax: '{{ route("panelUsers.data") }}',
    pageLength: 100,
    lengthMenu: [25, 50, 100, 200],
    scrollX: false,
    autoWidth: false,
    order: [[7, 'desc']],
    columnDefs: [
        { targets: 0, width: '110px' },
        { targets: 1, width: '220px' },
        { targets: 2, width: '70px' },
        { targets: 3, width: '80px' },
        { targets: 4, width: '80px' },
        { targets: 5, width: '80px' },
        { targets: 6, width: '100px' },
        { targets: 7, width: '140px' }
    ],
columns: [
    { data: 'user_id',         name: 'u.user_id', searchable: true },
    { data: 'email',           name: 'u.email', searchable: true },
    { data: 'birth_date',      name: 'u.birth_date', searchable: false },
    { data: 'invites',         name: 'invites', searchable: false },
    { data: 'activity_count',  name: 'activity_count', searchable: false },
    { data: 'partecipazione',  name: 'partecipazione', orderable: false, searchable: false },
    { data: 'reg_date',        name: 'u.reg_date', searchable: false },
    { data: 'last_event_date', name: 'last_event_date', searchable: false }
],
    language: {
        url: "https://cdn.datatables.net/plug-ins/1.13.4/i18n/it-IT.json",
        search: "Cerca utente:",
        searchPlaceholder: "UID o email..."
    }
});

});
</script>

<script>
$(document).ready(function () {

    $('#panel-stats-year').on('change', function () {
        let anno = $(this).val();

        $.ajax({
            url: '{{ route("panelUsers.panelStats") }}',
            type: 'GET',
            data: { anno: anno },
            success: function (response) {
                if (!response.success || !response.mesi) {
                    return;
                }

                let rows = '';

                response.mesi.forEach(function (mese) {
                    rows += `
                        <tr>
                            <td class="fw-bold text-capitalize">${mese.mese_nome}</td>
                            <td>${Number(mese.ricerche).toLocaleString('it-IT')}</td>
                            <td>
                                <span class="badge bg-light text-dark">
                                    ${mese.ir_medio}%
                                </span>
                            </td>
                            <td>${Number(mese.contatti).toLocaleString('it-IT')}</td>
                            <td>
                                <span class="text-primary fw-bold">
                                    ${Number(mese.attivi).toLocaleString('it-IT')}
                                </span>
                            </td>
                            <td>
                                <span class="text-success fw-bold">
                                    ${Number(mese.registrati).toLocaleString('it-IT')}
                                </span>
                            </td>
                        </tr>
                    `;
                });

                $('#panel-stats-tbody').html(rows);
            },
            error: function () {
                alert('Errore nel caricamento delle statistiche del panel.');
            }
        });
    });

});
</script>


<script>
$(document).ready(function () {

    function getSelectedSearchFields() {
        let fields = [];
        $('.search-field:checked').each(function () {
            fields.push($(this).val());
        });
        return fields;
    }

    function buildPreviewTable(columns, rows) {
        let headHtml = '<tr>';
        columns.forEach(function (col) {
            headHtml += `<th>${col}</th>`;
        });
        headHtml += '</tr>';

        $('#searchPreviewHead').html(headHtml);

        if (!rows.length) {
            $('#searchPreviewBody').html(`
                <tr>
                    <td colspan="${columns.length}" class="text-muted text-center py-4">
                        Nessun risultato trovato
                    </td>
                </tr>
            `);
            return;
        }

        let bodyHtml = '';
        rows.forEach(function (row) {
            bodyHtml += '<tr>';
            columns.forEach(function (col) {
                bodyHtml += `<td>${row[col] ?? ''}</td>`;
            });
            bodyHtml += '</tr>';
        });

        $('#searchPreviewBody').html(bodyHtml);
    }

    $('#searchMode').on('change', function () {
        let mode = $(this).val();

        if (mode === 'email') {
            $('#searchPlaceholder').text('Inserisci Email, una per riga');
        } else {
            $('#searchPlaceholder').text('Inserisci UID, uno per riga');
        }
    });

    $('#previewUsersBtn').on('click', function () {
        let mode = $('#searchMode').val();
        let values = $('#searchValues').val();
        let fields = getSelectedSearchFields();
        let decodeLocation = $('#decodeLocation').is(':checked') ? 1 : 0;

        $.ajax({
            url: '{{ route("panelUsers.searchPreview") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                mode: mode,
                values: values,
                fields: fields,
                decode_location: decodeLocation
            },
            success: function (response) {
                if (!response.success) {
                    return;
                }

                $('#previewCount').text(response.count + ' risultati');
                buildPreviewTable(response.columns, response.rows);

                $('#searchPreviewWrapper').removeClass('d-none');
            },
            error: function () {
                alert('Errore durante la ricerca utenti.');
            }
        });
    });

    $('#downloadUsersCsvBtn').on('click', function () {
        let mode = $('#searchMode').val();
        let values = $('#searchValues').val();
        let fields = getSelectedSearchFields();
        let decodeLocation = $('#decodeLocation').is(':checked') ? 1 : 0;

        let form = $('<form>', {
            method: 'POST',
            action: '{{ route("panelUsers.searchDownload") }}'
        });

        form.append($('<input>', {
            type: 'hidden',
            name: '_token',
            value: '{{ csrf_token() }}'
        }));

        form.append($('<input>', {
            type: 'hidden',
            name: 'mode',
            value: mode
        }));

        form.append($('<input>', {
            type: 'hidden',
            name: 'values',
            value: values
        }));

        form.append($('<input>', {
                type: 'hidden',
                name: 'decode_location',
                value: decodeLocation
            }));

        fields.forEach(function (field) {
            form.append($('<input>', {
                type: 'hidden',
                name: 'fields[]',
                value: field
            }));
        });

        $('body').append(form);
        form.submit();
        form.remove();
    });

});
</script>

<script>

// ✅ FUNZIONE GLOBALE (FUORI)
function loadInactiveSummary() {
    let years = $('#inactiveYears').val();

    $('#inactiveCountLoader').removeClass('d-none');
    $('#inactiveCountBox').addClass('d-none');

    $.ajax({
        url: '{{ route("panelUsers.inactiveSummary") }}',
        type: 'GET',
        data: { years: years },
        success: function (response) {
            if (!response.success) {
                return;
            }

            $('#inactiveTotalValue').text(response.totalInactive.toLocaleString('it-IT'));
            $('#inactiveCountValue').text(response.inactiveCount.toLocaleString('it-IT'));
            $('#abandonersCountValue').text(response.abandonersCount.toLocaleString('it-IT'));
            $('#inactivePercentValue').text(response.inactivePercent + '%');
            $('#inactiveTotalActives').text(response.totalActives.toLocaleString('it-IT'));
            $('#inactivePercentBar').css('width', response.inactivePercent + '%');

            $('#inactiveCountLoader').addClass('d-none');
            $('#inactiveCountBox').removeClass('d-none');
        },
        error: function () {
            $('#inactiveCountLoader').addClass('d-none');
            alert('Errore nel caricamento utenti inattivi.');
        }
    });
}


// ✅ QUI RESTA SOLO LA PARTE EVENTI
$(document).ready(function () {

    $('#btnRefreshInactive').on('click', function () {
        loadInactiveSummary();
    });

    $('#inactiveYears').on('change', function () {
        loadInactiveSummary();
    });

    // primo caricamento
    loadInactiveSummary();

});

</script>

<script>
let currentInactiveListType = 'inactive';

$(document).ready(function () {

    function loadInactiveUsersList(type) {
        currentInactiveListType = type;
        let years = $('#inactiveYears').val();

        $('#inactiveUsersTableWrap').addClass('d-none');
        $('#inactiveUsersLoader').removeClass('d-none');
        $('#inactiveUsersTableBody').html('');

        let modalTitle = (type === 'inactive') ? 'Elenco utenti inattivi' : 'Elenco abandoners';
        let modalSubtitle = 'Filtro inattività: ' + $('#inactiveYears option:selected').text();

        $('#inactiveUsersModalLabel').text(modalTitle);
        $('#inactiveUsersModalSubtitle').text(modalSubtitle);

        let modalEl = document.getElementById('inactiveUsersModal');
        let modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();

        $.ajax({
            url: '{{ route("panelUsers.inactiveList") }}',
            type: 'GET',
            data: {
                years: years,
                type: type
            },
            success: function (response) {
                $('#inactiveUsersLoader').addClass('d-none');

                if (!response.success) {
                    return;
                }

                let rows = '';

                if (!response.rows.length) {
                    rows = `
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                Nessun utente trovato
                            </td>
                        </tr>
                    `;
                } else {
                    response.rows.forEach(function (row) {
                        rows += `
                        <tr>
                            <td class="fw-bold">${row.uid}</td>
                            <td>${row.email ?? ''}</td>
                            <td>${row.actions ?? 0}</td>
                            <td>${row.points ?? 0}</td>
                            <td>${row.provenienza ?? 'N.D.'}</td>
                            <td>${row.tipo}</td>
                            <td>${row.inattivita}</td>
                            <td>${row.ultima_azione}</td>
                        </tr>
                    `;
                    });
                }

                $('#inactiveUsersTableBody').html(rows);
                $('#inactiveUsersTableWrap').removeClass('d-none');
            },
            error: function () {
                $('#inactiveUsersLoader').addClass('d-none');
                $('#inactiveUsersTableBody').html(`
                    <tr>
                        <td colspan="8" class="text-center text-danger py-4">
                            Errore nel caricamento della lista utenti
                        </td>
                    </tr>
                `);
                $('#inactiveUsersTableWrap').removeClass('d-none');
            }
        });
    }

    $('#btnShowInactiveList').on('click', function () {
        loadInactiveUsersList('inactive');
    });

    $('#btnShowAbandonersList').on('click', function () {
        loadInactiveUsersList('abandoner');
    });

        // 🔽 SCROLL IN FONDO
    $('#btnScrollInactiveBottom').on('click', function () {
        const container = document.getElementById('inactiveUsersTableResponsive');

        if (container) {
            container.scrollTop = container.scrollHeight;
        }
    });

    // 🔼 SCROLL IN ALTO
    $('#btnScrollInactiveTop').on('click', function () {
        const container = document.getElementById('inactiveUsersTableResponsive');

        if (container) {
            container.scrollTop = 0;
        }
    });

});
</script>

<script>
$(document).ready(function () {

    $('#btnDownloadInactiveCsv').on('click', function () {
        let years = $('#inactiveYears').val();
        let type = currentInactiveListType || 'inactive';

        let url = '{{ route("panelUsers.downloadInactiveList") }}'
            + '?years=' + encodeURIComponent(years)
            + '&type=' + encodeURIComponent(type);

        window.location.href = url;
    });

    $('#btnDisableInactiveUsers').on('click', function () {
        let years = $('#inactiveYears').val();
        let type = currentInactiveListType || 'inactive';

        let label = (type === 'inactive') ? 'inattivi' : 'abandoners';

        if (!confirm('Sei sicuro di voler disabilitare tutti gli utenti ' + label + ' della soglia selezionata?')) {
            return;
        }

        $.ajax({
            url: '{{ route("panelUsers.disableInactiveUsers") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                years: years,
                type: type
            },
            success: function (response) {
                if (!response.success) {
                    alert('Errore durante la disabilitazione utenti.');
                    return;
                }

                alert(response.updated + ' utenti disabilitati correttamente.');

                // ricarica summary
                if (typeof loadInactiveSummary === 'function') {
                    loadInactiveSummary();
                }

                // ricarica lista aperta
                if (typeof loadInactiveUsersList === 'function') {
                    loadInactiveUsersList(type);
                }

                // ricarica tabella utenti sinistra, se presente
                if ($.fn.DataTable.isDataTable('#panel-users-table')) {
                    $('#panel-users-table').DataTable().ajax.reload(null, false);
                }
            },
            error: function () {
                alert('Errore durante la disabilitazione utenti.');
            }
        });
    });

});
</script>

@endsection
