@extends('layouts.main')

@section('head')
<link rel="stylesheet" href="{{ asset('css/panelQuality.css') }}">
<style>
.pq-tab-skeleton{display:flex;flex-direction:column;align-items:center;gap:12px;padding:60px 20px;color:oklch(50% 0.02 250);}
.pq-tab-skeleton-text{font-size:13px;}
</style>
@endsection

@section('content')

<div class="container-fluid pq-container mt-3">

    {{-- ═══ PAGE HEADER ═══════════════════════════════════════════════════ --}}
    <div class="pq-page-header">
        <div class="pq-page-header-inner">
            <div class="pq-page-icon"><i class="bi bi-shield-check"></i></div>
            <div class="pq-page-header-text">
                <div class="pq-page-title-row">
                    <h1 class="pq-page-title">Controllo Qualità Interviste</h1>
                </div>
                <p class="pq-page-sub">Monitoraggio aggregato della qualità delle interviste per panelisti, ricerche e panel esterni</p>
            </div>
            <div class="pq-page-updated">
                <i class="bi bi-clock-history"></i>
                Aggiornato al {{ now()->format('d/m/Y H:i') }}
            </div>
        </div>
    </div>

    {{-- ═══ TABS (in cima) ════════════════════════════════════════════════ --}}
    <div class="pq-tabs-bar">
        <ul class="nav pq-tabs" id="pqTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="tab-panelisti-btn"
                        data-bs-toggle="tab" data-bs-target="#tab-panelisti"
                        type="button" role="tab">
                    <i class="bi bi-people me-1"></i>Panelisti
                    <span class="pq-tab-count" id="countPanelisti">{{ $panelisti->count() }}</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-ricerche-btn"
                        data-bs-toggle="tab" data-bs-target="#tab-ricerche"
                        type="button" role="tab">
                    <i class="bi bi-journal-text me-1"></i>Ricerche
                    <span class="pq-tab-count">{{ $countRicercheConDati + $countRicerceSenzaDati }}</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-panel-esterni-btn"
                        data-bs-toggle="tab" data-bs-target="#tab-panel-esterni"
                        type="button" role="tab">
                    <i class="bi bi-globe me-1"></i>Panel Esterni
                    <span class="pq-tab-count">{{ $countPanelEsterni }}</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-duplicati-btn"
                        data-bs-toggle="tab" data-bs-target="#tab-duplicati"
                        type="button" role="tab">
                    <i class="bi bi-copy me-1"></i>Duplicati
                    <span class="pq-tab-count" style="{{ $nUidDuplicati > 0 ? 'background:oklch(88% 0.10 25);color:oklch(40% 0.16 25);' : '' }}">{{ $nUidDuplicati }}</span>
                </button>
            </li>
        </ul>
    </div>

    {{-- ═══ KPI (swap su cambio tab) ══════════════════════════════════════ --}}
    @php
        $scoreColor = ($globalStats->score_medio ?? 0) >= 70
            ? 'oklch(55% 0.13 150)'
            : (($globalStats->score_medio ?? 0) >= 50 ? 'oklch(58% 0.14 75)' : 'oklch(55% 0.17 25)');
    @endphp
    <div id="kpi-global" class="pq-kpi-grid">
        <div class="pq-kpi-cell">
            <div class="pq-kpi-label">
                <i class="bi bi-people-fill" style="color:oklch(55% 0.10 255);"></i>
                Panelisti valutati
            </div>
            <div class="pq-kpi-value" style="color:oklch(42% 0.13 255);">
                {{ number_format($globalStats->panelisti_totali ?? 0) }}
            </div>
            <div class="pq-kpi-sub">utenti con almeno una valutazione</div>
        </div>
        <div class="pq-kpi-cell">
            <div class="pq-kpi-label">
                <i class="bi bi-stars" style="color:{{ $scoreColor }};"></i>
                Score medio panel
            </div>
            <div class="pq-kpi-value" style="color:{{ $scoreColor }};">
                {{ $globalStats->score_medio ?? '—' }}
            </div>
            <div class="pq-kpi-bar">
                <div class="pq-kpi-bar-fill" style="width:{{ $globalStats->score_medio ?? 0 }}%;background:{{ $scoreColor }};"></div>
            </div>
        </div>
        <div class="pq-kpi-cell">
            <div class="pq-kpi-label">
                <i class="bi bi-exclamation-triangle-fill" style="color:oklch(55% 0.17 25);"></i>
                Interviste anomale
            </div>
            <div class="pq-kpi-value" style="color:oklch(45% 0.16 25);">
                {{ $pctAnomali }}%
            </div>
            <div class="pq-kpi-sub">{{ number_format($globalStats->anomale_totali ?? 0) }} su {{ number_format($globalStats->interviste_totali ?? 0) }} totali</div>
        </div>
        <div class="pq-kpi-cell">
            <div class="pq-kpi-label">
                <i class="bi bi-clipboard-data-fill" style="color:oklch(55% 0.10 190);"></i>
                Interviste valutate
            </div>
            <div class="pq-kpi-value" style="color:oklch(35% 0.02 250);">
                {{ number_format($globalStats->interviste_totali ?? 0) }}
            </div>
            <div class="pq-kpi-sub">
                <span style="color:oklch(40% 0.13 150);">{{ number_format($globalStats->regolari_totali ?? 0) }} reg</span>
                &nbsp;·&nbsp;
                <span style="color:oklch(42% 0.13 75);">{{ number_format($globalStats->incerte_totali ?? 0) }} inc</span>
                &nbsp;·&nbsp;
                <span style="color:oklch(45% 0.16 25);">{{ number_format($globalStats->anomale_totali ?? 0) }} ano</span>
            </div>
        </div>
    </div>

    <div id="kpi-duplicati" class="pq-kpi-grid" style="display:none;">
        <div class="pq-kpi-cell">
            <div class="pq-kpi-label"><i class="bi bi-diagram-3-fill" style="color:oklch(50% 0.14 55);"></i> Gruppi sospetti</div>
            <div class="pq-kpi-value" style="color:oklch(38% 0.12 55);">{{ $nGruppi }}</div>
            <div class="pq-kpi-sub">identità probabilmente doppie</div>
        </div>
        <div class="pq-kpi-cell">
            <div class="pq-kpi-label"><i class="bi bi-people-fill" style="color:oklch(42% 0.13 255);"></i> UID coinvolti</div>
            <div class="pq-kpi-value" style="color:oklch(38% 0.10 255);">{{ $nUidDuplicati }}</div>
            <div class="pq-kpi-sub">panelisti segnalati almeno una volta</div>
        </div>
        <div class="pq-kpi-cell">
            <div class="pq-kpi-label"><i class="bi bi-journal-text" style="color:oklch(45% 0.10 190);"></i> Ricerche</div>
            <div class="pq-kpi-value" style="color:oklch(38% 0.08 190);">{{ $nRicercheDuplicati }}</div>
            <div class="pq-kpi-sub">ricerche con almeno una segnalazione</div>
        </div>
        <div class="pq-kpi-cell">
            <div class="pq-kpi-label"><i class="bi bi-exclamation-triangle-fill" style="color:oklch(50% 0.17 25);"></i> Alto rischio</div>
            <div class="pq-kpi-value" style="color:{{ $nAltoRischio > 0 ? 'oklch(45% 0.18 25)' : 'oklch(50% 0.02 250)' }};">{{ $nAltoRischio }}</div>
            <div class="pq-kpi-sub">gruppi segnalati in 2+ ricerche</div>
        </div>
    </div>

    {{-- ═══ ANALISI GRUPPI (visibile solo su tab Duplicati) ══════════════ --}}
    @if($nGruppi > 0)
    <div id="analisi-gruppi-section" style="display:none;margin-bottom:20px;">
        <div class="pq-card" style="margin-bottom:0;">
            <div class="pq-card-header pq-border-amber" style="cursor:pointer;" onclick="bootstrap.Collapse.getOrCreateInstance(document.getElementById('collapseGruppi')).toggle()">
                <div class="pq-card-header-left">
                    <i class="bi bi-diagram-3-fill" style="font-size:16px;color:oklch(50% 0.14 55);"></i>
                    <div>
                        <div class="pq-card-title" style="color:oklch(38% 0.12 55);">Analisi gruppi probabilmente identici</div>
                        <div class="pq-card-sub">Connessioni transitive tra UID segnalati — ogni gruppo è una potenziale identità duplicata</div>
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:10px;">
                    @if($nAltoRischio > 0)
                    <span style="font-size:11px;padding:3px 10px;background:oklch(93% 0.08 25);color:oklch(40% 0.16 25);border-radius:999px;font-weight:700;">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>{{ $nAltoRischio }} alto rischio
                    </span>
                    @endif
                    <span style="font-size:11px;padding:3px 10px;background:oklch(94% 0.06 55);color:oklch(38% 0.12 55);border-radius:999px;font-weight:700;">{{ $nGruppi }} gruppi</span>
                    <a href="{{ route('panelQuality.exportGruppi') }}" target="_blank" onclick="event.stopPropagation();"
                       style="display:inline-flex;align-items:center;gap:5px;font-size:11px;padding:3px 10px;background:oklch(94% 0.02 250);color:oklch(40% 0.05 250);border:1px solid oklch(85% 0.03 250);border-radius:999px;font-weight:700;text-decoration:none;">
                        <i class="bi bi-download"></i>Esporta tutti
                    </a>
                    <i class="bi bi-chevron-down" id="icnGruppi" style="font-size:12px;color:oklch(55% 0.04 250);transition:transform .2s;transform:rotate(-90deg);"></i>
                </div>
            </div>
            <div class="collapse" id="collapseGruppi">
                <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;font-size:12px;">
                    <thead>
                        <tr style="border-bottom:2px solid oklch(92% 0.006 250);background:oklch(98% 0.004 250);">
                            <th style="padding:10px 16px;text-align:left;font-weight:600;color:oklch(45% 0.05 250);font-size:11px;text-transform:uppercase;letter-spacing:.04em;white-space:nowrap;">Rischio</th>
                            <th style="padding:10px 16px;text-align:left;font-weight:600;color:oklch(45% 0.05 250);font-size:11px;text-transform:uppercase;letter-spacing:.04em;">Utenti del gruppo</th>
                            <th style="padding:10px 16px;text-align:center;font-weight:600;color:oklch(45% 0.05 250);font-size:11px;text-transform:uppercase;letter-spacing:.04em;white-space:nowrap;">Segn.</th>
                            <th style="padding:10px 16px;text-align:center;font-weight:600;color:oklch(45% 0.05 250);font-size:11px;text-transform:uppercase;letter-spacing:.04em;white-space:nowrap;"></th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($gruppiSospetti as $gi => $g)
                    @php
                        $isAlto  = $g['risk'] === 'alto';
                        $rowBg   = $gi % 2 === 0 ? '#fff' : 'oklch(98.5% 0.003 250)';
                        $riskClr = $isAlto ? 'oklch(42% 0.18 25)' : 'oklch(44% 0.13 75)';
                        $riskBg  = $isAlto ? 'oklch(93% 0.08 25)' : 'oklch(95% 0.07 80)';
                        $riskLbl = $isAlto ? 'Alto' : 'Medio';
                        $riskIco = $isAlto ? 'bi-exclamation-triangle-fill' : 'bi-dash-circle-fill';
                    @endphp
                    <tr style="background:{{ $rowBg }};border-bottom:1px solid oklch(93% 0.004 250);">
                        <td style="padding:10px 16px;white-space:nowrap;">
                            <span style="display:inline-flex;align-items:center;gap:4px;padding:3px 9px;background:{{ $riskBg }};color:{{ $riskClr }};border-radius:999px;font-weight:700;font-size:11px;">
                                <i class="bi {{ $riskIco }}" style="font-size:10px;"></i>{{ $riskLbl }}
                            </span>
                        </td>
                        <td style="padding:10px 16px;">
                            @php $gHasBreakdown = $g['bannati'] > 0 || $g['ammoniti'] > 0; @endphp
                            <span class="dup-group-trigger" tabindex="0"
                                  data-group-idx="{{ $gi + 1 }}"
                                  data-group-risk="{{ $riskLbl }}"
                                  title="{{ $gHasBreakdown ? ($g['bannati'] . ' bannati · ' . $g['ammoniti'] . ' ammoniti · ' . ($g['attivi'] - $g['ammoniti']) . ' attivi liberi') : '' }}"
                                  style="display:inline-flex;align-items:center;gap:6px;cursor:pointer;padding:3px 9px;background:oklch(95% 0.03 250);border:1px solid oklch(85% 0.05 250);border-radius:5px;font-size:12px;color:oklch(35% 0.10 255);white-space:nowrap;">
                                <i class="bi bi-people-fill" style="font-size:11px;opacity:.7;"></i>
                                {{ $g['size'] }}&nbsp;{{ $g['size'] === 1 ? 'utente' : 'utenti' }}
                                @if($gHasBreakdown)
                                    <span style="width:1px;height:12px;background:oklch(85% 0.02 250);"></span>
                                    @if($g['bannati'] > 0)<span style="color:#dc2626;font-weight:700;">{{ $g['bannati'] }} ban.</span>@endif
                                    @if($g['ammoniti'] > 0)<span style="color:#c2410c;font-weight:700;">{{ $g['ammoniti'] }} amm.</span>@endif
                                @endif
                            </span>
                        </td>
                        <td style="padding:10px 16px;text-align:center;font-weight:800;font-size:15px;color:{{ $riskClr }};">
                            {{ $g['segnalazioni'] }}
                        </td>
                        <td style="padding:10px 16px;text-align:center;">
                            <a href="{{ route('panelQuality.exportGruppi', ['gruppo' => $gi + 1]) }}" target="_blank"
                               title="Esporta questo gruppo"
                               style="display:inline-flex;align-items:center;justify-content:center;width:26px;height:26px;background:oklch(95% 0.03 250);border:1px solid oklch(85% 0.05 250);border-radius:6px;color:oklch(40% 0.08 250);">
                                <i class="bi bi-download" style="font-size:12px;"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ═══ MODALE "Utenti del gruppo" — analisi segnali condivisi ═══════════ --}}
    <div class="modal fade" id="pqGroupModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-people-fill me-2"></i>Analisi gruppo <span id="pqGroupModalRisk"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
                </div>
                <div class="modal-body" id="pqGroupModalBody">
                    <div class="pq-tab-skeleton">
                        <div class="spinner-border text-secondary" role="status"></div>
                        <div class="pq-tab-skeleton-text">Caricamento analisi…</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <span id="pqGroupModalWarnMsg" class="me-auto small text-muted"></span>
                    <button type="button" id="pqGroupModalWarnBtn" class="btn btn-sm btn-outline-secondary" style="color:#c2410c;border-color:#fdba8c;">
                        <i class="bi bi-megaphone me-1"></i>Ammonisci utenti attivi del gruppo
                    </button>
                    <a href="#" target="_blank" id="pqGroupModalExport" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-download me-1"></i>Esporta questo gruppo
                    </a>
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Chiudi</button>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-content">

        {{-- ───────────────────────────────────────────────────────────────── --}}
        {{-- TAB 1 — PANELISTI                                                 --}}
        {{-- ───────────────────────────────────────────────────────────────── --}}
        <div class="tab-pane fade show active" id="tab-panelisti" role="tabpanel">
            <div class="pq-card">

                {{-- Header --}}
                <div class="pq-card-header pq-border-green">
                    <div class="pq-card-header-left">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="oklch(45% 0.12 255)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        <div>
                            <div class="pq-card-title">Qualità per panelista</div>
                            <div class="pq-card-sub">
                                Solo panel Interactive — ordinati per score medio crescente, i peggiori in cima
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Filtri --}}
                <div class="pq-filters">
                    <div class="pq-filter-group">
                        <label class="pq-filter-label" for="fltPanelistiSearch">Cerca</label>
                        <input type="text" class="pq-filter-input" id="fltPanelistiSearch"
                               placeholder="Nome o UID…">
                    </div>

                    <div class="pq-filter-group">
                        <label class="pq-filter-label" for="fltPanelistiTier">Tier</label>
                        <select class="pq-filter-select" id="fltPanelistiTier">
                            <option value="">Tutti</option>
                            <option value="anomala">Solo anomali</option>
                            <option value="incerta">Solo incerti</option>
                            <option value="regolare">Solo regolari</option>
                        </select>
                    </div>

                    <div class="pq-filter-group">
                        <label class="pq-filter-label" for="fltPanelistiScoreMax">Score max</label>
                        <input type="number" class="pq-filter-input pq-filter-input-num" id="fltPanelistiScoreMax"
                               min="0" max="100" step="1" placeholder="100">
                    </div>

                    <div class="pq-filter-group">
                        <label class="pq-filter-label" for="fltPanelistiIntervisteMin">Interviste min</label>
                        <input type="number" class="pq-filter-input pq-filter-input-num" id="fltPanelistiIntervisteMin"
                               min="0" step="1" placeholder="0">
                    </div>

                    <span class="pq-filter-count" id="panelistiVisibili">
                        {{ $panelisti->count() }} panelisti
                    </span>

                    <form method="GET" action="{{ route('panelQuality.exportPanelisti') }}" target="_blank" class="pq-export-form">
                        <span class="pq-export-label">Score da</span>
                        <input type="number" name="score_min" class="pq-filter-input pq-export-input" min="0" max="100" step="1" value="0" required>
                        <span class="pq-export-label">a</span>
                        <input type="number" name="score_max" class="pq-filter-input pq-export-input" min="0" max="100" step="1" value="100" required>
                        <span class="pq-export-label">Almeno</span>
                        <input type="number" name="min_interviste" class="pq-filter-input pq-export-input" min="0" step="1" value="0">
                        <span class="pq-export-label">interviste</span>
                        <button type="submit" class="btn btn-sm btn-outline-success">
                            <i class="bi bi-download me-1"></i>Esporta CSV
                        </button>
                    </form>
                </div>

                {{-- Tabella --}}
                <div class="pq-table-wrap">
                    <table class="pq-table" id="tblPanelisti" data-pq-lazy-full-url="{{ route('panelQuality.tabPanelistiFull') }}">
                        <thead class="pq-thead">
                            <tr>
                                <th class="pq-th">Panelista</th>
                                <th class="pq-th pq-th-sort" data-col="score">Score medio ↕</th>
                                <th class="pq-th">Tier prevalente</th>
                                <th class="pq-th">Distribuzione</th>
                                <th class="pq-th pq-th-sort" data-col="interviste">Interviste ↕</th>
                                <th class="pq-th">Bytes</th>
                                <th class="pq-th">Malus</th>
                                <th class="pq-th">Ultima val.</th>
                            </tr>
                        </thead>
                        <tbody id="bodyPanelisti">
