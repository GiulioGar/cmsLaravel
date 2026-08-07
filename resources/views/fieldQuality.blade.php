@extends('layouts.main')

@section('content')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/fieldControl.css') }}">
    {{-- fieldQuality.css rimosso: sostituito dal design system dq-* inline --}}

    <div class="container field-control-container">

<!-- NAVBAR MODERNA CON MENU A TENDINA -->
<nav class="navbar custom-navbar mb-4">
    <div class="container-fluid d-flex align-items-center justify-content-between px-0">
        <!-- Brand a sinistra -->
        <a class="navbar-brand d-flex align-items-center" href="{{ url('fieldControl?prj='.$prj.'&sid='.$sid) }}">
            <i class="fas fa-chart-bar me-2"></i>
            <span>Status Field</span>
        </a>

        <!-- Menu orizzontale con dropdown -->
        <ul class="nav custom-nav-links">
            <!-- Ricerche in corso -->
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="ongoingResearchDropdown" role="button"
                   data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-tasks me-1"></i> Ricerche in corso
                </a>
                <ul class="dropdown-menu" aria-labelledby="ongoingResearchDropdown">
                    @forelse($ricercheInCorso as $ricerca)
                        <li>
                            <a class="dropdown-item"
                               href="{{ url('fieldControl?prj=' . $ricerca->prj . '&sid=' . $ricerca->sur_id) }}">
                                {{ $ricerca->description }}
                            </a>
                        </li>
                    @empty
                        <li><span class="dropdown-item text-muted">Nessuna ricerca attiva</span></li>
                    @endforelse
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item " href="{{url('surveys')}}"><b>Vedi tutte</b></a></li>
                </ul>
            </li>

            <!-- Imposta Target -->
            <li class="nav-item">
                <a class="nav-link"
                   href="{{ route('targetField.index', ['prj' => $prj, 'sid' => $sid]) }}">
                   <i class="fas fa-bullseye me-1"></i> Imposta Target
                </a>
            </li>

            <!-- Controllo Qualità -->
            <li class="nav-item">
                <a class="nav-link" href="{{ route('fieldQuality.index', ['prj' => $prj, 'sid' => $sid]) }}">
                    <i class="fas fa-check-circle me-1"></i> Controllo Qualità
                </a>
            </li>

            <!-- Download -->
            <li class="nav-item">
<a href="#" class="nav-link" data-bs-toggle="modal" data-bs-target="#downloadModal">
    <i class="fas fa-download me-1"></i> Download
</a>
            </li>

            <!-- Impostazioni con dropdown -->
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="settingsDropdown" role="button"
                   data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-cog me-1"></i> Impostazioni
                </a>
                <ul class="dropdown-menu" aria-labelledby="settingsDropdown">
                    <li>
                        <a class="dropdown-item
                        @if(!empty($panelData) && $panelData->stato == 1)
                            disabled text-muted
                        @endif"
                        href="#"
                        @if(!empty($panelData) && $panelData->stato == 1)
                            style="pointer-events:none;opacity:0.5;"
                        @endif
                        onclick="closeSurvey('{{ $prj }}', '{{ $sid }}')"
                    >
                        Chiudi Ricerca
                    </a>
                    </li>
                    <li>
                    <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#resetBloccateModal">
                        Reset Bloccate
                    </a>
                </li>
                </ul>
            </li>
        </ul>
    </div>
</nav>
<!-- FINE NAVBAR -->

@php
    /* ---- Panel stats per breakdown table ---- */
    $panelStats = [];
    foreach ($completeInterviews as $iv) {
        $pKey = ($iv['panel'] ?: 'N/D');
        if (!isset($panelStats[$pKey])) {
            $panelStats[$pKey] = ['count'=>0,'scores'=>[],'lois'=>[],'high'=>0,'accept'=>0,'low'=>0];
        }
        $panelStats[$pKey]['count']++;
        if (($iv['loiSec'] ?? 0) > 0) {
            $panelStats[$pKey]['lois'][] = $iv['loiSec'];
        }
        if ($iv['score'] !== null) {
            $panelStats[$pKey]['scores'][] = $iv['score'];
            $s = (int) $iv['score'];
            if      ($s >= 70) $panelStats[$pKey]['high']++;
            elseif  ($s >= 50) $panelStats[$pKey]['accept']++;
            else               $panelStats[$pKey]['low']++;
        }
    }
    foreach ($panelStats as $pKey => &$ps) {
        sort($ps['lois']);
        $n = count($ps['lois']);
        $ps['loiMedian'] = $n > 0
            ? ($n % 2 === 0
                ? ($ps['lois'][$n/2-1] + $ps['lois'][$n/2]) / 2
                : $ps['lois'][($n-1)/2])
            : 0;
        $_mS = (int)$ps['loiMedian'];
        $_mH = (int)floor($_mS / 3600);
        $_mM = (int)floor(($_mS % 3600) / 60);
        $_mSec = $_mS % 60;
        $ps['loiMedianFmt'] = $_mH > 0
            ? sprintf('%02d:%02d:%02d', $_mH, $_mM, $_mSec)
            : sprintf('%02d:%02d', $_mM, $_mSec);
        $ps['avgScore'] = count($ps['scores']) > 0
            ? round(array_sum($ps['scores']) / count($ps['scores']), 1)
            : null;
        $ev = count($ps['scores']);
        $ps['pctHigh']   = $ev > 0 ? round($ps['high']   / $ev * 100) : 0;
        $ps['pctAccept'] = $ev > 0 ? round($ps['accept'] / $ev * 100) : 0;
        $ps['pctLow']    = $ev > 0 ? 100 - $ps['pctHigh'] - $ps['pctAccept'] : 0;
    }
    unset($ps);

    /* ---- Open: fake per IID + solo righe fake ---- */
    $openFakeByIid = [];
    foreach ($openQuestionsData as $row) {
        $iid = $row['iid'];
        if (!isset($openFakeByIid[$iid])) {
            $openFakeByIid[$iid] = ['fake'=>0,'total'=>0];
        }
        $openFakeByIid[$iid]['total']++;
        if (!empty($row['isFake'])) $openFakeByIid[$iid]['fake']++;
    }
    /* $fakeOpenRows, $allOpenRows, $openTotalCount — pre-calcolati dal controller (cap 1500) */

    /* ---- Panel unici per dropdown filtri ---- */
    $uniquePanels = array_unique(array_column($completeInterviews, 'panel'));
    sort($uniquePanels);

    /* ---- LOI lookup per IID — pre-calcolati dal controller ---- */
    /* helper per formattare secondi in mm:ss o hh:mm:ss (usato nella view) */
    $fmtLoi = function($s) {
        $s = (int) $s;
        if ($s <= 0) { return '—'; }
        $h = (int) floor($s / 3600);
        $m = (int) floor(($s % 3600) / 60);
        $sec = $s % 60;
        if ($h > 0) { return sprintf('%02d:%02d:%02d', $h, $m, $sec); }
        return sprintf('%02d:%02d', $m, $sec);
    };
    $loiRatioByIid    = array_map(fn($l) => $l['ratio']    ?? null,   $loiCriteriaByIid);
    $loiTooSlowByIid  = array_map(fn($l) => !empty($l['too_slow']),   $loiCriteriaByIid);
    $loiSurveyRefQCount  = $loiSurveyMeta['refQCount'];
    $loiSurveyRefType    = $loiSurveyMeta['refType'];
    $loiSurveyRefFullSec = $loiSurveyMeta['refFullSec'];
    $loiSurveySampleSize = $loiSurveyMeta['sampleSize'];
    $loiSurveyExcluded   = $loiSurveyMeta['excluded'];
    $loiSurveyCoverage   = $loiSurveyMeta['coverage'];

    /* Mediana LOI osservata (tutte le interviste valide, esclude <60s e >=2700s) */
    $_obsLois = array_values(array_filter($loiSecByIid, fn($s) => $s >= 60 && $s < 2700));
    sort($_obsLois);
    $_obsN = count($_obsLois);
    $_obsMedianSec = $_obsN > 0
        ? ($_obsN % 2 === 0
            ? ($_obsLois[$_obsN/2 - 1] + $_obsLois[$_obsN/2]) / 2.0
            : (float)$_obsLois[($_obsN-1)/2])
        : 0;
    $loiObsMedianFmt = $fmtLoi((int)round($_obsMedianSec));

    /* ---- Ordina le tabelle per IID numerico ---- */
    /* $allOpenRows e $fakeOpenRows già ordinati dal controller (fake-first, poi IID) */
    usort($completeInterviews, fn($a, $b) => (int)$a['iid'] <=> (int)$b['iid']);
    usort($loiData,            fn($a, $b) => (int)$a['iid'] <=> (int)$b['iid']);
    usort($scaleData,          fn($a, $b) => (int)$a['iid'] <=> (int)$b['iid']);
