@extends('layouts.main')

@section('head')
<link rel="stylesheet" href="{{ asset('css/fieldControl.css') }}">
@endsection

@section('content')




<div class="container field-control-container">

<!-- NAVBAR MODERNA CON MENU A TENDINA -->
<nav class="navbar custom-navbar mb-4">
    <div class="container-fluid d-flex align-items-center justify-content-start px-0">
        <!-- Brand a sinistra -->
        <a class="navbar-brand d-flex align-items-center" href="{{ url('fieldControl?prj='.$prj.'&sid='.$sid) }}">
            <span class="fc-brand-icon"><i class="fas fa-chart-bar"></i></span>
            <span>Status Field</span>
        </a>

        <div class="fc-navbar-sep d-none d-md-block"></div>

        <!-- Menu orizzontale con dropdown -->
        <ul class="nav custom-nav-links">
            <!-- Ricerche in corso -->
<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle" href="#" id="ongoingResearchDropdown" role="button"
       data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-tasks me-1"></i> Ricerche in corso
                </a>
                <ul class="dropdown-menu fc-research-dropdown" aria-labelledby="ongoingResearchDropdown">
                    @forelse($ricercheInCorso as $ricerca)
                        <li>
                            <a class="dropdown-item fc-nav-research-item"
                               href="{{ url('fieldControl?prj=' . $ricerca->prj . '&sid=' . $ricerca->sur_id) }}">
                                <span class="fc-nav-research-id"><span class="fc-nav-live-dot"></span>#{{ $ricerca->sur_id }}</span>
                                <span class="fc-nav-research-name">{{ $ricerca->description }}</span>
                            </a>
                        </li>
                    @empty
                        <li><span class="dropdown-item text-muted" style="font-size:.85rem">Nessuna ricerca attiva</span></li>
                    @endforelse
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="{{ url('surveys') }}" style="font-size:.82rem; color:#64748b"><i class="fas fa-list me-1"></i> Vedi tutte</a></li>
                </ul>
            </li>

            <!-- Imposta Target -->
            <li class="nav-item">
                <a class="nav-link"
                   href="{{ route('targetField.index', ['prj' => $prj, 'sid' => $sid]) }}">
                   <i class="fa-solid fa-arrows-down-to-people"></i> Imposta Target
                </a>
            </li>


            <!-- Controllo Qualità -->
            <li class="nav-item">
                <a class="nav-link" href="{{ route('fieldQuality.index', ['prj' => $prj, 'sid' => $sid]) }}">
                    <i class="fas fa-check-circle me-1"></i> Controllo Qualità
                </a>
            </li>

        </ul>
    </div>
</nav>
<!-- FINE NAVBAR -->