@include('panelQuality.tabs.panelisti-rows')
                        </tbody>
                    </table>
                </div>

                {{-- Paginator --}}
                <div id="panelistiPaginator" class="pq-paginator-wrap"></div>

            </div>
        </div>{{-- /tab-panelisti --}}

        {{-- ───────────────────────────────────────────────────────────────── --}}
        {{-- TAB 2 — RICERCHE                                                   --}}
        {{-- ───────────────────────────────────────────────────────────────── --}}
        <div class="tab-pane fade" id="tab-ricerche" role="tabpanel" data-pq-lazy-url="{{ route('panelQuality.tabRicerche') }}">
        <div class="pq-tab-skeleton">
            <div class="spinner-border text-secondary" role="status"></div>
            <div class="pq-tab-skeleton-text">Caricamento ricerche…</div>
        </div>
        </div>{{-- /tab-ricerche --}}

        {{-- ───────────────────────────────────────────────────────────────── --}}
        {{-- TAB 3 — PANEL ESTERNI                                              --}}
        {{-- ───────────────────────────────────────────────────────────────── --}}
        <div class="tab-pane fade" id="tab-panel-esterni" role="tabpanel" data-pq-lazy-url="{{ route('panelQuality.tabPanelEsterni') }}">
            <div class="pq-tab-skeleton">
                <div class="spinner-border text-secondary" role="status"></div>
                <div class="pq-tab-skeleton-text">Caricamento panel esterni…</div>
            </div>
        </div>{{-- /tab-panel-esterni --}}

        {{-- ───────────────────────────────────────────────────────────────── --}}
        {{-- TAB 4 — DUPLICATI                                                  --}}
        {{-- ───────────────────────────────────────────────────────────────── --}}
        <div class="tab-pane fade" id="tab-duplicati" role="tabpanel" data-pq-lazy-url="{{ route('panelQuality.tabDuplicati') }}">
            <div class="pq-tab-skeleton">
                <div class="spinner-border text-secondary" role="status"></div>
                <div class="pq-tab-skeleton-text">Caricamento duplicati…</div>
            </div>
        </div>{{-- /tab-duplicati --}}

    </div>{{-- /tab-content --}}