@endphp

<!-- ================================================================
     DASHBOARD
     ================================================================ -->
<div class="dq-sections">

    <div class="dq-page-header">
        <div class="dq-page-header-inner">
            <div>
                <h1 class="dq-page-title">Dashboard Qualità Interviste</h1>
                <p class="dq-page-sub">Monitoraggio qualità dati e controlli antifrode &middot; {{ $panelData->description ?? ($prj . '/' . $sid) }}</p>
            </div>
            <span class="dq-page-badge">{{ $totalInterviews }} interviste</span>
        </div>
    </div>

    <!-- ============================================================
         1. VALUTAZIONE GENERALE
         ============================================================ -->
    <section id="sez-generale" class="dq-card dq-section" style="animation-delay:.02s;">
        <div class="dq-card-header dq-border-blue">
            <div class="dq-header-left">
                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="oklch(45% 0.12 255)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/></svg>
                <span class="dq-section-title">Valutazione generale</span>
            </div>
            <span class="dq-header-right">{{ $totalInterviews }} interviste totali</span>
        </div>

        <!-- 4 stat cards -->
        <div class="dq-stat-grid">
            <div class="dq-stat-cell">
                <div class="dq-stat-label">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 6h13"/><path d="M8 12h13"/><path d="M8 18h13"/><path d="M3 6h.01"/><path d="M3 12h.01"/><path d="M3 18h.01"/></svg>
                    Interviste
                </div>
                <div class="dq-stat-value">{{ $totalInterviews }}</div>
                @if($notEvaluableInterviews > 0)
                <div style="font-size:11px;color:oklch(55% 0.02 250);margin-top:4px;">{{ $notEvaluableInterviews }} non valutabili</div>
                @endif
            </div>
            <div class="dq-stat-cell">
                <div class="dq-stat-label">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20V10"/><path d="M18 20V4"/><path d="M6 20v-4"/></svg>
                    Score medio
                </div>
                <div style="display:flex;align-items:baseline;gap:6px;margin-top:8px;">
                    <div class="dq-stat-value" style="margin-top:0;">{{ $averageScore ?? '—' }}</div>
                    @if($averageScore !== null)<div style="font-size:13px;color:oklch(45% 0.02 250);">/100</div>@endif
                </div>
                @if($averageScore !== null)
                <div class="dq-progress-bar">
                    <div class="dq-progress-fill" style="width:{{ $averageScore }}%;background:oklch(55% 0.13 150);"></div>
                </div>
                @endif
                @if($maxScore !== null && $minScore !== null)
                <div style="font-size:11px;color:oklch(55% 0.02 250);margin-top:6px;">
                    <span style="color:oklch(45% 0.13 150);">▲ {{ $maxScore }}</span>
                    &nbsp;·&nbsp;
                    <span style="color:oklch(48% 0.16 25);">▼ {{ $minScore }}</span>
                </div>
                @endif
            </div>
            <div class="dq-stat-cell">
                <div class="dq-stat-label">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                    Durate LOI
                </div>
                <div style="margin-top:8px;">
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:6px;">
                        <div style="font-size:11px;color:oklch(50% 0.02 250);display:flex;align-items:center;gap:3px;">
                            Mediana osservata
                            <span class="dq-loi-info-pop" data-loi-key="obs"
                                  style="cursor:help;color:oklch(60% 0.08 250);font-size:12px;line-height:1;">ⓘ</span>
                        </div>
                        <div style="font-size:15px;font-weight:700;color:oklch(25% 0.02 250);font-variant-numeric:tabular-nums;">{{ $loiObsMedianFmt }}</div>
                    </div>
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:6px;margin-top:6px;">
                        <div style="font-size:11px;color:oklch(50% 0.02 250);display:flex;align-items:center;gap:3px;">
                            Riferimento norm.
                            <span class="dq-loi-info-pop" data-loi-key="ref"
                                  style="cursor:help;color:oklch(60% 0.08 250);font-size:12px;line-height:1;">ⓘ</span>
                        </div>
                        <div style="font-size:15px;font-weight:700;color:oklch(25% 0.02 250);font-variant-numeric:tabular-nums;">{{ $loiMediaFormatted }}</div>
                    </div>
                </div>
            </div>
            <div class="dq-stat-cell">
                <div class="dq-stat-label">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.21 15.89A10 10 0 1 1 8 2.83"/><path d="M22 12A10 10 0 0 0 12 2v10z"/></svg>
                    Distribuzione qualità
                </div>
                <div class="dq-distrib-bar">
                    <div style="width:{{ $pctHigh }}%;background:oklch(55% 0.13 150);"></div>
                    <div style="width:{{ $pctAccept }}%;background:oklch(58% 0.14 75);"></div>
                    <div style="width:{{ $pctLow }}%;background:oklch(55% 0.17 25);"></div>
                </div>
                <div class="dq-distrib-legend">
                    <div style="font-size:11px;color:oklch(45% 0.02 250);"><span class="dq-distrib-dot" style="background:oklch(55% 0.13 150);"></span>Alta {{ $pctHigh }}%</div>
                    <div style="font-size:11px;color:oklch(45% 0.02 250);"><span class="dq-distrib-dot" style="background:oklch(58% 0.14 75);"></span>Acc. {{ $pctAccept }}%</div>
                    <div style="font-size:11px;color:oklch(45% 0.02 250);"><span class="dq-distrib-dot" style="background:oklch(55% 0.17 25);"></span>Bassa {{ $pctLow }}%</div>
                </div>
            </div>
        </div>

        <!-- Dettaglio per panel -->
        @if(!empty($panelStats))
        <div class="dq-panel-wrap">
            <div class="dq-panel-title">Dettaglio per panel</div>
            <table class="dq-table">
                <thead class="dq-thead">
                    <tr>
                        <th class="dq-th">Panel</th>
                        <th class="dq-th">Interviste</th>
                        <th class="dq-th">Score medio</th>
                        <th class="dq-th">Alta qualità</th>
                        <th class="dq-th">Accettabile</th>
                        <th class="dq-th">Bassa qualità</th>
                        <th class="dq-th">Durata mediana</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($panelStats as $pName => $ps)
                    <tr class="dq-row">
                        <td class="dq-td" style="font-weight:600;">{{ $pName }}</td>
                        <td class="dq-td" style="font-variant-numeric:tabular-nums;">{{ $ps['count'] }}</td>
                        <td class="dq-td" style="font-variant-numeric:tabular-nums;">{{ $ps['avgScore'] ?? '—' }}</td>
                        <td class="dq-td dq-text-green">{{ $ps['pctHigh'] }}%</td>
                        <td class="dq-td dq-text-amber">{{ $ps['pctAccept'] }}%</td>
                        <td class="dq-td dq-text-red">{{ $ps['pctLow'] }}%</td>
                        <td class="dq-td" style="font-variant-numeric:tabular-nums;">{{ $ps['loiMedianFmt'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </section>

    <!-- ============================================================
         2. LISTA INTERVISTE
         ============================================================ -->
    <section id="sez-interviste" class="dq-card dq-section" style="animation-delay:.08s;">
        <div class="dq-card-header dq-border-teal">
            <div class="dq-header-left">
                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="oklch(45% 0.12 190)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 6h13"/><path d="M8 12h13"/><path d="M8 18h13"/><path d="M3 6h.01"/><path d="M3 12h.01"/><path d="M3 18h.01"/></svg>
                <span class="dq-section-title">Valutazione singole interviste</span>
            </div>
            <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
                <input id="flt-iv-search" placeholder="Cerca ID / UID…" class="dq-filter-input">
                <select id="flt-iv-panel" class="dq-filter-select">
                    <option value="">Tutti i panel</option>
                    @foreach($uniquePanels as $p)
                    <option value="{{ strtolower($p) }}">{{ $p }}</option>
                    @endforeach
                </select>
                <select id="flt-iv-tier" class="dq-filter-select">
                    <option value="">Tutte le qualità</option>
                    <option value="alta">Alta qualità</option>
                    <option value="accettabile">Accettabile</option>
                    <option value="bassa">Bassa qualità</option>
                </select>
                <button class="dq-btn dq-btn-outline-teal" onclick="exportCsv('tbl-interviews', ['ID','UID','Panel','Score','Stato'])">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Esporta CSV
                </button>
            </div>
        </div>

        @if(count($completeInterviews) > 0)
        <div class="dq-table-scroll">
            <table id="tbl-interviews" class="dq-table" style="min-width:820px;">
                <thead class="dq-thead">
                    <tr>
                        <th class="dq-th">ID</th>
                        <th class="dq-th">UID</th>
                        <th class="dq-th">Panel</th>
                        <th class="dq-th">Score</th>
                        <th class="dq-th">Stato</th>
                        <th class="dq-th" style="width:40px;text-align:center;">Info</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($completeInterviews as $interview)
                @php
                    $ivSc    = $interview['score'] !== null ? (int) $interview['score'] : null;
                    $ivTier  = $ivSc === null ? 'na' : ($ivSc >= 70 ? 'alta' : ($ivSc >= 50 ? 'accettabile' : 'bassa'));
                    $ivBadge = $ivTier === 'alta' ? 'dq-badge-high'
                             : ($ivTier === 'accettabile' ? 'dq-badge-accept'
                             : ($ivTier === 'bassa' ? 'dq-badge-low' : 'dq-badge-unknown'));
                    $ivStatoCls = $ivTier === 'alta' ? 'dq-stato-high'
                                : ($ivTier === 'accettabile' ? 'dq-stato-accept'
                                : ($ivTier === 'bassa' ? 'dq-stato-low' : 'dq-stato-unknown'));
                    $ivCapApplied    = !empty($interview['quality_score_cap']['applied']);
                    $ivCapBaseScore  = $interview['quality_score_caps']['base_score'] ?? null;
                    $ivCovLabel   = $interview['quality_coverage']['label'] ?? 'Non valutabile';
                    $mot          = $interview['quality_motivation'] ?? [];
                    $ivMotData    = array_values(array_filter([
                        ($mot['open']  ?? null) !== null ? ['label'=>'Open',  'text'=>$mot['open']]  : null,
                        ($mot['scale'] ?? null) !== null ? ['label'=>'Scale', 'text'=>$mot['scale']] : null,
                        ($mot['loi']   ?? null) !== null ? ['label'=>'LOI',   'text'=>$mot['loi']]   : null,
                        $ivCapApplied            ? ['label'=>'Cap',   'text'=>'Ridotto da ' . $ivCapBaseScore . ' per anomalia rilevata'] : null,
                    ]));
                    $ivPanelLow = strtolower($interview['panel'] ?? '');
                @endphp
                <tr class="dq-row"
                    data-iid="{{ $interview['iid'] }}"
                    data-uid="{{ $interview['uid'] }}"
                    data-panel="{{ $ivPanelLow }}"
                    data-tier="{{ $ivTier }}">
                    <td class="dq-td" style="font-weight:600;">{{ $interview['iid'] }}</td>
                    <td class="dq-td dq-td-mono">{{ $interview['uid'] }}</td>
                    <td class="dq-td dq-td-panel">{{ $interview['panel'] ?? '—' }}</td>
                    <td class="dq-td">
                        @if($ivSc !== null)
                            <div class="dq-badge {{ $ivBadge }}">
                                {{ $ivSc }}<span class="dq-badge-denom">/100</span>
                            </div>
                        @else
                            <span style="color:oklch(55% 0.02 250);font-size:13px;">—</span>
                        @endif
                    </td>
                    <td class="dq-td">
                        <div class="{{ $ivStatoCls }}">{{ strtoupper($interview['rating_label'] ?? 'N/D') }}</div>
                        <div class="dq-coverage-sub">{{ $ivCovLabel }}</div>
                    </td>
                    <td class="dq-td" style="text-align:center;vertical-align:middle;padding:0 12px;">
                        <button type="button" class="dq-info-btn"
                            data-bs-toggle="popover"
                            data-bs-trigger="hover focus"
                            data-bs-placement="left"
                            data-fq-mot="{{ json_encode($ivMotData) }}">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                        </button>
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="dq-table-footer" id="iv-count">{{ count($completeInterviews) }} risultati</div>
        @else
        <div style="padding:24px;color:oklch(55% 0.02 250);font-style:italic;">Nessuna intervista completa trovata.</div>
        @endif
    </section>

    <!-- ============================================================
         3. CONTROLLO DOMANDE APERTE
         ============================================================ -->
    <section id="sez-open" class="dq-card dq-section" style="animation-delay:.14s;">
        <div class="dq-card-header dq-border-red">
            <div class="dq-header-left">
                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="oklch(45% 0.14 25)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4"/><path d="M12 17h.01"/><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/></svg>
                <div>
                    <div class="dq-section-title">Controllo domande aperte</div>
                    <div style="font-size:12px;color:oklch(45% 0.02 250);margin-top:2px;">Risposte segnalate per probabilità di risposta non autentica</div>
                </div>
            </div>
            <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
                <input id="flt-open-search" placeholder="Cerca ID / UID…" class="dq-filter-input">
                <select id="flt-open-panel" class="dq-filter-select">
                    <option value="">Tutti i panel</option>
                    @foreach($uniquePanels as $p)
                    <option value="{{ strtolower($p) }}">{{ $p }}</option>
                    @endforeach
                </select>
                <button id="flt-open-all" class="dq-btn dq-btn-outline">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    Mostra tutte
                </button>
                <button class="dq-btn dq-btn-outline-red" onclick="exportCsv('tbl-open', ['ID','UID','Panel','Codice','Risposta','Fake%'])">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Esporta CSV
                </button>
            </div>
        </div>

        @if(count($allOpenRows) > 0)
        <div class="dq-table-scroll">
            <table id="tbl-open" class="dq-table" style="min-width:820px;">
                <thead class="dq-thead">
                    <tr>
                        <th class="dq-th">ID</th>
                        <th class="dq-th">UID</th>
                        <th class="dq-th">Panel</th>
                        <th class="dq-th">Codice</th>
                        <th class="dq-th">Risposta</th>
                        <th class="dq-th" style="text-align:right;">Fake %</th>
                        <th class="dq-th" style="width:32px;"></th>
                    </tr>
                </thead>
                <tbody>
                @foreach($allOpenRows as $index => $open)
                @php
                    $modalId     = "modalOpen_{$open['iid']}_{$index}";
                    $iidStats    = $openFakeByIid[$open['iid']] ?? null;
                    $iFake       = $iidStats['fake']  ?? 0;
                    $iTotal      = $iidStats['total'] ?? 0;
                    $iPct        = $iTotal > 0 ? round($iFake / $iTotal * 100) : 0;
                    $fakeCls     = $iPct >= 50 ? 'dq-fake-high' : 'dq-fake-low';
                    $openPanelLow = strtolower($open['panel'] ?? '');
                    $isRowFake   = !empty($open['isFake']);
                @endphp
                <tr class="dq-row"
                    data-iid="{{ $open['iid'] }}"
                    data-uid="{{ $open['uid'] }}"
                    data-panel="{{ $openPanelLow }}"
                    data-fake="{{ $isRowFake ? '1' : '0' }}"
                    @if(!$isRowFake) style="display:none;" @endif>
                    <td class="dq-td" style="font-weight:600;padding:12px 16px;">{{ $open['iid'] }}</td>
                    <td class="dq-td dq-td-mono" style="padding:12px 16px;">{{ $open['uid'] }}</td>
                    <td class="dq-td" style="color:oklch(40% 0.02 250);padding:12px 16px;">{{ $open['panel'] }}</td>
                    <td class="dq-td" style="padding:12px 16px;">
                        <span class="fq-codice-pop"
                              data-codice="{{ $open['codice'] }}"
                              data-qtext="{{ $open['tooltip'] }}"
                              style="font-family:'SF Mono',Consolas,monospace;font-size:12px;color:oklch(45% 0.02 250);cursor:pointer;text-decoration:underline dotted #aaa;text-underline-offset:3px;">
                            {{ $open['codice'] }}
                        </span>
                    </td>
                    <td class="dq-td" style="padding:12px 16px;">
                        <span class="dq-response-cell">{{ $open['openResponse'] }}</span>
                    </td>
                    <td class="dq-td" style="text-align:right;padding:12px 16px;">
                        <span class="dq-fake-badge {{ $fakeCls }}">{{ $iPct }}%</span>
                    </td>
                    <td class="dq-td" style="padding:12px 16px;">
                        <button class="dq-info-btn" data-bs-toggle="modal" data-bs-target="#{{ $modalId }}"
                                style="color:oklch(50% 0.08 80);">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                        </button>
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="dq-table-footer">
            <span id="open-count-label">{{ count($fakeOpenRows) }} sospette</span>
            <span style="color:oklch(65% 0.02 250);margin:0 6px;">/</span>
            <span style="color:oklch(55% 0.02 250);">{{ $openTotalCount }} totali</span>
            @if($openTotalCount > count($allOpenRows))
                <span style="color:oklch(55% 0.02 250);font-size:.78em;margin-left:.4rem;">(visualizzate {{ count($allOpenRows) }})</span>
            @endif
        </div>
        @else
        <div style="padding:24px;color:oklch(55% 0.02 250);font-style:italic;">Nessuna risposta aperta disponibile.</div>
        @endif
    </section>

    <!-- ============================================================
         4+5. LOI + GRIGLIA — affiancate
         ============================================================ -->
    <div class="dq-side-by-side">

        <section id="sez-loi" class="dq-card dq-section" style="animation-delay:.20s;">
            <div class="dq-card-header dq-border-amber">
                <div class="dq-header-left">
                    <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="oklch(45% 0.12 80)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                    <div>
                        <div class="dq-section-title">Controllo LOI</div>
                        <div style="font-size:12px;color:oklch(45% 0.02 250);margin-top:2px;">
                            Durata mediana: <strong>{{ $loiObsMedianFmt }}</strong>
                            &nbsp;·&nbsp;
                            Riferimento LOI normalizzato: <strong>{{ $loiMediaFormatted }}</strong>
                        </div>
                    </div>
                </div>
                <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                    <input id="flt-loi-search" placeholder="Cerca ID…" class="dq-filter-input" style="width:130px;">
                    <button id="flt-loi-btn" class="dq-btn dq-btn-outline" style="font-size:12px;padding:6px 10px;">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                        &lt;50% mediana
                    </button>
                    <button id="flt-loi-sort" class="dq-btn dq-btn-amber" style="font-size:12px;padding:6px 10px;">
                        LOI &darr;
                    </button>
                </div>
            </div>

            @if(count($loiData) > 0)
            <div class="dq-table-scroll">
                <table id="tbl-loi" class="dq-table">
                    <thead class="dq-thead">
                        <tr>
                            <th class="dq-th">ID</th>
                            <th class="dq-th">UID</th>
                            <th class="dq-th">LOI</th>
                            <th class="dq-th" title="Passaggi effettuati dall'intervistato / riferimento P95 dello studio (include schermate di esposizione stimolo).">Passaggi / rif.</th>
                            <th class="dq-th">Valutazione LOI</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($loiData as $item)
                    @php
                        $lIid        = $item['iid'];
                        $lRatio      = $loiRatioByIid[$lIid]  ?? null;
                        $lSec        = $loiSecByIid[$lIid]    ?? 0;
                        $lCrit       = $loiCriteriaByIid[$lIid] ?? [];
                        $lEval       = $lCrit['evaluation']       ?? 'not_evaluable';
                        $lEvalLabel  = $lCrit['evaluation_label'] ?? 'Non valutabile';
                        $lQAnswered  = $item['questionsAnswered'] ?? null;
                        $lQCount     = $lQAnswered !== null
                            ? $lQAnswered . ' / ' . ($loiSurveyRefQCount ?? '?')
                            : '—';
                        $lExpSec     = $lCrit['expected_seconds']   ?? null;
                        $lRefFull    = $loiSurveyRefFullSec          ?? null;
                        $lPct        = $lRatio !== null ? number_format($lRatio * 100, 1, ',', '.') . '%' : '—';
                        $lUnavail    = $lCrit['unavailable_reason']  ?? null;
                        $lAbsMin     = !empty($lCrit['absolute_minimum_triggered']);
                        $lCovLabel   = $loiSurveyCoverage !== null ? ((int)($loiSurveyCoverage * 100)) . '%' : '—';
                        $lRefTypeLabel = ($loiSurveyRefType === 'normalized_cohort_90') ? 'coorte 90%'
                                       : (($loiSurveyRefType === 'normalized_cohort_80') ? 'coorte 80%' : '—');
                        /* Costruisce il JSON dei dati per il popover LOI */
                        $lLoiInfo = json_encode([
                            'actualSec'   => $lSec,
                            'actualFmt'   => $fmtLoi($lSec),
                            'q'           => $lQAnswered,
                            'refQ'        => $loiSurveyRefQCount,
                            'expSec'      => $lExpSec,
                            'expFmt'      => ($lExpSec !== null && $lExpSec > 0) ? $fmtLoi((int)round($lExpSec)) : null,
                            'refFullSec'  => $lRefFull,
                            'refFullFmt'  => ($lRefFull !== null && $lRefFull > 0) ? $fmtLoi((int)round($lRefFull)) : null,
                            'pct'         => $lPct,
                            'refType'     => $loiSurveyRefType,
                            'refTypeLabel'=> $lRefTypeLabel,
                            'sampleSize'  => $loiSurveySampleSize,
                            'excluded'    => $loiSurveyExcluded,
                            'eval'        => $lEval,
                            'evalLabel'   => $lEvalLabel,
                            'unavail'     => $lUnavail,
                            'absMin'      => $lAbsMin,
                        ], JSON_UNESCAPED_UNICODE);
                    @endphp
                    <tr class="dq-row"
                        data-iid="{{ $lIid }}"
                        data-uid="{{ $item['uid'] }}"
                        data-ratio="{{ $lRatio ?? '' }}"
                        data-loi-sec="{{ $lSec }}">
                        <td class="dq-td" style="font-weight:600;padding:10px 14px;">{{ $lIid }}</td>
                        <td class="dq-td dq-td-mono" style="padding:10px 14px;">{{ $item['uid'] }}</td>
                        <td class="dq-td" style="font-variant-numeric:tabular-nums;padding:10px 14px;">{{ $item['loi'] }}</td>
                        <td class="dq-td" style="font-variant-numeric:tabular-nums;padding:10px 14px;color:oklch(50% 0.02 250);">{{ $lQCount }}</td>
                        <td class="dq-td" style="padding:10px 14px;">
                            @if($lEval === 'ok')
                                <span class="badge bg-success dq-loi-badge" data-loi-info='{{ $lLoiInfo }}' style="cursor:pointer;font-size:11px;">{{ $lEvalLabel }}</span>
                            @elseif($lEval === 'suspicious')
                                <span class="badge bg-warning text-dark dq-loi-badge" data-loi-info='{{ $lLoiInfo }}' style="cursor:pointer;font-size:11px;">{{ $lEvalLabel }}</span>
                            @elseif($lEval === 'verify')
                                <span class="badge bg-danger dq-loi-badge" data-loi-info='{{ $lLoiInfo }}' style="cursor:pointer;font-size:11px;">{{ $lEvalLabel }}</span>
                            @else
                                <span class="badge bg-secondary dq-loi-badge" data-loi-info='{{ $lLoiInfo }}' style="cursor:pointer;font-size:11px;">{{ $lEvalLabel }}</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div style="padding:24px;color:oklch(55% 0.02 250);font-style:italic;">Nessun dato LOI disponibile.</div>
            @endif
        </section>

        <section id="sez-griglia" class="dq-card dq-section" style="animation-delay:.26s;">
            <div class="dq-card-header dq-border-green">
                <div class="dq-header-left">
                    <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="oklch(45% 0.12 145)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                    <span class="dq-section-title">Qualità griglie</span>
                </div>
                <input id="flt-scale-search" placeholder="Cerca ID…" class="dq-filter-input" style="width:130px;">
            </div>

            @if(count($scaleData) > 0)
            <div class="dq-table-scroll">
                <table id="tbl-scale" class="dq-table">
                    <thead class="dq-thead">
                        <tr>
                            <th class="dq-th">ID</th>
                            <th class="dq-th">UID</th>
                            <th class="dq-th">Domanda</th>
                            <th class="dq-th">Qualità</th>
                            <th class="dq-th" style="width:36px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($scaleData as $scale)
                    @php
                        $sqLabel    = ($scale['code'] !== 'unknown') ? 'Dom. ' . $scale['code'] : 'Dom. ' . $scale['questionId'];
                        $sqDetail   = $scaleQsByIidQid[$scale['iid']][$scale['questionId']] ?? null;
                        $sqLevel    = $sqDetail['level']   ?? null;
                        $sqReasons  = $sqDetail['reasons'] ?? [];
                        $sqAnomaly  = in_array($sqLevel, ['Sospetta', 'Da Verificare']);
                        $sqBadgeCls = $sqLevel === 'Normale'        ? 'dq-badge-scale-normale'
                                    : ($sqLevel === 'Sospetta'      ? 'dq-badge-scale-sospetta'
                                    : ($sqLevel === 'Da Verificare' ? 'dq-badge-scale-daverif' : ''));
                    @endphp
                    <tr class="dq-row" data-iid="{{ $scale['iid'] }}" data-uid="{{ $scale['uid'] }}">
                        <td class="dq-td" style="font-weight:600;padding:10px 14px;">{{ $scale['iid'] }}</td>
                        <td class="dq-td dq-td-mono" style="padding:10px 14px;">{{ $scale['uid'] }}</td>
                        <td class="dq-td" style="padding:10px 14px;">
                            <span class="fq-codice-pop"
                                  data-codice="{{ $sqLabel }}"
                                  data-qtext="{{ $scale['tooltip'] }}"
                                  data-qid="{{ $scale['questionId'] }}"
                                  style="cursor:pointer;text-decoration:underline dotted #aaa;text-underline-offset:3px;font-size:13px;">
                                {{ $sqLabel }}
                            </span>
                        </td>
                        <td class="dq-td" style="padding:10px 14px;">
                            @if($sqLevel !== null)
                                <span class="{{ $sqBadgeCls }}">{{ $sqLevel }}</span>
                            @else
                                <span style="color:oklch(55% 0.02 250);font-size:12px;">N/D</span>
                            @endif
                        </td>
                        <td class="dq-td" style="padding:10px 14px;text-align:center;">
                            @if($sqAnomaly && !empty($sqReasons))
                            <button type="button" class="dq-scale-info-btn dq-info-btn"
                                data-fq-reasons="{{ json_encode($sqReasons) }}">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                            </button>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div style="padding:24px;color:oklch(55% 0.02 250);font-style:italic;">Nessuna griglia da visualizzare.</div>
            @endif
        </section>

    </div><!-- /dq-side-by-side -->

</div><!-- /dq-sections -->

</div><!-- /container field-control-container -->

{{-- Modali risposta aperta — fuori da ogni card/transform/overflow --}}
@foreach($allOpenRows as $index => $open)
@php
    $modalId = "modalOpen_{$open['iid']}_{$index}";
    $mCat    = $open['category'] ?? 'weak';
    $mReason = $open['reason']   ?? '—';
    $mBadge  = $mCat === 'strong' ? 'danger' : ($mCat === 'medium' ? 'warning text-dark' : 'secondary');
    $mLabel  = $mCat === 'strong' ? 'Forte'  : ($mCat === 'medium' ? 'Medio' : 'Debole');
@endphp
<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}Label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="{{ $modalId }}Label">Risposta Aperta — IID {{ $open['iid'] }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><strong>Testo:</strong></p>
                <p>{{ $open['openResponse'] }}</p>
                @if(!empty($open['isFake']) && $open['isFake'] === true)
                <div class="alert alert-light border py-2 px-3 mb-2" style="font-size:0.85em">
                    <strong>Classificazione:</strong>
                    <span class="badge bg-{{ $mBadge }} ms-1">{{ $mLabel }}</span>
                    &nbsp;<span class="text-muted">{{ $mReason }}</span>
                </div>
                @endif
                <hr/>
                <div class="d-grid gap-2">
                    <button class="btn" style="background-color:white;color:black;border:1px solid #ccc"
                        onclick="addToWhiteList('{{ $open['openResponse'] }}')">
                        Aggiungi a Whitelist
                    </button>
                    <button class="btn" style="background-color:black;color:white;border:1px solid #000"
                        onclick="addToBlackList('{{ $open['openResponse'] }}')">
                        Aggiungi a Blacklist
                    </button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
            </div>
        </div>
    </div>
</div>
@endforeach

@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    /* ---- Dropdown navbar (workaround AdminKit app.js conflict) ---- */
    (function initDropdowns() {
        var toggles = document.querySelectorAll('.custom-navbar [data-bs-toggle="dropdown"]');
        toggles.forEach(function (toggleEl) {
            var instance = bootstrap.Dropdown.getOrCreateInstance(toggleEl);
            toggleEl.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                toggles.forEach(function (other) {
                    if (other !== toggleEl) {
                        var otherInst = bootstrap.Dropdown.getInstance(other);
                        if (otherInst) otherInst.hide();
                    }
                });
                var menu = toggleEl.nextElementSibling;
                if (menu && menu.classList.contains('show')) {
                    instance.hide();
                } else {
                    instance.show();
                }
            });
        });
        document.addEventListener('click', function (event) {
            if (!event.target.closest('.custom-navbar .dropdown')) {
                toggles.forEach(function (toggleEl) {
                    var inst = bootstrap.Dropdown.getInstance(toggleEl);
                    if (inst) inst.hide();
                });
            }
        });
    })();

    /* ---- Tooltip semplici ---- */
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
        new bootstrap.Tooltip(el, { trigger: 'hover focus' });
    });

    /* ---- Popover card Durate LOI ---- */
    var _loiPopContent = {
        obs: {
            title: 'Mediana osservata',
            body: '<div style="padding:6px 2px;font-size:12px;line-height:1.6;">'
                + 'Il <strong>tempo tipico reale</strong> impiegato dai rispondenti per completare il questionario.<br><br>'
                + 'Include tutti i completati, anche chi ha seguito percorsi più corti (meno domande), '
                + 'quindi può risultare più bassa del riferimento.'
                + '</div>',
        },
        ref: {
            title: 'Riferimento normalizzato',
            body: '<div style="padding:6px 2px;font-size:12px;line-height:1.6;">'
                + 'Il <strong>tempo atteso</strong> stimato per chi risponde a tutte le domande del questionario.<br><br>'
                + 'Ogni durata viene corretta in base alla lunghezza del percorso seguito, '
                + 'poi si calcola la mediana di questi valori corretti. '
                + 'È il valore usato per giudicare se un\'intervista è troppo veloce.<br><br>'
                + '<em style="color:#888;">Se i due valori divergono, è normale: '
                + 'indica che c\'è routing e molti rispondenti vedono solo una parte delle domande.</em>'
                + '</div>',
        },
    };
    document.querySelectorAll('.dq-loi-info-pop').forEach(function (el) {
        var key = el.dataset.loiKey;
        var cfg = _loiPopContent[key];
        if (!cfg) return;
        new bootstrap.Popover(el, {
            html: true,
            trigger: 'hover focus',
            placement: 'top',
            sanitize: false,
            title: '<span style="font-size:.8rem;font-weight:600;">' + cfg.title + '</span>',
            content: cfg.body,
        });
    });

    /* ---- Popover codice domanda ---- */
    document.querySelectorAll('.fq-codice-pop').forEach(function (el) {
        new bootstrap.Popover(el, {
            html: true,
            trigger: 'hover focus',
            placement: 'auto',
            sanitize: false,
            title: '<span style="font-size:.8rem;font-weight:600">' + el.dataset.codice + '</span>'
                 + (el.dataset.qid ? '<span style="font-size:.75rem;color:#888;font-weight:400;margin-left:6px">#' + el.dataset.qid + '</span>' : ''),
            content: '<span style="font-size:.78rem;color:#555">' + el.dataset.qtext + '</span>',
        });
    });

    /* ---- Popover motivazioni interviste (hover) ---- */
    document.querySelectorAll('.dq-info-btn:not(.dq-scale-info-btn)').forEach(function (el) {
        var items = [];
        try { items = JSON.parse(el.dataset.fqMot || '[]'); } catch(e) {}

        var labelColor = { Open:'oklch(45% 0.12 255)', Scale:'oklch(45% 0.12 145)', LOI:'oklch(45% 0.12 80)', Cap:'oklch(45% 0.16 25)' };

        var rows = items.map(function(item) {
            var esc = (item.text || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
            var col = labelColor[item.label] || 'oklch(50% 0.02 250)';
            return '<div style="display:flex;gap:10px;align-items:baseline;padding:5px 0;border-bottom:1px solid oklch(93% 0.006 250);">'
                 + '<span style="font-size:10px;font-weight:700;color:' + col + ';text-transform:uppercase;min-width:38px;flex-shrink:0;">' + item.label + '</span>'
                 + '<span style="font-size:12px;color:oklch(28% 0.02 250);line-height:1.5;">' + esc + '</span>'
                 + '</div>';
        }).join('');

        var content = rows
            ? '<div style="min-width:260px;max-width:340px;padding:2px 0;">' + rows + '</div>'
            : '<span style="font-size:12px;color:oklch(55% 0.02 250);font-style:italic;">Nessun criterio disponibile</span>';

        new bootstrap.Popover(el, {
            html: true,
            content: content,
            trigger: 'hover focus',
            placement: 'left',
            title: '<span style="font-size:12px;font-weight:700;color:oklch(28% 0.02 250);">Motivazioni</span>',
            sanitize: false
        });
    });

    /* ---- Popover valutazione LOI ---- */
    document.querySelectorAll('.dq-loi-badge').forEach(function (el) {
        var d = {};
        try { d = JSON.parse(el.dataset.loiInfo || '{}'); } catch(e) {}

        function fmtMotivation(eval, pct, unavail, absMin) {
            if (absMin) {
                return 'Completata in meno di 60 secondi (minimo assoluto).';
            }
            var unavailMap = {
                'insufficient_reference_sample': 'Campione di riferimento insufficiente.',
                'invalid_question_count':        'Numero passaggi non disponibile.',
                'invalid_loi':                   'Durata non valida.',
                'invalid_reference':             'Riferimento non calcolabile.',
                'excessively_slow':              'Durata superiore a 1,67× il riferimento (eccessivamente lenta).',
            };
            if (unavail) { return unavailMap[unavail] || unavail; }
            var p = (pct && pct !== '—') ? pct : null;
            if (eval === 'ok')         { return p ? 'Ha impiegato il ' + p + ' del tempo atteso; soglia OK ≥70%.' : 'OK.'; }
            if (eval === 'suspicious') { return p ? 'Ha impiegato il ' + p + ' del tempo atteso; soglia Sospetta ≥50% e <70%.' : 'Sospetta.'; }
            if (eval === 'verify')     { return p ? 'Ha impiegato il ' + p + ' del tempo atteso; soglia Da verificare <50%.' : 'Da verificare.'; }
            return p ? 'Ha impiegato il ' + p + ' del tempo atteso.' : '—';
        }

        var rows = [];
        if (d.actualFmt)   { rows.push(['LOI effettiva', d.actualFmt]); }
        if (d.q !== null && d.refQ !== null) {
            rows.push(['Passaggi effettuati', d.q + ' / ' + d.refQ + ' (rif.)']);
        }
        if (d.expFmt)      { rows.push(['LOI attesa', d.expFmt]); }
        if (d.pct && d.pct !== '—') { rows.push(['% del tempo atteso', d.pct]); }
        if (d.refFullFmt)  { rows.push(['Riferimento normalizzato', d.refFullFmt]); }
        if (d.sampleSize)  { rows.push(['Numerosità coorte', d.sampleSize]); }
        if (d.refTypeLabel && d.refTypeLabel !== '—') { rows.push(['Coorte', d.refTypeLabel]); }

        rows.push(['Motivazione', fmtMotivation(d.eval, d.pct, d.unavail, d.absMin)]);

        var html = rows.map(function(r) {
            return '<div style="display:flex;gap:8px;padding:3px 0;border-bottom:1px solid oklch(93% 0.006 250);">'
                + '<span style="font-size:11px;color:oklch(50% 0.02 250);min-width:130px;flex-shrink:0;">' + r[0] + '</span>'
                + '<span style="font-size:11px;color:oklch(22% 0.02 250);font-weight:500;">' + r[1] + '</span>'
                + '</div>';
        }).join('');
        var content = '<div style="min-width:260px;max-width:340px;padding:2px 0;">' + html + '</div>';

        new bootstrap.Popover(el, {
            html:      true,
            content:   content,
            trigger:   'hover focus',
            placement: 'left',
            title:     '<span style="font-size:12px;font-weight:700;color:oklch(28% 0.02 250);">Dettaglio LOI</span>',
            sanitize:  false,
        });
    });

    /* ---- Popover motivi griglia (Sospetta / Da Verificare) ---- */
    document.querySelectorAll('.dq-scale-info-btn').forEach(function (el) {
        var reasons = [];
        try { reasons = JSON.parse(el.dataset.fqReasons || '[]'); } catch(e) {}
        var rows = reasons.map(function(r) {
            var esc = (r || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
            return '<div style="padding:4px 0;border-bottom:1px solid oklch(93% 0.006 250);font-size:12px;color:oklch(28% 0.02 250);line-height:1.5;">'
                 + '&bull; ' + esc + '</div>';
        }).join('');
        var content = rows
            ? '<div style="min-width:220px;max-width:300px;padding:2px 0;">' + rows + '</div>'
            : '<span style="font-size:12px;color:oklch(55% 0.02 250);font-style:italic;">Nessun dettaglio</span>';
        new bootstrap.Popover(el, {
            html: true,
            content: content,
            trigger: 'hover focus',
            placement: 'left',
            title: '<span style="font-size:12px;font-weight:700;color:oklch(28% 0.02 250);">Anomalie rilevate</span>',
            sanitize: false
        });
    });

    /* ================================================================
       FILTRI
       ================================================================ */

    function filterRows(tableId, testFn) {
        var visible = 0;
        document.querySelectorAll('#' + tableId + ' tbody tr:not(.modal)').forEach(function (row) {
            if (row.classList.contains('modal') || row.closest('.modal')) return;
            var show = testFn(row);
            row.style.display = show ? '' : 'none';
            if (show) visible++;
        });
        return visible;
    }

    function iidUidMatch(row, q) {
        if (!q) return true;
        return (row.dataset.iid || '').toLowerCase().includes(q)
            || (row.dataset.uid || '').toLowerCase().includes(q);
    }

    /* -- Lista interviste -- */
    var fltIvSearch = document.getElementById('flt-iv-search');
    var fltIvPanel  = document.getElementById('flt-iv-panel');
    var fltIvTier   = document.getElementById('flt-iv-tier');
    var ivCount     = document.getElementById('iv-count');

    function applyIvFilter() {
        var q = (fltIvSearch ? fltIvSearch.value.trim().toLowerCase() : '');
        var p = (fltIvPanel  ? fltIvPanel.value.toLowerCase()  : '');
        var t = (fltIvTier   ? fltIvTier.value.toLowerCase()   : '');
        var n = filterRows('tbl-interviews', function (row) {
            return iidUidMatch(row, q)
                && (!p || (row.dataset.panel || '') === p)
                && (!t || (row.dataset.tier  || '') === t);
        });
        if (ivCount) ivCount.textContent = n + ' risultati';
    }
    if (fltIvSearch) fltIvSearch.addEventListener('input', applyIvFilter);
    if (fltIvPanel)  fltIvPanel.addEventListener('change', applyIvFilter);
    if (fltIvTier)   fltIvTier.addEventListener('change', applyIvFilter);

    /* -- LOI: filtro sotto-soglia + ricerca + ordinamento -- */
    var loiActive = false;
    var loiSortDir = 'desc';
    var loiBtn     = document.getElementById('flt-loi-btn');
    var loiSort    = document.getElementById('flt-loi-sort');
    var loiSearch  = document.getElementById('flt-loi-search');

    function applyLoiFilter() {
        var q = (loiSearch ? loiSearch.value.trim().toLowerCase() : '');
        filterRows('tbl-loi', function (row) {
            return iidUidMatch(row, q)
                && (!loiActive || (parseFloat(row.dataset.ratio) < 0.5));
        });
    }
    function sortLoi() {
        var tbody = document.querySelector('#tbl-loi tbody');
        if (!tbody) return;
        var rows = Array.from(tbody.querySelectorAll('tr'));
        rows.sort(function (a, b) {
            var sa = parseInt(a.dataset.loiSec) || 0;
            var sb = parseInt(b.dataset.loiSec) || 0;
            return loiSortDir === 'desc' ? sb - sa : sa - sb;
        });
        rows.forEach(function (r) { tbody.appendChild(r); });
    }
    if (loiBtn) {
        loiBtn.addEventListener('click', function () {
            loiActive = !loiActive;
            loiBtn.style.background    = loiActive ? 'oklch(55% 0.10 80)' : '';
            loiBtn.style.color         = loiActive ? '#fff' : '';
            loiBtn.style.borderColor   = loiActive ? 'oklch(55% 0.10 80)' : '';
            applyLoiFilter();
        });
    }
    if (loiSort) {
        loiSort.addEventListener('click', function () {
            loiSortDir = loiSortDir === 'desc' ? 'asc' : 'desc';
            loiSort.textContent = 'Ordina per LOI ' + (loiSortDir === 'desc' ? '↓' : '↑');
            sortLoi();
        });
    }
    if (loiSearch) loiSearch.addEventListener('input', applyLoiFilter);

    /* -- Domande aperte: toggle tutte / solo sospette -- */
    var fltOpenAll  = document.getElementById('flt-open-all');
    var openShowAll = false;
    var fltOpenSearch = document.getElementById('flt-open-search');
    function applyOpenVisibility() {
        var p = fltOpenPanel ? fltOpenPanel.value.toLowerCase() : '';
        var q = fltOpenSearch ? fltOpenSearch.value.toLowerCase() : '';
        var rows = document.querySelectorAll('#tbl-open tbody tr');
        var visible = 0;
        rows.forEach(function(row) {
            var isFake   = row.dataset.fake === '1';
            var panelOk  = !p || (row.dataset.panel || '') === p;
            var searchOk = !q || (row.dataset.iid || '').toLowerCase().includes(q)
                               || (row.dataset.uid || '').toLowerCase().includes(q);
            var show     = (openShowAll || isFake) && panelOk && searchOk;
            row.style.display = show ? '' : 'none';
            if (show) visible++;
        });
        var lbl = document.getElementById('open-count-label');
        if (lbl) lbl.textContent = visible + (openShowAll ? ' risposte' : ' sospette');
    }
    if (fltOpenAll) {
        fltOpenAll.addEventListener('click', function () {
            openShowAll = !openShowAll;
            fltOpenAll.innerHTML = openShowAll
                ? '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg> Solo sospette'
                : '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg> Mostra tutte';
            applyOpenVisibility();
        });
    }

    /* -- Domande aperte: filtro panel + ricerca -- */
    var fltOpenPanel = document.getElementById('flt-open-panel');
    if (fltOpenPanel) fltOpenPanel.addEventListener('change', applyOpenVisibility);
    if (fltOpenSearch) fltOpenSearch.addEventListener('input', applyOpenVisibility);

    /* -- Scale: ricerca -- */
    var fltScaleSearch = document.getElementById('flt-scale-search');
    if (fltScaleSearch) {
        fltScaleSearch.addEventListener('input', function () {
            var q = fltScaleSearch.value.trim().toLowerCase();
            filterRows('tbl-scale', function (row) { return iidUidMatch(row, q); });
        });
    }
});

