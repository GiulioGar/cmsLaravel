@extends('layouts.main')

@section('head')
<link rel="stylesheet" href="{{ asset('css/recruitment.css') }}">
@endsection

@section('content')

<main class="content recruitment-page">
    <div class="container-fluid">

<div class="recruitment-header mb-4 d-flex justify-content-between align-items-start flex-wrap gap-3">
    <div>
        <h2 class="mb-1">Recruitment Monitor</h2>
        <p class="text-muted mb-0">
            Monitoraggio campagne di reclutamento, costi, attività e statistiche per referral
        </p>
    </div>

    <div class="d-flex gap-2 flex-wrap">
        <button type="button" class="btn btn-outline-primary" id="btnOpenReportModal">
            <i class="bi bi-download me-1"></i>
            Genera Report
        </button>

        <button type="button" class="btn btn-primary" id="btnOpenCampaignModal">
            <i class="bi bi-plus-circle me-1"></i>
            Gestione Campagne
        </button>
    </div>
</div>

        <div class="row g-4">

            {{-- COLONNA SINISTRA --}}
            <div class="col-xl-6">

                {{-- BOX RIEPILOGO ANNO --}}
                <div class="card recruitment-card mb-4">
                    <div class="card-header recruitment-card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">Riepilogo anno</h5>
                            <small class="text-muted">Panoramica generale registrati e attivi</small>
                        </div>

                        <div class="filter-box">
                            <label for="filterSummaryYear" class="form-label mb-1">Anno</label>
                            <select id="filterSummaryYear" class="form-select form-select-sm">
                                @foreach($years as $year)
                                    <option value="{{ $year }}" {{ $year == $currentYear ? 'selected' : '' }}>
                                        {{ $year }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="card-body">
                        <div id="summaryYearBox" class="placeholder-box">
                            Box riepilogo annuale
                        </div>
                    </div>
                </div>

                {{-- BOX SPESE --}}
                <div class="card recruitment-card mb-4">
                    <div class="card-header recruitment-card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">Spese referral</h5>
                            <small class="text-muted">Costi, registrati e attivi per referral</small>
                        </div>

                        <div class="filter-box">
                            <label for="filterCostsYear" class="form-label mb-1">Anno</label>
                            <select id="filterCostsYear" class="form-select form-select-sm">
                                @foreach($years as $year)
                                    <option value="{{ $year }}" {{ $year == $currentYear ? 'selected' : '' }}>
                                        {{ $year }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="card-body">
                        <div id="costsBox" class="placeholder-box">
                            Box spese referral
                        </div>
                    </div>
                </div>

                {{-- BOX ATTIVITA --}}
                <div class="card recruitment-card mb-4">
                    <div class="card-header recruitment-card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">Dettaglio attività per referral</h5>
                            <small class="text-muted">Distribuzione utenti per fasce di attività</small>
                        </div>

                        <div class="filter-box">
                            <label for="filterActivityYear" class="form-label mb-1">Anno</label>
                            <select id="filterActivityYear" class="form-select form-select-sm">
                                @foreach($years as $year)
                                    <option value="{{ $year }}" {{ $year == $currentYear ? 'selected' : '' }}>
                                        {{ $year }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="card-body">
                        <div id="activityBox" class="placeholder-box">
                            Box dettaglio attività
                        </div>
                    </div>
                </div>

            </div>

            {{-- COLONNA DESTRA --}}
            <div class="col-xl-6">

                {{-- BOX RIEPILOGO MENSILE --}}
                <div class="card recruitment-card mb-4">
                    <div class="card-header recruitment-card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <div>
                            <h5 class="mb-0">Riepilogo mensile per referral</h5>
                            <small class="text-muted">Conteggi mensili filtrati per mese e anno</small>
                        </div>

                        <div class="d-flex gap-2">
                            <div class="filter-box">
                                <label for="filterDailyMonth" class="form-label mb-1">Mese</label>
                                <select id="filterDailyMonth" class="form-select form-select-sm">
                                    @foreach($months as $monthNumber => $monthLabel)
                                        <option value="{{ $monthNumber }}" {{ $monthNumber == $currentMonth ? 'selected' : '' }}>
                                            {{ $monthLabel }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="filter-box">
                                <label for="filterDailyYear" class="form-label mb-1">Anno</label>
                                <select id="filterDailyYear" class="form-select form-select-sm">
                                    @foreach($years as $year)
                                        <option value="{{ $year }}" {{ $year == $currentYear ? 'selected' : '' }}>
                                            {{ $year }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div id="dailyBox" class="placeholder-box">
                            Box riepilogo mensile
                        </div>
                    </div>
                </div>

                {{-- BOX LOG ULTIMI REGISTRATI --}}
                <div class="card recruitment-card mb-4">
                    <div class="card-header recruitment-card-header">
                        <div>
                            <h5 class="mb-0">Ultimi 100 registrati</h5>
                            <small class="text-muted">Log rapido ultime registrazioni</small>
                        </div>
                    </div>

                    <div class="card-body">
                        <div id="latestRegistrationsBox" class="placeholder-box">
                            Tabella ultimi registrati
                        </div>
                    </div>
                </div>

                {{-- BOX STATS --}}
                <div class="card recruitment-card mb-4">
                    <div class="card-header recruitment-card-header d-flex justify-content-between align-items-center">
                        <div>
                        <h5 class="mb-0">Statistiche per Anagrafica</h5>
                        <small class="text-muted">Distribuzione anagrafica per referral</small>
                        </div>

                        <div class="filter-box">
                            <label for="filterStatsYear" class="form-label mb-1">Anno</label>
                            <select id="filterStatsYear" class="form-select form-select-sm">
                                @foreach($years as $year)
                                    <option value="{{ $year }}" {{ $year == $currentYear ? 'selected' : '' }}>
                                        {{ $year }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="card-body">
                        <div id="statsBox" class="placeholder-box">
                            Box statistiche
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>
</main>


 {{-- MODALE NUOVA CAMPAGNA --}}

<div class="modal fade" id="campaignModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content recruitment-modal">
            <div class="modal-header">
                <h5 class="modal-title">Gestione Campagne</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
            </div>

            <div class="modal-body">

                {{-- Lista tutte le campagne --}}
                <div id="allCampaignsList">
                    <div class="text-center py-3 text-muted small">Caricamento...</div>
                </div>

                {{-- Form crea/modifica periodo --}}
                <div id="campaignFormBox" class="d-none">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span id="campaignFormTitle" class="fw-semibold">Nuova campagna</span>
                        <button type="button" id="btnCancelEdit" class="btn btn-sm btn-link text-secondary text-decoration-none p-0">← Torna alla lista</button>
                    </div>

                    {{-- Referral: selettore (solo modalità nuova) --}}
                    <div id="campaignReferralBox" class="mb-3">
                        <label class="form-label fw-bold d-block">Referral</label>
                        <div class="d-flex gap-3 mb-2">
                            <label class="form-check-label">
                                <input class="form-check-input me-1" type="radio" name="referral_mode" value="existing" checked>
                                Esistente
                            </label>
                            <label class="form-check-label">
                                <input class="form-check-input me-1" type="radio" name="referral_mode" value="new">
                                Nuovo
                            </label>
                        </div>
                        <div id="existingReferralBox">
                            <select class="form-select" id="existingReferralSelect">
                                <option value="">-- seleziona --</option>
                                @foreach($referrals as $ref)
                                    @if($ref->group_type !== 'fallback')
                                        <option value="{{ $ref->id }}">{{ $ref->title }} ({{ $ref->code }})</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div id="newReferralBox" class="d-none">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Codice</label>
                                    <input type="text" class="form-control" id="newReferralCode" maxlength="50">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Titolo</label>
                                    <input type="text" class="form-control" id="newReferralTitle" maxlength="255">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Icona</label>
                                    <input type="text" class="form-control" id="newReferralIcon" maxlength="150" placeholder="es. fa-regular fa-thumbs-up">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Referral: label statica (solo modalità modifica) --}}
                    <div id="campaignReferralLabel" class="mb-3 d-none">
                        <span class="form-label mb-1 d-block">Referral</span>
                        <span id="campaignReferralName" class="fw-semibold"></span>
                    </div>

                    <hr class="my-3">

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Data inizio</label>
                            <input type="date" class="form-control" id="campaignStart">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Data fine</label>
                            <input type="date" class="form-control" id="campaignEnd">
                        </div>
                    </div>

                    <div class="mt-3">
                        <label class="form-label fw-semibold">Fasce CPI</label>
                        <div id="segmentsContainer"></div>
                        <button type="button" id="btnAddSegment" class="btn btn-sm btn-outline-secondary mt-2">+ Aggiungi fascia d'età</button>
                        <div id="segmentsWarning" class="form-text text-warning d-none mt-1">Nessuna fascia copre tutti gli utenti: aggiungi una riga senza età min/max come fallback.</div>
                    </div>

                    <div class="mt-3">
                        <label class="form-check-label">
                            <input class="form-check-input me-1" type="checkbox" id="campaignActive" checked>
                            Attiva
                        </label>
                    </div>
                </div>

                <div id="campaignError" class="alert alert-danger mt-3 d-none mb-0"></div>
                <div id="campaignSuccess" class="alert alert-success mt-3 d-none mb-0"></div>
            </div>

            <div class="modal-footer">
                <button type="button" id="btnNewCampaign" class="btn btn-outline-primary me-auto">+ Nuova campagna</button>
                <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Chiudi</button>
                <button class="btn btn-primary d-none" type="button" id="btnSaveCampaign">Salva</button>
            </div>
        </div>
    </div>
</div>



<div class="modal fade" id="reportModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content recruitment-modal">
            <div class="modal-header">
                <h5 class="modal-title">Genera Report</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
            </div>

            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Da mese</label>
                        <select class="form-select" id="reportMonthFrom">
                            @foreach($months as $monthNumber => $monthLabel)
                                <option value="{{ $monthNumber }}" {{ $monthNumber == $currentMonth ? 'selected' : '' }}>
                                    {{ $monthLabel }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">A mese</label>
                        <select class="form-select" id="reportMonthTo">
                            @foreach($months as $monthNumber => $monthLabel)
                                <option value="{{ $monthNumber }}" {{ $monthNumber == $currentMonth ? 'selected' : '' }}>
                                    {{ $monthLabel }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Anno</label>
                        <select class="form-select" id="reportYear">
                            @foreach($years as $year)
                                <option value="{{ $year }}" {{ $year == $currentYear ? 'selected' : '' }}>
                                    {{ $year }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <small class="text-muted d-block mt-1">
                    L'intervallo deve essere nello stesso anno (es. "Da Gennaio a Marzo").
                </small>

                <div class="mt-3">
<label class="form-label">Provenienza / Referral</label>
<select class="form-select" id="reportReferral" multiple size="8">
                        @foreach($referrals as $ref)
                            <option value="{{ $ref->id }}">
                                {{ $ref->title }} ({{ $ref->code }})
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">
                        Puoi selezionare più referral. Se non selezioni nulla verranno incluse tutte le provenienze.
                    </small>
                    <small class="text-muted">
                        Se selezioni un referral, verranno incluse tutte le sue source_codes.
                    </small>
                </div>

                <div id="reportError" class="alert alert-danger mt-3 d-none mb-0"></div>
            </div>

            <div class="modal-footer">
                <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Chiudi</button>
                <button class="btn btn-primary" type="button" id="btnDownloadReport">
                    Scarica Excel
                </button>
            </div>
        </div>
    </div>
</div>

@endsection


@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    const dailyBox = document.getElementById('dailyBox');
    const filterDailyMonth = document.getElementById('filterDailyMonth');
    const filterDailyYear = document.getElementById('filterDailyYear');

    const costsBox = document.getElementById('costsBox');
    const filterCostsYear = document.getElementById('filterCostsYear');

    const activityBox = document.getElementById('activityBox');
    const filterActivityYear = document.getElementById('filterActivityYear');

    const statsBox = document.getElementById('statsBox');
    const filterStatsYear = document.getElementById('filterStatsYear');

    const latestRegistrationsBox = document.getElementById('latestRegistrationsBox');

    const summaryYearBox = document.getElementById('summaryYearBox');
    const filterSummaryYear = document.getElementById('filterSummaryYear');

    const btnOpenCampaignModal = document.getElementById('btnOpenCampaignModal');
    const btnSaveCampaign = document.getElementById('btnSaveCampaign');

    const campaignModalElement = document.getElementById('campaignModal');
    const campaignModal = new bootstrap.Modal(campaignModalElement);


    const existingReferralBox = document.getElementById('existingReferralBox');
    const newReferralBox = document.getElementById('newReferralBox');

    const existingReferralSelect = document.getElementById('existingReferralSelect');
    const newReferralCode = document.getElementById('newReferralCode');
    const newReferralTitle = document.getElementById('newReferralTitle');
    const newReferralIcon = document.getElementById('newReferralIcon');
    const campaignStart = document.getElementById('campaignStart');
    const campaignEnd = document.getElementById('campaignEnd');
    const campaignActive = document.getElementById('campaignActive');

    const campaignError = document.getElementById('campaignError');
    const campaignSuccess = document.getElementById('campaignSuccess');

    const campaignFormBox = document.getElementById('campaignFormBox');
    const campaignFormTitle = document.getElementById('campaignFormTitle');
    const btnCancelEdit = document.getElementById('btnCancelEdit');

    var editState = null;

    const btnOpenReportModal = document.getElementById('btnOpenReportModal');
const btnDownloadReport = document.getElementById('btnDownloadReport');

const reportModalElement = document.getElementById('reportModal');
const reportModal = new bootstrap.Modal(reportModalElement);

const reportMonthFrom = document.getElementById('reportMonthFrom');
const reportMonthTo = document.getElementById('reportMonthTo');
const reportYear = document.getElementById('reportYear');
const reportReferral = document.getElementById('reportReferral');
const reportError = document.getElementById('reportError');

function renderSegments(segments) {
    var container = document.getElementById('segmentsContainer');
    container.innerHTML = '';
    segments.forEach(function(seg) {
        var row = document.createElement('div');
        row.className = 'segment-row d-flex gap-2 align-items-end mb-2';
        row.innerHTML =
            '<div style="width:100px">'
            + '<label class="form-label mb-1 small">CPI (€)</label>'
            + '<input type="number" step="0.0001" min="0" class="form-control form-control-sm segment-cpi" value="' + (seg.cpi !== null && seg.cpi !== undefined ? seg.cpi : '') + '" required>'
            + '</div>'
            + '<div style="width:90px">'
            + '<label class="form-label mb-1 small">Età min</label>'
            + '<input type="number" min="1" max="119" placeholder="nessun min" class="form-control form-control-sm segment-age-min" value="' + (seg.age_min !== null && seg.age_min !== undefined ? seg.age_min : '') + '">'
            + '</div>'
            + '<div style="width:90px">'
            + '<label class="form-label mb-1 small">Età max</label>'
            + '<input type="number" min="1" max="120" placeholder="nessun max" class="form-control form-control-sm segment-age-max" value="' + (seg.age_max !== null && seg.age_max !== undefined ? seg.age_max : '') + '">'
            + '</div>'
            + '<button type="button" class="btn btn-sm btn-outline-danger remove-segment mb-1">×</button>';
        container.appendChild(row);
        row.querySelector('.remove-segment').addEventListener('click', function() {
            var allRows = document.querySelectorAll('#segmentsContainer .segment-row');
            if (allRows.length > 1) {
                row.remove();
                updateRemoveButtons();
                updateSegmentsWarning();
            }
        });
        row.querySelector('.segment-age-min').addEventListener('input', updateSegmentsWarning);
        row.querySelector('.segment-age-max').addEventListener('input', updateSegmentsWarning);
    });
    updateRemoveButtons();
    updateSegmentsWarning();
}

function updateRemoveButtons() {
    var allRows = document.querySelectorAll('#segmentsContainer .segment-row');
    allRows.forEach(function(r) {
        r.querySelector('.remove-segment').style.visibility = allRows.length > 1 ? 'visible' : 'hidden';
    });
}

function updateSegmentsWarning() {
    var warning = document.getElementById('segmentsWarning');
    var allRows = document.querySelectorAll('#segmentsContainer .segment-row');
    if (allRows.length <= 1) { warning.classList.add('d-none'); return; }
    var hasFallback = Array.prototype.some.call(allRows, function(r) {
        return r.querySelector('.segment-age-min').value.trim() === ''
            && r.querySelector('.segment-age-max').value.trim() === '';
    });
    warning.classList.toggle('d-none', hasFallback);
}

document.getElementById('btnAddSegment').addEventListener('click', function() {
    var allRows = document.querySelectorAll('#segmentsContainer .segment-row');
    var currentSegments = Array.prototype.map.call(allRows, function(row) {
        return {
            cpi:     row.querySelector('.segment-cpi').value,
            age_min: row.querySelector('.segment-age-min').value || null,
            age_max: row.querySelector('.segment-age-max').value || null
        };
    });
    currentSegments.push({ cpi: '', age_min: '', age_max: '' });
    renderSegments(currentSegments);
});

function formatCampaignDate(s) {
    if (!s) return '';
    var p = s.substring(0, 10).split('-');
    return p[2] + '/' + p[1] + '/' + p[0];
}

function hideCampaignAlerts() {
    campaignError.classList.add('d-none');
    campaignError.innerText = '';
    campaignSuccess.classList.add('d-none');
    campaignSuccess.innerText = '';
}

function hideCampaignForm() {
    editState = null;
    campaignFormBox.classList.add('d-none');
    document.getElementById('allCampaignsList').classList.remove('d-none');
    document.getElementById('btnNewCampaign').classList.remove('d-none');
    document.getElementById('btnSaveCampaign').classList.add('d-none');
    hideCampaignAlerts();
}

function showCampaignForm(mode, period, referralInfo) {
    if (mode === 'edit') {
        editState = {
            referralId:    referralInfo.id,
            originalStart: period.start_date,
            originalEnd:   period.end_date
        };
        campaignFormTitle.textContent = 'Modifica periodo';
        document.getElementById('campaignReferralBox').classList.add('d-none');
        document.getElementById('campaignReferralLabel').classList.remove('d-none');
        document.getElementById('campaignReferralName').textContent = referralInfo.title + ' (' + referralInfo.code + ')';
        campaignStart.value = period.start_date;
        campaignEnd.value   = period.end_date || '';
        renderSegments(period.segments.map(function(s) {
            return {
                cpi:     s.cpi,
                age_min: s.age_min !== null && s.age_min !== undefined ? s.age_min : '',
                age_max: s.age_max !== null && s.age_max !== undefined ? s.age_max : ''
            };
        }));
        campaignActive.checked = period.is_active === 1;
    } else {
        editState = null;
        campaignFormTitle.textContent = 'Nuova campagna';
        document.getElementById('campaignReferralBox').classList.remove('d-none');
        document.getElementById('campaignReferralLabel').classList.add('d-none');
        document.querySelector('input[name="referral_mode"][value="existing"]').checked = true;
        existingReferralBox.classList.remove('d-none');
        newReferralBox.classList.add('d-none');
        existingReferralSelect.value = '';
        newReferralCode.value        = '';
        newReferralTitle.value       = '';
        newReferralIcon.value        = '';
        campaignStart.value = '';
        campaignEnd.value   = '';
        renderSegments([{ cpi: '', age_min: '', age_max: '' }]);
        campaignActive.checked = true;
    }

    hideCampaignAlerts();
    document.getElementById('allCampaignsList').classList.add('d-none');
    campaignFormBox.classList.remove('d-none');
    document.getElementById('btnNewCampaign').classList.add('d-none');
    document.getElementById('btnSaveCampaign').classList.remove('d-none');
}

function loadAllCampaigns() {
    var container = document.getElementById('allCampaignsList');
    container.innerHTML = '<div class="text-center py-3 text-muted small">Caricamento...</div>';
    container._groups = null;

    fetch('{{ route("recruitment.campaigns.list") }}', {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (!data.success) {
            container.innerHTML = '<div class="text-danger small py-2">Errore nel caricamento.</div>';
            return;
        }
        renderAllCampaigns(data.groups);
    })
    .catch(function() {
        container.innerHTML = '<div class="text-danger small py-2">Errore di connessione.</div>';
    });
}

function segmentAgeLabel(s) {
    var min = (s.age_min !== null && s.age_min !== undefined) ? parseInt(s.age_min) : null;
    var max = (s.age_max !== null && s.age_max !== undefined) ? parseInt(s.age_max) : null;
    if (min !== null && max !== null) return min + '-' + max + 'a';
    if (min !== null) return '≥' + min + 'a';
    if (max !== null) return '≤' + max + 'a';
    return 'tutti';
}

function campaignPeriodBadge(p, today) {
    if (p.end_date && p.end_date < today) {
        return '<span class="badge bg-secondary-subtle text-secondary-emphasis ms-1">Scaduta</span>';
    }
    if (p.start_date > today) {
        return '<span class="badge bg-info-subtle text-info-emphasis ms-1">Futura</span>';
    }
    return p.is_active
        ? '<span class="badge bg-success-subtle text-success-emphasis ms-1">Attiva</span>'
        : '<span class="badge bg-warning-subtle text-warning-emphasis ms-1">Inattiva</span>';
}

function buildCampaignTableRow(gidx, pidx, p, today, editable) {
    var periodo = formatCampaignDate(p.start_date) + ' → '
        + (p.end_date ? formatCampaignDate(p.end_date) : '<span class="text-muted">aperta</span>');
    var fasce = p.segments.map(function(s) {
        var lbl = segmentAgeLabel(s);
        var cpiStr = '€' + (s.cpi % 1 === 0 ? s.cpi.toFixed(0) : s.cpi);
        return lbl !== 'tutti'
            ? cpiStr + ' <span class="text-muted">(' + lbl + ')</span>'
            : cpiStr;
    }).join('&ensp;');
    var badge = campaignPeriodBadge(p, today);
    var actionCell = editable
        ? '<button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 btn-edit-campaign"'
          + ' data-gidx="' + gidx + '" data-pidx="' + pidx + '">Modifica</button>'
        : '';
    return '<tr>'
        + '<td class="small ps-0" style="width:38%">' + periodo + badge + '</td>'
        + '<td class="small">' + fasce + '</td>'
        + '<td class="text-end pe-0">' + actionCell + '</td></tr>';
}

function renderAllCampaigns(groups) {
    var container = document.getElementById('allCampaignsList');
    var today = new Date().toISOString().substring(0, 10);

    container._groups = groups;

    if (groups.length === 0) {
        container.innerHTML = '<div class="text-muted small fst-italic py-3">Nessuna campagna configurata.</div>';
        return;
    }

    // Separa periodi attivi/futuri da scaduti
    var activeHtml = '';
    var expiredRows = '';
    var expiredCount = 0;

    groups.forEach(function(group, gidx) {
        var groupActiveRows = '';
        var groupExpiredRows = '';

        group.periods.forEach(function(p, pidx) {
            var expired = p.end_date && p.end_date < today;
            if (expired) {
                groupExpiredRows += buildCampaignTableRow(gidx, pidx, p, today, true);
                expiredCount++;
            } else {
                groupActiveRows += buildCampaignTableRow(gidx, pidx, p, today, true);
            }
        });

        var referralHeader = '<div class="d-flex align-items-center gap-2 mb-1">'
            + (group.referral_icon ? '<i class="' + escapeHtml(group.referral_icon) + ' text-muted small"></i>' : '')
            + '<span class="fw-semibold small">' + escapeHtml(group.referral_title) + '</span>'
            + '<span class="text-muted small">(' + escapeHtml(group.referral_code) + ')</span>'
            + '</div>';

        if (groupActiveRows) {
            activeHtml += '<div class="mb-3">' + referralHeader
                + '<table class="table table-sm table-borderless align-middle mb-0"><tbody>'
                + groupActiveRows + '</tbody></table></div>';
        }

        if (groupExpiredRows) {
            expiredRows += '<div class="mb-2">' + referralHeader
                + '<table class="table table-sm table-borderless align-middle mb-0"><tbody>'
                + groupExpiredRows + '</tbody></table></div>';
        }
    });

    var html = activeHtml || '<div class="text-muted small fst-italic py-2">Nessuna campagna attiva o futura.</div>';

    if (expiredCount > 0) {
        html += '<div class="mt-2 border-top pt-2">'
            + '<button type="button" id="btnToggleStorico" class="btn btn-link btn-sm p-0 text-muted text-decoration-none">'
            + 'Mostra storico (' + expiredCount + ' scadut' + (expiredCount === 1 ? 'a' : 'e') + ')'
            + '</button>'
            + '<div id="storicoSection" class="d-none mt-2">' + expiredRows + '</div>'
            + '</div>';
    }

    container.innerHTML = html;

    container.querySelectorAll('.btn-edit-campaign').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var g = container._groups[parseInt(this.getAttribute('data-gidx'))];
            var p = g.periods[parseInt(this.getAttribute('data-pidx'))];
            showCampaignForm('edit', p, { id: g.referral_id, title: g.referral_title, code: g.referral_code });
        });
    });

    var btnStorico = document.getElementById('btnToggleStorico');
    if (btnStorico) {
        btnStorico.addEventListener('click', function() {
            var sec = document.getElementById('storicoSection');
            var open = !sec.classList.contains('d-none');
            sec.classList.toggle('d-none', open);
            this.textContent = open
                ? 'Mostra storico (' + expiredCount + ' scadut' + (expiredCount === 1 ? 'a' : 'e') + ')'
                : 'Nascondi storico';
        });
    }
}

btnCancelEdit.addEventListener('click', hideCampaignForm);

document.getElementById('btnNewCampaign').addEventListener('click', function() {
    showCampaignForm('new', null, null);
});

btnOpenCampaignModal.addEventListener('click', function () {
    resetCampaignForm();
    campaignModal.show();
});

document.querySelectorAll('input[name="referral_mode"]').forEach(function(el) {
    el.addEventListener('change', function() {
        if (this.value === 'existing') {
            existingReferralBox.classList.remove('d-none');
            newReferralBox.classList.add('d-none');
        } else {
            existingReferralBox.classList.add('d-none');
            newReferralBox.classList.remove('d-none');
        }
    });
});

campaignModalElement.addEventListener('shown.bs.modal', loadAllCampaigns);

campaignModalElement.addEventListener('hidden.bs.modal', function () {
    resetCampaignForm();
});

document.getElementById('btnSaveCampaign').addEventListener('click', function () {
    var segments = Array.prototype.map.call(document.querySelectorAll('#segmentsContainer .segment-row'), function(row) {
        return {
            cpi:     parseFloat(row.querySelector('.segment-cpi').value) || 0,
            age_min: row.querySelector('.segment-age-min').value || null,
            age_max: row.querySelector('.segment-age-max').value || null
        };
    });

    var url, payload;

    if (editState !== null) {
        url = '{{ route("recruitment.campaigns.update") }}';
        payload = {
            referral_id:         editState.referralId,
            original_start_date: editState.originalStart,
            original_end_date:   editState.originalEnd,
            start_date:          campaignStart.value,
            end_date:            campaignEnd.value,
            segments:            segments,
            is_active:           campaignActive.checked ? 1 : 0
        };
    } else {
        var referralMode = document.querySelector('input[name="referral_mode"]:checked').value;
        url = '{{ route("recruitment.campaigns.store") }}';
        payload = {
            referral_mode:        referralMode,
            existing_referral_id: existingReferralSelect.value,
            new_referral_code:    newReferralCode.value.trim(),
            new_referral_title:   newReferralTitle.value.trim(),
            new_referral_icon:    newReferralIcon.value.trim(),
            start_date:           campaignStart.value,
            end_date:             campaignEnd.value,
            segments:             segments,
            is_active:            campaignActive.checked ? 1 : 0
        };
    }

    hideCampaignAlerts();
    this.disabled = true;
    this.innerHTML = 'Salvataggio...';

    var btn = this;

    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: JSON.stringify(payload)
    })
    .then(function(response) {
        return response.json().then(function(data) {
            return { ok: response.ok, data: data };
        });
    })
    .then(function(result) {
        btn.disabled = false;
        btn.innerHTML = 'Salva';

        if (!result.ok || !result.data.success) {
            showCampaignError(result.data.message || 'Errore durante il salvataggio.');
            return;
        }

        // Ricarica lista e torna alla vista campagne
        hideCampaignForm();
        showCampaignSuccess(result.data.message);
        loadAllCampaigns();

        loadCostsBox();
        loadSummaryYearBox();
    })
    .catch(function(error) {
        console.error(error);
        showCampaignError('Errore di connessione.');
        btn.disabled = false;
        btn.innerHTML = 'Salva';
    });
});

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.innerText = text === null || text === undefined ? '' : text;
        return div.innerHTML;
    }

    function formatNumber(value) {
        return new Intl.NumberFormat('it-IT').format(value);
    }

    function formatDecimal(value, decimals) {
        return new Intl.NumberFormat('it-IT', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        }).format(value);
    }

    function formatCurrency(value) {
        return formatDecimal(value, 2) + ' €';
    }

