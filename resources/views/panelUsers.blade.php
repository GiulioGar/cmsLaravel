@extends('layouts.main')

@section('head')
<link rel="stylesheet" href="{{ asset('css/panelUsers.css') }}">
@endsection

@section('content')

<main class="content">
    <div class="container-fluid">

        <div class="row">
            {{-- COLONNA SINISTRA --}}
            <div class="col-lg-7">
                <div class="card panel-users-card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="mb-0">Utenti Panel</h4>
                        <small class="text-muted">Consultazione utenti, inviti e dati di iscrizione</small>
                    </div>

                    <button type="button" id="btnSyncActions" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-arrow-repeat me-1"></i>
                        Aggiorna Attività
                    </button>
                </div>

                    <div class="card-body">
                        <table id="panel-users-table" class="table table-sm table-striped align-middle w-100">
                        <thead>
                            <tr>
                                <th>UID</th>
                                <th>Email</th>
                                <th>Età</th>
                                <th>Inviti</th>
                                <th>Iscrizione</th>
                                <th>Stato</th>
                            </tr>
                        </thead>
                            <tbody>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        Caricamento utenti in corso...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- COLONNA DESTRA --}}

{{-- COLONNA DESTRA --}}
<div class="col-lg-5">

<div class="card panel-users-card panel-control-card">
    <div class="card-header">
        <h4 class="mb-0"><i class="bi bi-shield-check me-2"></i>Centro Controllo</h4>
        <small class="text-muted">Verifiche automatiche sugli iscritti, con azioni rapide</small>
    </div>

    <div class="card-body">
        <div class="control-checks-row">

            <div class="control-check-icon-btn" id="tileUnconfirmed"
                 data-bs-toggle="tooltip" data-bs-placement="bottom"
                 title="Email di conferma non ricevuta: iscritti non attivi negli ultimi 6 mesi. Clicca per verificare e abilitare manualmente.">
                <span class="control-check-icon control-check-icon-amber">
                    <i class="bi bi-envelope-exclamation"></i>
                </span>
                <span class="control-check-dot d-none" id="countUnconfirmed"></span>
            </div>

            <div class="control-check-icon-btn" id="tileImplausibleAge"
                 data-bs-toggle="tooltip" data-bs-placement="bottom"
                 title="Età poco credibile: attivi con meno di 14 o più di 85 anni. Clicca per verificare e correggere.">
                <span class="control-check-icon control-check-icon-rose">
                    <i class="bi bi-person-exclamation"></i>
                </span>
                <span class="control-check-dot d-none" id="countImplausibleAge"></span>
            </div>

            <div class="control-check-icon-btn" id="tileSuspiciousEmail"
                 data-bs-toggle="tooltip" data-bs-placement="bottom"
                 title="Email con dominio sospetto (probabile refuso, es. gnail.com). Clicca per verificare e correggere.">
                <span class="control-check-icon control-check-icon-indigo">
                    <i class="bi bi-at"></i>
                </span>
                <span class="control-check-dot d-none" id="countSuspiciousEmail"></span>
            </div>

            <div class="control-check-icon-btn" id="tileMissingGeo"
                 data-bs-toggle="tooltip" data-bs-placement="bottom"
                 title="Dati geografici mancanti (area/regione/ampiezza) derivabili dalla provincia. Clicca per verificare e applicare la correzione.">
                <span class="control-check-icon control-check-icon-teal">
                    <i class="bi bi-geo-alt"></i>
                </span>
                <span class="control-check-dot d-none" id="countMissingGeo"></span>
            </div>

            <div class="control-check-icon-btn" id="tileDataAnomalies"
                 data-bs-toggle="tooltip" data-bs-placement="bottom"
                 title="Anomalie anagrafiche: data iscrizione/nascita mal formattata, genere mancante o incoerente col nome, password non hashata. Clicca per verificare.">
                <span class="control-check-icon control-check-icon-crimson">
                    <i class="bi bi-person-vcard"></i>
                </span>
                <span class="control-check-dot d-none" id="countDataAnomalies"></span>
            </div>

        </div>
    </div>
</div>

