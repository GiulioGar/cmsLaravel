@extends('layouts.main')

@section('content')
<link rel="stylesheet" href="{{ asset('css/panelQuality.css') }}">

<div class="container-fluid pq-container mt-3">

    {{-- ═══ PAGE HEADER ═══════════════════════════════════════════════════ --}}
    <div class="pq-page-header">
        <div class="d-flex align-items-start justify-content-between flex-wrap gap-2">
            <div>
                <h1 class="pq-page-title">Controllo Qualità Panel</h1>
                <p class="pq-page-sub">Monitoraggio aggregato degli score di qualità per i panelisti Interactive</p>
                <span class="pq-page-badge"><i class="bi bi-person-fill me-1"></i>Solo panel Interactive</span>
            </div>
            <div class="text-end pq-page-sub mt-1">
                Aggiornato al {{ now()->format('d/m/Y H:i') }}
            </div>
        </div>
    </div>

    {{-- ═══ KPI GLOBALI ════════════════════════════════════════════════════ --}}
    @php
        $scoreColor = ($globalStats->score_medio ?? 0) >= 70
            ? 'oklch(55% 0.13 150)'
            : (($globalStats->score_medio ?? 0) >= 50 ? 'oklch(58% 0.14 75)' : 'oklch(55% 0.17 25)');
    @endphp
    <div class="pq-kpi-grid">
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

    {{-- ═══ TABS ════════════════════════════════════════════════════════════ --}}
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
    </ul>

    <div class="tab-content">

        {{-- ───────────────────────────────────────────────────────────────── --}}
        {{-- TAB 1 — PANELISTI                                                 --}}
        {{-- ───────────────────────────────────────────────────────────────── --}}
        <div class="tab-pane fade show active" id="tab-panelisti" role="tabpanel">
            <div class="pq-card">

                {{-- Header --}}
                <div class="pq-card-header pq-border-blue">
                    <div class="pq-card-header-left">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="oklch(45% 0.12 255)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        <div>
                            <div class="pq-card-title">Qualità per panelista</div>
                            <div class="pq-card-sub">Ordinati per score medio crescente — i peggiori in cima</div>
                        </div>
                    </div>
                </div>

                {{-- Filtri --}}
                <div class="pq-filters">
                    <input type="text" class="pq-filter-input" id="fltPanelistiSearch"
                           placeholder="Cerca per nome o UID…">
                    <select class="pq-filter-select" id="fltPanelistiTier">
                        <option value="">Tutti i tier</option>
                        <option value="anomala">Solo anomali</option>
                        <option value="incerta">Solo incerti</option>
                        <option value="regolare">Solo regolari</option>
                    </select>
                    <span class="pq-filter-count" id="panelistiVisibili">
                        {{ $panelisti->count() }} panelisti
                    </span>
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
                                <th class="pq-th">Ultima val.</th>
                                <th class="pq-th"></th>
                            </tr>
                        </thead>
                        <tbody id="bodyPanelisti">
                        @php
                            $avatarPalette = ['#3b82f6','#8b5cf6','#10b981','#f59e0b','#ef4444','#ec4899','#14b8a6','#f97316'];
                        @endphp
                        @forelse($panelisti as $p)
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
                                <div class="pq-user-cell">
                                    <div class="pq-avatar-mini" style="background:{{ $avatarBg }};">{{ $initials }}</div>
                                    <div>
                                        <div class="pq-user-name">{{ $nameDisplay }}</div>
                                        <div class="pq-user-uid">{{ $p->uid }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="pq-td">
                                <span class="pq-score {{ $scoreCls }}">
                                    {{ $p->score_medio ?? '—' }}
                                    <span class="pq-score-denom">/100</span>
                                </span>
                                @if($p->cap_count > 0)
                                    <div class="mt-1">
                                        <span class="pq-cap-pill">
                                            <i class="bi bi-slash-circle" style="font-size:9px;"></i>
                                            CAP ×{{ $p->cap_count }}
                                        </span>
                                    </div>
                                @endif
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
                            <td class="pq-td pq-td-muted">
                                {{ $p->ultima_val ? \Carbon\Carbon::parse($p->ultima_val)->format('d/m/Y') : '—' }}
                            </td>
                            <td class="pq-td">
                                <a href="{{ url('user/' . $p->uid) }}" target="_blank"
                                   class="btn btn-sm btn-outline-secondary" style="font-size:11px;padding:3px 9px;">
                                    Profilo
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="pq-empty">Nessun dato di qualità disponibile.</td>
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
                <div class="pq-card-header pq-border-blue">
                    <div class="pq-card-header-left">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="oklch(45% 0.12 255)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                        <div>
                            <div class="pq-card-title">Ricerche con dati qualità</div>
                            <div class="pq-card-sub">{{ $ricercheConDati->count() }} ricerche — ordinate per score medio crescente</div>
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

            {{-- ── Sezione B: senza dati ──────────────────────────────────── --}}
            <div class="pq-card">
                <div class="pq-card-header pq-border-amber">
                    <div class="pq-card-header-left">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="oklch(45% 0.12 80)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        <div>
                            <div class="pq-card-title" style="color:oklch(40% 0.12 80);">Ricerche senza dati qualità</div>
                            <div class="pq-card-sub">{{ $ricerceSenzaDati->count() }} ricerche Interactive senza valutazione nel {{ $annoSenzaDati }} — aprire fieldQuality per calcolarla</div>
                        </div>
                    </div>
                    {{-- Selettore anno --}}
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
                            data-sid="{{ strtolower($r->sur_id) }}">
                            <td class="pq-td">
                                <div class="pq-td-mono" style="font-size:11px;color:oklch(50% 0.02 250);">{{ $r->prj }}</div>
                                <div class="pq-td-mono fw-semibold">{{ $r->sur_id }}</div>
                            </td>
                            <td class="pq-td" style="max-width:220px;">
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
                    <div class="pq-empty">Tutte le ricerche Interactive hanno già dati di qualità.</div>
                @endif

            </div>

        </div>{{-- /tab-ricerche --}}

    </div>{{-- /tab-content --}}

</div>{{-- /pq-container --}}

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

/* ── Panelisti ─────────────────────────────────────────────────── */
var _pan = pqTable({
    rowsSelector: '#bodyPanelisti .pq-row',
    paginatorId:  'panelistiPaginator',
    countId:      'panelistiVisibili',
    tableId:      'tblPanelisti',
    goFn:         'pqGoPan',
    label:        'panelisti',
    match: function (r) {
        var term = document.getElementById('fltPanelistiSearch').value.toLowerCase().trim();
        var tier = document.getElementById('fltPanelistiTier').value;
        return (!term || r.dataset.uid.includes(term) || r.dataset.name.includes(term))
            && (!tier || r.dataset.tier === tier);
    }
});
window.pqGoPan = function (p) { _pan.go(p); };
document.getElementById('fltPanelistiSearch').addEventListener('input',  function () { _pan.reset(); });
document.getElementById('fltPanelistiTier').addEventListener('change', function () { _pan.reset(); });
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
        match: function (r) {
            var term = document.getElementById('fltConDatiSearch').value.toLowerCase().trim();
            return !term || r.dataset.prj.includes(term) || r.dataset.sid.includes(term);
        }
    });
    window.pqGoCon = function (p) { _con.go(p); };
    document.getElementById('fltConDatiSearch').addEventListener('input', function () { _con.reset(); });
    _con.render();
}

/* ── Ricerche senza dati ───────────────────────────────────────── */
if (document.getElementById('bodySenzaDati')) {
    var _senza = pqTable({
        rowsSelector: '#bodySenzaDati .pq-row',
        paginatorId:  'senzaDatiPaginator',
        countId:      'senzaDatiVisibili',
        tableId:      'tblSenzaDati',
        goFn:         'pqGoSenza',
        label:        'ricerche',
        match: function (r) {
            var term = document.getElementById('fltSenzaDatiSearch').value.toLowerCase().trim();
            return !term || r.dataset.prj.includes(term) || r.dataset.sid.includes(term);
        }
    });
    window.pqGoSenza = function (p) { _senza.go(p); };
    document.getElementById('fltSenzaDatiSearch').addEventListener('input', function () { _senza.reset(); });
    _senza.render();
}
</script>

@endsection