/* ================================================================
   EXPORT CSV
   ================================================================ */
function exportCsv(tableId, headers) {
    var rows = [];
    rows.push(headers.join(','));
    document.querySelectorAll('#' + tableId + ' tbody tr').forEach(function (row) {
        if (row.style.display === 'none') return;
        if (row.closest('.modal')) return;
        var cells = row.querySelectorAll('td');
        var cols = [];
        cells.forEach(function (td) {
            var txt = td.innerText.replace(/\n/g,' ').replace(/,/g,' ').trim();
            cols.push('"' + txt + '"');
        });
        if (cols.length > 0) rows.push(cols.join(','));
    });
    var csv  = rows.join('\n');
    var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    var url  = URL.createObjectURL(blob);
    var a    = document.createElement('a');
    a.href   = url;
    a.download = tableId + '.csv';
    a.click();
    URL.revokeObjectURL(url);
}

/* ================================================================
   WHITELIST / BLACKLIST
   ================================================================ */
function addToWhiteList(responseText) {
    fetch("{{ route('fieldQuality.addToWhiteList') }}", {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({ text: responseText })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) { alert('Aggiunto alla whitelist!'); window.location.reload(); }
        else              { alert('Errore: ' + data.message); }
    })
    .catch(() => alert('Errore durante l\'aggiunta alla whitelist.'));
}