@php
    /*
    |--------------------------------------------------------------------------
    | VARIABILI DI SOLA VISUALIZZAZIONE (nessuna nuova query, nessuna nuova
    | regola di business: solo calcoli di formattazione già presenti prima,
    | riuniti qui per alimentare il nuovo layout).
    |--------------------------------------------------------------------------
    */
    $fmtPct = function ($value) {
        return rtrim(rtrim(number_format((float) $value, 1), '0'), '.');
    };

    // Formattazione numerica "italiana" (virgola decimale, punto migliaia)
    $fmtIt = function ($value, $decimals = 0) {
        return number_format((float) $value, $decimals, ',', '.');
    };

    $outcomeIcons = [
        'complete' => 'fas fa-circle-check',
        'non_target' => 'fas fa-bullseye',
        'over_quota' => 'fas fa-arrow-trend-up',
        'sospese' => 'fas fa-pause',
        'bloccate' => 'fas fa-lock',
        'bad_quality' => 'fas fa-flag',
    ];

    // Ricerca / avanzamento (già presente nella vecchia card "Ricerca")
    $goal = (int) ($panelData->goal ?? 0);
    $complete = (int) ($counts['complete'] ?? 0);
    $pct = ($goal > 0) ? min(100, round(($complete / $goal) * 100)) : 0;
    $missingInterviste = $goal > 0 ? max(0, $goal - $complete) : null;

    // Bandiera paese per l'header (solo presentazione, mapping sui nomi più comuni)
    $countryFlags = [
        'italia' => '🇮🇹', 'francia' => '🇫🇷', 'germania' => '🇩🇪', 'spagna' => '🇪🇸',
        'regno unito' => '🇬🇧', 'uk' => '🇬🇧', 'inghilterra' => '🇬🇧', 'stati uniti' => '🇺🇸',
        'usa' => '🇺🇸', 'svizzera' => '🇨🇭', 'austria' => '🇦🇹', 'belgio' => '🇧🇪',
        'olanda' => '🇳🇱', 'paesi bassi' => '🇳🇱', 'portogallo' => '🇵🇹', 'polonia' => '🇵🇱',
        'grecia' => '🇬🇷', 'svezia' => '🇸🇪', 'norvegia' => '🇳🇴', 'danimarca' => '🇩🇰',
        'finlandia' => '🇫🇮', 'irlanda' => '🇮🇪', 'romania' => '🇷🇴', 'turchia' => '🇹🇷',
        'brasile' => '🇧🇷', 'messico' => '🇲🇽', 'canada' => '🇨🇦', 'giappone' => '🇯🇵',
        'cina' => '🇨🇳', 'india' => '🇮🇳', 'australia' => '🇦🇺',
    ];
    $paeseFlag = $countryFlags[mb_strtolower(trim($panelData->paese ?? ''))] ?? null;

    // Target (già presente nella vecchia card "Target")
    $sexLabel = 'N/A';
    if (($panelData->sex_target ?? null) == 1) $sexLabel = 'Uomo';
    elseif (($panelData->sex_target ?? null) == 2) $sexLabel = 'Donna';
    elseif (($panelData->sex_target ?? null) == 3) $sexLabel = 'Uomo/Donna';

    // Timing (già presente nella vecchia card "Timing")
    $giorniField = 'N/A';
    $timingHint = null;
    $timingHintClass = 'timing-hint-neutral';
    $timingHintIcon = 'fas fa-clock';

    $startField = !empty($panelData->sur_date) ? \Carbon\Carbon::parse($panelData->sur_date)->startOfDay() : null;
    $endField = !empty($panelData->end_field) ? \Carbon\Carbon::parse($panelData->end_field)->startOfDay() : null;
    $today = now()->startOfDay();
    $isClosed = ((int) ($panelData->stato ?? 0) === 1);

    if ($startField) {
        if ($isClosed) {
            $giorniField = $endField ? $startField->diffInDays($endField) : $startField->diffInDays($today);
        } else {
            $giorniField = $startField->diffInDays($today);
        }
    }

    if (!$isClosed && $endField) {
        $daysDiff = $today->diffInDays($endField, false);

        if ($daysDiff > 1) {
            $timingHint = "Altri {$daysDiff} giorni di field";
            $timingHintClass = 'timing-hint-info';
            $timingHintIcon = 'fas fa-hourglass-half';
        } elseif ($daysDiff === 1) {
            $timingHint = 'Domani ultimo giorno di field!';
            $timingHintClass = 'timing-hint-info';
            $timingHintIcon = 'fas fa-hourglass-end';
        } elseif ($daysDiff === 0) {
            $timingHint = 'Oggi il field chiude!';
            $timingHintClass = 'timing-hint-warning';
            $timingHintIcon = 'fas fa-bell';
        } else {
            $giorniRitardo = abs($daysDiff);
            $timingHint = $giorniRitardo === 1 ? 'Il field è in ritardo di 1 giorno' : "Il field è in ritardo di {$giorniRitardo} giorni";
            $timingHintClass = 'timing-hint-danger';
            $timingHintIcon = 'fas fa-triangle-exclamation';
        }
    }
    // Se chiusa, nessun hint aggiuntivo: lo stato "Field chiuso" è già mostrato
    // una sola volta dalla pill di stato nel blocco Avanzamento.

    // Stato ricerca / stato Primis (già presente nella vecchia card "Info")
    $statusLabel = 'N/A';
    $statusIcon = 'fas fa-question-circle';

    if (($panelData->stato ?? null) == 0) {
        $statusLabel = 'Aperta';
        $statusIcon = 'fa-solid fa-door-open';
    } elseif (($panelData->stato ?? null) == 1) {
        $statusLabel = 'Chiusa';
        $statusIcon = 'fa-solid fa-door-closed';
    }

    $primisStatusLabel = 'N/A';
    $primisStatusClass = 'primis-status-neutral';
    $primisStatusIcon = 'fas fa-circle-question';

    if ($primisSurveyStatus !== null) {
        $primisSurveyStatusInt = (int) $primisSurveyStatus;

        if (in_array($primisSurveyStatusInt, [0, 1], true)) {
            $primisStatusLabel = 'Progettazione';
            $primisStatusClass = 'primis-status-planning';
            $primisStatusIcon = 'fas fa-drafting-compass';
        } elseif ($primisSurveyStatusInt === 2) {
            $primisStatusLabel = 'Raccolta';
            $primisStatusClass = 'primis-status-collect';
            $primisStatusIcon = 'fas fa-database';
        } elseif ($primisSurveyStatusInt >= 3) {
            $primisStatusLabel = 'Chiusa';
            $primisStatusClass = 'primis-status-closed';
            $primisStatusIcon = 'fas fa-lock';
        }
    }

    // Icone/tooltip panel, riusate in tutti i selettori della pagina
    $panelMeta = [];
    foreach ($panelCounts as $pName => $pVal) {
        if ($pName === 'Interactive') {
            $panelMeta[$pName] = ['icon' => 'fa-solid fa-users', 'css' => 'fc-panel-interactive', 'tooltip' => null];
        } elseif ($pName === 'Da lista') {
            $panelMeta[$pName] = ['icon' => 'fa-solid fa-list-ul', 'css' => 'fc-panel-lista', 'tooltip' => 'Interviste con uid da lista, uid GUEST o non classificate con nessun panel'];
        } else {
            $panelMeta[$pName] = ['icon' => 'fa-solid fa-building-user', 'css' => 'fc-panel-external', 'tooltip' => null];
        }
    }

    // Sezioni "Andamento Field": Totale + ogni panel, stesse formule/denominatori di prima
    $outcomeKeys = ['complete', 'non_target', 'over_quota', 'sospese', 'bloccate', 'bad_quality'];
    $outcomeLabels = [
        'complete' => 'Complete',
        'non_target' => 'Non in target',
        'over_quota' => 'Over quota',
        'sospese' => 'Sospese',
        'bloccate' => 'Bloccate',
        'bad_quality' => 'Bad Quality',
    ];

    $afieldSections = [];

    $totContattiAF = max(1, (int) ($counts['contatti'] ?? 0));
    $totPercAF = [];
    foreach ($outcomeKeys as $k) {
        $totPercAF[$k] = round(((int) ($counts[$k] ?? 0) / $totContattiAF) * 100, 1);
    }

    $afieldSections['Totale'] = [
        'is_total' => true,
        'ir' => $redemption ?? 0,
        'contatti' => (int) ($counts['contatti'] ?? 0),
        'counts' => $counts,
        'perc' => $totPercAF,
        'goal' => $goal,
        'complete' => $complete,
        'pct' => $pct,
        'missing' => $missingInterviste,
    ];

    foreach ($panelCounts as $panelName => $pData) {
        $pContatti = max(1, (int) ($pData['contatti'] ?? 0));
        $pPerc = [];
        foreach ($outcomeKeys as $k) {
            $pPerc[$k] = round(((int) ($pData[$k] ?? 0) / $pContatti) * 100, 1);
        }

        $panelIrValue = is_numeric($pData['redemption'] ?? null) ? $pData['redemption'] : 0;

        $afieldSections[$panelName] = [
            'is_total' => false,
            'ir' => $panelIrValue,
            'contatti' => (int) ($pData['contatti'] ?? 0),
            'counts' => $pData,
            'perc' => $pPerc,
            'goal' => null,
            'complete' => (int) ($pData['complete'] ?? 0),
            'pct' => null,
            'missing' => null,
        ];
    }

    // Serie giornaliere (Totale + panel) per il grafico "Andamento giornaliero":
    // riusa i dati già aggregati per il Report giornaliero (nessuna nuova query),
    // applicando la stessa formula IR già in uso nella tabella sottostante.
    $mergedDaily = [];
    foreach ($dataSummaryByPanel as $panelName => $summaryData) {
        foreach ($summaryData as $dayKey => $stats) {
            if (!isset($mergedDaily[$dayKey])) {
                $mergedDaily[$dayKey] = [
                    'contatti' => 0, 'complete' => 0, 'non_target' => 0,
                    'quotafull' => 0, 'bloccate' => 0, 'bad_quality' => 0,
                ];
            }
            foreach (['contatti', 'complete', 'non_target', 'quotafull', 'bloccate', 'bad_quality'] as $k) {
                $mergedDaily[$dayKey][$k] += $stats[$k] ?? 0;
            }
        }
    }

    $buildDailySeries = function (array $daily) {
        ksort($daily);
        $series = [];

        foreach ($daily as $dayKey => $stats) {
            $badQ = $stats['bad_quality'] ?? 0;
            $sospese = $stats['contatti'] - ($stats['non_target'] + $stats['quotafull'] + $stats['complete'] + $stats['bloccate'] + $badQ);
            $den = $stats['contatti'] - $sospese - $stats['quotafull'] - $stats['bloccate'];
            $ir = ($den > 0) ? round(($stats['complete'] / $den) * 100, 2) : 0;

            // Mostra il giorno solo se c'è almeno una complete o un IR diverso da zero
            if (($stats['complete'] ?? 0) <= 0 && $ir <= 0) {
                continue;
            }

            $series[] = [
                'date' => $dayKey,
                'contatti' => (int) $stats['contatti'],
                'complete' => (int) $stats['complete'],
                'ir' => $ir,
            ];
        }

        return $series;
    };

    $dailySeriesByPanel = [];
    $dailySeriesByPanel['Totale'] = $buildDailySeries($mergedDaily);
    foreach ($dataSummaryByPanel as $panelName => $summaryData) {
        $dailySeriesByPanel[$panelName] = $buildDailySeries($summaryData);
    }

    $hasDailySeries = false;
    foreach ($dailySeriesByPanel as $s) {
        if (!empty($s)) { $hasDailySeries = true; break; }
    }

    // Confronto tra panel: solo se c'è più di un panel (altrimenti non è un confronto)
    $panelComparison = [];
    if (count($panelCounts) > 1) {
        $totalCompleteForShare = (int) ($counts['complete'] ?? 0);

        foreach ($panelCounts as $panelName => $pData) {
            $pComplete = (int) ($pData['complete'] ?? 0);
            $panelComparison[] = [
                'label' => $panelName,
                'ir' => is_numeric($pData['redemption'] ?? null) ? $pData['redemption'] : 0,
                'complete' => $pComplete,
                'contatti' => (int) ($pData['contatti'] ?? 0),
                'share' => $totalCompleteForShare > 0 ? round(($pComplete / $totalCompleteForShare) * 100, 1) : 0,
            ];
        }
        $panelComparison[] = [
            'label' => 'Totale',
            'ir' => $redemption ?? 0,
            'complete' => $totalCompleteForShare,
            'contatti' => (int) ($counts['contatti'] ?? 0),
            'share' => 100,
        ];
    }
@endphp