</div>{{-- /pq-container --}}

@endsection

{{-- Script in @section('scripts'), NON inline in 'content': il layout carica Bootstrap/jQuery
     dopo @yield('content') ma prima di @yield('scripts') — uno script qui dentro 'content'
     gira prima che `bootstrap` esista, causando ReferenceError e bloccando tutto il resto
     (tooltip, filtri, paginazione falliscono silenziosamente). --}}
@section('scripts')
{{-- ═══ JS: filtri + paginazione (panelisti + ricerche) ══════════════════ --}}
<script>
/* Funzione riutilizzabile per filtro+paginazione su qualsiasi tabella */
function pqTable(cfg) {
    var PAGE  = cfg.pageSize || 50;
    var page  = 1;
    var rows  = Array.from(document.querySelectorAll(cfg.rowsSelector));
    var pagEl = document.getElementById(cfg.paginatorId);
    var cntEl = document.getElementById(cfg.countId);
    var label = cfg.label || 'righe';
    var tblId = cfg.tableId;

    function filtered() {
        return rows.filter(function (r) { return cfg.match(r); });
    }

    function render() {
        var vis   = filtered();
        var total = Math.max(1, Math.ceil(vis.length / PAGE));
        page      = Math.min(page, total);
        var start = (page - 1) * PAGE;

        rows.forEach(function (r) { r.style.display = 'none'; });
        vis.slice(start, start + PAGE).forEach(function (r) { r.style.display = ''; });

        if (cntEl) cntEl.textContent = vis.length + ' ' + label;
        drawPaginator(vis.length, total);
    }

    function drawPaginator(total, totalPages) {
        if (!pagEl) return;
        if (totalPages <= 1) { pagEl.innerHTML = ''; return; }

        var gofn = cfg.goFn;
        var h = '<div class="pq-paginator">';
        h += mkBtn('‹', page - 1, page === 1, gofn);
        for (var i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= page - 2 && i <= page + 2)) {
                h += '<button class="pq-page-btn' + (i === page ? ' active' : '') +
                     '" onclick="' + gofn + '(' + i + ')">' + i + '</button>';
            } else if (i === page - 3 || i === page + 3) {
                h += '<span class="pq-page-ellipsis">…</span>';
            }
        }
        h += mkBtn('›', page + 1, page === totalPages, gofn);
        h += '<span class="pq-page-info">Pag. ' + page + ' / ' + totalPages +
             ' &nbsp;·&nbsp; ' + total + ' totali</span></div>';
        pagEl.innerHTML = h;
    }

    function mkBtn(lbl, p, dis, fn) {
        return '<button class="pq-page-btn"' + (dis ? ' disabled' : '') +
               ' onclick="' + fn + '(' + p + ')">' + lbl + '</button>';
    }

    return {
        go: function (p) {
            page = p;
            render();
            if (tblId) document.getElementById(tblId).scrollIntoView({ behavior: 'smooth', block: 'start' });
        },
        reset: function () { page = 1; render(); },
        render: render
    };
}