<div class="modal fade" id="modalUnconfirmed" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-envelope-exclamation me-2 text-warning"></i>Email di conferma non ricevuta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">
                    Iscritti risultati <strong>non attivi</strong> negli ultimi 6 mesi: in alcuni casi l'email di conferma non arriva mai.
                    Verifica e abilita manualmente chi ritieni valido.
                </p>
                <div id="unconfirmedLoader" class="text-center py-4">
                    <div class="spinner-border text-secondary mb-2" style="width:1.8rem;height:1.8rem;"></div>
                    <div class="text-muted small">Caricamento in corso...</div>
                </div>
                <div class="table-responsive d-none" id="unconfirmedTableWrap">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>UID</th>
                                <th>Email</th>
                                <th>Iscrizione</th>
                                <th>Email confermata</th>
                                <th class="text-end">Azione</th>
                            </tr>
                        </thead>
                        <tbody id="unconfirmedTableBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalImplausibleAge" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-person-exclamation me-2 text-danger"></i>Età poco credibile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">
                    Utenti <strong>attivi</strong> con età anagrafica inferiore a 14 o superiore a 85 anni: correggi la data di nascita se errata.
                </p>
                <div id="implausibleAgeLoader" class="text-center py-4">
                    <div class="spinner-border text-secondary mb-2" style="width:1.8rem;height:1.8rem;"></div>
                    <div class="text-muted small">Caricamento in corso...</div>
                </div>
                <div class="table-responsive d-none" id="implausibleAgeTableWrap">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>UID</th>
                                <th>Email</th>
                                <th>Età</th>
                                <th>Nuova data di nascita</th>
                                <th class="text-end">Azione</th>
                            </tr>
                        </thead>
                        <tbody id="implausibleAgeTableBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalSuspiciousEmail" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content pu-email-modal">
            <div class="modal-header pu-email-modal-header">
                <div class="d-flex align-items-center gap-3">
                    <span class="pu-email-modal-icon"><i class="bi bi-at"></i></span>
                    <div>
                        <h5 class="modal-title mb-0">Email con dominio sospetto</h5>
                        <small class="text-muted">Verifica DNS + analisi di somiglianza sui domini del panel</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">

                <div class="pu-email-legend">
                    <div class="pu-email-legend-item">
                        <span class="pu-sev-badge pu-sev-invalid">Non valida</span>
                        <span>dominio senza server di posta — sicuramente non funzionante</span>
                        <span class="pu-email-legend-count" id="suspiciousEmailCountInvalid">0</span>
                    </div>
                    <div class="pu-email-legend-item">
                        <span class="pu-sev-badge pu-sev-suspect">Da verificare</span>
                        <span>DNS valido ma dominio raro e simile a uno diffuso — potrebbe anche essere un provider reale poco comune</span>
                        <span class="pu-email-legend-count" id="suspiciousEmailCountSuspect">0</span>
                    </div>
                </div>

                <div id="suspiciousEmailLoader" class="text-center py-5">
                    <div class="spinner-border text-secondary mb-2" style="width:1.8rem;height:1.8rem;"></div>
                    <div class="text-muted small">Caricamento in corso...</div>
                </div>

                <div id="suspiciousEmailEmpty" class="pu-email-empty d-none">
                    <i class="bi bi-patch-check"></i>
                    <div class="fw-semibold mt-2">Nessun dominio sospetto trovato</div>
                    <div class="text-muted small">Tutte le email attive risultano su domini affidabili.</div>
                </div>

                <div class="table-responsive d-none pu-email-table-wrap" id="suspiciousEmailTableWrap">
                    <table class="table table-sm align-middle pu-email-table">
                        <thead>
                            <tr>
                                <th style="width:120px;">Stato</th>
                                <th style="width:140px;">UID</th>
                                <th>Email attuale</th>
                                <th>Email corretta</th>
                                <th class="text-end" style="width:110px;">Azione</th>
                            </tr>
                        </thead>
                        <tbody id="suspiciousEmailTableBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalMissingGeo" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content pu-email-modal">
            <div class="modal-header pu-email-modal-header">
                <div class="d-flex align-items-center gap-3">
                    <span class="pu-email-modal-icon"><i class="bi bi-geo-alt"></i></span>
                    <div>
                        <h5 class="modal-title mb-0">Dati geografici mancanti</h5>
                        <small class="text-muted">Area geografica, regione e ampiezza centro derivate dalla provincia</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">

                <p class="text-muted small mb-3">
                    Chi ha la provincia di residenza ma non area/regione/ampiezza può essere corretto in automatico:
                    il valore corretto si ricava dagli altri utenti della stessa provincia già censiti correttamente,
                    nessun dato esterno. Operazione sicura e ripetibile (aggiorna solo i campi vuoti).
                </p>

                <div id="missingGeoLoader" class="text-center py-4">
                    <div class="spinner-border text-secondary mb-2" style="width:1.8rem;height:1.8rem;"></div>
                    <div class="text-muted small">Caricamento in corso...</div>
                </div>

                <div id="missingGeoContent" class="d-none">
                    <div class="pu-email-legend mb-3">
                        <div class="pu-email-legend-item">
                            <span class="pu-sev-badge pu-sev-suspect">Risolvibili</span>
                            <span>provincia nota, valore ricavabile dagli altri utenti</span>
                            <span class="pu-email-legend-count" id="missingGeoResolvable">0</span>
                        </div>
                        <div class="pu-email-legend-item">
                            <span class="pu-sev-badge pu-sev-invalid">Non risolvibili</span>
                            <span>provincia senza alcun riferimento noto nel database</span>
                            <span class="pu-email-legend-count" id="missingGeoUnresolvable">0</span>
                        </div>
                    </div>

                    <div id="missingGeoUnresolvableList" class="small text-muted mb-3 d-none"></div>

                    <div id="missingGeoAllDone" class="pu-email-empty d-none">
                        <i class="bi bi-patch-check"></i>
                        <div class="fw-semibold mt-2">Nessun dato mancante</div>
                        <div class="text-muted small">Area, regione e ampiezza sono completi per tutti gli iscritti con provincia nota.</div>
                    </div>

                    <div id="missingGeoActionWrap" class="text-end">
                        <button type="button" class="btn btn-primary" id="btnApplyMissingGeo">
                            <i class="bi bi-magic me-1"></i>Applica a tutti i risolvibili
                        </button>
                    </div>

                    <div id="missingGeoResult" class="alert alert-success mt-3 d-none"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalDataAnomalies" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content pu-email-modal">
            <div class="modal-header pu-email-modal-header">
                <div class="d-flex align-items-center gap-3">
                    <span class="pu-email-modal-icon" style="background: linear-gradient(135deg, #f87171, #991b1b);"><i class="bi bi-person-vcard"></i></span>
                    <div>
                        <h5 class="modal-title mb-0">Anomalie anagrafiche</h5>
                        <small class="text-muted">Data iscrizione, data di nascita, genere, password</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">

                <div id="dataAnomaliesLoader" class="text-center py-5">
                    <div class="spinner-border text-secondary mb-2" style="width:1.8rem;height:1.8rem;"></div>
                    <div class="text-muted small">Caricamento in corso...</div>
                </div>

                <div id="dataAnomaliesContent" class="d-none">

                    <!-- 1. reg_date -->
                    <div class="pu-anom-section">
                        <div class="pu-anom-section-header">
                            <span>Data iscrizione mal formattata</span>
                            <span class="pu-email-legend-count" id="countSectionRegDate">0</span>
                        </div>
                        <div class="table-responsive pu-anom-table-wrap">
                            <table class="table table-sm align-middle pu-email-table">
                                <thead><tr><th style="width:140px;">UID</th><th>Email</th><th>Valore attuale</th><th>Corretto</th><th class="text-end" style="width:90px;">Azione</th></tr></thead>
                                <tbody id="regDateTableBody"></tbody>
                            </table>
                        </div>
                    </div>

                    <!-- 2. birth_date formato -->
                    <div class="pu-anom-section">
                        <div class="pu-anom-section-header">
                            <span>Data di nascita mal formattata</span>
                            <span class="pu-email-legend-count" id="countSectionBirthDate">0</span>
                        </div>
                        <div class="table-responsive pu-anom-table-wrap">
                            <table class="table table-sm align-middle pu-email-table">
                                <thead><tr><th style="width:140px;">UID</th><th>Email</th><th>Valore attuale</th><th>Corretto</th><th class="text-end" style="width:90px;">Azione</th></tr></thead>
                                <tbody id="birthDateFormatTableBody"></tbody>
                            </table>
                        </div>
                    </div>

                    <!-- 3. gender mancante -->
                    <div class="pu-anom-section">
                        <div class="pu-anom-section-header">
                            <span>Genere mancante</span>
                            <span class="pu-email-legend-count" id="countSectionMissingGender">0</span>
                        </div>
                        <div class="table-responsive pu-anom-table-wrap">
                            <table class="table table-sm align-middle pu-email-table">
                                <thead><tr><th style="width:140px;">UID</th><th>Email</th><th>Nome</th><th>Genere</th><th class="text-end" style="width:90px;">Azione</th></tr></thead>
                                <tbody id="missingGenderTableBody"></tbody>
                            </table>
                        </div>
                    </div>

                    <!-- 4. nome/genere incoerente -->
                    <div class="pu-anom-section">
                        <div class="pu-anom-section-header">
                            <span>
                                <span class="pu-sev-badge pu-sev-suspect">Da verificare</span>
                                Genere incoerente col nome
                            </span>
                            <span class="pu-email-legend-count" id="countSectionNameGender">0</span>
                        </div>
                        <p class="text-muted small mb-2">Nomi molto diffusi nel panel (&ge;20 casi) usati per oltre il 95% con un solo genere: la minoranza è probabile un errore, ma può anche trattarsi di un nome realmente unisex o straniero — verificare prima di modificare.</p>
                        <div class="table-responsive pu-anom-table-wrap">
                            <table class="table table-sm align-middle pu-email-table">
                                <thead><tr><th style="width:140px;">UID</th><th>Email</th><th>Nome</th><th>Genere</th><th class="text-end" style="width:90px;">Azione</th></tr></thead>
                                <tbody id="nameGenderTableBody"></tbody>
                            </table>
                        </div>
                    </div>

                    <!-- 5. password -->
                    <div class="pu-anom-section">
                        <div class="pu-anom-section-header">
                            <span>Password non sicura</span>
                        </div>
                        <p class="text-muted small mb-2">
                            Le password non vengono mai mostrate in chiaro in questa interfaccia.
                        </p>
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                            <div>
                                <span class="pu-sev-badge pu-sev-invalid">Non valida</span>
                                <span class="small text-muted">salvata in chiaro invece che con hash — <strong id="countPlaintextPassword">0</strong> utenti</span>
                            </div>
                            <button type="button" class="btn btn-sm btn-primary" id="btnHashPasswords">
                                <i class="bi bi-shield-lock me-1"></i>Hash automatico (stessa password, nessuna visualizzata)
                            </button>
                        </div>
                        <div class="small text-muted">
                            <span class="pu-sev-badge pu-sev-suspect">Da verificare</span>
                            password assente — <strong id="countMissingPassword">0</strong> utenti: non risolvibile da qui, richiede reset manuale con l'utente.
                        </div>
                        <div id="passwordHashResult" class="alert alert-success mt-2 d-none"></div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

 <div class="card panel-users-card panel-stats-card">
    <div class="card-header">
        <h4 class="mb-0">Utenti attivi ultimi 18 mesi</h4>
        <small class="text-muted">Utenti attivi e confermati con almeno un'azione negli ultimi 18 mesi</small>
    </div>

    <div class="card-body">

        <div id="activeSummaryLoader" class="text-center py-4">
            <div class="spinner-border text-secondary mb-2" style="width:1.8rem;height:1.8rem;"></div>
            <div class="text-muted small">Caricamento riepilogo in corso...</div>
        </div>

        <div id="activeSummaryBox" class="d-none">

            <div class="pu-stat-hero">
                <div class="pu-stat-hero-top">
                    <div>
                        <div class="pu-stat-label">Totale attivi 18 mesi</div>
                        <div class="pu-stat-main">
                            <span id="totaleAttivi18MesiValue">0</span>
                            <span class="pu-stat-main-sub">
                                / <span id="totalePanelValue">0</span>
                            </span>
                        </div>
                    </div>

                    <div class="pu-stat-percent">
                        <span id="percentualeAttivi18MesiValue">0</span>%
                    </div>
                </div>

                <div class="pu-progress-wrap mt-3">
                    <div class="pu-progress-track">
                        <div id="percentualeAttivi18MesiBar" class="pu-progress-fill pu-progress-fill-total" style="width: 0%;"></div>
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-md-6 mb-3 mb-md-0">
                    <div class="pu-mini-stat pu-mini-stat-male">
                        <div class="pu-mini-head">
                            <span class="pu-mini-title">Uomini</span>
                            <span class="pu-mini-percent"><span id="percentualeAttivi18MesiUomoValue">0</span>%</span>
                        </div>

                        <div class="pu-mini-value">
                            <span id="totaleAttivi18MesiUomoValue">0</span>
                            <span class="pu-mini-sub">
                                / <span id="totalePanelUomoValue">0</span>
                            </span>
                        </div>

                        <div class="pu-progress-wrap mt-2">
                            <div class="pu-progress-track">
                                <div id="percentualeAttivi18MesiUomoBar" class="pu-progress-fill pu-progress-fill-male" style="width: 0%;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="pu-mini-stat pu-mini-stat-female">
                        <div class="pu-mini-head">
                            <span class="pu-mini-title">Donne</span>
                            <span class="pu-mini-percent"><span id="percentualeAttivi18MesiDonnaValue">0</span>%</span>
                        </div>

                        <div class="pu-mini-value">
                            <span id="totaleAttivi18MesiDonnaValue">0</span>
                            <span class="pu-mini-sub">
                                / <span id="totalePanelDonnaValue">0</span>
                            </span>
                        </div>

                        <div class="pu-progress-wrap mt-2">
                            <div class="pu-progress-track">
                                <div id="percentualeAttivi18MesiDonnaBar" class="pu-progress-fill pu-progress-fill-female" style="width: 0%;"></div>
                            </div>
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

                <div class="form-check">
                    <input class="form-check-input search-field" type="checkbox" value="stato" id="fStato">
                    <label for="fStato" class="form-check-label">Stato</label>
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
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                Caricamento statistiche in corso...
                            </td>
                        </tr>
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

        <div id="inactiveInitialState" class="text-center py-4 mt-3">
            <div class="text-muted small mb-2">
                Il riepilogo inattivi non viene caricato automaticamente per alleggerire la pagina.
            </div>
            <div class="fw-semibold">
                Clicca su <span class="text-primary">Aggiorna</span> per eseguire il calcolo.
            </div>
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

