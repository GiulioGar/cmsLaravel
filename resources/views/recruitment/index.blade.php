@extends('layouts.main')

@section('content')
<link rel="stylesheet" href="{{ asset('css/recruitment.css') }}">

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
            Genera Report CSV
        </button>

        <button type="button" class="btn btn-primary" id="btnOpenCampaignModal">
            <i class="bi bi-plus-circle me-1"></i>
            Nuova Campagna
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
                <h5 class="modal-title">Nuova Campagna Referral</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
            </div>

            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-bold d-block">Tipo Referral</label>

                    <div class="d-flex flex-wrap gap-3">
                        <label class="form-check-label">
                            <input class="form-check-input me-1" type="radio" name="referral_mode" value="existing" checked>
                            Esistente
                        </label>

                        <label class="form-check-label">
                            <input class="form-check-input me-1" type="radio" name="referral_mode" value="new">
                            Nuovo
                        </label>
                    </div>
                </div>

                <div id="existingReferralBox" class="mb-3">
                    <label class="form-label">Seleziona Referral</label>
                    <select class="form-select" id="existingReferralSelect">
                        <option value="">-- seleziona --</option>
                        @foreach($referrals as $ref)
                            @if($ref->group_type !== 'fallback')
                                <option value="{{ $ref->id }}">
                                    {{ $ref->title }} ({{ $ref->code }})
                                </option>
                            @endif
                        @endforeach
                    </select>
                </div>

                <div id="newReferralBox" class="mb-3 d-none">
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

                <hr>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Data inizio</label>
                        <input type="date" class="form-control" id="campaignStart">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Data fine</label>
                        <input type="date" class="form-control" id="campaignEnd">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">CPI</label>
                        <input type="number" step="0.0001" min="0" class="form-control" id="campaignCpi">
                    </div>
                </div>

                <div class="mt-3">
                    <label class="form-check-label">
                        <input class="form-check-input me-1" type="checkbox" id="campaignActive" checked>
                        Attiva
                    </label>
                </div>

                <div id="campaignError" class="alert alert-danger mt-3 d-none mb-0"></div>
                <div id="campaignSuccess" class="alert alert-success mt-3 d-none mb-0"></div>
            </div>

            <div class="modal-footer">
                <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Chiudi</button>
                <button class="btn btn-primary" type="button" id="btnSaveCampaign">
                    Salva Campagna
                </button>
            </div>
        </div>
    </div>
</div>