/* ── Tooltip Bootstrap — scoped a 'root' per poter re-inizializzare solo il
       contenuto appena iniettato via AJAX (lazy load tab 2-4), senza toccare
       di nuovo gli elementi già attivi altrove nella pagina ──────────────── */
function pqInitTooltips(root) {
    root.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
        new bootstrap.Tooltip(el, { trigger: 'hover' });
    });
}
pqInitTooltips(document);

/* ── Swap KPI globali ↔ KPI duplicati al cambio tab (indipendente dal lazy
       load: i contatori/KPI/analisi-gruppi sono già calcolati lato server) ── */
(function () {
    var kpiGlobal = document.getElementById('kpi-global');
    var kpiDup    = document.getElementById('kpi-duplicati');
    if (!kpiGlobal || !kpiDup) return;

    function syncKpi(targetId) {
        var isDup    = targetId === 'tab-duplicati';
        var gruppiEl = document.getElementById('analisi-gruppi-section');
        kpiGlobal.style.display = isDup ? 'none' : '';
        kpiDup.style.display    = isDup ? ''     : 'none';
        if (gruppiEl) gruppiEl.style.display = isDup ? '' : 'none';
    }

    document.querySelectorAll('[data-bs-toggle="tab"]').forEach(function (btn) {
        btn.addEventListener('shown.bs.tab', function (e) {
            syncKpi(e.target.getAttribute('data-bs-target').replace('#', ''));
        });
    });
})();