<div class="modal fade" id="syncActionsModal" tabindex="-1" aria-labelledby="syncActionsModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="syncActionsModalLabel">Aggiornamento Attività</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
            </div>

            <div class="modal-body">

                <div id="syncActionsLoader" class="text-center py-4">
                    <div class="spinner-border text-primary mb-2" style="width:1.8rem;height:1.8rem;"></div>
                    <div class="text-muted small">Sincronizzazione in corso...</div>
                </div>

                <div id="syncActionsResult" class="d-none">
                    <div id="syncActionsMessage" class="alert mb-3"></div>

                    <div class="row text-center g-2">
                        <div class="col-6">
                            <div class="fw-bold fs-4" id="syncActionsRows">0</div>
                            <div class="small text-muted">Righe attività elaborate</div>
                        </div>
                        <div class="col-6">
                            <div class="fw-bold fs-4" id="syncActionsUsers">0</div>
                            <div class="small text-muted">Utenti aggiornati</div>
                        </div>
                    </div>

                    <div class="text-muted small text-center mt-3">
                        Durata: <span id="syncActionsDuration">0</span> ms
                    </div>
                </div>

            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
            </div>

        </div>
    </div>
</div>

@endsection

@section('scripts')

<script>
function initPanelUsersTable() {
    if ($.fn.DataTable.isDataTable('#panel-users-table')) {
        return;
    }

    $('#panel-users-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route("panelUsers.data") }}',
        pageLength: 25,
        lengthMenu: [25, 50, 100, 200],
        scrollX: false,
        autoWidth: false,
        order: [[0, 'asc']],
        columnDefs: [
            { targets: 0, width: '120px' },
            { targets: 1, width: '240px' },
            { targets: 2, width: '80px' },
            { targets: 3, width: '90px' },
            { targets: 4, width: '110px' },
            { targets: 5, width: '110px' }
        ],
        columns: [
            { data: 'user_id',    name: 'u.user_id', searchable: true,  orderable: true },
            { data: 'email',      name: 'u.email', searchable: true,    orderable: true },
            { data: 'birth_date', name: 'u.birth_date', searchable: false, orderable: false },
            { data: 'invites',    name: 'invites', searchable: false,   orderable: true },
            { data: 'reg_date',   name: 'u.reg_date', searchable: false, orderable: true },
            { data: 'stato',      name: 'u.active', searchable: false, orderable: true }
        ],
        createdRow: function (row, data) {
            var rowClass = {
                0: 'pu-row-inactive',
                8: 'pu-row-banned',
                9: 'pu-row-cancelled'
            }[parseInt(data.active, 10)];

            if (rowClass) {
                $(row).addClass(rowClass);
            }
        },
        language: {
            url: "https://cdn.datatables.net/plug-ins/1.13.4/i18n/it-IT.json",
            search: "Cerca utente:",
            searchPlaceholder: "UID o email...",
            paginate: {
                previous: '‹ Prec.',
                next: 'Succ. ›'
            }
        }
    });
}