<!-- ================= HEADER RICERCA UNIFICATO ================= -->
<div class="row g-3">
    <div class="col-12">
        <div class="card fc-header-card">
            <div class="fc-header-body">

                <!-- Area A: Identita' ricerca -->
                <div class="fc-header-block fc-header-id">
                    <div class="fc-header-eyebrow"><i class="fas fa-folder-open me-1"></i>Ricerca</div>
                    <div class="fc-header-idvalue">#{{ $panelData->sur_id ?? 'N/A' }}</div>
                    <div class="fc-header-name">{{ $panelData->description ?? 'No description available' }}</div>
                    <div class="fc-header-subrow">
                        <div class="fc-header-subitem"><span class="fc-header-subkey">Cliente</span><span class="fc-header-subval">{{ $panelData->cliente ?? 'N/A' }}</span></div>
                        <div class="fc-header-subitem"><span class="fc-header-subkey">Paese</span><span class="fc-header-subval">@if($paeseFlag)<span class="fc-header-flag">{{ $paeseFlag }}</span>@endif{{ $panelData->paese ?? 'N/A' }}</span></div>
                    </div>
                </div>

                <!-- Area B: Stato e LOI -->
                <div class="fc-header-block fc-header-status">
                    <div class="fc-header-status-badges">
                        <span class="fc-status-pill {{ $isClosed ? 'is-closed' : 'is-open' }}">
                            <i class="{{ $statusIcon }} me-1"></i>{{ $isClosed ? 'Field chiuso' : 'Field aperto' }}
                        </span>
                        <span class="primis-status-badge {{ $primisStatusClass }}">
                            <i class="{{ $primisStatusIcon }} me-1"></i>Primis: {{ $primisStatusLabel }}
                        </span>
                    </div>
                    <div class="fc-header-loi">
                        <span class="fc-header-loi-icon"><i class="fas fa-stopwatch"></i></span>
                        <div>
                            <span class="fc-header-mini-key">LOI media</span>
                            <span class="fc-header-loi-val">{{ $panelData->durata ?? 'N/A' }} min</span>
                        </div>
                    </div>
                </div>

                <!-- Area C: Target e Timing -->
                <div class="fc-header-block fc-header-target">
                    <div class="fc-header-mini">
                        <span class="fc-header-mini-icon"><i class="fa-solid fa-arrows-down-to-people"></i></span>
                        <div>
                            <span class="fc-header-mini-key">Target</span>
                            <span class="fc-header-mini-val">
                                <span class="fc-target-sex">{{ $sexLabel }}</span>
                                <span class="fc-target-age">{{ $panelData->age1_target ?? 'N/A' }}-{{ $panelData->age2_target ?? 'N/A' }} anni</span>
                            </span>
                        </div>
                    </div>
                    <div class="fc-header-mini">
                        <span class="fc-header-mini-icon"><i class="fas fa-clock"></i></span>
                        <div>
                            <span class="fc-header-mini-key">Timing</span>
                            <span class="fc-header-mini-val">
                                <span class="fc-timing-dates">
                                    {{ $panelData->sur_date ? \Carbon\Carbon::parse($panelData->sur_date)->format('d/m/Y') : 'N/A' }}
                                    <i class="fas fa-arrow-right-long fc-timing-arrow"></i>
                                    {{ $panelData->end_field ? \Carbon\Carbon::parse($panelData->end_field)->format('d/m/Y') : 'N/A' }}
                                </span>
                                <span class="fc-timing-days">{{ $giorniField }} giorni{{ $isClosed ? ' · chiuso' : '' }}</span>
                            </span>
                            @if($timingHint)
                                <div class="timing-hint {{ $timingHintClass }} fc-header-timinghint">
                                    <i class="{{ $timingHintIcon }} me-1"></i><span>{{ $timingHint }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Area D: Azioni rapide -->
                <div class="fc-header-block fc-header-actions">
                    <div class="fc-header-actions-title">Azioni rapide</div>
                    <div class="fc-header-actions-grid">
                        <a href="#" class="fc-header-action-btn is-info" data-bs-toggle="modal" data-bs-target="#downloadModal">
                            <span class="fc-header-action-icon"><i class="fas fa-download"></i></span>
                            <span class="fc-header-action-label">Download risultati</span>
                        </a>

                        @if(!empty($panelData) && (int) $panelData->stato === 1)
                            <a href="#" class="fc-header-action-btn is-success" onclick="reopenSurvey('{{ $prj }}', '{{ $sid }}')">
                                <span class="fc-header-action-icon"><i class="fas fa-play"></i></span>
                                <span class="fc-header-action-label">Riapri Ricerca</span>
                            </a>
                        @else
                            <a href="#" class="fc-header-action-btn is-danger" onclick="closeSurvey('{{ $prj }}', '{{ $sid }}')">
                                <span class="fc-header-action-icon"><i class="fas fa-stop"></i></span>
                                <span class="fc-header-action-label">Chiudi Ricerca</span>
                            </a>
                        @endif

                        <a href="#" class="fc-header-action-btn is-warning" data-bs-toggle="modal" data-bs-target="#resetBloccateModal">
                            <span class="fc-header-action-icon"><i class="fas fa-lock-open"></i></span>
                            <span class="fc-header-action-label">Reset Bloccate</span>
                        </a>

                        <a href="#" class="fc-header-action-btn is-link" data-bs-toggle="modal" data-bs-target="#redirectionsModal">
                            <span class="fc-header-action-icon"><i class="fa-solid fa-arrow-up-right-from-square"></i></span>
                            <span class="fc-header-action-label">Redirection</span>
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
<!-- ================= FINE HEADER RICERCA ================= -->


<!-- ================= ANDAMENTO FIELD ================= -->
<div class="row g-3 mt-1">
    <div class="col-12">
        <div class="card fc-afield-card">
            <div class="fc-afield-header">
                <div>
                    <div class="fc-afield-eyebrow">Vista panel</div>
                    <div class="fc-afield-title">Andamento Field</div>
                </div>

                <ul class="nav nav-pills fc-afield-tabs" id="menu-tabs">
                    @foreach ($afieldSections as $sectionName => $section)
                        <li class="nav-item">
                            <a class="nav-link fc-afield-tab-link {{ $loop->first ? 'active' : '' }}"
                               id="afield-tab-{{ $loop->index }}-btn" data-bs-toggle="pill" href="#afield-tab-{{ $loop->index }}"
                               title="{{ $sectionName }}">
                                @if($section['is_total'])
                                    <span class="fc-panel-tab-icon fc-panel-total"><i class="fas fa-home"></i></span>
                                @else
                                    <span class="fc-panel-tab-icon {{ $panelMeta[$sectionName]['css'] }}"
                                        @if($panelMeta[$sectionName]['tooltip']) data-bs-toggle="tooltip" title="{{ $panelMeta[$sectionName]['tooltip'] }}" @endif>
                                        <i class="{{ $panelMeta[$sectionName]['icon'] }}"></i>
                                    </span>
                                @endif
                                <span class="fc-panel-tab-label">{{ $sectionName }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="card-body">
                <div class="tab-content">
                    @foreach ($afieldSections as $sectionName => $section)
                        <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="afield-tab-{{ $loop->index }}">
                            <div class="fc-afield-grid-v2">

                                <div class="fc-afield-left-block">
                                    @if($section['is_total'])
                                        <div class="fc-metric-card fc-metric-card-highlight fc-metric-card-tall">
                                            <div class="fc-metric-card-title">Avanzamento</div>
                                            <div class="fc-metric-card-main">
                                                <div class="fc-metric-ring-visual" style="--ring-pct: {{ $section['pct'] }}">
                                                    <span>{{ $section['pct'] }}%</span>
                                                </div>
                                                <div>
                                                    <div class="fc-metric-card-value">{{ $fmtIt($section['complete']) }} / {{ $section['goal'] ? $fmtIt($section['goal']) : 'N/A' }}</div>
                                                    @if($section['missing'] !== null)
                                                        <div class="fc-metric-card-sub">Mancano {{ $fmtIt($section['missing']) }}</div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <div class="fc-metric-card fc-metric-card-highlight fc-metric-card-tall">
                                            <div class="fc-metric-card-title">
                                                <span class="fc-metric-card-icon fc-dist-complete"><i class="fas fa-circle-check"></i></span>Complete
                                            </div>
                                            <div class="fc-metric-card-value fc-metric-card-value-xl">{{ $fmtIt($section['complete']) }}</div>
                                            <div class="fc-metric-card-sub">Nessun obiettivo specifico per questo panel</div>
                                        </div>
                                    @endif

                                    <div class="fc-metric-card fc-metrics-stack">
                                        <div class="fc-metrics-stack-item">
                                            <div class="fc-metric-card-title">
                                                <span class="fc-metric-card-icon fc-icon-contatti"><i class="fas fa-users"></i></span>
                                                {{ $section['is_total'] ? 'Contatti totali' : 'Contatti' }}
                                            </div>
                                            <div class="fc-metric-card-value">{{ $fmtIt($section['contatti']) }}</div>
                                            <div class="fc-metric-card-sub">{{ $section['is_total'] ? 'Interviste avviate' : 'Contatti del panel' }}</div>
                                        </div>

                                        <div class="fc-metrics-stack-item fc-metrics-stack-item-bottom">
                                            <div class="fc-metric-card-title">
                                                <span class="fc-metric-card-icon fc-icon-ir"><i class="fas fa-percent"></i></span>
                                                Incidence Rate
                                            </div>
                                            <div class="fc-metric-card-value">{{ $fmtIt($section['ir'], 2) }}%</div>
                                            <div class="fc-metric-card-sub">{{ $section['is_total'] ? 'IR della ricerca' : 'IR del panel' }}</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="fc-afield-dist-card">
                                    <div class="fc-dist-head">
                                        <span>Distribuzione esiti</span>
                                        <span class="fc-dist-total">{{ $fmtIt($section['contatti']) }} contatti</span>
                                    </div>

                                    <div class="fc-dist-bar">
                                        @foreach ($outcomeKeys as $k)
                                            @if(($section['perc'][$k] ?? 0) > 0)
                                                <div class="fc-dist-seg fc-dist-{{ $k }}" style="width: {{ $section['perc'][$k] }}%"
                                                     data-bs-toggle="tooltip" title="{{ $outcomeLabels[$k] }}: {{ $fmtIt($section['perc'][$k], 1) }}%"></div>
                                            @endif
                                        @endforeach
                                    </div>

                                    <div class="fc-dist-legend-v2">
                                        @foreach ($outcomeKeys as $k)
                                            <div class="fc-dist-legend-card fc-dist-card-{{ $k }} {{ $k === 'complete' ? 'is-highlight' : '' }}">
                                                <div class="fc-dist-legend-card-title">
                                                    <span class="fc-dist-legend-icon fc-dist-{{ $k }}"><i class="{{ $outcomeIcons[$k] }}"></i></span>
                                                    {{ $outcomeLabels[$k] }}
                                                </div>
                                                <div class="fc-dist-legend-card-value">{{ $fmtIt($section['counts'][$k] ?? 0) }}</div>
                                                <div class="fc-dist-legend-card-pct fc-dist-{{ $k }}">{{ $fmtIt($section['perc'][$k] ?? 0, 1) }}%</div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
<!-- ================= FINE ANDAMENTO FIELD ================= -->


<!-- ================= TREND / SCREENOUT / CONFRONTO PANEL ================= -->
<div class="row g-3 mt-1">

    <div class="col-12 col-xl-6">
        <div class="card fc-chart-card-v2 h-100">
            <div class="fc-chart-head-v2">
                <div>
                    <div class="fc-afield-eyebrow">Trend</div>
                    <div class="fc-afield-title" style="font-size:1rem">Andamento giornaliero</div>
                </div>

                @if($hasDailySeries)
                    <ul class="nav nav-pills fc-mini-tabs" id="daily-nav">
                        @foreach ($dailySeriesByPanel as $seriesName => $series)
                            @if(!empty($series))
                                <li class="nav-item">
                                    <a class="nav-link fc-mini-tab-link {{ $loop->first ? 'active' : '' }}"
                                       data-bs-toggle="pill" href="#daily-tab-{{ $loop->index }}">{{ $seriesName }}</a>
                                </li>
                            @endif
                        @endforeach
                    </ul>
                @endif
            </div>

            <div class="card-body">
                @if($hasDailySeries)
                    <div class="tab-content">
                        @foreach ($dailySeriesByPanel as $seriesName => $series)
                            @if(!empty($series))
                                <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="daily-tab-{{ $loop->index }}">
                                    <div class="fc-daily-chart" id="chart-daily-{{ \Illuminate\Support\Str::slug($seriesName, '-') }}"></div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @else
                    <div class="fc-empty-card">
                        <div class="fc-empty-icon"><i class="fas fa-chart-line"></i></div>
                        <div class="fc-empty-title">Nessun dato giornaliero disponibile</div>
                        <div class="fc-empty-subtitle">Quando arriveranno i primi contatti vedrai qui l'andamento nel tempo.</div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-4">
        <div class="card fc-chart-card-v2 h-100">
            <div class="fc-chart-head-v2">
                <div>
                    <div class="fc-afield-eyebrow">Uscite</div>
                    <div class="fc-afield-title" style="font-size:1rem">Cause di screenout</div>
                </div>
            </div>

            <div class="card-body">
                @if($hasFiltrate)
                    <ul class="nav nav-pills fc-mini-tabs" id="panel-nav">
                        @foreach ($panelCounts as $panelName => $pData)
                            <li class="nav-item">
                                <a class="nav-link fc-mini-tab-link {{ $loop->first ? 'active' : '' }}"
                                   data-bs-toggle="pill" href="#tab-panel-{{ $loop->index }}">{{ $panelName }}</a>
                            </li>
                        @endforeach
                    </ul>

                    <div class="tab-content mt-2">
                        @foreach ($panelCounts as $panelName => $pData)
                            <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="tab-panel-{{ $loop->index }}">
                                <div class="fc-screenout-subtitle">Panel: <b>{{ $panelName }}</b> — principali domande di screenout</div>
                                <div class="fc-chart-canvas">
                                    <div id="chart-panel-{{ \Illuminate\Support\Str::slug($panelName, '-') }}" style="width: 100%; height: 100%;"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="fc-empty-card">
                        <div class="fc-empty-icon"><i class="fas fa-filter"></i></div>
                        <div class="fc-empty-title">Nessuna intervista filtrata da analizzare</div>
                        <div class="fc-empty-subtitle">Quando ci saranno screenout, qui vedrai le domande che stanno filtrando maggiormente.</div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-2">
        <div class="card fc-chart-card-v2 h-100">
            <div class="fc-chart-head-v2 fc-chart-head-v2-compact">
                <div>
                    <div class="fc-afield-eyebrow">Confronto</div>
                    <div class="fc-afield-title" style="font-size:.9rem">Quota field per panel</div>
                </div>
            </div>

            <div class="card-body">
                @if(!empty($panelComparison))
                    <div class="fc-cmp-list">
                        @foreach ($panelComparison as $cmp)
                            <div class="fc-cmp-row {{ $cmp['label'] === 'Totale' ? 'is-total' : '' }}">
                                <div class="fc-cmp-top">
                                    <span class="fc-cmp-label">{{ $cmp['label'] }}</span>
                                    <span class="fc-cmp-share">{{ $fmtPct($cmp['share']) }}%</span>
                                </div>
                                <div class="fc-cmp-bar"><div class="fc-cmp-bar-fill" style="width: {{ min(100, $cmp['share']) }}%"></div></div>
                                <div class="fc-cmp-meta">
                                    <span>{{ $cmp['complete'] }} / {{ $cmp['contatti'] }}</span>
                                    <span class="fc-cmp-ir-small">IR {{ $fmtPct($cmp['ir']) }}%</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="fc-empty-card">
                        <div class="fc-empty-icon"><i class="fa-solid fa-scale-balanced"></i></div>
                        <div class="fc-empty-title">Un solo panel attivo</div>
                        <div class="fc-empty-subtitle">Il confronto sarà disponibile quando la ricerca avrà più di un panel.</div>
                    </div>
                @endif
            </div>
        </div>
    </div>

</div>
<!-- ================= FINE TREND / SCREENOUT / CONFRONTO ================= -->


<!-- ================= SEZIONI OPERATIVE: QUOTE / STIMA ================= -->
<div class="row g-3 mt-1">

    <!-- Controllo Quote -->
    <div class="col-12 col-xl-6">
        <div class="card shadow-sm quota-card-modern h-100">
            <div class="card-header quota-card-header d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <i class="fas fa-chart-line me-2"></i>
                    <h6 class="mb-0"><b>Controllo Quote</b></h6>
                </div>
                <span class="quota-header-badge">Live</span>
            </div>

            <div class="card-body p-2">
                <div class="table-responsive quota-table-container" style="max-height: 350px; overflow-y: auto;">
                    <table class="table table-sm quota-table-modern align-middle mb-0">
                        <thead class="sticky-header quota-table-head">
                            <tr>
                                <th><i class="fa-solid fa-layer-group me-1"></i>Quota</th>
                                <th class="text-center"><i class="fa-solid fa-bullseye me-1"></i>Totale</th>
                                <th class="text-center"><i class="fa-solid fa-arrow-trend-up me-1"></i>Entrate / IR</th>
                                <th class="text-center"><i class="fa-solid fa-triangle-exclamation me-1"></i>Missing</th>
                            </tr>
                        </thead>
                        <tbody>
                        @if($quotaData->isEmpty())
                            <tr>
                                <td colspan="4" class="p-0">
                                    <div class="fc-empty-inline">
                                        <div class="fc-empty-icon sm">
                                            <i class="fas fa-layer-group"></i>
                                        </div>
                                        <div>
                                            <div class="fc-empty-title sm">Quote non impostate</div>
                                            <div class="fc-empty-subtitle sm">
                                                Imposta le quote per visualizzare il controllo avanzamento.
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @else
            @php
                $totaleContatti = (int) ($counts['contatti'] ?? 0);
            @endphp

            @foreach ($quotaData as $quota)
                @php
                    $totale = (int) $quota->totale;
                    $entrate = (int) $quota->entrate;
                    $missing = (int) $quota->missing;
                    $quotaLabel = (string) $quota->quota;

                    $isTotalRow = $quotaLabel === 'Interviste Totali';

                    $irPercent = 0;
                    if ($entrate > 0 && $totaleContatti > 0) {
                        $irPercent = round(($entrate / $totaleContatti) * 100, 1);
                    }
                @endphp

                <tr class="quota-row {{ $isTotalRow ? 'quota-row-total' : '' }}">
                    <td>
                        <div class="quota-name-wrap">
                            <div class="quota-name">
                                <span>{{ $quotaLabel }}</span>
                                @if(!empty($quota->quota_tooltip))
                                    <span class="quota-name-help"
                                        data-bs-toggle="tooltip"
                                        data-bs-html="true"
                                        data-bs-custom-class="quota-tooltip"
                                        title="{{ $quota->quota_tooltip }}">
                                        <i class="fa-solid fa-circle-info"></i>
                                    </span>
                                @endif
                            </div>
                        </div>
                    </td>

                    <td class="text-center">
                        <span class="quota-total-pill">
                            {{ $totale }}
                        </span>
                    </td>

                    <td class="text-center">
                        <div class="quota-entrate-inline-row">
                            <span class="quota-entrate-pill {{ $entrate <= 0 ? 'is-zero' : '' }}">
                                @if($entrate > 0)
                                    <i class="fas fa-check-circle me-1"></i>
                                @else
                                    <i class="fas fa-minus-circle me-1"></i>
                                @endif
                                {{ $entrate }}
                            </span>

                            <span class="quota-entrate-percent-pill {{ $entrate > 0 ? 'is-positive' : 'is-zero' }}">
                                {{ $irPercent > 0 ? number_format($irPercent, 1) : '0' }}%
                            </span>
                        </div>
                    </td>

                    <td class="text-center">
                        @if ($missing > 0)
                            <span class="quota-missing-pill is-danger">
                                <i class="fas fa-exclamation-circle me-1"></i>{{ $missing }}
                            </span>
                        @else
                            <span class="quota-missing-pill is-ok">
                                <i class="fas fa-check me-1"></i>0
                            </span>
                        @endif
                    </td>
                </tr>
            @endforeach
        @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Stima interviste e disponibilità panel -->
    <div class="col-12 col-xl-6">
        <div class="card fc-stima-card h-100">
            <div class="card-header fc-stima-header">
                <div class="d-flex align-items-center">
                    <i class="fas fa-calculator me-2"></i>
                    <h6 class="mb-0"><b>Stima interviste e disponibilità panel</b></h6>
                </div>
                @if(array_key_exists('Interactive', $panelCounts))
                    <span class="fc-stima-badge"><i class="fa-solid fa-users me-1"></i>Interactive</span>
                @endif
            </div>

            <div class="card-body">
                @if(array_key_exists('Interactive', $panelCounts))
                    @php
                        $_sep1    = '<br><small style=\'opacity:.6\'>────────────────</small><br>';
                        $_etaAdj1 = ($panelRateInfo && $panelRateInfo['eta_info']['coefficiente_eta'] != 1.0) ? ' <i>(età-adj.)</i>' : '';
                        if ($ondateInfo !== null) {
                            $_stima1 = $_sep1 . '<small>Disponibili: <b>' . number_format($ondateInfo['utenti_disponibili']) . '</b>';
                            if ($ondateInfo['utenti_residui_ondate'] > 0) {
                                $_stima1 .= ' &nbsp;+&nbsp; ondate <b>+' . number_format($ondateInfo['utenti_residui_ondate']) . '</b><br>Effettivi: <b>' . number_format($ondateInfo['utenti_effettivi']) . '</b>';
                            }
                            $_stima1 .= '<br>IR survey: <b>' . number_format($irInteractive, 1) . '%</b></small>';
                            if ($stimaDiagnostica !== null) {
                                $_stima1 .= $_sep1 . '→ <b>' . $stimaDiagnostica . ' interviste</b>';
                            }
                        } else {
                            $_stima1 = $_sep1 . '<small>IR survey: ' . number_format($irInteractive, 1) . '%</small>';
                        }
                        $_fonte1 = $panelRateInfo['fonte'] ?? '';
                        if ($_fonte1 === 'studio_stabile') {
                            $irTooltip1 =
                                '<b>✓ Campione stabile</b> · da ' . $panelRateInfo['ore_dalla_variazione'] . 'h' .
                                $_sep1 .
                                'IR panel usato: <b>' . number_format($panelRateInfo['valore_utilizzato'], 1) . '%</b><br>' .
                                '<small>Studio ' . number_format($panelRateInfo['valore_corrente'], 1) . '% &nbsp;·&nbsp; Storico ' . number_format($panelRateInfo['eta_info']['media_corretta'], 1) . '%' . $_etaAdj1 . ' · media 50/50</small>' .
                                $_stima1;
                        } elseif ($_fonte1 === 'studio') {
                            $_oreLabel1 = $panelRateInfo['ore_dalla_variazione'] !== null ? ' · ' . $panelRateInfo['ore_dalla_variazione'] . 'h / 24h' : '';
                            $irTooltip1 =
                                '<b>⏳ In aggiornamento</b>' . $_oreLabel1 .
                                $_sep1 .
                                'IR panel usato: <b>' . number_format($panelRateInfo['valore_utilizzato'], 1) . '%</b><br>' .
                                '<small>Studio ' . number_format($panelRateInfo['valore_corrente'], 1) . '% &nbsp;·&nbsp; Storico ' . number_format($panelRateInfo['eta_info']['media_corretta'], 1) . '%' . $_etaAdj1 . ' · media 50/50</small>' .
                                $_stima1;
                        } elseif ($_fonte1 === 'storico') {
                            $irTooltip1 =
                                '<b>Nessun contatto</b> · usando storico' .
                                $_sep1 .
                                'IR panel usato: <b>' . number_format($panelRateInfo['valore_utilizzato'], 1) . '%</b><br>' .
                                '<small>Storico ' . number_format($panelRateInfo['eta_info']['media_corretta'], 1) . '%' . $_etaAdj1 . '</small>' .
                                $_stima1;
                        } else {
                            $irTooltip1 = 'IR: ' . number_format($redemption, 1) . '%  ·  Media red: ' . number_format($mediaRedPanel, 1) . '%';
                        }

                        $_stC = (int) ($panelCounts['Interactive']['complete'] ?? 0);
                    @endphp

                    <div class="fc-stima-grid">
                        <div class="fc-stima-item">
                            <div class="fc-stima-label"><i class="fa-solid fa-check-circle me-1"></i>Già raccolte</div>
                            <div class="fc-stima-value text-success">{{ $_stC }}</div>
                        </div>

                        @if($stimaInterviste !== null)
                            @php $_stTot = $_stC + $stimaInterviste; @endphp
                            <div class="fc-stima-item">
                                <div class="fc-stima-label"><i class="fa-solid fa-arrow-trend-up me-1"></i>Stima aggiuntive</div>
                                <div class="fc-stima-value text-primary">+{{ $stimaInterviste }}</div>
                            </div>
                            <div class="fc-stima-item">
                                <div class="fc-stima-label"><i class="fa-solid fa-flag-checkered me-1"></i>Max raggiungibile</div>
                                <div class="fc-stima-value">{{ $_stTot }}</div>
                            </div>
                        @endif
                    </div>

                    <div class="fc-stima-extra mt-3">
                        <div class="fc-stima-extra-item">
                            <span><i class="fa-solid fa-user-check me-1"></i>Abilitati panel</span><b>{{ $abilitati }}</b>
                        </div>
                        <div class="fc-stima-extra-item">
                            <span><i class="fa-solid fa-users me-1"></i>Utenti disponibili</span>
                            <b>{{ $utentiDisponibili }}
                                <i class="fas fa-info-circle ms-1" data-bs-toggle="tooltip" data-bs-html="true"
                                   title="{!! $irTooltip1 !!}" style="opacity:.5; font-size:.85em"></i>
                            </b>
                        </div>
                    </div>

                    <div class="fc-stima-note">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span>La stima non considera eventuali chiusure di quote ed è relativa al panel Interactive.</span>
                    </div>
                @else
                    <div class="fc-empty-card">
                        <div class="fc-empty-icon"><i class="fas fa-users"></i></div>
                        <div class="fc-empty-title">Panel Interactive non presente</div>
                        <div class="fc-empty-subtitle">La stima interviste è disponibile solo per le ricerche con panel Interactive.</div>
                    </div>
                @endif
            </div>
        </div>
    </div>

</div>
<!-- ================= FINE SEZIONI OPERATIVE ================= -->


<!-- ================= REPORT GIORNALIERO + LOG ATTIVITÀ ================= -->
<div class="row g-3 mt-1">

    <!-- Report giornaliero -->
    <div class="col-12 col-xl-8">
        <div class="custom-tab-container-modern fc-report-card h-100">
            <!-- Nav Tabs -->
            @if(!empty($dataSummaryByPanel))
                <ul class="nav custom-nav-tabs-modern" id="date-nav">
                    @if(count($panelCounts) == 1)
                        <li class="nav-item">
                            <a class="nav-link active modern-tab-link" id="tot-date-tab" data-bs-toggle="pill" href="#tot-date">
                                <i class="fas fa-calendar-alt me-2"></i> TOT
                            </a>
                        </li>
                    @else
                        @foreach ($dataSummaryByPanel as $panelName => $summaryData)
                            <li class="nav-item">
                                <a class="nav-link modern-tab-link {{ $loop->first ? 'active' : '' }}"
                                   id="tab-date-{{ $loop->index }}-nav"
                                   data-bs-toggle="pill"
                                   href="#tab-date-{{ $loop->index }}">
                                    <i class="fas fa-calendar-day me-2"></i> {{ $panelName }}
                                </a>
                            </li>
                        @endforeach
                    @endif
                </ul>
            @endif

            <!-- Tab Content -->
            <div class="tab-content custom-tab-content-modern">
                <br/>
                @if(empty($dataSummaryByPanel))
                    <div class="fc-empty-state fc-empty-state--report">
                        <div class="fc-empty-robot">
                            <i class="fa-solid fa-robot"></i>
                        </div>
                        <div class="fc-empty-title">Report giornaliero in attesa</div>
                        <div class="fc-empty-subtitle">Al momento non ci sono interviste da conteggiare.</div>
                    </div>
                @endif

                @foreach ($dataSummaryByPanel as $panelName => $summaryData)
                    <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}"
                         id="tab-date-{{ $loop->index }}">
                        <h4 class="tab-title text-center">Report giornaliero - {{ $panelName }}</h4>

                        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                            <table class="table table-hover table-striped custom-table-modern fc-report-table-compact text-center">
                                <thead class="table-dark sticky-top">
                                    <tr>
                                        <th>Giorno</th>
                                        <th>Contatti</th>
                                        <th>Complete</th>
                                        <th>Non in target</th>
                                        <th>Quotafull</th>
                                        <th>Bloccate</th>
                                        <th>Bad Quality</th>
                                        <th>IR (%)</th>
                                        <th>LOI (Media)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @php
                                    // Convertiamo in array con ordine già decrescente (tu fai krsort nel controller)
                                    $rows = $summaryData;
                                    $rowKeys = array_keys($rows);
                                @endphp

                                @forelse ($rowKeys as $i => $date)
                                    @php
                                        $stats = $rows[$date];

                                        // ==== IR (come già fai) ====
                                        $badQualityDay = $stats['bad_quality'] ?? 0;
                                        $sospese = $stats['contatti'] - ($stats['non_target'] + $stats['quotafull'] + $stats['complete'] + $stats['bloccate'] + $badQualityDay);
                                        $denominator = $stats['contatti'] - $sospese - $stats['quotafull'] - $stats['bloccate'];
                                        $ir = ($denominator > 0) ? round(($stats['complete'] / $denominator) * 100, 2) : 0;

                                        // ==== LOI (minuti) ====
                                        $loiValue = (isset($stats['total_duration']) && $stats['complete'] > 0)
                                            ? round(($stats['total_duration'] / $stats['complete']) / 60, 1)
                                            : null;

                                        $loi = ($loiValue !== null) ? ($loiValue . " min.") : 'N/A';

                                        // ==== Delta vs giorno precedente (solo se esiste riga successiva) ====
                                        $showTrend = false;
                                        $irTrendIcon = null;  $irTrendClass = null;
                                        $loiTrendIcon = null; $loiTrendClass = null;

                                        // Prendiamo "ieri" come la riga successiva (dato che siamo in ordine decrescente)
                                        if (isset($rowKeys[$i + 1])) {
                                            $prevDate = $rowKeys[$i + 1];
                                            $prev = $rows[$prevDate];

                                            // IR prev
                                            $prevBadQuality = $prev['bad_quality'] ?? 0;
                                            $prevSospese = $prev['contatti'] - ($prev['non_target'] + $prev['quotafull'] + $prev['complete'] + $prev['bloccate'] + $prevBadQuality);
                                            $prevDen = $prev['contatti'] - $prevSospese - $prev['quotafull'] - $prev['bloccate'];
                                            $prevIr = ($prevDen > 0) ? round(($prev['complete'] / $prevDen) * 100, 2) : 0;

                                            // LOI prev
                                            $prevLoiValue = (isset($prev['total_duration']) && $prev['complete'] > 0)
                                                ? round(($prev['total_duration'] / $prev['complete']) / 60, 1)
                                                : null;

                                            // Mostriamo trend solo se entrambi i giorni hanno valori "validi"
                                            // (IR ha sempre valore numerico, LOI è valido solo se complete>0)
                                            $showTrend = true;

                                            // IR trend: su = rosso, giu = verde, uguale = giallo
                                            if ($ir > $prevIr) { $irTrendIcon = '▲'; $irTrendClass = 'trend-up'; }
                                            elseif ($ir < $prevIr) { $irTrendIcon = '▼'; $irTrendClass = 'trend-down'; }
                                            else { $irTrendIcon = '='; $irTrendClass = 'trend-eq'; }

                                            // LOI trend solo se LOI valido oggi e ieri
                                            if ($loiValue !== null && $prevLoiValue !== null) {
                                                if ($loiValue > $prevLoiValue) { $loiTrendIcon = '▲'; $loiTrendClass = 'trend-up'; }
                                                elseif ($loiValue < $prevLoiValue) { $loiTrendIcon = '▼'; $loiTrendClass = 'trend-down'; }
                                                else { $loiTrendIcon = '='; $loiTrendClass = 'trend-eq'; }
                                            } else {
                                                // se non abbiamo LOI valido, non mostriamo trend LOI
                                                $loiTrendIcon = null;
                                                $loiTrendClass = null;
                                            }
                                        }
                                    @endphp

                                    <tr>
                                        <td><strong>{{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</strong></td>
                                        <td>{{ $stats['contatti'] }}</td>
                                        <td class="text-success fw-bold">{{ $stats['complete'] }}</td>
                                        <td class="text-warning fw-bold">{{ $stats['non_target'] }}</td>
                                        <td class="text-danger fw-bold">{{ $stats['quotafull'] }}</td>
                                        <td class="text-danger fw-bold">{{ $stats['bloccate'] }}</td>
                                        <td class="fw-bold" style="color:#991b1b;">{{ $stats['bad_quality'] ?? 0 }}</td>

                                        <td class="text-primary fw-bold">
                                            {{ $ir }}%
                                            @if($showTrend && $irTrendIcon)
                                                <span class="trend-badge {{ $irTrendClass }}">{{ $irTrendIcon }}</span>
                                            @endif
                                        </td>

                                        <td>
                                            {{ $loi }}
                                            @if($showTrend && $loiTrendIcon)
                                                <span class="trend-badge {{ $loiTrendClass }}">{{ $loiTrendIcon }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="p-0 border-0">
                                            <div class="fc-empty-state fc-empty-state--compact">
                                                <div class="fc-empty-robot">
                                                    <i class="fa-solid fa-robot"></i>
                                                </div>
                                                <div class="fc-empty-title">Nessun dato giornaliero disponibile</div>
                                                <div class="fc-empty-subtitle">Al momento non ci sono interviste da conteggiare.</div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>

                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Log Attività -->
    <div class="col-12 col-xl-4">
        <div class="card log-card-modern h-100">
            <div class="card-header log-card-header d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <i class="fas fa-list-alt me-2"></i>
                    <h6 class="mb-0"><b>Log Attività</b></h6>
                </div>
                @if(!empty($logData))
                    <span class="log-header-badge">{{ count($logData) }} interviste</span>
                @endif
            </div>
            <div class="card-body p-2">
                <div class="log-table-container" style="max-height: 440px; overflow-y: auto; overflow-x: hidden;">
                    <table class="table table-sm log-table-modern align-middle text-center mb-0">
                        <colgroup>
                            <col style="width:12%">
                            <col style="width:15%">
                            <col style="width:21%">
                            <col style="width:13%">
                            <col style="width:13%">
                            <col style="width:12%">
                            <col style="width:14%">
                        </colgroup>
                        <thead class="sticky-header-log log-table-head">
                            <tr>
                                <th class="small" rowspan="2">IID</th>
                                <th class="small" rowspan="2">UID</th>
                                <th class="small" rowspan="2" data-bs-toggle="tooltip" title="Ultimo Update"><i class="fa-solid fa-clock-rotate-left"></i></th>
                                <th class="small" colspan="2" data-bs-toggle="tooltip" title="Ultima Azione"><i class="fa-solid fa-bolt"></i></th>
                                <th class="small" rowspan="2" data-bs-toggle="tooltip" title="Stato"><i class="fa-solid fa-flag"></i></th>
                                <th class="small" rowspan="2" data-bs-toggle="tooltip" title="Panel di provenienza"><i class="fa-solid fa-layer-group"></i></th>
                            </tr>
                            <tr>
                                <th class="small" data-bs-toggle="tooltip" title="Sistema"><i class="fa-solid fa-server"></i></th>
                                <th class="small" data-bs-toggle="tooltip" title="File"><i class="fa-solid fa-file-lines"></i></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($logData as $log)
                                <tr class="align-middle">
                                    <td class="small">
                                        <span class="fc-iid-link" data-bs-toggle="tooltip" data-bs-html="true"
                                              data-bs-custom-class="fc-iid-tooltip-box" title="{{ $log['iid_tooltip'] ?? '' }}">
                                            {{ $log['iid'] }}
                                        </span>
                                    </td>
                                    <td class="small">{{ $log['uid'] }}</td>
                                    <td class="small">{!! $log['ultimo_update'] !!}</td>
                                    <td class="small">
                                        {!! $log['ultima_azione_sistema'] !!}
                                    </td>
                                    <td class="small">
                                        {!! $log['ultima_azione_file'] !!}
                                    </td>
                                    <td class="small">
                                        @if ($log['stato'] === 'Completa')
                                            <span class="fc-log-status-icon" data-bs-toggle="tooltip" title="Completa">
                                                <i class="fas fa-check-circle text-success"></i>
                                            </span>
                                        @elseif ($log['stato'] === 'Non in target')
                                            <span class="fc-log-status-icon" data-bs-toggle="tooltip" title="Non in target">
                                                <i class="fas fa-times-circle text-danger"></i>
                                            </span>
                                        @elseif ($log['stato'] === 'Quotafull')
                                            <span class="fc-log-status-icon" data-bs-toggle="tooltip" title="Quotafull">
                                                <i class="fas fa-exclamation-triangle text-warning"></i>
                                            </span>
                                        @elseif ($log['stato'] === 'In Corso')
                                            <span class="fc-log-status-icon" data-bs-toggle="tooltip" title="In Corso">
                                                <i class="fas fa-hourglass-half text-primary"></i>
                                            </span>
                                        @elseif ($log['stato'] === 'Stop Int')
                                            <span class="fc-log-status-icon" data-bs-toggle="tooltip" title="Stop Int">
                                                <i class="fas fa-ban text-dark"></i>
                                            </span>
                                        @elseif ($log['stato'] === 'Bad Quality')
                                            <span class="fc-log-status-icon" data-bs-toggle="tooltip" title="Bad Quality">
                                                <i class="fas fa-flag text-danger"></i>
                                            </span>
                                        @else
                                            <span class="fc-log-status-icon" data-bs-toggle="tooltip" title="{{ $log['stato'] }}">
                                                <i class="fas fa-circle text-secondary"></i>
                                            </span>
                                        @endif
                                    </td>
                                    <td class="small">
                                        <span class="fc-log-status-icon" data-bs-toggle="tooltip" title="{{ $log['panel'] ?? 'N/A' }}">
                                            @if (($log['panel'] ?? null) === 'Interactive')
                                                <i class="fa-solid fa-house text-success"></i>
                                            @else
                                                <i class="fa-solid fa-earth-americas text-primary"></i>
                                            @endif
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="p-0 border-0">
                                        <div class="fc-empty-state fc-empty-state--compact">
                                            <div class="fc-empty-robot">
                                                <i class="fa-solid fa-robot"></i>
                                            </div>
                                            <div class="fc-empty-title">Log attività ancora vuoto</div>
                                            <div class="fc-empty-subtitle">Al momento non ci sono interviste da conteggiare.</div>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- ================= FINE REPORT GIORNALIERO ================= -->


    <!-- fine container -->
</div>


@include('partials.field-toolbar-modals')

<!-- MODALE REDIRECTION -->
<div class="modal fade" id="redirectionsModal" tabindex="-1" aria-labelledby="redirectionsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="redirectionsModalLabel"><i class="fa-solid fa-arrow-up-right-from-square me-2"></i>Redirection</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
            </div>
            <div class="modal-body">
                @if(empty($redirections))
                    <div class="fc-empty-inline">
                        <div class="fc-empty-icon sm">
                            <i class="fa-solid fa-arrow-up-right-from-square"></i>
                        </div>
                        <div>
                            <div class="fc-empty-title sm">Nessuna redirection impostata</div>
                            <div class="fc-empty-subtitle sm">Il file config.json di questa ricerca non contiene redirection configurate.</div>
                        </div>
                    </div>
                @else
                    @php
                        $redirectionGroups = [];
                        foreach ($redirections as $redirect) {
                            $groupKey = $redirect['parameter_name'] . '|' . $redirect['parameter_value'];

                            if (!isset($redirectionGroups[$groupKey])) {
                                $isPanel = $redirect['parameter_name'] === 'pan';
                                $groupLabel = $isPanel
                                    ? ($panelNamesMap[$redirect['parameter_value']] ?? ('Panel #' . $redirect['parameter_value']))
                                    : ($redirect['parameter_name'] . '=' . $redirect['parameter_value']);

                                $redirectionGroups[$groupKey] = [
                                    'label' => $groupLabel,
                                    'parameter_name' => $redirect['parameter_name'],
                                    'parameter_value' => $redirect['parameter_value'],
                                    'items' => [],
                                ];
                            }

                            $redirectionGroups[$groupKey]['items'][] = $redirect;
                        }
                    @endphp

                    <div class="fc-redirect-list" id="redirectionsTableBody">
                        @foreach ($redirectionGroups as $group)
                            <div class="fc-redirect-group">
                                <div class="fc-redirect-group-head">
                                    <span class="fc-redirect-group-label"><i class="fa-solid fa-layer-group me-1"></i>{{ $group['label'] }}</span>
                                    <button type="button" class="btn btn-sm btn-outline-danger fc-redirect-delete-group"
                                            data-parameter-name="{{ $group['parameter_name'] }}"
                                            data-parameter-value="{{ $group['parameter_value'] }}"
                                            data-group-label="{{ $group['label'] }}">
                                        <i class="fa-solid fa-trash me-1"></i>Elimina {{ $group['label'] }}
                                    </button>
                                </div>

                                @foreach ($group['items'] as $redirect)
                                    @php
                                        if ($redirect['exit_code'] === 3) {
                                            $exitClass = 'is-ok';
                                        } elseif ($redirect['exit_code'] === 4) {
                                            $exitClass = 'is-warn';
                                        } elseif ($redirect['exit_code'] === 5) {
                                            $exitClass = 'is-danger';
                                        } else {
                                            $exitClass = 'is-neutral';
                                        }
                                    @endphp
                                    <div class="fc-redirect-card" data-redirect-index="{{ $redirect['index'] }}">
                                        <span class="fc-redirect-exit-pill {{ $exitClass }}">{{ $redirect['exit_label'] }}</span>
                                        <input type="text" class="form-control fc-redirect-input"
                                               data-index="{{ $redirect['index'] }}"
                                               value="{{ $redirect['redirect_to'] }}"
                                               spellcheck="false">
                                        <button type="button" class="btn btn-outline-primary fc-redirect-save-row" data-index="{{ $redirect['index'] }}" title="Salva questo link">
                                            <i class="fa-solid fa-floppy-disk"></i>
                                        </button>
                                        <span class="fc-redirect-row-status" data-index="{{ $redirect['index'] }}"></span>
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                @endif

                <hr class="my-3">

                <div class="fc-redirect-import">
                    <div class="fc-redirect-import-title"><i class="fa-solid fa-wand-magic-sparkles me-2"></i>Aggiungi da testo fornitore</div>
                    <p class="fc-redirect-import-hint">Incolla qui il testo dell'email con i link di redirezione (es. "Complete: https://..."), poi analizza e rivedi prima di aggiungere.</p>

                    <div class="row g-2 mb-2">
                        <div class="col-7">
                            <label class="fc-redirect-import-label" for="redirectImportPanelSelect">Panel di destinazione</label>
                            <select id="redirectImportPanelSelect" class="form-select form-select-sm">
                                <option value="">-- seleziona panel --</option>
                                @foreach ($panelNamesMap as $code => $name)
                                    <option value="{{ $code }}">{{ $name }} (#{{ $code }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-5">
                            <label class="fc-redirect-import-label" for="redirectImportPanelCustom">oppure codice personalizzato</label>
                            <input type="text" id="redirectImportPanelCustom" class="form-control form-control-sm" placeholder="es. 12">
                        </div>
                    </div>

                    <textarea id="redirectImportTextarea" class="form-control form-control-sm" rows="5" placeholder="Incolla qui il testo del fornitore..."></textarea>

                    <div class="d-flex justify-content-end mt-2">
                        <button type="button" class="btn btn-sm btn-outline-primary" id="redirectImportAnalyzeBtn">
                            <i class="fa-solid fa-magnifying-glass me-1"></i>Analizza testo
                        </button>
                    </div>

                    <div id="redirectImportReviewWrap" class="mt-3" style="display:none;">
                        <div id="redirectImportReviewBody" class="fc-redirect-list"></div>
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <span id="redirectImportStatus" class="fc-redirect-save-all-status"></span>
                            <button type="button" class="btn btn-sm btn-primary" id="redirectImportConfirmBtn">
                                <i class="fa-solid fa-plus me-1"></i>Aggiungi le redirection selezionate
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <span id="redirectionsSaveAllStatus" class="fc-redirect-save-all-status me-auto"></span>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
                @if(!empty($redirections))
                    <button type="button" class="btn btn-primary" id="redirectionsSaveAllBtn">
                        <i class="fa-solid fa-floppy-disk me-1"></i>Salva tutte le modifiche
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    window.FieldControlConfig = {
        filtrateCountsByPanel: @json($filtrateCountsByPanel),
        dailySeriesByPanel: @json($dailySeriesByPanel)
    };
</script>

<script src="https://cdn.jsdelivr.net/npm/echarts@6/dist/echarts.min.js"></script>
@include('partials.field-toolbar-scripts')

<script>
(function () {
    @if(session('success'))
        Swal.fire({ icon: 'success', title: 'Fatto', text: @json(session('success')), timer: 2000, showConfirmButton: false });
    @endif
    @if(session('error'))
        Swal.fire({ icon: 'error', title: 'Errore', text: @json(session('error')) });
    @endif
}());
</script>
@endsection