/* ── Popover duplicati (click, con link cliccabili) — scoped a 'root' ───── */
function pqInitPopovers(root) {
    root.querySelectorAll('.dup-sim-trigger').forEach(function (el) {
        var pop = new bootstrap.Popover(el, { trigger: 'manual', html: true });
        el.addEventListener('click', function (e) {
            e.stopPropagation();
            document.querySelectorAll('.dup-sim-trigger').forEach(function (other) {
                if (other !== el) bootstrap.Popover.getInstance(other)?.hide();
            });
            pop.toggle();
        });
    });
}
pqInitPopovers(document);
document.addEventListener('click', function () {
    document.querySelectorAll('.dup-sim-trigger').forEach(function (el) {
        bootstrap.Popover.getInstance(el)?.hide();
    });
});

/* ── Analisi gruppi: freccia collapse — sezione calcolata eager, quindi
       presente dal primo caricamento indipendentemente dal lazy load ────── */
(function () {
    var elCG = document.getElementById('collapseGruppi');
    if (!elCG) return;
    elCG.addEventListener('hide.bs.collapse', function () {
        document.getElementById('icnGruppi').style.transform = 'rotate(-90deg)';
    });
    elCG.addEventListener('show.bs.collapse', function () {
        document.getElementById('icnGruppi').style.transform = 'rotate(0deg)';
    });
})();