$(document).ready(function() {
    setTimeout(function () {
        initPanelUsersTable();
    }, 350);
});
</script>


<script>
function loadActiveSummary() {
    $('#activeSummaryLoader').removeClass('d-none');
    $('#activeSummaryBox').addClass('d-none');

    $.ajax({
        url: '{{ route("panelUsers.activeSummary") }}',
        type: 'GET',
        success: function (response) {
            $('#activeSummaryLoader').addClass('d-none');

            if (!response.success) {
                alert('Errore nel caricamento del riepilogo utenti attivi.');
                return;
            }

            $('#totaleAttivi18MesiValue').text(Number(response.totaleAttivi18Mesi).toLocaleString('it-IT'));
            $('#totalePanelValue').text(Number(response.totalePanel).toLocaleString('it-IT'));
            $('#percentualeAttivi18MesiValue').text(response.percentualeAttivi18Mesi);
            $('#percentualeAttivi18MesiBar').css('width', response.percentualeAttivi18Mesi + '%');

            $('#totaleAttivi18MesiUomoValue').text(Number(response.totaleAttivi18MesiUomo).toLocaleString('it-IT'));
            $('#totalePanelUomoValue').text(Number(response.totalePanelUomo).toLocaleString('it-IT'));
            $('#percentualeAttivi18MesiUomoValue').text(response.percentualeAttivi18MesiUomo);
            $('#percentualeAttivi18MesiUomoBar').css('width', response.percentualeAttivi18MesiUomo + '%');

            $('#totaleAttivi18MesiDonnaValue').text(Number(response.totaleAttivi18MesiDonna).toLocaleString('it-IT'));
            $('#totalePanelDonnaValue').text(Number(response.totalePanelDonna).toLocaleString('it-IT'));
            $('#percentualeAttivi18MesiDonnaValue').text(response.percentualeAttivi18MesiDonna);
            $('#percentualeAttivi18MesiDonnaBar').css('width', response.percentualeAttivi18MesiDonna + '%');

            $('#activeSummaryBox').removeClass('d-none');
        },
        error: function () {
            $('#activeSummaryLoader').addClass('d-none');
            alert('Errore nel caricamento del riepilogo utenti attivi.');
        }
    });
}

$(document).ready(function () {
    setTimeout(function () {
        loadActiveSummary();
    }, 1200);
});
</script>

