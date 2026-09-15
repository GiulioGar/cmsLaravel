@extends('layouts.main')

@section('head')
<link rel="stylesheet" href="{{ asset('css/panelQuality.css') }}">
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
                    <span class="pq-tab-count">{{ $ricercheConDati->count() + $ricerceSenzaDati->count() }}</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-panel-esterni-btn"
                        data-bs-toggle="tab" data-bs-target="#tab-panel-esterni"
                        type="button" role="tab">
                    <i class="bi bi-globe me-1"></i>Panel Esterni
                    <span class="pq-tab-count">{{ $panelEsterniRollup->count() }}</span>
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
                            @php
                                $grpPopLines = array_map(function ($m) {
                                    $label = e($m['uid']) . ($m['name'] ? ' — ' . e($m['name']) : '');
                                    return '<a href="' . url('user/' . $m['uid']) . '" target="_blank"'
                                          . ' style="font-family:monospace;font-size:11px;color:#1a6fc4;text-decoration:none;">'
                                          . $label . '</a>';
                                }, $g['membri']);
                                $grpPopContent = implode('<br>', $grpPopLines);
                            @endphp
                            <span class="dup-sim-trigger" tabindex="0"
                                  data-bs-toggle="popover"
                                  data-bs-trigger="click"
                                  data-bs-html="true"
                                  data-bs-placement="right"
                                  data-bs-content="{{ $grpPopContent }}"
                                  style="display:inline-flex;align-items:center;gap:5px;cursor:pointer;padding:3px 8px;background:oklch(95% 0.03 250);border:1px solid oklch(85% 0.05 250);border-radius:5px;font-size:12px;color:oklch(35% 0.10 255);white-space:nowrap;">
                                <i class="bi bi-people-fill" style="font-size:11px;opacity:.7;"></i>
                                {{ $g['size'] }}&nbsp;{{ $g['size'] === 1 ? 'utente' : 'utenti' }}
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
                        {{ $panelistiTable->count() }} panelisti
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
                    <table class="pq-table" id="tblPanelisti">
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
                        @php
                            $avatarPalette = ['#3b82f6','#8b5cf6','#10b981','#f59e0b','#ef4444','#ec4899','#14b8a6','#f97316'];
                        @endphp
                        @forelse($panelistiTable as $p)
                        @php
                            $score    = (float)($p->score_medio ?? 0);
                            $scoreCls = $score >= 70 ? 'pq-score-high' : ($score >= 50 ? 'pq-score-accept' : 'pq-score-low');
                            $tot      = max(1, $p->regolari + $p->incerte + $p->anomale);
                            $pctR     = round($p->regolari / $tot * 100);
                            $pctI     = round($p->incerte  / $tot * 100);
                            $pctA     = 100 - $pctR - $pctI;
                            $tierPrev = $p->anomale >= $p->regolari && $p->anomale >= $p->incerte
                                        ? 'anomala'
                                        : ($p->incerte >= $p->regolari ? 'incerta' : 'regolare');
                            $nameParts = explode(' ', trim($p->full_name));
                            $initials  = strtoupper(
                                substr($nameParts[0] ?? $p->uid, 0, 1) .
                                substr(end($nameParts) ?: '', 0, 1)
                            );
                            $avatarBg = $avatarPalette[abs(crc32($p->uid)) % count($avatarPalette)];
                            $nameDisplay = trim($p->full_name) ?: '—';
                        @endphp
                        <tr class="pq-row"
                            data-uid="{{ strtolower($p->uid) }}"
                            data-name="{{ strtolower($nameDisplay) }}"
                            data-tier="{{ $tierPrev }}"
                            data-score="{{ $score }}"
                            data-interviste="{{ $p->interviste }}">
                            <td class="pq-td">
                                <a href="{{ url('user/' . $p->uid) }}" target="_blank" class="pq-user-cell pq-user-link">
                                    <div class="pq-avatar-mini" style="background:{{ $avatarBg }};">{{ $initials }}</div>
                                    <div>
                                        <div class="pq-user-name">{{ $nameDisplay }}</div>
                                        <div class="pq-user-uid">{{ $p->uid }}</div>
                                    </div>
                                </a>
                            </td>
                            <td class="pq-td">
                                <span class="pq-score {{ $scoreCls }}">
                                    {{ $p->score_medio ?? '—' }}
                                    <span class="pq-score-denom">/100</span>
                                </span>
                            </td>
                            <td class="pq-td">
                                <span class="pq-tier pq-tier-{{ $tierPrev }}">{{ $tierPrev }}</span>
                            </td>
                            <td class="pq-td">
                                <div class="pq-distrib">
                                    <div class="pq-distrib-seg-high" style="width:{{ $pctR }}%;"></div>
                                    <div class="pq-distrib-seg-mid"  style="width:{{ $pctI }}%;"></div>
                                    <div class="pq-distrib-seg-low"  style="width:{{ $pctA }}%;"></div>
                                </div>
                                <div class="pq-distrib-label">
                                    <span>{{ $p->regolari }} reg</span>
                                    <span>{{ $p->incerte }} inc</span>
                                    <span>{{ $p->anomale }} ano</span>
                                </div>
                            </td>
                            <td class="pq-td pq-td-muted">{{ $p->interviste }}</td>
                            <td class="pq-td pq-td-muted">{{ number_format($p->bytes) }}</td>
                            <td class="pq-td">
                                @if($p->malus_count > 0)
                                    <span class="badge bg-danger">{{ $p->malus_count }}</span>
                                @else
                                    <span class="pq-td-muted">—</span>
                                @endif
                            </td>
                            <td class="pq-td pq-td-muted">
                                {{ $p->ultima_val ? \Carbon\Carbon::parse($p->ultima_val)->format('d/m/Y') : '—' }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="pq-empty">Nessun dato di qualità disponibile.</td>
                        </tr>
                        @endforelse
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
        <div class="tab-pane fade" id="tab-ricerche" role="tabpanel">
        @php
            $panelBadgesFn = function($panelInterno, $panelEsterno, $nomeEsterno = null) {
                $icons = '';
                if ((int)$panelInterno > 0) {
                    $icons .= '<i class="bi bi-house-fill pq-panel-icon pq-panel-int"
                                  data-bs-toggle="tooltip" data-bs-placement="top"
                                  title="Interactive"></i>';
                }
                if ((int)$panelEsterno > 0) {
                    $label = htmlspecialchars($nomeEsterno ?? 'Esterno', ENT_QUOTES);
                    $icons .= '<i class="bi bi-airplane-fill pq-panel-icon pq-panel-ext"
                                  data-bs-toggle="tooltip" data-bs-placement="top"
                                  title="' . $label . '"></i>';
                }
                return $icons
                    ? '<div class="pq-panel-icons">' . $icons . '</div>'
                    : '<span class="pq-td-muted">—</span>';
            };
        @endphp

            {{-- ── Sezione A: con dati ────────────────────────────────────── --}}
            <div class="pq-card">
                <div class="pq-card-header pq-border-green">
                    <div class="pq-card-header-left">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="oklch(45% 0.12 255)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                        <div>
                            <div class="pq-card-title">Ricerche con dati qualità</div>
                            <div class="pq-card-sub">Panel Interactive — {{ $ricercheConDati->count() }} ricerche, ordinate per score medio crescente</div>
                        </div>
                    </div>
                </div>

                <div class="pq-filters">
                    <input type="text" class="pq-filter-input" id="fltConDatiSearch"
                           placeholder="Cerca per PRJ o SID…">
                    <span class="pq-filter-count" id="conDatiVisibili">{{ $ricercheConDati->count() }} ricerche</span>
                </div>

                <div class="pq-table-wrap">
                    <table class="pq-table" id="tblConDati">
                        <thead class="pq-thead">
                            <tr>
                                <th class="pq-th">PRJ / SID</th>
                                <th class="pq-th">Descrizione</th>
                                <th class="pq-th">Panel</th>
                                <th class="pq-th">Stato</th>
                                <th class="pq-th">Score medio</th>
                                <th class="pq-th">Distribuzione</th>
                                <th class="pq-th">Interviste val.</th>
                                <th class="pq-th">Ultima val.</th>
                                <th class="pq-th"></th>
                            </tr>
                        </thead>
                        <tbody id="bodyConDati">
                        @forelse($ricercheConDati as $r)
                        @php
                            $score    = (float)($r->score_medio ?? 0);
                            $scoreCls = $score >= 70 ? 'pq-score-high' : ($score >= 50 ? 'pq-score-accept' : 'pq-score-low');
                            $tot      = max(1, $r->regolari + $r->incerte + $r->anomale);
                            $pctR     = round($r->regolari / $tot * 100);
                            $pctI     = round($r->incerte  / $tot * 100);
                            $pctA     = 100 - $pctR - $pctI;
                        @endphp
                        <tr class="pq-row"
                            data-prj="{{ strtolower($r->prj) }}"
                            data-sid="{{ strtolower($r->sid) }}">
                            <td class="pq-td">
                                <div class="pq-td-mono" style="font-size:11px;color:oklch(50% 0.02 250);">{{ $r->prj }}</div>
                                <div class="pq-td-mono fw-semibold">{{ $r->sid }}</div>
                            </td>
                            <td class="pq-td" style="max-width:240px;">
                                <div style="font-weight:500;color:oklch(25% 0.02 250);">{{ $r->description ?? '—' }}</div>
                            </td>
                            <td class="pq-td">{!! $panelBadgesFn($r->panel_interno ?? 0, $r->panel_esterno ?? 0, $r->panel_nome_esterno ?? null) !!}</td>
                            <td class="pq-td">
                                @if(($r->stato ?? 1) == 0)
                                    <span class="pq-stato-aperta"><i class="bi bi-circle-fill me-1" style="font-size:7px;"></i>Aperta</span>
                                @else
                                    <span class="pq-stato-chiusa"><i class="bi bi-check-circle me-1"></i>Chiusa</span>
                                @endif
                            </td>
                            <td class="pq-td">
                                <span class="pq-score {{ $scoreCls }}">
                                    {{ $r->score_medio ?? '—' }}
                                    <span class="pq-score-denom">/100</span>
                                </span>
                            </td>
                            <td class="pq-td">
                                <div class="pq-distrib">
                                    <div class="pq-distrib-seg-high" style="width:{{ $pctR }}%;"></div>
                                    <div class="pq-distrib-seg-mid"  style="width:{{ $pctI }}%;"></div>
                                    <div class="pq-distrib-seg-low"  style="width:{{ $pctA }}%;"></div>
                                </div>
                                <div class="pq-distrib-label">
                                    <span>{{ $r->regolari }} reg</span>
                                    <span>{{ $r->incerte }} inc</span>
                                    <span>{{ $r->anomale }} ano</span>
                                </div>
                            </td>
                            <td class="pq-td pq-td-muted">{{ $r->interviste_valutate }}</td>
                            <td class="pq-td pq-td-muted">
                                {{ $r->ultima_val ? \Carbon\Carbon::parse($r->ultima_val)->format('d/m/Y') : '—' }}
                            </td>
                            <td class="pq-td">
                                <a href="{{ url('fieldQuality') }}?prj={{ urlencode($r->prj) }}&sid={{ urlencode($r->sid) }}"
                                   target="_blank"
                                   class="btn btn-sm btn-outline-secondary" style="font-size:11px;padding:3px 9px;">
                                    Dettaglio
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="pq-empty">Nessuna ricerca con dati di qualità.</td>
                        </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div id="conDatiPaginator" class="pq-paginator-wrap"></div>
            </div>

            {{-- ── Sezione B: senza dati (Interactive + Esterno unificati) ── --}}
            <div class="pq-card">
                <div class="pq-card-header pq-border-amber">
                    <div class="pq-card-header-left">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="oklch(45% 0.12 80)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        <div>
                            <div class="pq-card-title" style="color:oklch(40% 0.12 80);">Ricerche senza dati qualità</div>
                            <div class="pq-card-sub">{{ $ricerceSenzaDati->count() }} ricerche senza valutazione nel {{ $annoSenzaDati }} — aprire fieldQuality per calcolarla</div>
                        </div>
                    </div>
                    <form method="GET" action="{{ route('panelQuality.index') }}" class="d-flex align-items-center gap-2">
                        <input type="hidden" name="#tab-ricerche" value="1">
                        <label style="font-size:12px;color:oklch(50% 0.02 250);margin:0;">Anno:</label>
                        <select name="anno_senza_dati" class="pq-filter-select" style="padding:5px 10px;"
                                onchange="this.form.submit()">
                            @foreach($anniDisponibili as $anno)
                                <option value="{{ $anno }}" {{ $anno == $annoSenzaDati ? 'selected' : '' }}>
                                    {{ $anno }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                </div>

                @if($ricerceSenzaDati->count() > 0)
                <div class="pq-filters">
                    <input type="text" class="pq-filter-input" id="fltSenzaDatiSearch"
                           placeholder="Cerca per PRJ o SID…">
                    <div class="btn-group btn-group-sm ms-2" role="group">
                        <button type="button" class="btn btn-outline-secondary pq-tipo-btn active" data-tipo="tutti">Tutti</button>
                        <button type="button" class="btn btn-outline-secondary pq-tipo-btn" data-tipo="interactive">
                            <i class="bi bi-house-fill me-1"></i>Interactive
                        </button>
                        <button type="button" class="btn btn-outline-secondary pq-tipo-btn" data-tipo="esterno">
                            <i class="bi bi-airplane-fill me-1"></i>Esterno
                        </button>
                    </div>
                    <span class="pq-filter-count" id="senzaDatiVisibili">{{ $ricerceSenzaDati->count() }} ricerche</span>
                </div>

                <div class="pq-table-wrap">
                    <table class="pq-table" id="tblSenzaDati">
                        <thead class="pq-thead">
                            <tr>
                                <th class="pq-th">PRJ / SID</th>
                                <th class="pq-th">Descrizione</th>
                                <th class="pq-th">Panel</th>
                                <th class="pq-th">Stato</th>
                                <th class="pq-th">Completate</th>
                                <th class="pq-th">Target</th>
                                <th class="pq-th">Data inizio</th>
                                <th class="pq-th"></th>
                            </tr>
                        </thead>
                        <tbody id="bodySenzaDati">
                        @foreach($ricerceSenzaDati as $r)
                        <tr class="pq-row"
                            data-prj="{{ strtolower($r->prj) }}"
                            data-sid="{{ strtolower($r->sur_id) }}"
                            data-int="{{ $r->complete_int > 0 ? 1 : 0 }}"
                            data-ext="{{ $r->complete_ext > 0 ? 1 : 0 }}">
                            <td class="pq-td">
                                <div class="pq-td-mono" style="font-size:11px;color:oklch(50% 0.02 250);">{{ $r->prj }}</div>
                                <div class="pq-td-mono fw-semibold">{{ $r->sur_id }}</div>
                            </td>
                            <td class="pq-td" style="max-width:220px;">
                                <div style="font-weight:500;color:oklch(25% 0.02 250);">{{ $r->description ?? '—' }}</div>
                            </td>
                            <td class="pq-td">{!! $panelBadgesFn($r->complete_int, $r->complete_ext, $r->panel_nome_esterno ?? null) !!}</td>
                            <td class="pq-td">
                                @if(($r->stato ?? 1) == 0)
                                    <span class="pq-stato-aperta"><i class="bi bi-circle-fill me-1" style="font-size:7px;"></i>Aperta</span>
                                @else
                                    <span class="pq-stato-chiusa"><i class="bi bi-check-circle me-1"></i>Chiusa</span>
                                @endif
                            </td>
                            <td class="pq-td pq-td-muted">{{ $r->complete ?? '—' }}</td>
                            <td class="pq-td pq-td-muted">{{ $r->goal ?? '—' }}</td>
                            <td class="pq-td pq-td-muted">
                                {{ $r->sur_date ? \Carbon\Carbon::parse($r->sur_date)->format('d/m/Y') : '—' }}
                            </td>
                            <td class="pq-td">
                                <a href="{{ url('fieldQuality') }}?prj={{ urlencode($r->prj) }}&sid={{ urlencode($r->sur_id) }}"
                                   target="_blank"
                                   class="btn btn-sm btn-outline-warning" style="font-size:11px;padding:3px 9px;">
                                    Calcola qualità
                                </a>
                            </td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div id="senzaDatiPaginator" class="pq-paginator-wrap"></div>
                @else
                    <div class="pq-empty">Tutte le ricerche hanno già dati di qualità nel {{ $annoSenzaDati }}.</div>
                @endif

            </div>

        </div>{{-- /tab-ricerche --}}

        {{-- ───────────────────────────────────────────────────────────────── --}}
        {{-- TAB 3 — PANEL ESTERNI                                              --}}
        {{-- ───────────────────────────────────────────────────────────────── --}}
        <div class="tab-pane fade" id="tab-panel-esterni" role="tabpanel">

            {{-- ── Sezione A: media per panel ────────────────────────────── --}}
            <div class="pq-card">
                <div class="pq-card-header pq-border-green">
                    <div class="pq-card-header-left">
                        <i class="bi bi-globe" style="font-size:18px;color:#6e904b;"></i>
                        <div>
                            <div class="pq-card-title">Valutazione media per panel</div>
                            <div class="pq-card-sub">Esclude panel Interactive — {{ $panelEsterniRollup->count() }} panel monitorati, ordinati per score medio crescente</div>
                        </div>
                    </div>
                </div>

                <div class="pq-table-wrap">
                    <table class="pq-table">
                        <thead class="pq-thead">
                            <tr>
                                <th class="pq-th">Panel</th>
                                <th class="pq-th">Score medio</th>
                                <th class="pq-th">Distribuzione</th>
                                <th class="pq-th">Ricerche</th>
                                <th class="pq-th">Interviste val.</th>
                                <th class="pq-th">Ultima val.</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($panelEsterniRollup as $row)
                        @php
                            $score    = (float)($row->score_medio ?? 0);
                            $scoreCls = $score >= 70 ? 'pq-score-high' : ($score >= 50 ? 'pq-score-accept' : 'pq-score-low');
                            $tot      = max(1, $row->regolari + $row->incerte + $row->anomale);
                            $pctR     = round($row->regolari / $tot * 100);
                            $pctI     = round($row->incerte  / $tot * 100);
                            $pctA     = 100 - $pctR - $pctI;
                        @endphp
                        <tr class="pq-row">
                            <td class="pq-td">
                                <span class="pq-panel-name-badge"><i class="bi bi-globe"></i>{{ $row->panel }}</span>
                            </td>
                            <td class="pq-td">
                                <span class="pq-score {{ $scoreCls }}">
                                    {{ $row->score_medio ?? '—' }}
                                    <span class="pq-score-denom">/100</span>
                                </span>
                            </td>
                            <td class="pq-td">
                                <div class="pq-distrib">
                                    <div class="pq-distrib-seg-high" style="width:{{ $pctR }}%;"></div>
                                    <div class="pq-distrib-seg-mid"  style="width:{{ $pctI }}%;"></div>
                                    <div class="pq-distrib-seg-low"  style="width:{{ $pctA }}%;"></div>
                                </div>
                                <div class="pq-distrib-label">
                                    <span>{{ $row->regolari }} reg</span>
                                    <span>{{ $row->incerte }} inc</span>
                                    <span>{{ $row->anomale }} ano</span>
                                </div>
                            </td>
                            <td class="pq-td pq-td-muted">{{ $row->ricerche }}</td>
                            <td class="pq-td pq-td-muted">{{ $row->interviste }}</td>
                            <td class="pq-td pq-td-muted">
                                {{ $row->ultima_val ? \Carbon\Carbon::parse($row->ultima_val)->format('d/m/Y') : '—' }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="pq-empty">Nessun dato di qualità disponibile per panel esterni.</td>
                        </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- ── Sezione B: dettaglio per ricerca ──────────────────────── --}}
            <div class="pq-card">
                <div class="pq-card-header pq-border-blue">
                    <div class="pq-card-header-left">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="oklch(45% 0.12 255)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                        <div>
                            <div class="pq-card-title">Dettaglio per ricerca</div>
                            <div class="pq-card-sub">{{ $panelEsterniPerRicerca->count() }} combinazioni ricerca/panel — ordinate per score medio crescente</div>
                        </div>
                    </div>
                </div>

                <div class="pq-filters">
                    <input type="text" class="pq-filter-input" id="fltPanelEstSearch"
                           placeholder="Cerca per PRJ o SID…">
                    <select class="pq-filter-select" id="fltPanelEstPanel">
                        <option value="">Tutti i panel</option>
                        @foreach($panelEsterniRollup as $row)
                            <option value="{{ strtolower($row->panel) }}">{{ $row->panel }}</option>
                        @endforeach
                    </select>
                    <span class="pq-filter-count" id="panelEstVisibili">{{ $panelEsterniPerRicerca->count() }} righe</span>
                </div>

                <div class="pq-table-wrap">
                    <table class="pq-table" id="tblPanelEst">
                        <thead class="pq-thead">
                            <tr>
                                <th class="pq-th">PRJ / SID</th>
                                <th class="pq-th">Descrizione</th>
                                <th class="pq-th">Panel</th>
                                <th class="pq-th">Score medio</th>
                                <th class="pq-th">Distribuzione</th>
                                <th class="pq-th">Interviste val.</th>
                                <th class="pq-th">Ultima val.</th>
                                <th class="pq-th"></th>
                            </tr>
                        </thead>
                        <tbody id="bodyPanelEst">
                        @forelse($panelEsterniPerRicerca as $r)
                        @php
                            $score    = (float)($r->score_medio ?? 0);
                            $scoreCls = $score >= 70 ? 'pq-score-high' : ($score >= 50 ? 'pq-score-accept' : 'pq-score-low');
                            $tot      = max(1, $r->regolari + $r->incerte + $r->anomale);
                            $pctR     = round($r->regolari / $tot * 100);
                            $pctI     = round($r->incerte  / $tot * 100);
                            $pctA     = 100 - $pctR - $pctI;
                        @endphp
                        <tr class="pq-row"
                            data-prj="{{ strtolower($r->prj) }}"
                            data-sid="{{ strtolower($r->sid) }}"
                            data-panel="{{ strtolower($r->panel) }}">
                            <td class="pq-td">
                                <div class="pq-td-mono" style="font-size:11px;color:oklch(50% 0.02 250);">{{ $r->prj }}</div>
                                <div class="pq-td-mono fw-semibold">{{ $r->sid }}</div>
                            </td>
                            <td class="pq-td" style="max-width:220px;">
                                <div style="font-weight:500;color:oklch(25% 0.02 250);">{{ $r->description ?? '—' }}</div>
                            </td>
                            <td class="pq-td">
                                <span class="pq-panel-name-badge"><i class="bi bi-globe"></i>{{ $r->panel }}</span>
                            </td>
                            <td class="pq-td">
                                <span class="pq-score {{ $scoreCls }}">
                                    {{ $r->score_medio ?? '—' }}
                                    <span class="pq-score-denom">/100</span>
                                </span>
                            </td>
                            <td class="pq-td">
                                <div class="pq-distrib">
                                    <div class="pq-distrib-seg-high" style="width:{{ $pctR }}%;"></div>
                                    <div class="pq-distrib-seg-mid"  style="width:{{ $pctI }}%;"></div>
                                    <div class="pq-distrib-seg-low"  style="width:{{ $pctA }}%;"></div>
                                </div>
                                <div class="pq-distrib-label">
                                    <span>{{ $r->regolari }} reg</span>
                                    <span>{{ $r->incerte }} inc</span>
                                    <span>{{ $r->anomale }} ano</span>
                                </div>
                            </td>
                            <td class="pq-td pq-td-muted">{{ $r->interviste_valutate }}</td>
                            <td class="pq-td pq-td-muted">
                                {{ $r->ultima_val ? \Carbon\Carbon::parse($r->ultima_val)->format('d/m/Y') : '—' }}
                            </td>
                            <td class="pq-td">
                                <a href="{{ url('fieldQuality') }}?prj={{ urlencode($r->prj) }}&sid={{ urlencode($r->sid) }}"
                                   target="_blank"
                                   class="btn btn-sm btn-outline-secondary" style="font-size:11px;padding:3px 9px;">
                                    Dettaglio
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="pq-empty">Nessuna ricerca con dati di qualità per panel esterni.</td>
                        </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div id="panelEstPaginator" class="pq-paginator-wrap"></div>
            </div>

        </div>{{-- /tab-panel-esterni --}}

        {{-- ───────────────────────────────────────────────────────────────── --}}
        {{-- TAB 4 — DUPLICATI                                                  --}}
        {{-- ───────────────────────────────────────────────────────────────── --}}
        <div class="tab-pane fade" id="tab-duplicati" role="tabpanel">
            <div class="pq-card">

                <div class="pq-card-header pq-border-amber">
                    <div class="pq-card-header-left">
                        <i class="bi bi-copy" style="font-size:18px;color:oklch(50% 0.14 55);"></i>
                        <div>
                            <div class="pq-card-title" style="color:oklch(38% 0.12 55);">Segnalazioni duplicati</div>
                            <div class="pq-card-sub">
                                {{ $nUidDuplicati }} UID segnalati in {{ $nRicercheDuplicati }} {{ $nRicercheDuplicati === 1 ? 'ricerca' : 'ricerche' }}
                            </div>
                        </div>
                    </div>
                </div>

                @if($duplicati->isEmpty())
                    <div class="pq-empty">Nessuna segnalazione di duplicati.</div>
                @else

                <div class="pq-filters">
                    <input type="text" class="pq-filter-input" id="fltDuplicatiSearch"
                           placeholder="Cerca per UID o nome…">
                    <span class="pq-filter-count" id="duplicatiVisibili">{{ $duplicati->count() }} panelisti</span>
                </div>

                <div class="pq-table-wrap">
                    <table class="pq-table" id="tblDuplicati">
                        <thead class="pq-thead">
                            <tr>
                                <th class="pq-th">UID</th>
                                <th class="pq-th">Nome</th>
                                <th class="pq-th">Segnalazioni</th>
                                <th class="pq-th">Simile a</th>
                                <th class="pq-th" style="white-space:nowrap;">Ultima segn.</th>
                            </tr>
                        </thead>
                        <tbody id="bodyDuplicati">
                        @foreach($duplicati as $d)
                        @php
                            $ricercheTooltip = implode('<br>', array_map(
                                fn($key, $desc) => '<span style="font-family:monospace;font-size:11px;">' . e($key) . '</span>'
                                    . ($desc && $desc !== $key ? ' &mdash; ' . e($desc) : ''),
                                array_keys($d['ricerche']),
                                array_values($d['ricerche'])
                            ));
                        @endphp
                        <tr class="pq-row"
                            data-uid="{{ strtolower($d['uid']) }}"
                            data-name="{{ strtolower($d['full_name'] ?? '') }}">
                            <td class="pq-td">
                                <a href="{{ url('user/' . $d['uid']) }}" target="_blank" class="pq-user-link">
                                    <span class="pq-td-mono" style="font-size:11px;">{{ $d['uid'] }}</span>
                                </a>
                            </td>
                            <td class="pq-td">
                                <span style="font-size:13px;">{{ $d['full_name'] ?: '—' }}</span>
                            </td>
                            <td class="pq-td">
                                <span data-bs-toggle="tooltip" data-bs-html="true"
                                      data-bs-placement="right"
                                      title="{{ $ricercheTooltip }}"
                                      style="display:inline-flex;align-items:center;gap:5px;cursor:default;">
                                    <span style="font-size:15px;font-weight:700;color:oklch(42% 0.14 55);">{{ $d['segnalazioni'] }}</span>
                                    <i class="bi bi-info-circle" style="font-size:11px;color:oklch(60% 0.08 250);"></i>
                                </span>
                            </td>
                            <td class="pq-td">
                                @php
                                    $simList  = $d['simile_a'];
                                    $simTotal = count($simList);
                                    $popLines = [];
                                    foreach ($simList as $sUid => $cnt) {
                                        $line = '<a href="' . url('user/' . $sUid) . '" target="_blank"'
                                              . ' style="font-family:monospace;font-size:11px;color:#1a6fc4;text-decoration:none;">'
                                              . e($sUid) . '</a>';
                                        if ($cnt > 1) {
                                            $line .= ' <span style="font-size:10px;font-weight:700;color:#b45309;">×' . $cnt . '</span>';
                                        }
                                        $popLines[] = $line;
                                    }
                                    $popContent = implode('<br>', $popLines);
                                @endphp
                                <span class="dup-sim-trigger" tabindex="0"
                                      data-bs-toggle="popover"
                                      data-bs-trigger="click"
                                      data-bs-html="true"
                                      data-bs-placement="left"
                                      data-bs-content="{{ $popContent }}"
                                      style="display:inline-flex;align-items:center;gap:5px;cursor:pointer;padding:3px 8px;background:oklch(95% 0.03 250);border:1px solid oklch(85% 0.05 250);border-radius:5px;font-size:12px;color:oklch(35% 0.10 255);white-space:nowrap;">
                                    <i class="bi bi-people-fill" style="font-size:11px;opacity:.7;"></i>
                                    Simile a <strong style="margin-left:2px;">{{ $simTotal }}</strong>&nbsp;{{ $simTotal === 1 ? 'utente' : 'utenti' }}
                                </span>
                            </td>
                            <td class="pq-td pq-td-muted" style="white-space:nowrap;font-size:12px;">
                                {{ \Carbon\Carbon::parse($d['ultima'])->format('d/m/Y H:i') }}
                            </td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div id="duplicatiPaginator" class="pq-paginator-wrap"></div>

                @endif
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

/* ── Tooltip Bootstrap ─────────────────────────────────────────── */
document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
    new bootstrap.Tooltip(el, { trigger: 'hover' });
});