/* ── Modale "Utenti del gruppo" — analisi segnali condivisi (IP, nascita,
       città, giorno registrazione). Fetch on-demand ad ogni click, nessuna
       cache: i dati sono derivati da query leggere ma è bene restino aggiornati
       se nel frattempo si segnalano/bannano utenti. ───────────────────────── */
(function () {
    var modalEl = document.getElementById('pqGroupModal');
    if (!modalEl) return;
    var modal     = new bootstrap.Modal(modalEl);
    var bodyEl    = document.getElementById('pqGroupModalBody');
    var riskEl    = document.getElementById('pqGroupModalRisk');
    var exportEl  = document.getElementById('pqGroupModalExport');
    var warnBtn   = document.getElementById('pqGroupModalWarnBtn');
    var warnMsgEl = document.getElementById('pqGroupModalWarnMsg');
    var baseUrl   = "{{ route('panelQuality.groupDetail') }}";
    var exportBase = "{{ route('panelQuality.exportGruppi') }}";
    var warnUrl   = "{{ route('panelQuality.groupWarn') }}";
    var csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    var currentIdx = null;

    function loadGroup(idx, risk, preserveMsg) {
        currentIdx = idx;
        if (!preserveMsg) warnMsgEl.textContent = '';
        riskEl.textContent = '#' + idx + ' — rischio ' + risk;
        exportEl.setAttribute('href', exportBase + '?gruppo=' + idx);
        bodyEl.innerHTML = '<div class="pq-tab-skeleton">'
            + '<div class="spinner-border text-secondary" role="status"></div>'
            + '<div class="pq-tab-skeleton-text">Caricamento analisi…</div></div>';

        return fetch(baseUrl + '?gruppo=' + idx, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (resp) {
                if (!resp.ok) throw new Error('HTTP ' + resp.status);
                return resp.text();
            })
            .then(function (html) {
                bodyEl.innerHTML = html;
            })
            .catch(function () {
                bodyEl.innerHTML = '<div class="pq-empty">Errore nel caricamento dell\'analisi. Riprova.</div>';
            });
    }

    document.querySelectorAll('.dup-group-trigger').forEach(function (el) {
        el.addEventListener('click', function () {
            var idx  = el.getAttribute('data-group-idx');
            var risk = el.getAttribute('data-group-risk');
            modal.show();
            loadGroup(idx, risk);
        });
    });

    warnBtn.addEventListener('click', function () {
        if (!currentIdx) return;
        warnBtn.disabled = true;
        warnMsgEl.textContent = 'Invio in corso…';

        fetch(warnUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ gruppo: currentIdx }),
        })
            .then(function (resp) {
                if (!resp.ok) throw new Error('HTTP ' + resp.status);
                return resp.json();
            })
            .then(function (data) {
                var parts = [];
                if (data.warned && data.warned.length) parts.push(data.warned.length + ' nuovi ammoniti');
                if (data.already && data.already.length) parts.push(data.already.length + ' già ammoniti in precedenza');
                if (data.excluded) parts.push(data.excluded + ' esclusi (bannati/non attivi)');
                warnMsgEl.textContent = parts.length ? parts.join(' · ') : 'Nessun utente attivo da ammonire in questo gruppo.';
                return loadGroup(currentIdx, riskEl.textContent.replace(/^#\d+\s*—\s*rischio\s*/, ''), true);
            })
            .catch(function () {
                warnMsgEl.textContent = 'Errore durante l\'invio. Riprova.';
            })
            .finally(function () {
                warnBtn.disabled = false;
            });
    });
})();