function addToBlackList(responseText) {
    fetch("{{ route('fieldQuality.addToBlackList') }}", {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({ text: responseText })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) { alert('Aggiunto alla blacklist!'); window.location.reload(); }
        else              { alert('Errore: ' + data.message); }
    })
    .catch(() => alert('Errore durante l\'aggiunta alla blacklist.'));
}
</script>

<style>
/* ================================================================
   DASHBOARD QUALITÀ — Design System dq-*
   ================================================================ */
body { font-family: 'Inter', system-ui, sans-serif; }

.dq-sections {
    display: flex;
    flex-direction: column;
    gap: 24px;
    padding-bottom: 48px;
}

/* Page header */
.dq-page-header {
    border-left: 4px solid oklch(55% 0.10 255);
    padding-left: 16px;
    margin-bottom: 4px;
}
.dq-page-header-inner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
}
.dq-page-title {
    font-size: 20px;
    font-weight: 800;
    color: oklch(22% 0.02 250);
    letter-spacing: -0.02em;
    margin: 0;
    line-height: 1.2;
}
.dq-page-sub {
    font-size: 12px;
    color: oklch(50% 0.02 250);
    margin: 4px 0 0;
}
.dq-page-badge {
    font-size: 12px;
    font-weight: 600;
    color: oklch(40% 0.10 255);
    background: oklch(95% 0.04 255);
    border: 1px solid oklch(85% 0.06 255);
    padding: 5px 14px;
    border-radius: 999px;
    white-space: nowrap;
}