/* ── Swap KPI globali ↔ KPI duplicati al cambio tab ───────────── */
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

/* ── Popover duplicati (click, con link cliccabili) ────────────── */
document.querySelectorAll('.dup-sim-trigger').forEach(function (el) {
    var pop = new bootstrap.Popover(el, { trigger: 'manual', html: true });
    el.addEventListener('click', function (e) {
        e.stopPropagation();
        document.querySelectorAll('.dup-sim-trigger').forEach(function (other) {
            if (other !== el) bootstrap.Popover.getInstance(other)?.hide();
        });
        pop.toggle();
    });
});
document.addEventListener('click', function () {
    document.querySelectorAll('.dup-sim-trigger').forEach(function (el) {
        bootstrap.Popover.getInstance(el)?.hide();
    });
});

/* ── Panelisti ─────────────────────────────────────────────────── */
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

/* ── Ricerche con dati ─────────────────────────────────────────── */
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

/* ── Ricerche senza dati (Interactive + Esterno unificati) ────────── */
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

/* ── Duplicati ───────────────────────────────────────────────────── */
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

    var elCG = document.getElementById('collapseGruppi');
    if (elCG) {
        elCG.addEventListener('hide.bs.collapse', function () {
            document.getElementById('icnGruppi').style.transform = 'rotate(-90deg)';
        });
        elCG.addEventListener('show.bs.collapse', function () {
            document.getElementById('icnGruppi').style.transform = 'rotate(0deg)';
        });
    }
}


/* ── Panel esterni — dettaglio per ricerca ───────────────────────── */
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
</script>

@endsection