/* ── Panelisti — wrappata: la tabella mostra subito le prime 30 righe (già nel
       markup iniziale), poi il resto arriva in background (vedi lazy load più
       sotto) e questa funzione viene chiamata una sola volta, quando il set di
       righe finale (30 o tutte 958) è già nel DOM. ─────────────────────────── */
function pqInitPanelistiTab() {
    var _pan = pqTable({
        rowsSelector: '#bodyPanelisti .pq-row',
        paginatorId:  'panelistiPaginator',
        countId:      'panelistiVisibili',
        tableId:      'tblPanelisti',
        goFn:         'pqGoPan',
        label:        'panelisti',
        pageSize:     30,
        match: function (r) {
            var term       = document.getElementById('fltPanelistiSearch').value.toLowerCase().trim();
            var tier       = document.getElementById('fltPanelistiTier').value;
            var scoreMax   = document.getElementById('fltPanelistiScoreMax').value;
            var intMin     = document.getElementById('fltPanelistiIntervisteMin').value;
            return (!term || r.dataset.uid.includes(term) || r.dataset.name.includes(term))
                && (!tier || r.dataset.tier === tier)
                && (scoreMax === '' || parseFloat(r.dataset.score) <= parseFloat(scoreMax))
                && (intMin === '' || parseInt(r.dataset.interviste, 10) >= parseInt(intMin, 10));
        }
    });
    window.pqGoPan = function (p) { _pan.go(p); };
    document.getElementById('fltPanelistiSearch').addEventListener('input',  function () { _pan.reset(); });
    document.getElementById('fltPanelistiTier').addEventListener('change', function () { _pan.reset(); });
    document.getElementById('fltPanelistiScoreMax').addEventListener('input', function () { _pan.reset(); });
    document.getElementById('fltPanelistiIntervisteMin').addEventListener('input', function () { _pan.reset(); });
    _pan.render();
}

/* Carica subito (non al click, la tab è già attiva) il resto dei panelisti in
   background; nel frattempo le prime 30 righe già renderizzate sono visibili.
   pqInitPanelistiTab() parte una sola volta, a prescindere dall'esito, sul set
   di righe disponibile in quel momento (completo se il fetch riesce, altrimenti
   solo le 30 iniziali). */
(function () {
    var table = document.getElementById('tblPanelisti');
    var url   = table && table.getAttribute('data-pq-lazy-full-url');
    if (!url) { pqInitPanelistiTab(); return; }

    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (resp) {
            if (!resp.ok) throw new Error('HTTP ' + resp.status);
            return resp.text();
        })
        .then(function (html) {
            document.getElementById('bodyPanelisti').innerHTML = html;
        })
        .catch(function () {
            // Fallback: restano visibili e filtrabili solo le prime 30 righe già renderizzate.
        })
        .finally(function () {
            pqInitPanelistiTab();
        });
})();

/* ── Ricerche (con dati + senza dati) — contenuto caricato via lazy load,
       quindi wrappato in una funzione richiamabile dopo l'injection ──────── */