function renderDailyBox(data) {
    if (!data.success) {
        dailyBox.innerHTML = `<div class="daily-empty">Errore nel caricamento dei dati</div>`;
        return;
    }

    const VAT = 0.22;
    const totalGross = data.total_cost > 0 ? data.total_cost * (1 + VAT) : 0;

    let html = `
        <div class="daily-month-shell">
            <div class="daily-month-top">
                <div class="daily-month-summary">
                    <div class="daily-month-summary-label">Registrati mese</div>
                    <div class="daily-month-summary-value">${formatNumber(data.total_registered)}</div>
                    <div class="daily-month-summary-subtitle">${escapeHtml(data.month_label)}</div>
                </div>

                ${data.total_cost > 0 ? `
                <div class="daily-month-invoice-summary">
                    <div class="daily-month-summary-label">Fattura prevista</div>
                    <div class="daily-month-summary-value">${formatCurrency(data.total_cost)}</div>
                    <div class="daily-month-summary-subtitle">Totale ${formatCurrency(totalGross)} (+ ${formatCurrency(data.total_cost * VAT)} IVA)</div>
                </div>` : ''}

                <div class="daily-month-badge">
                    <span class="daily-month-badge-label">Referral attivi</span>
                    <strong class="daily-month-badge-value">${formatNumber((data.referrals || []).length)}</strong>
                </div>
            </div>
    `;

    if (!data.referrals || data.referrals.length === 0) {
        html += `
                <div class="daily-empty">
                    Nessun dato disponibile per il periodo selezionato
                </div>
            </div>
        `;
        dailyBox.innerHTML = html;
        return;
    }

    html += `<div class="daily-referral-grid">`;

    data.referrals.forEach(function(item) {
        const sourcesText = item.sources && item.sources.length ? item.sources.join(', ') : '-';
        const iconHtml = item.icon ? `<i class="${escapeHtml(item.icon)}"></i>` : '';
        const sourceCount = item.sources && item.sources.length ? item.sources.length : 0;

        const itemCostGross = item.cost > 0 ? item.cost * (1 + VAT) : 0;
        html += `
            <div class="daily-referral-card">
                <div class="daily-referral-card-main">
                    <div class="daily-referral-card-left">
                        <div class="daily-referral-label-wrap">
                            <span class="daily-referral-icon">${iconHtml}</span>
                            <span class="daily-referral-label">${escapeHtml(item.label)}</span>
                        </div>

                        <div class="daily-referral-meta">
                            <span class="daily-referral-meta-pill">${sourceCount} source${sourceCount === 1 ? '' : 's'}</span>
                            <span class="daily-referral-meta-pill daily-referral-meta-pill-hover" title="${escapeHtml(sourcesText)}">
                                codici referral
                            </span>
                        </div>
                    </div>

                    <div class="daily-referral-total-wrap">
                        <div class="daily-referral-total-label">Registrati</div>
                        <div class="daily-referral-total">${formatNumber(item.total)}</div>
                    </div>
                </div>

                ${item.cost > 0 ? `
                <div class="daily-referral-invoice">
                    <div class="daily-referral-invoice-row">
                        <span class="daily-referral-invoice-label">Imponibile</span>
                        <span class="daily-referral-invoice-value">${formatCurrency(item.cost)}</span>
                    </div>
                    <div class="daily-referral-invoice-row daily-referral-invoice-total">
                        <span class="daily-referral-invoice-label">Totale</span>
                        <span class="daily-referral-invoice-value">${formatCurrency(itemCostGross)} <span class="daily-referral-invoice-vat">(+ ${formatCurrency(item.cost * VAT)} IVA)</span></span>
                    </div>
                </div>` : ''}
            </div>
        `;
    });

    html += `
            </div>
        </div>
    `;

    dailyBox.innerHTML = html;
}

    function loadDailyBox() {
        const month = filterDailyMonth.value;
        const year = filterDailyYear.value;

        dailyBox.innerHTML = `<div class="daily-loading">Caricamento riepilogo mensile...</div>`;

        fetch(`{{ route('recruitment.daily') }}?month=${month}&year=${year}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(function(response) {
            if (!response.ok) {
                throw new Error('Errore HTTP');
            }
            return response.json();
        })
        .then(function(data) {
            renderDailyBox(data);
        })
        .catch(function(error) {
            console.error(error);
            dailyBox.innerHTML = `<div class="daily-empty">Impossibile caricare i dati del riepilogo mensile</div>`;
        });
    }

function buildCostCard(item) {
    const iconHtml = item.icon ? `<i class="${escapeHtml(item.icon)}"></i>` : '';
    const sourcesText = item.sources && item.sources.length ? item.sources.join(', ') : '-';
    const sourceCount = item.sources ? item.sources.length : 0;

    let breakdownHtml = '';
    if (item.breakdown && item.breakdown.length > 0) {
        breakdownHtml = '<table class="costs-breakdown-table"><tbody>';
        item.breakdown.forEach(function(seg) {
            const lbl = segmentAgeLabel(seg);
            breakdownHtml += `<tr>
                <td><span class="costs-breakdown-seg">${escapeHtml(lbl)}</span></td>
                <td class="costs-breakdown-count">${formatNumber(seg.count)} reg.</td>
                <td class="costs-breakdown-cost">${formatCurrency(seg.cost)}</td>
            </tr>`;
        });
        breakdownHtml += '</tbody></table>';
    }

    return `<div class="costs-referral-card">
        <div class="costs-referral-card-main">
            <div class="costs-referral-card-left">
                <div class="daily-referral-label-wrap">
                    <span class="daily-referral-icon">${iconHtml}</span>
                    <span class="daily-referral-label">${escapeHtml(item.label)}</span>
                </div>
                <div class="daily-referral-meta">
                    <span class="daily-referral-meta-pill">${sourceCount} source${sourceCount === 1 ? '' : 's'}</span>
                    <span class="daily-referral-meta-pill daily-referral-meta-pill-hover" title="${escapeHtml(sourcesText)}">codici referral</span>
                </div>
            </div>
            <div class="costs-referral-side">
                <div class="costs-referral-main-number-label">Costo</div>
                <div class="costs-referral-main-number">${formatCurrency(item.cost)}</div>
                <div class="costs-referral-registered">${formatNumber(item.registered)} reg.</div>
            </div>
        </div>
        <div class="costs-referral-kpi">
            <div class="costs-referral-kpi-item">
                <span class="costs-referral-kpi-label">Attivi</span>
                <strong>${formatNumber(item.active)}</strong>
            </div>
            <div class="costs-referral-kpi-item">
                <span class="costs-referral-kpi-label">Attivi %</span>
                <strong>${formatDecimal(item.active_rate, 1)}%</strong>
            </div>
            <div class="costs-referral-kpi-item">
                <span class="costs-referral-kpi-label">CPI</span>
                <strong>${formatDecimal(item.cpi, 4)}</strong>
            </div>
            <div class="costs-referral-kpi-item">
                <span class="costs-referral-kpi-label">CPA</span>
                <strong>${formatCurrency(item.cpa)}</strong>
            </div>
        </div>
        ${breakdownHtml}
    </div>`;
}

function renderCostsBox(data) {
    if (!data.success) {
        costsBox.innerHTML = '<div class="daily-empty">Errore nel caricamento dei costi</div>';
        return;
    }
    if (!data.rows || data.rows.length === 0) {
        costsBox.innerHTML = '<div class="daily-empty">Nessun referral disponibile per l\'anno selezionato</div>';
        return;
    }

    const activeRows  = data.rows.filter(function(r) { return r.has_current_campaign; });
    const passiveRows = data.rows.filter(function(r) { return !r.has_current_campaign; });

    let html = '';

    if (activeRows.length > 0) {
        html += '<div class="costs-referral-grid">';
        activeRows.forEach(function(item) { html += buildCostCard(item); });
        html += '</div>';
    } else {
        html += '<div class="daily-empty">Nessuna campagna attiva per l\'anno selezionato</div>';
    }

    if (passiveRows.length > 0) {
        html += '<div class="costs-passive-section' + (activeRows.length > 0 ? ' mt-3 pt-3 border-top' : '') + '">';
        html += '<p class="costs-passive-title">Referral senza campagna attiva</p>';
        html += '<table class="table table-sm align-middle mb-0"><thead><tr>';
        html += '<th class="small text-muted fw-normal">Referral</th>';
        html += '<th class="small text-muted fw-normal text-end">Registrati</th>';
        html += '<th class="small text-muted fw-normal text-end">Attivi</th>';
        html += '<th class="small text-muted fw-normal text-end">Attivi %</th>';
        html += '<th class="small text-muted fw-normal text-end">Costo</th>';
        html += '<th class="small text-muted fw-normal text-end">CPI</th>';
        html += '</tr></thead><tbody>';
        passiveRows.forEach(function(item) {
            const iconHtml = item.icon ? `<i class="${escapeHtml(item.icon)} me-1 text-muted"></i>` : '';
            html += `<tr>
                <td class="small">${iconHtml}${escapeHtml(item.label)}</td>
                <td class="small text-end">${formatNumber(item.registered)}</td>
                <td class="small text-end">${formatNumber(item.active)}</td>
                <td class="small text-end">${formatDecimal(item.active_rate, 1)}%</td>
                <td class="small text-end">${formatCurrency(item.cost)}</td>
                <td class="small text-end">${formatDecimal(item.cpi, 4)}</td>
            </tr>`;
        });
        html += '</tbody></table></div>';
    }

    costsBox.innerHTML = html;
    costsBox.style.display = 'block';
    costsBox.style.padding = '0';
    costsBox.style.border = 'none';
    costsBox.style.minHeight = 'auto';
    costsBox.style.textAlign = 'left';
}

    function loadCostsBox() {
        const year = filterCostsYear.value;

        costsBox.innerHTML = `<div class="daily-loading">Caricamento spese referral...</div>`;

        fetch(`{{ route('recruitment.costs') }}?year=${year}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(function(response) {
            if (!response.ok) {
                throw new Error('Errore HTTP');
            }
            return response.json();
        })
        .then(function(data) {
            renderCostsBox(data);
        })
        .catch(function(error) {
            console.error(error);
            costsBox.innerHTML = `<div class="daily-empty">Impossibile caricare i costi referral</div>`;
        });
    }

function buildActivityCard(item) {
    const iconHtml = item.icon ? `<i class="${escapeHtml(item.icon)}"></i>` : '';
    const sources = item.sources || [];
    const sourcesText = sources.join(', ');
    const sourcesCount = sources.length;
    return `
        <div class="activity-card">
            <div class="activity-card-header">
                <div>
                    <div class="activity-card-title">
                        <span class="daily-referral-icon me-1">${iconHtml}</span>
                        ${escapeHtml(item.label)}
                    </div>
                    <div class="activity-card-subtitle">
                        Totale registrati: <strong>${formatNumber(item.total_registered)}</strong>
                    </div>
                </div>
            </div>
            <div class="activity-stats-table">
                <div class="activity-row activity-row-red">
                    <div class="activity-label">Nessuna (0)</div>
                    <div class="activity-value">${formatNumber(item.act_0)}</div>
                    <div class="activity-percent">${formatDecimal(item.perc_0, 1)}%</div>
                </div>
                <div class="activity-row activity-row-orange">
                    <div class="activity-label">Bassa (1-2)</div>
                    <div class="activity-value">${formatNumber(item.act_1_2)}</div>
                    <div class="activity-percent">${formatDecimal(item.perc_1_2, 1)}%</div>
                </div>
                <div class="activity-row activity-row-yellow">
                    <div class="activity-label">Media (3-5)</div>
                    <div class="activity-value">${formatNumber(item.act_3_5)}</div>
                    <div class="activity-percent">${formatDecimal(item.perc_3_5, 1)}%</div>
                </div>
                <div class="activity-row activity-row-lime">
                    <div class="activity-label">Buona (6-9)</div>
                    <div class="activity-value">${formatNumber(item.act_6_9)}</div>
                    <div class="activity-percent">${formatDecimal(item.perc_6_9, 1)}%</div>
                </div>
                <div class="activity-row activity-row-green">
                    <div class="activity-label">Ottima (10+)</div>
                    <div class="activity-value">${formatNumber(item.act_10_plus)}</div>
                    <div class="activity-percent">${formatDecimal(item.perc_10_plus, 1)}%</div>
                </div>
            </div>
            <div class="activity-sources">
                <span class="activity-sources-pill" title="${escapeHtml(sourcesText)}">
                    ${sourcesCount} source${sourcesCount === 1 ? '' : 's'}
                </span>
            </div>
        </div>
    `;
}

function renderActivityBox(data) {
    if (!data.success) {
        activityBox.innerHTML = '<div class="daily-empty">Errore nel caricamento del dettaglio attività</div>';
        return;
    }
    if (!data.rows || data.rows.length === 0) {
        activityBox.innerHTML = '<div class="daily-empty">Nessun dato disponibile per l\'anno selezionato</div>';
        return;
    }

    const activeRows  = data.rows.filter(function(r) { return r.has_current_campaign; });
    const passiveRows = data.rows.filter(function(r) { return !r.has_current_campaign; });

    let html = '';

    if (activeRows.length > 0) {
        html += '<div class="activity-card-grid">';
        activeRows.forEach(function(item) { html += buildActivityCard(item); });
        html += '</div>';
    }

    if (passiveRows.length > 0) {
        html += '<div class="activity-passive-section' + (activeRows.length > 0 ? ' mt-3 pt-3 border-top' : '') + '">';
        if (activeRows.length > 0) {
            html += '<div class="costs-passive-title">Altri referral</div>';
        }
        html += `<table class="table table-sm mb-0" style="font-size:13px">
            <thead>
                <tr>
                    <th class="ps-0">Referral</th>
                    <th class="text-end">Reg.</th>
                    <th class="text-end" style="color:#ef4444">0</th>
                    <th class="text-end" style="color:#f97316">1-2</th>
                    <th class="text-end" style="color:#ca8a04">3-5</th>
                    <th class="text-end" style="color:#65a30d">6-9</th>
                    <th class="text-end" style="color:#16a34a">10+</th>
                </tr>
            </thead>
            <tbody>`;
        passiveRows.forEach(function(item) {
            const iconHtml = item.icon ? `<i class="${escapeHtml(item.icon)} me-1"></i>` : '';
            html += `<tr>
                <td class="ps-0">${iconHtml}${escapeHtml(item.label)}</td>
                <td class="text-end">${formatNumber(item.total_registered)}</td>
                <td class="text-end" style="color:#ef4444;font-weight:600">${formatDecimal(item.perc_0, 1)}%</td>
                <td class="text-end" style="color:#f97316;font-weight:600">${formatDecimal(item.perc_1_2, 1)}%</td>
                <td class="text-end" style="color:#ca8a04;font-weight:600">${formatDecimal(item.perc_3_5, 1)}%</td>
                <td class="text-end" style="color:#65a30d;font-weight:600">${formatDecimal(item.perc_6_9, 1)}%</td>
                <td class="text-end" style="color:#16a34a;font-weight:600">${formatDecimal(item.perc_10_plus, 1)}%</td>
            </tr>`;
        });
        html += '</tbody></table></div>';
    }

    activityBox.style.display = 'block';
    activityBox.style.padding = '0';
    activityBox.style.border = 'none';
    activityBox.style.minHeight = 'auto';
    activityBox.style.textAlign = 'left';
    activityBox.innerHTML = html;
}

    function loadActivityBox() {
        const year = filterActivityYear.value;

        activityBox.innerHTML = `
            <div class="daily-loading">
                Caricamento dettaglio attività...
            </div>
        `;

        fetch(`{{ route('recruitment.activity') }}?year=${year}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(function(response) {
            if (!response.ok) {
                throw new Error('Errore HTTP');
            }
            return response.json();
        })
        .then(function(data) {
            renderActivityBox(data);
        })
        .catch(function(error) {
            console.error(error);
            activityBox.innerHTML = `
                <div class="daily-empty">
                    Impossibile caricare il dettaglio attività
                </div>
            `;
        });
    }

function demoBar(count, total, color) {
    var pct = total > 0 ? Math.round(count / total * 100) : 0;
    return `<div class="demographic-list-bar-wrap"><div class="demographic-list-bar-fill" style="width:${pct}%;background:${color}"></div></div>`;
}

function demoRow(label, count, total, color) {
    var pct = total > 0 ? Math.round(count / total * 100) : 0;
    return `<div class="demographic-list-row">
        <span class="demographic-list-label">${label}</span>
        ${demoBar(count, total, color)}
        <span class="demographic-list-value">${pct}%</span>
    </div>`;
}

function buildDemographicCard(item) {
    const iconHtml = item.icon ? `<i class="${escapeHtml(item.icon)}"></i>` : '';
    const sources = item.sources || [];
    const sourcesText = sources.length ? sources.join(', ') : '-';
    const sourcesCount = sources.length;
    const tot = item.total_registered;
    const knownAge = tot - item.age_unknown;

    return `
        <div class="demographic-card">
            <div class="demographic-card-header">
                <div class="demographic-card-title-wrap">
                    <span class="demographic-card-icon">${iconHtml}</span>
                    <div>
                        <div class="demographic-card-title">${escapeHtml(item.label)}</div>
                        <div class="demographic-card-subtitle">
                            <strong>${formatNumber(tot)}</strong> registrati
                            &nbsp;·&nbsp; N.D. età: ${tot > 0 ? Math.round(item.age_unknown / tot * 100) : 0}%
                        </div>
                    </div>
                </div>
            </div>

            <div class="demographic-layout">
                <div class="demographic-layout-left">
                    <div class="demographic-section">
                        <div class="demographic-section-title">
                            <i class="bi bi-gender-ambiguous demographic-section-icon"></i>
                            <span>Genere</span>
                        </div>
                        <div class="demographic-list">
                            ${demoRow('Uomini',  item.gender_male,    tot, '#3b82f6')}
                            ${demoRow('Donne',   item.gender_female,  tot, '#ec4899')}
                            ${demoRow('N.D.',    item.gender_unknown, tot, '#d1d5db')}
                        </div>
                    </div>

                    <div class="demographic-section">
                        <div class="demographic-section-title">
                            <i class="bi bi-geo-alt demographic-section-icon"></i>
                            <span>Area</span>
                        </div>
                        <div class="demographic-list">
                            ${demoRow('Nord O.', item.area_nord_ovest, tot, '#6366f1')}
                            ${demoRow('Nord E.', item.area_nord_est,   tot, '#8b5cf6')}
                            ${demoRow('Centro',  item.area_centro,     tot, '#f59e0b')}
                            ${demoRow('Sud',     item.area_sud,        tot, '#ef4444')}
                            ${demoRow('N.D.',    item.area_unknown,    tot, '#d1d5db')}
                        </div>
                    </div>
                </div>

                <div class="demographic-layout-right">
                    <div class="demographic-section demographic-section-age">
                        <div class="demographic-section-title">
                            <i class="bi bi-calendar3 demographic-section-icon"></i>
                            <span>Età <small class="text-muted fw-normal">(su noti)</small></span>
                        </div>
                        <div class="demographic-list">
                            ${demoRow('&lt;18',  item.age_under_18, knownAge, '#a78bfa')}
                            ${demoRow('18-24',   item.age_18_24,    knownAge, '#60a5fa')}
                            ${demoRow('25-34',   item.age_25_34,    knownAge, '#34d399')}
                            ${demoRow('35-44',   item.age_35_44,    knownAge, '#fbbf24')}
                            ${demoRow('45-54',   item.age_45_54,    knownAge, '#f97316')}
                            ${demoRow('55-64',   item.age_55_64,    knownAge, '#ef4444')}
                            ${demoRow('65+',     item.age_65_plus,  knownAge, '#6b7280')}
                        </div>
                    </div>
                </div>
            </div>

            <div class="demographic-card-footer">
                <span class="activity-sources-pill" title="${escapeHtml(sourcesText)}">
                    ${sourcesCount} source${sourcesCount === 1 ? '' : 's'}
                </span>
            </div>
        </div>
    `;
}

function topAgeBand(item) {
    var bands = [
        {label:'<18',   v: item.age_under_18},
        {label:'18-24', v: item.age_18_24},
        {label:'25-34', v: item.age_25_34},
        {label:'35-44', v: item.age_35_44},
        {label:'45-54', v: item.age_45_54},
        {label:'55-64', v: item.age_55_64},
        {label:'65+',   v: item.age_65_plus},
    ];
    var top = bands.reduce(function(a, b) { return b.v > a.v ? b : a; }, bands[0]);
    return top.v > 0 ? top.label : '-';
}

function renderStatsBox(data) {
    if (!data.success) {
        statsBox.innerHTML = '<div class="daily-empty">Errore nel caricamento delle statistiche</div>';
        return;
    }
    if (!data.rows || data.rows.length === 0) {
        statsBox.innerHTML = '<div class="daily-empty">Nessun dato disponibile per l\'anno selezionato</div>';
        return;
    }

    const activeRows  = data.rows.filter(function(r) { return r.has_current_campaign; });
    const passiveRows = data.rows.filter(function(r) { return !r.has_current_campaign; });

    let html = '';

    if (activeRows.length > 0) {
        html += '<div class="demographic-card-grid">';
        activeRows.forEach(function(item) { html += buildDemographicCard(item); });
        html += '</div>';
    }

    if (passiveRows.length > 0) {
        html += '<div class="demographic-passive-section' + (activeRows.length > 0 ? ' mt-3 pt-3 border-top' : '') + '">';
        if (activeRows.length > 0) {
            html += '<div class="costs-passive-title">Altri referral</div>';
        }
        html += `<table class="table table-sm mb-0" style="font-size:13px">
            <thead>
                <tr>
                    <th class="ps-0">Referral</th>
                    <th class="text-end">Reg.</th>
                    <th class="text-end" style="color:#3b82f6">M%</th>
                    <th class="text-end" style="color:#ec4899">F%</th>
                    <th class="text-end">N.D. età</th>
                    <th class="text-end">Fascia top</th>
                </tr>
            </thead>
            <tbody>`;
        passiveRows.forEach(function(item) {
            const tot = item.total_registered;
            const pMale   = tot > 0 ? Math.round(item.gender_male   / tot * 100) : 0;
            const pFemale = tot > 0 ? Math.round(item.gender_female  / tot * 100) : 0;
            const pAgeNd  = tot > 0 ? Math.round(item.age_unknown    / tot * 100) : 0;
            const iconHtml = item.icon ? `<i class="${escapeHtml(item.icon)} me-1"></i>` : '';
            html += `<tr>
                <td class="ps-0">${iconHtml}${escapeHtml(item.label)}</td>
                <td class="text-end">${formatNumber(tot)}</td>
                <td class="text-end" style="color:#3b82f6;font-weight:600">${pMale}%</td>
                <td class="text-end" style="color:#ec4899;font-weight:600">${pFemale}%</td>
                <td class="text-end text-muted">${pAgeNd}%</td>
                <td class="text-end"><span class="demographic-age-badge">${topAgeBand(item)}</span></td>
            </tr>`;
        });
        html += '</tbody></table></div>';
    }

    statsBox.style.display = 'block';
    statsBox.style.padding = '0';
    statsBox.style.border = 'none';
    statsBox.style.minHeight = 'auto';
    statsBox.style.textAlign = 'left';
    statsBox.innerHTML = html;
}

    function loadStatsBox() {
        const year = filterStatsYear.value;

        statsBox.innerHTML = `
            <div class="daily-loading">
                Caricamento statistiche...
            </div>
        `;

        fetch(`{{ route('recruitment.stats') }}?year=${year}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(function(response) {
            if (!response.ok) {
                throw new Error('Errore HTTP');
            }
            return response.json();
        })
        .then(function(data) {
            renderStatsBox(data);
        })
        .catch(function(error) {
            console.error(error);

            statsBox.innerHTML = `
                <div class="daily-empty">
                    Impossibile caricare le statistiche
                </div>
            `;
        });
    }

function renderLatestRegistrationsBox(data) {
    if (!data.success) {
        latestRegistrationsBox.innerHTML = `
            <div class="daily-empty">
                Errore nel caricamento degli ultimi registrati
            </div>
        `;
        return;
    }

    if (!data.rows || data.rows.length === 0) {
        latestRegistrationsBox.innerHTML = `
            <div class="daily-empty">
                Nessun dato disponibile
            </div>
        `;
        return;
    }

    let html = `
        <div class="latest-registrations-wrapper">
            <div class="table-responsive latest-registrations-table-wrap">
                <table class="table table-sm align-middle recruitment-table recruitment-table-compact mb-0">
                    <thead>
                        <tr>
                            <th>Data/Ora</th>
                            <th>Email</th>
                            <th>Referral</th>
                        </tr>
                    </thead>
                    <tbody>
    `;

    data.rows.forEach(function(item) {
        const iconHtml = item.referral_icon
            ? `<i class="${escapeHtml(item.referral_icon)} me-1"></i>`
            : '';

        html += `
            <tr>
                <td class="text-nowrap">${escapeHtml(item.reg_date)}</td>
                <td class="latest-email-cell">${escapeHtml(item.email)}</td>
                <td>
                    <div class="latest-referral-cell">
                        ${iconHtml}${escapeHtml(item.referral_label)}
                    </div>
                </td>
            </tr>
        `;
    });

    html += `
                    </tbody>
                </table>
            </div>
        </div>
    `;

    latestRegistrationsBox.innerHTML = html;
}

function loadLatestRegistrationsBox() {
    latestRegistrationsBox.innerHTML = `
        <div class="daily-loading">
            Caricamento ultimi registrati...
        </div>
    `;

    fetch(`{{ route('recruitment.latestRegistrations') }}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(function(response) {
        if (!response.ok) {
            throw new Error('Errore HTTP');
        }
        return response.json();
    })
    .then(function(data) {
        renderLatestRegistrationsBox(data);
    })
    .catch(function(error) {
        console.error(error);

        latestRegistrationsBox.innerHTML = `
            <div class="daily-empty">
                Impossibile caricare gli ultimi registrati
            </div>
        `;
    });
}

function renderSummaryYearBox(data) {
    if (!data.success) {
        summaryYearBox.innerHTML = `
            <div class="daily-empty">
                Errore nel caricamento del riepilogo annuale
            </div>
        `;
        return;
    }

    const budgetUsed = Math.max(0, Math.min(100, Number(data.kpi.budget_used_percent || 0)));
    const topRegistered = data.highlights && data.highlights.top_registered ? data.highlights.top_registered : null;
    const topActive = data.highlights && data.highlights.top_active ? data.highlights.top_active : null;

    const topRegisteredIcon = topRegistered && topRegistered.icon
        ? `<i class="${escapeHtml(topRegistered.icon)} me-1"></i>`
        : '';

    const topActiveIcon = topActive && topActive.icon
        ? `<i class="${escapeHtml(topActive.icon)} me-1"></i>`
        : '';

    summaryYearBox.innerHTML = `
        <div class="summary-year-layout">
            <div class="summary-year-left">
                <div class="summary-year-main-stack">
                    <div class="summary-main-row summary-main-row-budget">
                        <div class="summary-main-row-label">
                            <i class="bi bi-wallet2 summary-main-row-icon"></i>
                            <span>Budget</span>
                        </div>
                        <div class="summary-main-row-value">${formatCurrency(data.kpi.budget)}</div>
                    </div>

                    <div class="summary-main-row summary-main-row-spent">
                        <div class="summary-main-row-label">
                            <i class="bi bi-cash-stack summary-main-row-icon"></i>
                            <span>Speso</span>
                        </div>
                        <div class="summary-main-row-value">${formatCurrency(data.kpi.spent)}</div>
                    </div>

                    <div class="summary-main-row summary-main-row-rest">
                        <div class="summary-main-row-label">
                            <i class="bi bi-piggy-bank summary-main-row-icon"></i>
                            <span>Resto</span>
                        </div>
                        <div class="summary-main-row-value">${formatCurrency(data.kpi.rest)}</div>
                    </div>

                    <div class="summary-main-mini-grid">
                        <div class="summary-main-mini-card">
                            <div class="summary-main-mini-label">Iscritti</div>
                            <div class="summary-main-mini-value">${formatNumber(data.kpi.registered)}</div>
                        </div>

                        <div class="summary-main-mini-card">
                            <div class="summary-main-mini-label">Attivi</div>
                            <div class="summary-main-mini-value">${formatNumber(data.kpi.active)}</div>
                        </div>

                        <div class="summary-main-mini-card">
                            <div class="summary-main-mini-label">Attivi %</div>
                            <div class="summary-main-mini-value">${formatDecimal(data.kpi.active_rate, 2)}%</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="summary-year-right">
                <div class="summary-side-card">
                    <div class="summary-year-section-title">Indicatori economici</div>

                    <div class="summary-side-kpis">
                        <div class="summary-side-kpi">
                            <span class="summary-side-kpi-label">CPI Medio</span>
                            <strong class="summary-side-kpi-value">${formatDecimal(data.kpi.cpi, 2)} €</strong>
                        </div>

                        <div class="summary-side-kpi">
                            <span class="summary-side-kpi-label">CPA Medio</span>
                            <strong class="summary-side-kpi-value">${formatCurrency(data.kpi.cpa)}</strong>
                        </div>

                        <div class="summary-side-kpi">
                            <span class="summary-side-kpi-label">Referral attivi</span>
                            <strong class="summary-side-kpi-value">${formatNumber(data.kpi.active_referral_count)}</strong>
                        </div>
                    </div>
                </div>

                <div class="summary-side-card">
                    <div class="summary-year-section-title">Andamento budget</div>

                    <div class="summary-year-budget-values">
                        <span>Utilizzato</span>
                        <strong>${formatDecimal(budgetUsed, 2)}%</strong>
                    </div>

                    <div class="summary-year-budget-bar">
                        <div class="summary-year-budget-bar-fill" style="width: ${budgetUsed}%"></div>
                    </div>

                    <div class="summary-year-budget-legend">
                        <span>Speso: <strong>${formatCurrency(data.kpi.spent)}</strong></span>
                        <span>Resto: <strong>${formatCurrency(data.kpi.rest)}</strong></span>
                    </div>
                </div>

                <div class="summary-side-card">
                    <div class="summary-year-section-title">Top performance</div>

                    <div class="summary-year-pill-grid">
                        <div class="summary-year-pill">
                            <span class="summary-year-pill-label">Top iscritti</span>
                            <strong class="summary-year-pill-value">
                                ${topRegistered ? `${topRegisteredIcon}${escapeHtml(topRegistered.label)} (${formatNumber(topRegistered.value)})` : '-'}
                            </strong>
                        </div>

                        <div class="summary-year-pill">
                            <span class="summary-year-pill-label">Top attivi</span>
                            <strong class="summary-year-pill-value">
                                ${topActive ? `${topActiveIcon}${escapeHtml(topActive.label)} (${formatNumber(topActive.value)})` : '-'}
                            </strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
}

function loadSummaryYearBox() {
    const year = filterSummaryYear.value;

    summaryYearBox.innerHTML = `
        <div class="daily-loading">
            Caricamento riepilogo annuale...
        </div>
    `;

    fetch(`{{ route('recruitment.summaryYear') }}?year=${year}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(function(response) {
        if (!response.ok) {
            throw new Error('Errore HTTP');
        }
        return response.json();
    })
    .then(function(data) {
        renderSummaryYearBox(data);
    })
    .catch(function(error) {
        console.error(error);

        summaryYearBox.innerHTML = `
            <div class="daily-empty">
                Impossibile caricare il riepilogo annuale
            </div>
        `;
    });
}

function resetCampaignForm() {
    editState = null;

    // Torna alla vista lista
    campaignFormBox.classList.add('d-none');
    document.getElementById('allCampaignsList').classList.remove('d-none');
    document.getElementById('btnNewCampaign').classList.remove('d-none');
    var btnSave = document.getElementById('btnSaveCampaign');
    btnSave.disabled = false;
    btnSave.innerHTML = 'Salva';
    btnSave.classList.add('d-none');

    // Reset campi form
    existingReferralSelect.value = '';
    newReferralCode.value        = '';
    newReferralTitle.value       = '';
    newReferralIcon.value        = '';
    campaignStart.value = '';
    campaignEnd.value   = '';
    renderSegments([{ cpi: '', age_max: '' }]);
    campaignActive.checked = true;
    campaignFormTitle.textContent = 'Nuova campagna';

    // Reset lista a "Caricamento..." (verrà ricaricata da shown.bs.modal al prossimo open)
    document.getElementById('allCampaignsList').innerHTML = '<div class="text-center py-3 text-muted small">Caricamento...</div>';

    hideCampaignAlerts();
}

function showCampaignError(message) {
    campaignSuccess.classList.add('d-none');
    campaignSuccess.innerText = '';

    campaignError.innerText = message || 'Errore';
    campaignError.classList.remove('d-none');
}

function showCampaignSuccess(message) {
    campaignError.classList.add('d-none');
    campaignError.innerText = '';

    campaignSuccess.innerText = message || 'Operazione completata';
    campaignSuccess.classList.remove('d-none');
}

    filterDailyMonth.addEventListener('change', loadDailyBox);
    filterDailyYear.addEventListener('change', loadDailyBox);
    filterCostsYear.addEventListener('change', loadCostsBox);
    filterActivityYear.addEventListener('change', loadActivityBox);
    filterStatsYear.addEventListener('change', loadStatsBox);
    filterSummaryYear.addEventListener('change', loadSummaryYearBox);

    loadDailyBox();
    loadCostsBox();
    loadActivityBox();
    loadStatsBox();
    loadLatestRegistrationsBox();
    loadSummaryYearBox();

function loadReportReferrals(year) {
    const previouslySelected = Array.from(reportReferral.selectedOptions).map(function (opt) {
        return opt.value;
    });

    reportReferral.innerHTML = '<option disabled>Caricamento...</option>';

    fetch(`{{ route('recruitment.report.referrals') }}?year=${year}`)
        .then(function (response) {
            return response.json();
        })
        .then(function (data) {
            reportReferral.innerHTML = '';

            if (!data.success || !data.referrals || data.referrals.length === 0) {
                reportReferral.innerHTML = '<option disabled>Nessuna campagna attiva per questo anno</option>';
                return;
            }

            data.referrals.forEach(function (ref) {
                const option = document.createElement('option');
                option.value = ref.id;
                option.textContent = `${ref.title} (${ref.code})`;
                if (previouslySelected.includes(String(ref.id))) {
                    option.selected = true;
                }
                reportReferral.appendChild(option);
            });
        })
        .catch(function () {
            reportReferral.innerHTML = '<option disabled>Errore nel caricamento delle campagne</option>';
        });
}

function resetReportForm() {
    reportMonthFrom.value = '{{ $currentMonth }}';
    reportMonthTo.value = '{{ $currentMonth }}';
    reportYear.value = '{{ $currentYear }}';
    reportError.classList.add('d-none');
    reportError.innerText = '';
    btnDownloadReport.disabled = false;
    btnDownloadReport.innerHTML = 'Scarica Excel';
    loadReportReferrals(reportYear.value);
}

btnOpenReportModal.addEventListener('click', function () {
    resetReportForm();
    reportModal.show();
});

reportModalElement.addEventListener('hidden.bs.modal', function () {
    resetReportForm();
});

reportYear.addEventListener('change', function () {
    loadReportReferrals(reportYear.value);
});

btnDownloadReport.addEventListener('click', function () {
    const monthFrom = parseInt(reportMonthFrom.value, 10);
    const monthTo = parseInt(reportMonthTo.value, 10);
    const year = reportYear.value;

    reportError.classList.add('d-none');
    reportError.innerText = '';

    if (monthTo < monthFrom) {
        reportError.innerText = 'Il mese "A" non può essere precedente al mese "Da".';
        reportError.classList.remove('d-none');
        return;
    }

    const selectedReferralIds = Array.from(reportReferral.selectedOptions).map(function(option) {
        return option.value;
    });

    btnDownloadReport.disabled = true;
    btnDownloadReport.innerHTML = 'Preparazione...';

    const params = new URLSearchParams();
    params.append('month_from', monthFrom);
    params.append('month_to', monthTo);
    params.append('year', year);

    selectedReferralIds.forEach(function(id) {
        params.append('referral_ids[]', id);
    });

    fetch(`{{ route('recruitment.report.export') }}?${params.toString()}`)
        .then(function (response) {
            if (!response.ok) {
                return response.json().then(function (data) {
                    throw new Error(data.message || 'Errore durante la generazione del report.');
                });
            }
            const disposition = response.headers.get('Content-Disposition') || '';
            const match = disposition.match(/filename="?([^"]+)"?/);
            const fileName = match ? match[1] : 'recruitment_report.xlsx';
            return response.blob().then(function (blob) {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = fileName;
                document.body.appendChild(a);
                a.click();
                a.remove();
                window.URL.revokeObjectURL(url);
                reportModal.hide();
            });
        })
        .catch(function (error) {
            reportError.innerText = error.message || 'Errore durante il download.';
            reportError.classList.remove('d-none');
        })
        .finally(function () {
            btnDownloadReport.disabled = false;
            btnDownloadReport.innerHTML = 'Scarica Excel';
        });
});

});
</script>
@endsection