<div class="modal fade" id="reportModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content recruitment-modal">
            <div class="modal-header">
                <h5 class="modal-title">Genera Report CSV</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
            </div>

            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Mese</label>
                        <select class="form-select" id="reportMonth">
                            @foreach($months as $monthNumber => $monthLabel)
                                <option value="{{ $monthNumber }}" {{ $monthNumber == $currentMonth ? 'selected' : '' }}>
                                    {{ $monthLabel }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
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
                    Scarica CSV
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
    const campaignCpi = document.getElementById('campaignCpi');
    const campaignActive = document.getElementById('campaignActive');

    const campaignError = document.getElementById('campaignError');
    const campaignSuccess = document.getElementById('campaignSuccess');

    const btnOpenReportModal = document.getElementById('btnOpenReportModal');
const btnDownloadReport = document.getElementById('btnDownloadReport');

const reportModalElement = document.getElementById('reportModal');
const reportModal = new bootstrap.Modal(reportModalElement);

const reportMonth = document.getElementById('reportMonth');
const reportYear = document.getElementById('reportYear');
const reportReferral = document.getElementById('reportReferral');
const reportError = document.getElementById('reportError');

btnOpenCampaignModal.addEventListener('click', function () {
    resetCampaignForm();
    campaignModal.show();
});

document.querySelectorAll('input[name="referral_mode"]').forEach(function (el) {
    el.addEventListener('change', function () {
        if (this.value === 'existing') {
            existingReferralBox.classList.remove('d-none');
            newReferralBox.classList.add('d-none');
        } else {
            existingReferralBox.classList.add('d-none');
            newReferralBox.classList.remove('d-none');
        }
    });
});

campaignModalElement.addEventListener('hidden.bs.modal', function () {
    resetCampaignForm();
});

btnSaveCampaign.addEventListener('click', function () {
    const referralMode = document.querySelector('input[name="referral_mode"]:checked').value;

    const payload = {
        referral_mode: referralMode,
        existing_referral_id: existingReferralSelect.value,
        new_referral_code: newReferralCode.value.trim(),
        new_referral_title: newReferralTitle.value.trim(),
        new_referral_icon: newReferralIcon.value.trim(),
        start_date: campaignStart.value,
        end_date: campaignEnd.value,
        cpi: campaignCpi.value,
        is_active: campaignActive.checked ? 1 : 0
    };

    campaignError.classList.add('d-none');
    campaignError.innerText = '';
    campaignSuccess.classList.add('d-none');
    campaignSuccess.innerText = '';

    btnSaveCampaign.disabled = true;
    btnSaveCampaign.innerHTML = 'Salvataggio...';

    fetch(`{{ route('recruitment.campaigns.store') }}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: JSON.stringify(payload)
    })
    .then(function (response) {
        return response.json().then(function (data) {
            return {
                ok: response.ok,
                status: response.status,
                data: data
            };
        });
    })
    .then(function (result) {
        if (!result.ok || !result.data.success) {
            showCampaignError(result.data.message || 'Errore durante il salvataggio.');
            btnSaveCampaign.disabled = false;
            btnSaveCampaign.innerHTML = 'Salva Campagna';
            return;
        }

        showCampaignSuccess(result.data.message || 'Campagna inserita correttamente.');

        loadDailyBox();
        loadCostsBox();
        loadActivityBox();
        loadStatsBox();
        loadSummaryYearBox();

        setTimeout(function () {
            campaignModal.hide();
        }, 700);
    })
    .catch(function (error) {
        console.error(error);
        showCampaignError('Errore di connessione.');
        btnSaveCampaign.disabled = false;
        btnSaveCampaign.innerHTML = 'Salva Campagna';
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

    let html = `
        <div class="daily-month-shell">
            <div class="daily-month-top">
                <div class="daily-month-summary">
                    <div class="daily-month-summary-label">Registrati mese</div>
                    <div class="daily-month-summary-value">${formatNumber(data.total_registered)}</div>
                    <div class="daily-month-summary-subtitle">${escapeHtml(data.month_label)}</div>
                </div>

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

   function renderCostsBox(data) {
    if (!data.success) {
        costsBox.innerHTML = `
            <div class="daily-empty">
                Errore nel caricamento dei costi
            </div>
        `;
        return;
    }

    if (!data.rows || data.rows.length === 0) {
        costsBox.innerHTML = `
            <div class="daily-empty">
                Nessun referral disponibile per l'anno selezionato
            </div>
        `;
        return;
    }

    let html = `
        <div class="costs-referral-grid">
    `;

    data.rows.forEach(function(item) {
        const iconHtml = item.icon
            ? `<i class="${escapeHtml(item.icon)}"></i>`
            : '';

        const sourcesText = item.sources && item.sources.length
            ? item.sources.join(', ')
            : '-';

        const sourceCount = item.sources && item.sources.length
            ? item.sources.length
            : 0;

        html += `
            <div class="costs-referral-card">
                <div class="costs-referral-card-main">
                    <div class="costs-referral-card-left">
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

                    <div class="costs-referral-side">
                        <div class="costs-referral-main-number-label">Costo</div>
                        <div class="costs-referral-main-number">${formatCurrency(item.cost)}</div>
                    </div>
                </div>

                <div class="costs-referral-stats costs-referral-stats-top">
                    <div class="costs-referral-stat">
                        <span class="costs-referral-stat-label">Registrati</span>
                        <strong class="costs-referral-stat-value">${formatNumber(item.registered)}</strong>
                    </div>

                    <div class="costs-referral-stat">
                        <span class="costs-referral-stat-label">Attivi</span>
                        <strong class="costs-referral-stat-value">${formatNumber(item.active)}</strong>
                    </div>

                    <div class="costs-referral-stat">
                        <span class="costs-referral-stat-label">Attivi %</span>
                        <strong class="costs-referral-stat-value">${formatDecimal(item.active_rate, 2)}%</strong>
                    </div>
                </div>

                <div class="costs-referral-stats costs-referral-stats-bottom">
                    <div class="costs-referral-stat">
                        <span class="costs-referral-stat-label">CPI</span>
                        <strong class="costs-referral-stat-value">${formatDecimal(item.cpi, 4)}</strong>
                    </div>

                    <div class="costs-referral-stat">
                        <span class="costs-referral-stat-label">CPA</span>
                        <strong class="costs-referral-stat-value">${formatCurrency(item.cpa)}</strong>
                    </div>
                </div>
            </div>
        `;
    });

    html += `</div>`;

    costsBox.innerHTML = html;
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

function renderActivityBox(data) {
    if (!data.success) {
        activityBox.innerHTML = `
            <div class="daily-empty">
                Errore nel caricamento del dettaglio attività
            </div>
        `;
        return;
    }

    if (!data.rows || data.rows.length === 0) {
        activityBox.innerHTML = `
            <div class="daily-empty">
                Nessun dato disponibile per l'anno selezionato
            </div>
        `;
        return;
    }

    let html = `<div class="activity-card-grid">`;

    data.rows.forEach(function(item) {
        const iconHtml = item.icon ? `<i class="${escapeHtml(item.icon)}"></i>` : '';
        const sources = item.sources || [];
        const sourcesText = sources.join(', ');
        const sourcesCount = sources.length;

        html += `
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
                        <div class="activity-percent">${formatDecimal(item.perc_0, 2)}%</div>
                    </div>

                    <div class="activity-row activity-row-orange">
                        <div class="activity-label">Bassa (1-2)</div>
                        <div class="activity-value">${formatNumber(item.act_1_2)}</div>
                        <div class="activity-percent">${formatDecimal(item.perc_1_2, 2)}%</div>
                    </div>

                    <div class="activity-row activity-row-yellow">
                        <div class="activity-label">Media (3-5)</div>
                        <div class="activity-value">${formatNumber(item.act_3_5)}</div>
                        <div class="activity-percent">${formatDecimal(item.perc_3_5, 2)}%</div>
                    </div>

                    <div class="activity-row activity-row-lime">
                        <div class="activity-label">Buona (6-9)</div>
                        <div class="activity-value">${formatNumber(item.act_6_9)}</div>
                        <div class="activity-percent">${formatDecimal(item.perc_6_9, 2)}%</div>
                    </div>

                    <div class="activity-row activity-row-green">
                        <div class="activity-label">Ottima (10+)</div>
                        <div class="activity-value">${formatNumber(item.act_10_plus)}</div>
                        <div class="activity-percent">${formatDecimal(item.perc_10_plus, 2)}%</div>
                    </div>
                </div>

                <div class="activity-sources">
                    <span class="activity-sources-pill" title="${escapeHtml(sourcesText)}">
                        ${sourcesCount} source${sourcesCount === 1 ? '' : 's'}
                    </span>
                </div>
            </div>
        `;
    });

    html += `</div>`;
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

  function renderStatsBox(data) {
    if (!data.success) {
        statsBox.innerHTML = `
            <div class="daily-empty">
                Errore nel caricamento delle statistiche
            </div>
        `;
        return;
    }

    if (!data.rows || data.rows.length === 0) {
        statsBox.innerHTML = `
            <div class="daily-empty">
                Nessun dato disponibile per l'anno selezionato
            </div>
        `;
        return;
    }

    let html = `<div class="demographic-card-grid">`;

    data.rows.forEach(function(item) {
        const iconHtml = item.icon
            ? `<i class="${escapeHtml(item.icon)}"></i>`
            : '';

        const sources = item.sources || [];
        const sourcesText = sources.length ? sources.join(', ') : '-';
        const sourcesCount = sources.length;

        html += `
            <div class="demographic-card">
                <div class="demographic-card-header">
                    <div class="demographic-card-title-wrap">
                        <span class="demographic-card-icon">${iconHtml}</span>
                        <div>
                            <div class="demographic-card-title">${escapeHtml(item.label)}</div>
                            <div class="demographic-card-subtitle">
                                Totale registrati: <strong>${formatNumber(item.total_registered)}</strong>
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
                                <div class="demographic-list-row">
                                    <span class="demographic-list-label">Uomini</span>
                                    <strong class="demographic-list-value">${formatNumber(item.gender_male)}</strong>
                                </div>
                                <div class="demographic-list-row">
                                    <span class="demographic-list-label">Donne</span>
                                    <strong class="demographic-list-value">${formatNumber(item.gender_female)}</strong>
                                </div>
                                <div class="demographic-list-row">
                                    <span class="demographic-list-label">N.D.</span>
                                    <strong class="demographic-list-value">${formatNumber(item.gender_unknown)}</strong>
                                </div>
                            </div>
                        </div>

                        <div class="demographic-section">
                            <div class="demographic-section-title">
                                <i class="bi bi-geo-alt demographic-section-icon"></i>
                                <span>Area</span>
                            </div>

                            <div class="demographic-list">
                                <div class="demographic-list-row">
                                    <span class="demographic-list-label">Nord Ovest</span>
                                    <strong class="demographic-list-value">${formatNumber(item.area_nord_ovest)}</strong>
                                </div>
                                <div class="demographic-list-row">
                                    <span class="demographic-list-label">Nord Est</span>
                                    <strong class="demographic-list-value">${formatNumber(item.area_nord_est)}</strong>
                                </div>
                                <div class="demographic-list-row">
                                    <span class="demographic-list-label">Centro</span>
                                    <strong class="demographic-list-value">${formatNumber(item.area_centro)}</strong>
                                </div>
                                <div class="demographic-list-row">
                                    <span class="demographic-list-label">Sud</span>
                                    <strong class="demographic-list-value">${formatNumber(item.area_sud)}</strong>
                                </div>
                                <div class="demographic-list-row">
                                    <span class="demographic-list-label">N.D.</span>
                                    <strong class="demographic-list-value">${formatNumber(item.area_unknown)}</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="demographic-layout-right">
                        <div class="demographic-section demographic-section-age">
                            <div class="demographic-section-title">
                                <i class="bi bi-calendar3 demographic-section-icon"></i>
                                <span>Età</span>
                            </div>

                            <div class="demographic-list">
                                <div class="demographic-list-row">
                                    <span class="demographic-list-label">&lt;18</span>
                                    <strong class="demographic-list-value">${formatNumber(item.age_under_18)}</strong>
                                </div>
                                <div class="demographic-list-row">
                                    <span class="demographic-list-label">18-24</span>
                                    <strong class="demographic-list-value">${formatNumber(item.age_18_24)}</strong>
                                </div>
                                <div class="demographic-list-row">
                                    <span class="demographic-list-label">25-34</span>
                                    <strong class="demographic-list-value">${formatNumber(item.age_25_34)}</strong>
                                </div>
                                <div class="demographic-list-row">
                                    <span class="demographic-list-label">35-44</span>
                                    <strong class="demographic-list-value">${formatNumber(item.age_35_44)}</strong>
                                </div>
                                <div class="demographic-list-row">
                                    <span class="demographic-list-label">45-54</span>
                                    <strong class="demographic-list-value">${formatNumber(item.age_45_54)}</strong>
                                </div>
                                <div class="demographic-list-row">
                                    <span class="demographic-list-label">55-64</span>
                                    <strong class="demographic-list-value">${formatNumber(item.age_55_64)}</strong>
                                </div>
                                <div class="demographic-list-row">
                                    <span class="demographic-list-label">65+</span>
                                    <strong class="demographic-list-value">${formatNumber(item.age_65_plus)}</strong>
                                </div>
                                <div class="demographic-list-row">
                                    <span class="demographic-list-label">N.D.</span>
                                    <strong class="demographic-list-value">${formatNumber(item.age_unknown)}</strong>
                                </div>
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
    });

    html += `</div>`;
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
    document.querySelector('input[name="referral_mode"][value="existing"]').checked = true;

    existingReferralBox.classList.remove('d-none');
    newReferralBox.classList.add('d-none');

    existingReferralSelect.value = '';
    newReferralCode.value = '';
    newReferralTitle.value = '';
    newReferralIcon.value = '';
    campaignStart.value = '';
    campaignEnd.value = '';
    campaignCpi.value = '';
    campaignActive.checked = true;

    campaignError.classList.add('d-none');
    campaignError.innerText = '';

    campaignSuccess.classList.add('d-none');
    campaignSuccess.innerText = '';

    btnSaveCampaign.disabled = false;
    btnSaveCampaign.innerHTML = 'Salva Campagna';
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

function resetReportForm() {
    reportMonth.value = '{{ $currentMonth }}';
    reportYear.value = '{{ $currentYear }}';
    reportReferral.value = '';
    reportError.classList.add('d-none');
    reportError.innerText = '';
    btnDownloadReport.disabled = false;
    btnDownloadReport.innerHTML = 'Scarica CSV';
}

btnOpenReportModal.addEventListener('click', function () {
    resetReportForm();
    reportModal.show();
});

reportModalElement.addEventListener('hidden.bs.modal', function () {
    resetReportForm();
});

btnDownloadReport.addEventListener('click', function () {
    const month = reportMonth.value;
    const year = reportYear.value;

    const selectedReferralIds = Array.from(reportReferral.selectedOptions).map(function(option) {
        return option.value;
    });

    reportError.classList.add('d-none');
    reportError.innerText = '';

    btnDownloadReport.disabled = true;
    btnDownloadReport.innerHTML = 'Preparazione...';

    const params = new URLSearchParams();
    params.append('month', month);
    params.append('year', year);

    selectedReferralIds.forEach(function(id) {
        params.append('referral_ids[]', id);
    });

    window.location.href = `{{ route('recruitment.report.export') }}?${params.toString()}`;

    setTimeout(function () {
        btnDownloadReport.disabled = false;
        btnDownloadReport.innerHTML = 'Scarica CSV';
        reportModal.hide();
    }, 800);
});

});
</script>
@endsection