/* Cards */
.dq-card {
    background: #fff;
    border: 1px solid oklch(92% 0.006 250);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 1px 3px oklch(20% 0.02 260 / 0.06), 0 1px 2px oklch(20% 0.02 260 / 0.04);
    transition: box-shadow .2s ease;
}
.dq-card:hover {
    box-shadow: 0 8px 24px -8px oklch(30% 0.05 260 / 0.18);
}

/* Card header */
.dq-card-header {
    padding: 18px 24px;
    border-bottom: 1px solid oklch(92% 0.006 250);
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
}
.dq-border-blue  { border-left: 4px solid oklch(55% 0.10 255); }
.dq-border-teal  { border-left: 4px solid oklch(55% 0.10 190); }
.dq-border-amber { border-left: 4px solid oklch(55% 0.10 80);  }
.dq-border-red   { border-left: 4px solid oklch(55% 0.10 25);  }
.dq-border-green { border-left: 4px solid oklch(55% 0.10 145); }

.dq-header-left    { display: flex; align-items: center; gap: 10px; }
.dq-section-title  { font-size: 16px; font-weight: 700; }
.dq-header-right   { font-size: 12px; color: oklch(45% 0.02 250); }

/* LOI + Griglia affiancate */
.dq-side-by-side { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; align-items: start; }
@media (max-width: 900px) { .dq-side-by-side { grid-template-columns: 1fr; } }