function pqInitRicercheTab() {
    if (document.getElementById('bodyConDati')) {
        var _con = pqTable({
            rowsSelector: '#bodyConDati .pq-row',
            paginatorId:  'conDatiPaginator',
            countId:      'conDatiVisibili',
            tableId:      'tblConDati',
            goFn:         'pqGoCon',
            label:        'ricerche',
            pageSize:     10,
            match: function (r) {
                var term = document.getElementById('fltConDatiSearch').value.toLowerCase().trim();
                return !term || r.dataset.prj.includes(term) || r.dataset.sid.includes(term);
            }
        });
        window.pqGoCon = function (p) { _con.go(p); };
        document.getElementById('fltConDatiSearch').addEventListener('input', function () { _con.reset(); });
        _con.render();
    }

    if (document.getElementById('bodySenzaDati')) {
        var tipoSenza = 'tutti';
        var _senza = pqTable({
            rowsSelector: '#bodySenzaDati .pq-row',
            paginatorId:  'senzaDatiPaginator',
            countId:      'senzaDatiVisibili',
            tableId:      'tblSenzaDati',
            goFn:         'pqGoSenza',
            label:        'ricerche',
            match: function (r) {
                var term = document.getElementById('fltSenzaDatiSearch').value.toLowerCase().trim();
                var searchOk = !term || r.dataset.prj.includes(term) || r.dataset.sid.includes(term);
                var tipoOk = tipoSenza === 'tutti'
                    || (tipoSenza === 'interactive' && r.dataset.int === '1')
                    || (tipoSenza === 'esterno'     && r.dataset.ext === '1');
                return searchOk && tipoOk;
            }
        });
        window.pqGoSenza = function (p) { _senza.go(p); };
        document.getElementById('fltSenzaDatiSearch').addEventListener('input', function () { _senza.reset(); });
        document.querySelectorAll('.pq-tipo-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.querySelectorAll('.pq-tipo-btn').forEach(function (b) { b.classList.remove('active'); });
                this.classList.add('active');
                tipoSenza = this.dataset.tipo;
                _senza.reset();
            });
        });
        _senza.render();
    }
}

/* ── Duplicati — idem, wrappato per il lazy load ─────────────────────────── */
function pqInitDuplicatiTab() {
    if (document.getElementById('bodyDuplicati')) {
        var _dup = pqTable({
            rowsSelector: '#bodyDuplicati .pq-row',
            paginatorId:  'duplicatiPaginator',
            countId:      'duplicatiVisibili',
            tableId:      'tblDuplicati',
            goFn:         'pqGoDup',
            label:        'righe',
            pageSize:     30,
            match: function (r) {
                var term = document.getElementById('fltDuplicatiSearch').value.toLowerCase().trim();
                return !term || (r.dataset.uid || '').includes(term) || (r.dataset.name || '').includes(term);
            }
        });
        window.pqGoDup = function (p) { _dup.go(p); };
        document.getElementById('fltDuplicatiSearch').addEventListener('input', function () { _dup.reset(); });
        _dup.render();
    }
}

/* ── Panel esterni — dettaglio per ricerca — idem ────────────────────────── */
function pqInitPanelEsterniTab() {
    if (document.getElementById('bodyPanelEst')) {
        var _panelEst = pqTable({
            rowsSelector: '#bodyPanelEst .pq-row',
            paginatorId:  'panelEstPaginator',
            countId:      'panelEstVisibili',
            tableId:      'tblPanelEst',
            goFn:         'pqGoPanelEst',
            label:        'righe',
            match: function (r) {
                var term  = document.getElementById('fltPanelEstSearch').value.toLowerCase().trim();
                var panel = document.getElementById('fltPanelEstPanel').value;
                return (!term || r.dataset.prj.includes(term) || r.dataset.sid.includes(term))
                    && (!panel || r.dataset.panel === panel);
            }
        });
        window.pqGoPanelEst = function (p) { _panelEst.go(p); };
        document.getElementById('fltPanelEstSearch').addEventListener('input',  function () { _panelEst.reset(); });
        document.getElementById('fltPanelEstPanel').addEventListener('change', function () { _panelEst.reset(); });
        _panelEst.render();
    }
}

/* ── Lazy load: le tab 2-4 caricano il loro contenuto via AJAX al primo
       click (spinner nel frattempo), invece di arrivare tutte insieme nella
       risposta iniziale — pagina visibile/interattiva molto più in fretta
       e payload HTML iniziale molto più piccolo. ────────────────────────── */
document.querySelectorAll('.tab-pane[data-pq-lazy-url]').forEach(function (pane) {
    var btn = document.querySelector('[data-bs-target="#' + pane.id + '"]');
    if (!btn) return;

    var loaded = false;
    btn.addEventListener('shown.bs.tab', function () {
        if (loaded) return;
        loaded = true;

        var url = pane.getAttribute('data-pq-lazy-url');
        if (pane.id === 'tab-ricerche') {
            var anno = new URLSearchParams(location.search).get('anno_senza_dati');
            if (anno) url += (url.indexOf('?') > -1 ? '&' : '?') + 'anno_senza_dati=' + encodeURIComponent(anno);
        }

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (resp) {
                if (!resp.ok) throw new Error('HTTP ' + resp.status);
                return resp.text();
            })
            .then(function (html) {
                pane.innerHTML = html;
                pqInitTooltips(pane);
                pqInitPopovers(pane);
                if (pane.id === 'tab-ricerche')     pqInitRicercheTab();
                if (pane.id === 'tab-panel-esterni') pqInitPanelEsterniTab();
                if (pane.id === 'tab-duplicati')    pqInitDuplicatiTab();
            })
            .catch(function () {
                loaded = false;
                pane.innerHTML = '<div class="pq-empty">Errore nel caricamento dei dati. '
                    + '<a href="javascript:location.reload()">Ricarica la pagina</a>.</div>';
            });
    });
});
</script>

@endsection