<script>
function loadPanelStatsByYear(anno) {
    $('#panel-stats-tbody').html(`
        <tr>
            <td colspan="6" class="text-center text-muted py-4">
                Caricamento statistiche in corso...
            </td>
        </tr>
    `);

    $.ajax({
        url: '{{ route("panelUsers.panelStats") }}',
        type: 'GET',
        data: { anno: anno },
        success: function (response) {
            if (!response.success || !response.mesi) {
                $('#panel-stats-tbody').html(`
                    <tr>
                        <td colspan="6" class="text-center text-danger py-4">
                            Errore nel caricamento delle statistiche
                        </td>
                    </tr>
                `);
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
            $('#panel-stats-tbody').html(`
                <tr>
                    <td colspan="6" class="text-center text-danger py-4">
                        Errore nel caricamento delle statistiche
                    </td>
                </tr>
            `);
        }
    });
}

$(document).ready(function () {
    $('#panel-stats-year').on('change', function () {
        loadPanelStatsByYear($(this).val());
    });

    setTimeout(function () {
        loadPanelStatsByYear($('#panel-stats-year').val());
    }, 1800);
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
function loadInactiveSummary() {
    let years = $('#inactiveYears').val();

    $('#inactiveInitialState').addClass('d-none');
    $('#inactiveCountBox').addClass('d-none');
    $('#inactiveCountLoader').removeClass('d-none');

    $.ajax({
        url: '{{ route("panelUsers.inactiveSummary") }}',
        type: 'GET',
        data: { years: years },
        success: function (response) {
            $('#inactiveCountLoader').addClass('d-none');

            if (!response.success) {
                $('#inactiveInitialState').removeClass('d-none');
                alert('Errore nel caricamento utenti inattivi.');
                return;
            }

            $('#inactiveTotalValue').text(Number(response.totalInactive).toLocaleString('it-IT'));
            $('#inactiveCountValue').text(Number(response.inactiveCount).toLocaleString('it-IT'));
            $('#abandonersCountValue').text(Number(response.abandonersCount).toLocaleString('it-IT'));
            $('#inactivePercentValue').text(response.inactivePercent + '%');
            $('#inactiveTotalActives').text(Number(response.totalActives).toLocaleString('it-IT'));
            $('#inactivePercentBar').css('width', response.inactivePercent + '%');

            $('#inactiveCountBox').removeClass('d-none');
        },
        error: function () {
            $('#inactiveCountLoader').addClass('d-none');
            $('#inactiveInitialState').removeClass('d-none');
            alert('Errore nel caricamento utenti inattivi.');
        }
    });
}

$(document).ready(function () {

    $('#btnRefreshInactive').on('click', function () {
        loadInactiveSummary();
    });

    $('#inactiveYears').on('change', function () {
        loadInactiveSummary();
    });

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
                            <td colspan="5" class="text-center text-muted py-4">
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
                        <td colspan="5" class="text-center text-danger py-4">
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

<script>
$(document).ready(function () {
    const btnSyncActions = document.getElementById('btnSyncActions');
    const syncActionsModalElement = document.getElementById('syncActionsModal');
    const syncActionsModal = new bootstrap.Modal(syncActionsModalElement);

    const syncActionsLoader = document.getElementById('syncActionsLoader');
    const syncActionsResult = document.getElementById('syncActionsResult');
    const syncActionsMessage = document.getElementById('syncActionsMessage');
    const syncActionsRows = document.getElementById('syncActionsRows');
    const syncActionsUsers = document.getElementById('syncActionsUsers');
    const syncActionsDuration = document.getElementById('syncActionsDuration');

    btnSyncActions.addEventListener('click', function () {
        btnSyncActions.disabled = true;
        btnSyncActions.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Sincronizzazione...';

        syncActionsLoader.classList.remove('d-none');
        syncActionsResult.classList.add('d-none');
        syncActionsModal.show();

        $.ajax({
            url: '{{ route("panelUsers.syncActions") }}',
            type: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function (response) {
                syncActionsLoader.classList.add('d-none');
                syncActionsResult.classList.remove('d-none');

                syncActionsMessage.className = 'alert mb-3 ' + (response.success ? 'alert-success' : 'alert-danger');
                syncActionsMessage.innerText = response.message || 'Operazione completata.';

                syncActionsRows.innerText = response.rows_read ?? 0;
                syncActionsUsers.innerText = response.users_updated ?? 0;
                syncActionsDuration.innerText = response.duration_ms ?? 0;
            },
            error: function (xhr) {
                syncActionsLoader.classList.add('d-none');
                syncActionsResult.classList.remove('d-none');

                const msg = xhr.responseJSON && xhr.responseJSON.message
                    ? xhr.responseJSON.message
                    : 'Errore durante la sincronizzazione.';

                syncActionsMessage.className = 'alert alert-danger mb-3';
                syncActionsMessage.innerText = msg;
                syncActionsRows.innerText = '-';
                syncActionsUsers.innerText = '-';
                syncActionsDuration.innerText = '-';
            },
            complete: function () {
                btnSyncActions.disabled = false;
                btnSyncActions.innerHTML = '<i class="bi bi-arrow-repeat me-1"></i> Aggiorna Attività';
            }
        });
    });
});
</script>

<script>
$(document).ready(function () {

    [].slice.call(document.querySelectorAll('#tileUnconfirmed, #tileImplausibleAge, #tileSuspiciousEmail, #tileMissingGeo, #tileDataAnomalies'))
        .forEach(function (el) { new bootstrap.Tooltip(el); });

    $('#tileUnconfirmed').on('click', function () {
        $('#modalUnconfirmed').modal('show');
    });

    $('#tileImplausibleAge').on('click', function () {
        $('#modalImplausibleAge').modal('show');
    });

    $('#tileSuspiciousEmail').on('click', function () {
        $('#modalSuspiciousEmail').modal('show');
    });

    $('#tileMissingGeo').on('click', function () {
        $('#modalMissingGeo').modal('show');
    });

    $('#tileDataAnomalies').on('click', function () {
        $('#modalDataAnomalies').modal('show');
    });

    function renderCheckCount(elId, count) {
        const el = document.getElementById(elId);
        if (count > 0) {
            el.textContent = count > 99 ? '99+' : count;
            el.classList.remove('d-none');
        } else {
            el.classList.add('d-none');
        }
    }

    function loadAnomaliesSummary() {
        $.ajax({
            url: '{{ route("panelUsers.anomalies.summary") }}',
            type: 'GET',
            success: function (response) {
                if (!response.success) return;
                renderCheckCount('countUnconfirmed', response.checks.unconfirmed);
                renderCheckCount('countImplausibleAge', response.checks.implausible_age);
                renderCheckCount('countSuspiciousEmail', response.checks.suspicious_email);
                renderCheckCount('countMissingGeo', response.checks.missing_geo);
                renderCheckCount('countDataAnomalies', response.checks.data_anomalies);
            },
            error: function () {
                $('#countUnconfirmed, #countImplausibleAge, #countSuspiciousEmail, #countMissingGeo, #countDataAnomalies').addClass('d-none');
            }
        });
    }

    setTimeout(loadAnomaliesSummary, 400);

    // --- Modale: Email di conferma non ricevuta ---
    function loadUnconfirmedList() {
        $('#unconfirmedTableWrap').addClass('d-none');
        $('#unconfirmedLoader').removeClass('d-none');

        $.ajax({
            url: '{{ route("panelUsers.anomalies.unconfirmed") }}',
            type: 'GET',
            success: function (response) {
                $('#unconfirmedLoader').addClass('d-none');
                $('#unconfirmedTableWrap').removeClass('d-none');

                if (!response.success || !response.rows.length) {
                    $('#unconfirmedTableBody').html('<tr><td colspan="5" class="text-center text-muted py-3">Nessun iscritto da segnalare.</td></tr>');
                    return;
                }

                let html = '';
                response.rows.forEach(function (row) {
                    html += `
                        <tr data-uid="${row.user_id}">
                            <td class="fw-bold">${row.user_id}</td>
                            <td>${row.email ?? ''}</td>
                            <td>${row.reg_date ?? ''}</td>
                            <td>${parseInt(row.confirm, 10) === 1 ? '<span class="text-success">Sì</span>' : '<span class="text-danger">No</span>'}</td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-success btn-enable-user">
                                    <i class="bi bi-check-circle me-1"></i>Abilita
                                </button>
                            </td>
                        </tr>
                    `;
                });

                $('#unconfirmedTableBody').html(html);
            },
            error: function () {
                $('#unconfirmedLoader').addClass('d-none');
                $('#unconfirmedTableWrap').removeClass('d-none');
                $('#unconfirmedTableBody').html('<tr><td colspan="5" class="text-center text-danger py-3">Errore nel caricamento.</td></tr>');
            }
        });
    }

    $('#modalUnconfirmed').on('show.bs.modal', loadUnconfirmedList);

    $('#unconfirmedTableBody').on('click', '.btn-enable-user', function () {
        const btn = $(this);
        const row = btn.closest('tr');
        const uid = row.data('uid');

        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        $.ajax({
            url: '/user/' + uid + '/activate',
            type: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function (response) {
                if (response.success) {
                    row.fadeOut(250, function () { row.remove(); });
                    loadAnomaliesSummary();
                } else {
                    btn.prop('disabled', false).html('<i class="bi bi-check-circle me-1"></i>Abilita');
                    alert(response.message || 'Errore durante l\'abilitazione.');
                }
            },
            error: function () {
                btn.prop('disabled', false).html('<i class="bi bi-check-circle me-1"></i>Abilita');
                alert('Errore durante l\'abilitazione.');
            }
        });
    });

    // --- Modale: Età poco credibile ---
    function loadImplausibleAgeList() {
        $('#implausibleAgeTableWrap').addClass('d-none');
        $('#implausibleAgeLoader').removeClass('d-none');

        $.ajax({
            url: '{{ route("panelUsers.anomalies.implausibleAge") }}',
            type: 'GET',
            success: function (response) {
                $('#implausibleAgeLoader').addClass('d-none');
                $('#implausibleAgeTableWrap').removeClass('d-none');

                if (!response.success || !response.rows.length) {
                    $('#implausibleAgeTableBody').html('<tr><td colspan="5" class="text-center text-muted py-3">Nessun iscritto da segnalare.</td></tr>');
                    return;
                }

                let html = '';
                response.rows.forEach(function (row) {
                    html += `
                        <tr data-uid="${row.user_id}">
                            <td class="fw-bold">${row.user_id}</td>
                            <td>${row.email ?? ''}</td>
                            <td>${row.age ?? 'N.D.'} anni</td>
                            <td><input type="date" class="form-control form-control-sm input-new-birth-date" value="${row.birth_date}"></td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-primary btn-save-birth-date">
                                    <i class="bi bi-save me-1"></i>Salva
                                </button>
                            </td>
                        </tr>
                    `;
                });

                $('#implausibleAgeTableBody').html(html);
            },
            error: function () {
                $('#implausibleAgeLoader').addClass('d-none');
                $('#implausibleAgeTableWrap').removeClass('d-none');
                $('#implausibleAgeTableBody').html('<tr><td colspan="5" class="text-center text-danger py-3">Errore nel caricamento.</td></tr>');
            }
        });
    }

    $('#modalImplausibleAge').on('show.bs.modal', loadImplausibleAgeList);

    $('#implausibleAgeTableBody').on('click', '.btn-save-birth-date', function () {
        const btn = $(this);
        const row = btn.closest('tr');
        const uid = row.data('uid');
        const newBirthDate = row.find('.input-new-birth-date').val();

        if (!newBirthDate) {
            alert('Inserisci una data di nascita valida.');
            return;
        }

        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        $.ajax({
            url: '{{ url("/panelUsers/anomalies/update-birth-date") }}/' + uid,
            type: 'POST',
            data: { _token: '{{ csrf_token() }}', birth_date: newBirthDate },
            success: function (response) {
                if (response.success) {
                    row.fadeOut(250, function () { row.remove(); });
                    loadAnomaliesSummary();
                } else {
                    btn.prop('disabled', false).html('<i class="bi bi-save me-1"></i>Salva');
                    alert(response.message || 'Errore durante il salvataggio.');
                }
            },
            error: function (xhr) {
                btn.prop('disabled', false).html('<i class="bi bi-save me-1"></i>Salva');
                const msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Errore durante il salvataggio.';
                alert(msg);
            }
        });
    });

    // --- Modale: Email con dominio sospetto ---
    function loadSuspiciousEmailList() {
        $('#suspiciousEmailTableWrap, #suspiciousEmailEmpty').addClass('d-none');
        $('#suspiciousEmailLoader').removeClass('d-none');
        $('#suspiciousEmailCountInvalid, #suspiciousEmailCountSuspect').text('0');

        $.ajax({
            url: '{{ route("panelUsers.anomalies.suspiciousEmail") }}',
            type: 'GET',
            success: function (response) {
                $('#suspiciousEmailLoader').addClass('d-none');

                if (!response.success || !response.rows.length) {
                    $('#suspiciousEmailEmpty').removeClass('d-none');
                    return;
                }

                $('#suspiciousEmailTableWrap').removeClass('d-none');

                const countInvalid = response.rows.filter(r => r.severity === 'invalid').length;
                const countSuspect = response.rows.length - countInvalid;
                $('#suspiciousEmailCountInvalid').text(countInvalid);
                $('#suspiciousEmailCountSuspect').text(countSuspect);

                let html = '';
                response.rows.forEach(function (row) {
                    const isInvalid = row.severity === 'invalid';
                    const rowClass = isInvalid ? 'pu-email-row-invalid' : 'pu-email-row-suspect';
                    const badge = isInvalid
                        ? '<span class="pu-sev-badge pu-sev-invalid" data-bs-toggle="tooltip" title="Dominio senza server di posta: email sicuramente non funzionante.">Non valida</span>'
                        : '<span class="pu-sev-badge pu-sev-suspect" data-bs-toggle="tooltip" title="Dominio con DNS valido ma raro nel panel: potrebbe essere un refuso o un provider reale poco comune. Verificare prima di modificare.">Da verificare</span>';
                    const profileUrl = '{{ url("/user") }}/' + row.user_id;
                    const changed = row.suggested_email !== row.email;

                    html += `
                        <tr data-uid="${row.user_id}" class="${rowClass}">
                            <td>${badge}</td>
                            <td><a href="${profileUrl}" target="_blank" class="pu-email-uid-link">${row.user_id}<i class="bi bi-box-arrow-up-right ms-1"></i></a></td>
                            <td><span class="pu-email-current">${row.email ?? ''}</span></td>
                            <td><input type="email" class="form-control form-control-sm input-new-email ${changed ? 'pu-email-suggested-changed' : ''}" value="${row.suggested_email}"></td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-primary btn-save-email">
                                    <i class="bi bi-save me-1"></i>Salva
                                </button>
                            </td>
                        </tr>
                    `;
                });

                $('#suspiciousEmailTableBody').html(html);
                [].slice.call(document.querySelectorAll('#suspiciousEmailTableBody [data-bs-toggle="tooltip"]'))
                    .forEach(function (el) { new bootstrap.Tooltip(el); });
            },
            error: function () {
                $('#suspiciousEmailLoader').addClass('d-none');
                $('#suspiciousEmailTableWrap').removeClass('d-none');
                $('#suspiciousEmailTableBody').html('<tr><td colspan="5" class="text-center text-danger py-3">Errore nel caricamento.</td></tr>');
            }
        });
    }

    $('#modalSuspiciousEmail').on('show.bs.modal', loadSuspiciousEmailList);

    $('#suspiciousEmailTableBody').on('click', '.btn-save-email', function () {
        const btn = $(this);
        const row = btn.closest('tr');
        const uid = row.data('uid');
        const newEmail = row.find('.input-new-email').val();

        if (!newEmail) {
            alert('Inserisci un indirizzo email valido.');
            return;
        }

        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        $.ajax({
            url: '{{ url("/panelUsers/anomalies/update-email") }}/' + uid,
            type: 'POST',
            data: { _token: '{{ csrf_token() }}', email: newEmail },
            success: function (response) {
                if (response.success) {
                    row.fadeOut(250, function () { row.remove(); });
                    loadAnomaliesSummary();
                } else {
                    btn.prop('disabled', false).html('<i class="bi bi-save me-1"></i>Salva');
                    alert(response.message || 'Errore durante il salvataggio.');
                }
            },
            error: function (xhr) {
                btn.prop('disabled', false).html('<i class="bi bi-save me-1"></i>Salva');
                const msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Errore durante il salvataggio.';
                alert(msg);
            }
        });
    });

    // --- Modale: Dati geografici mancanti ---
    function loadMissingGeoSummary(resetResult) {
        $('#missingGeoContent, #missingGeoAllDone, #missingGeoUnresolvableList').addClass('d-none');
        if (resetResult !== false) {
            $('#missingGeoResult').addClass('d-none');
        }
        $('#missingGeoActionWrap').removeClass('d-none');
        $('#missingGeoLoader').removeClass('d-none');

        $.ajax({
            url: '{{ route("panelUsers.anomalies.missingGeo") }}',
            type: 'GET',
            success: function (response) {
                $('#missingGeoLoader').addClass('d-none');
                $('#missingGeoContent').removeClass('d-none');

                if (!response.success || response.total === 0) {
                    $('#missingGeoAllDone').removeClass('d-none');
                    $('#missingGeoActionWrap').addClass('d-none');
                    return;
                }

                $('#missingGeoResolvable').text(response.resolvable);
                $('#missingGeoUnresolvable').text(response.unresolvable);

                $('#btnApplyMissingGeo').prop('disabled', response.resolvable === 0);

                if (response.unresolvable_provinces && response.unresolvable_provinces.length) {
                    let list = 'Province senza riferimento noto: ';
                    list += response.unresolvable_provinces.map(p => `#${p.province_id} (${p.count} utenti)`).join(', ');
                    $('#missingGeoUnresolvableList').text(list).removeClass('d-none');
                }
            },
            error: function () {
                $('#missingGeoLoader').addClass('d-none');
                $('#missingGeoContent').removeClass('d-none');
                $('#missingGeoAllDone, #missingGeoActionWrap').addClass('d-none');
                alert('Errore durante il caricamento.');
            }
        });
    }

    $('#modalMissingGeo').on('show.bs.modal', loadMissingGeoSummary);

    $('#btnApplyMissingGeo').on('click', function () {
        const btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Applicazione in corso...');

        $.ajax({
            url: '{{ route("panelUsers.anomalies.applyMissingGeo") }}',
            type: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function (response) {
                if (response.success) {
                    $('#missingGeoResult')
                        .removeClass('d-none')
                        .html(`Aggiornati: <strong>${response.updated.area}</strong> area, <strong>${response.updated.reg}</strong> regione, <strong>${response.updated.amp}</strong> ampiezza.`);
                    btn.html('<i class="bi bi-magic me-1"></i>Applica a tutti i risolvibili');
                    loadAnomaliesSummary();
                    loadMissingGeoSummary(false);
                } else {
                    btn.prop('disabled', false).html('<i class="bi bi-magic me-1"></i>Applica a tutti i risolvibili');
                    alert(response.message || 'Errore durante l\'applicazione.');
                }
            },
            error: function (xhr) {
                btn.prop('disabled', false).html('<i class="bi bi-magic me-1"></i>Applica a tutti i risolvibili');
                const msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Errore durante l\'applicazione.';
                alert(msg);
            }
        });
    });

    // --- Modale: Anomalie anagrafiche ---
    const profileUrlFor = (uid) => '{{ url("/user") }}/' + uid;

    function emptyAnomRow(colspan, text) {
        return `<tr><td colspan="${colspan}" class="text-center text-muted py-3">${text}</td></tr>`;
    }

    function loadRegDateSection() {
        $.ajax({
            url: '{{ route("panelUsers.anomalies.regDate") }}',
            type: 'GET',
            success: function (response) {
                $('#countSectionRegDate').text(response.count);
                if (!response.rows.length) {
                    $('#regDateTableBody').html(emptyAnomRow(5, 'Nessuna anomalia trovata.'));
                    return;
                }
                let html = '';
                response.rows.forEach(function (row) {
                    html += `
                        <tr data-uid="${row.user_id}">
                            <td><a href="${profileUrlFor(row.user_id)}" target="_blank" class="pu-email-uid-link">${row.user_id}<i class="bi bi-box-arrow-up-right ms-1"></i></a></td>
                            <td>${row.email ?? ''}</td>
                            <td><span class="pu-email-current">${row.reg_date}</span></td>
                            <td><input type="text" class="form-control form-control-sm input-new-reg-date" value="${row.suggested_reg_date ?? ''}"></td>
                            <td class="text-end"><button type="button" class="btn btn-sm btn-primary btn-save-reg-date"><i class="bi bi-save"></i></button></td>
                        </tr>`;
                });
                $('#regDateTableBody').html(html);
            }
        });
    }

    $('#regDateTableBody').on('click', '.btn-save-reg-date', function () {
        const btn = $(this);
        const row = btn.closest('tr');
        const uid = row.data('uid');
        const val = row.find('.input-new-reg-date').val();
        if (!val) { alert('Inserisci una data valida.'); return; }
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
        $.ajax({
            url: '{{ url("/panelUsers/anomalies/update-reg-date") }}/' + uid,
            type: 'POST',
            data: { _token: '{{ csrf_token() }}', reg_date: val },
            success: function (response) {
                if (response.success) { row.fadeOut(250, function () { row.remove(); }); loadAnomaliesSummary(); }
                else { btn.prop('disabled', false).html('<i class="bi bi-save"></i>'); alert(response.message || 'Errore.'); }
            },
            error: function (xhr) {
                btn.prop('disabled', false).html('<i class="bi bi-save"></i>');
                alert((xhr.responseJSON && xhr.responseJSON.message) || 'Errore durante il salvataggio.');
            }
        });
    });

    function loadBirthDateFormatSection() {
        $.ajax({
            url: '{{ route("panelUsers.anomalies.birthDateFormat") }}',
            type: 'GET',
            success: function (response) {
                $('#countSectionBirthDate').text(response.count);
                if (!response.rows.length) {
                    $('#birthDateFormatTableBody').html(emptyAnomRow(5, 'Nessuna anomalia trovata.'));
                    return;
                }
                let html = '';
                response.rows.forEach(function (row) {
                    html += `
                        <tr data-uid="${row.user_id}">
                            <td><a href="${profileUrlFor(row.user_id)}" target="_blank" class="pu-email-uid-link">${row.user_id}<i class="bi bi-box-arrow-up-right ms-1"></i></a></td>
                            <td>${row.email ?? ''}</td>
                            <td><span class="pu-email-current">${row.birth_date}</span></td>
                            <td><input type="date" class="form-control form-control-sm input-new-birth-date-format" value="${row.suggested_birth_date ?? ''}"></td>
                            <td class="text-end"><button type="button" class="btn btn-sm btn-primary btn-save-birth-date-format"><i class="bi bi-save"></i></button></td>
                        </tr>`;
                });
                $('#birthDateFormatTableBody').html(html);
            }
        });
    }

    $('#birthDateFormatTableBody').on('click', '.btn-save-birth-date-format', function () {
        const btn = $(this);
        const row = btn.closest('tr');
        const uid = row.data('uid');
        const val = row.find('.input-new-birth-date-format').val();
        if (!val) { alert('Inserisci una data valida.'); return; }
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
        $.ajax({
            url: '{{ url("/panelUsers/anomalies/update-birth-date") }}/' + uid,
            type: 'POST',
            data: { _token: '{{ csrf_token() }}', birth_date: val },
            success: function (response) {
                if (response.success) { row.fadeOut(250, function () { row.remove(); }); loadAnomaliesSummary(); }
                else { btn.prop('disabled', false).html('<i class="bi bi-save"></i>'); alert(response.message || 'Errore.'); }
            },
            error: function (xhr) {
                btn.prop('disabled', false).html('<i class="bi bi-save"></i>');
                alert((xhr.responseJSON && xhr.responseJSON.message) || 'Errore durante il salvataggio.');
            }
        });
    });

    function genderSelectHtml(selected) {
        const sel1 = selected == 1 ? 'selected' : '';
        const sel2 = selected == 2 ? 'selected' : '';
        return `
            <select class="form-select form-select-sm input-new-gender">
                <option value="">Seleziona</option>
                <option value="1" ${sel1}>Uomo</option>
                <option value="2" ${sel2}>Donna</option>
            </select>`;
    }

    function loadMissingGenderSection() {
        $.ajax({
            url: '{{ route("panelUsers.anomalies.missingGender") }}',
            type: 'GET',
            success: function (response) {
                $('#countSectionMissingGender').text(response.count);
                if (!response.rows.length) {
                    $('#missingGenderTableBody').html(emptyAnomRow(5, 'Nessuna anomalia trovata.'));
                    return;
                }
                let html = '';
                response.rows.forEach(function (row) {
                    html += `
                        <tr data-uid="${row.user_id}">
                            <td><a href="${profileUrlFor(row.user_id)}" target="_blank" class="pu-email-uid-link">${row.user_id}<i class="bi bi-box-arrow-up-right ms-1"></i></a></td>
                            <td>${row.email ?? ''}</td>
                            <td>${row.first_name ?? ''}</td>
                            <td>${genderSelectHtml(row.suggested_gender)}</td>
                            <td class="text-end"><button type="button" class="btn btn-sm btn-primary btn-save-gender"><i class="bi bi-save"></i></button></td>
                        </tr>`;
                });
                $('#missingGenderTableBody').html(html);
            }
        });
    }

    function loadNameGenderSection() {
        $.ajax({
            url: '{{ route("panelUsers.anomalies.nameGenderMismatch") }}',
            type: 'GET',
            success: function (response) {
                $('#countSectionNameGender').text(response.count);
                if (!response.rows.length) {
                    $('#nameGenderTableBody').html(emptyAnomRow(5, 'Nessuna anomalia trovata.'));
                    return;
                }
                let html = '';
                response.rows.forEach(function (row) {
                    html += `
                        <tr data-uid="${row.user_id}">
                            <td><a href="${profileUrlFor(row.user_id)}" target="_blank" class="pu-email-uid-link">${row.user_id}<i class="bi bi-box-arrow-up-right ms-1"></i></a></td>
                            <td>${row.email ?? ''}</td>
                            <td>${row.first_name ?? ''}</td>
                            <td>${genderSelectHtml(row.suggested_gender)}</td>
                            <td class="text-end"><button type="button" class="btn btn-sm btn-primary btn-save-gender"><i class="bi bi-save"></i></button></td>
                        </tr>`;
                });
                $('#nameGenderTableBody').html(html);
            }
        });
    }

    $('#missingGenderTableBody, #nameGenderTableBody').on('click', '.btn-save-gender', function () {
        const btn = $(this);
        const row = btn.closest('tr');
        const uid = row.data('uid');
        const val = row.find('.input-new-gender').val();
        if (!val) { alert('Seleziona un genere.'); return; }
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
        $.ajax({
            url: '{{ url("/panelUsers/anomalies/update-gender") }}/' + uid,
            type: 'POST',
            data: { _token: '{{ csrf_token() }}', gender: val },
            success: function (response) {
                if (response.success) { row.fadeOut(250, function () { row.remove(); }); loadAnomaliesSummary(); }
                else { btn.prop('disabled', false).html('<i class="bi bi-save"></i>'); alert(response.message || 'Errore.'); }
            },
            error: function (xhr) {
                btn.prop('disabled', false).html('<i class="bi bi-save"></i>');
                alert((xhr.responseJSON && xhr.responseJSON.message) || 'Errore durante il salvataggio.');
            }
        });
    });

    function loadDataAnomalies() {
        $('#dataAnomaliesContent').addClass('d-none');
        $('#passwordHashResult').addClass('d-none');
        $('#dataAnomaliesLoader').removeClass('d-none');

        $.ajax({
            url: '{{ route("panelUsers.anomalies.dataSummary") }}',
            type: 'GET',
            success: function (response) {
                $('#dataAnomaliesLoader').addClass('d-none');
                $('#dataAnomaliesContent').removeClass('d-none');
                $('#countPlaintextPassword').text(response.counts.plaintext_password);
                $('#countMissingPassword').text(response.counts.missing_password);
                $('#btnHashPasswords').prop('disabled', response.counts.plaintext_password === 0);

                loadRegDateSection();
                loadBirthDateFormatSection();
                loadMissingGenderSection();
                loadNameGenderSection();
            },
            error: function () {
                $('#dataAnomaliesLoader').addClass('d-none');
                alert('Errore durante il caricamento.');
            }
        });
    }

    $('#modalDataAnomalies').on('show.bs.modal', loadDataAnomalies);

    $('#btnHashPasswords').on('click', function () {
        const btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> In corso...');
        $.ajax({
            url: '{{ route("panelUsers.anomalies.hashPasswords") }}',
            type: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function (response) {
                if (response.success) {
                    $('#passwordHashResult').removeClass('d-none').text('Hash applicato a ' + response.updated + ' password.');
                    $('#countPlaintextPassword').text(0);
                    btn.html('<i class="bi bi-shield-lock me-1"></i>Hash automatico (stessa password, nessuna visualizzata)').prop('disabled', true);
                    loadAnomaliesSummary();
                } else {
                    btn.prop('disabled', false).html('<i class="bi bi-shield-lock me-1"></i>Hash automatico (stessa password, nessuna visualizzata)');
                    alert(response.message || 'Errore.');
                }
            },
            error: function () {
                btn.prop('disabled', false).html('<i class="bi bi-shield-lock me-1"></i>Hash automatico (stessa password, nessuna visualizzata)');
                alert('Errore durante l\'operazione.');
            }
        });
    });

});
</script>

@endsection