/* Stat grid */
.dq-stat-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1px;
    background: oklch(92% 0.006 250);
}
.dq-stat-cell   { background: #fff; padding: 22px 24px; }
.dq-stat-label  {
    display: flex; align-items: center; gap: 6px;
    color: oklch(45% 0.02 250);
    font-weight: 600; text-transform: uppercase;
    letter-spacing: 0.04em; font-size: 12px;
}
.dq-stat-value {
    font-size: 32px; font-weight: 800; margin-top: 8px;
    font-variant-numeric: tabular-nums;
}
.dq-progress-bar  { height: 6px; background: oklch(92% 0.006 250); border-radius: 3px; margin-top: 10px; overflow: hidden; }
.dq-progress-fill { height: 100%; border-radius: 3px; }
.dq-distrib-bar   { display: flex; height: 10px; border-radius: 5px; overflow: hidden; margin-top: 14px; }
.dq-distrib-legend { display: flex; gap: 12px; margin-top: 10px; flex-wrap: wrap; }
.dq-distrib-dot   { display: inline-block; width: 7px; height: 7px; border-radius: 50%; margin-right: 4px; }

/* Panel breakdown */
.dq-panel-wrap  { padding: 20px 24px 24px; }
.dq-panel-title { font-size: 13px; font-weight: 700; margin-bottom: 10px; color: oklch(35% 0.02 250); }

/* Tables */
.dq-table  { width: 100%; border-collapse: collapse; font-size: 13px; }
.dq-thead  { text-align: left; background: oklch(98% 0.004 250); border-bottom: 1px solid oklch(90% 0.006 250); }
.dq-th     { padding: 12px 16px; font-weight: 600; color: oklch(45% 0.02 250); }
.dq-table-scroll .dq-th {
    position: sticky; top: 0; z-index: 2;
    background: oklch(98% 0.004 250);
    box-shadow: 0 1px 0 oklch(90% 0.006 250);
}
.dq-row    { border-bottom: 1px solid oklch(94% 0.006 250); }
.dq-row:hover { background: oklch(97.5% 0.008 260); }
.dq-td        { padding: 14px 16px; vertical-align: top; }
.dq-td-mono   { font-family: 'SF Mono', Consolas, monospace; font-size: 12px; color: oklch(35% 0.02 250); }
.dq-td-panel  { color: oklch(40% 0.02 250); }

/* Score badges */
.dq-badge         { display: inline-flex; align-items: baseline; gap: 4px; padding: 4px 10px; border-radius: 7px; font-weight: 700; font-size: 13px; }
.dq-badge-high    { background: oklch(95% 0.05 150); color: oklch(40% 0.13 150); }
.dq-badge-accept  { background: oklch(95% 0.05 75);  color: oklch(42% 0.13 75);  }
.dq-badge-low     { background: oklch(95% 0.04 25);  color: oklch(45% 0.16 25);  }
.dq-badge-unknown { background: oklch(94% 0.006 250); color: oklch(50% 0.02 250); }
.dq-badge-denom   { font-size: 10px; font-weight: 600; opacity: 0.75; }

.dq-cap-pill {
    display: inline-flex; align-items: center; gap: 3px;
    background: oklch(95% 0.04 25); color: oklch(48% 0.16 25);
    font-size: 10px; font-weight: 700; padding: 2px 7px;
    border-radius: 5px; margin-top: 6px;
}

/* Stato label */
.dq-stato-high    { font-size: 12px; font-weight: 700; color: oklch(40% 0.13 150); }
.dq-stato-accept  { font-size: 12px; font-weight: 700; color: oklch(42% 0.13 75);  }
.dq-stato-low     { font-size: 12px; font-weight: 700; color: oklch(45% 0.16 25);  }
.dq-stato-unknown { font-size: 12px; font-weight: 700; color: oklch(50% 0.02 250); }
.dq-coverage-sub  { font-size: 11px; color: oklch(45% 0.02 250); margin-top: 2px; }

/* Motivazioni */
.dq-mot-list { margin: 0; padding-left: 16px; color: oklch(35% 0.02 250); font-size: 12.5px; line-height: 1.6; }

/* Filter inputs & buttons */
.dq-filter-input {
    border: 1px solid oklch(88% 0.006 250); border-radius: 8px;
    padding: 8px 12px; font-size: 13px; width: 200px;
    font-family: inherit; outline: none;
}
.dq-filter-input:focus { border-color: oklch(55% 0.10 255); }
.dq-filter-select {
    border: 1px solid oklch(88% 0.006 250); border-radius: 8px;
    padding: 8px 12px; font-size: 13px;
    font-family: inherit; color: oklch(30% 0.02 250); outline: none;
}
.dq-filter-select:focus { border-color: oklch(55% 0.10 255); }

.dq-btn {
    display: inline-flex; align-items: center; gap: 6px;
    border-radius: 8px; padding: 8px 14px;
    font-size: 13px; font-weight: 700;
    text-decoration: none; white-space: nowrap;
    cursor: pointer; font-family: inherit;
    transition: opacity .15s ease, transform .1s ease;
}
.dq-btn:hover  { opacity: 0.85; }
.dq-btn:active { transform: scale(0.97); }
.dq-btn-outline-teal { border: 1px solid oklch(55% 0.10 190); color: oklch(45% 0.10 190); background: none; }
.dq-btn-outline-red  { border: 1px solid oklch(55% 0.10 25);  color: oklch(45% 0.10 25);  background: none; }
.dq-btn-outline      { border: 1px solid oklch(88% 0.006 250); color: oklch(35% 0.02 250); background: none; }
.dq-btn-amber        { background: oklch(55% 0.10 80); color: #fff; border: none; }

/* Info/popover button */
.dq-info-btn {
    background: none; border: none; padding: 4px;
    color: oklch(65% 0.06 255); cursor: pointer;
    line-height: 1; border-radius: 4px;
    transition: color .15s, background .15s;
}
.dq-info-btn:hover { color: oklch(45% 0.12 255); background: oklch(95% 0.04 255); }

/* LOI sotto-soglia */
.dq-badge-sottosoglia {
    background: oklch(95% 0.05 75); color: oklch(48% 0.14 75);
    font-size: 11px; font-weight: 700; padding: 3px 9px; border-radius: 6px;
}
/* LOI non valutabile (troppo alta) */
.dq-badge-nonvalutabile {
    background: oklch(93% 0.01 250); color: oklch(50% 0.03 250);
    font-size: 11px; font-weight: 700; padding: 3px 9px; border-radius: 6px;
}

/* Scale level badges */
.dq-badge-scale-normale  { background: oklch(95% 0.04 150); color: oklch(45% 0.12 150); font-size: 11px; font-weight: 700; padding: 3px 9px; border-radius: 6px; }
.dq-badge-scale-sospetta { background: oklch(95% 0.05 75);  color: oklch(48% 0.12 75);  font-size: 11px; font-weight: 700; padding: 3px 9px; border-radius: 6px; }
.dq-badge-scale-daverif  { background: oklch(95% 0.04 25);  color: oklch(48% 0.12 25);  font-size: 11px; font-weight: 700; padding: 3px 9px; border-radius: 6px; }

/* Open fake badge */
.dq-fake-badge { font-size: 11px; font-weight: 700; padding: 3px 9px; border-radius: 6px; }
.dq-fake-low   { background: oklch(95% 0.05 75); color: oklch(48% 0.14 75); }
.dq-fake-high  { background: oklch(95% 0.04 25); color: oklch(48% 0.16 25); }

/* Risposta open evidenziata */
.dq-response-cell {
    display: inline-block;
    font-family: 'SF Mono', Consolas, monospace;
    font-size: 12px; background: oklch(97% 0.006 250);
    border-radius: 6px; color: oklch(30% 0.02 250);
    padding: 3px 8px; max-width: 300px;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}

/* Panel text colors */
.dq-text-green { color: oklch(45% 0.11 150); font-weight: 600; }
.dq-text-amber { color: oklch(48% 0.12 75);  font-weight: 600; }
.dq-text-red   { color: oklch(48% 0.15 25);  font-weight: 600; }

/* Table footer */
.dq-table-footer {
    padding: 12px 24px;
    border-top: 1px solid oklch(92% 0.006 250);
    font-size: 12px; color: oklch(45% 0.02 250);
}
.dq-table-scroll { overflow-x: auto; max-height: 420px; overflow-y: auto; }

/* Section fade-in */
.dq-section { animation: dq-fade .5s ease both; }
@keyframes dq-fade {
    from { opacity: 0; transform: translateY(6px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* Responsive */
@media (max-width: 900px) {
    .dq-stat-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 600px) {
    .dq-stat-grid { grid-template-columns: 1fr; }
}
</style>

@endsection
