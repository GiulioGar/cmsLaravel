@extends('layouts.main')

@section('content')
<!-- Importazione dello stile personalizzato -->
<link rel="stylesheet" href="{{ asset('css/fieldControl.css') }}">





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
                   <i class="fa-solid fa-arrows-down-to-people"></i> Imposta Target
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
<!-- Bottone Download -->
<a href="#" class="nav-link" data-bs-toggle="modal" data-bs-target="#downloadModal">
    <i class="fas fa-download me-1"></i> Download
</a>


            </li>

            <!-- Impostazioni con dropdown -->
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle"
                href="#"
                id="settingsDropdown"
                role="button"
                data-bs-toggle="dropdown"
                data-bs-auto-close="true"
                aria-expanded="false">
                    <i class="fas fa-cog me-1"></i> Impostazioni
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="settingsDropdown">
                    <li>
                        <a class="dropdown-item {{ $panelData->stato == 1 ? 'disabled text-muted' : '' }}"
                           href="#"
                           onclick="closeSurvey('{{ $prj }}', '{{ $sid }}')"
                           {{ $panelData->stato == 1 ? 'style=pointer-events:none;opacity:0.5;' : '' }}>
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



<div class="row g-3">
    {{-- CARD: RICERCA --}}
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card stat-kpi kpi-theme-slate h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <div class="kpi-label">
                            <i class="fas fa-folder-open "></i>
                            Ricerca
                        </div>
                        <div class="kpi-value">
                            #{{ $panelData->sur_id ?? 'N/A' }}
                        </div>
                    </div>
                    <span class="kpi-chip kpi-chip-dark">ID</span>
                </div>

                <div class="kpi-subtitle mt-2">
                    {{ $panelData->description ?? 'No description available' }}
                </div>

                @php
                $goal = (int) ($panelData->goal ?? 0);
                $complete = (int) ($counts['complete'] ?? 0);
                $pct = ($goal > 0) ? min(100, round(($complete / $goal) * 100)) : 0;
            @endphp

            <div class="kpi-progress mt-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <small class="text-muted">Avanzamento</small>
                    <small class="fw-semibold">{{ $complete }} / {{ $goal ?: 'N/A' }} ({{ $pct }}%)</small>
                </div>
                <div class="progress kpi-progressbar" role="progressbar" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100">
                    <div class="progress-bar kpi-progressfill" style="width:0" data-pct="{{ $pct }}"></div>
                </div>
            </div>

                <div class="kpi-meta mt-3">
                    <div class="kpi-row">
                        <span class="kpi-key">Cliente</span>
                        <span class="kpi-val">{{ $panelData->cliente ?? 'N/A' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- CARD: TARGET --}}
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card stat-kpi kpi-theme-red h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <div class="kpi-label">
                            <i class="fa-solid fa-arrows-down-to-people "></i>
                            Target
                        </div>
                        <div class="kpi-value">
                            {{ $panelData->goal ?? 'N/A' }}
                            <span class="kpi-unit">interviste</span>
                        </div>
                    </div>
                    <span class="kpi-chip kpi-chip-red">{{ $panelData->paese ?? 'N/A' }}</span>
                </div>

                @php
                    $sexLabel = 'N/A';
                    if (($panelData->sex_target ?? null) == 1) $sexLabel = 'Uomo';
                    elseif (($panelData->sex_target ?? null) == 2) $sexLabel = 'Donna';
                    elseif (($panelData->sex_target ?? null) == 3) $sexLabel = 'Uomo/Donna';
                @endphp

                <div class="kpi-meta mt-3">
                    <div class="kpi-row">
                        <span class="kpi-key">Sesso</span>
                        <span class="kpi-val">{{ $sexLabel }}</span>
                    </div>
                    <div class="kpi-row">
                        <span class="kpi-key">Età</span>
                        <span class="kpi-val">{{ $panelData->age1_target ?? 'N/A' }}–{{ $panelData->age2_target ?? 'N/A' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- CARD: TIMING --}}
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card stat-kpi kpi-theme-green h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <div class="kpi-label">
                            <i class="fas fa-clock "></i>
                            Timing
                        </div>

@php
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
            $timingHint = '1 altro giorno di field';
            $timingHintClass = 'timing-hint-info';
            $timingHintIcon = 'fas fa-hourglass-end';
        } elseif ($daysDiff === 0) {
            $timingHint = 'Oggi il field chiude!';
            $timingHintClass = 'timing-hint-warning';
            $timingHintIcon = 'fas fa-bell';
        } else {
            $giorniRitardo = abs($daysDiff);

            if ($giorniRitardo === 1) {
                $timingHint = 'Il field è in ritardo di 1 giorno';
            } else {
                $timingHint = "Il field è in ritardo di {$giorniRitardo} giorni";
            }

            $timingHintClass = 'timing-hint-danger';
            $timingHintIcon = 'fas fa-triangle-exclamation';
        }
    } elseif ($isClosed) {
        $timingHint = 'Field chiuso';
        $timingHintClass = 'timing-hint-success';
        $timingHintIcon = 'fas fa-circle-check';
    }
@endphp

                    <div class="kpi-value">
                        {{ $giorniField }}
                        <span class="kpi-unit">giorni</span>
                    </div>

                    @if($timingHint)
                        <div class="timing-hint {{ $timingHintClass }}">
                            <i class="{{ $timingHintIcon }} me-2"></i>
                            <span>{{ $timingHint }}</span>
                        </div>
                    @endif
                    </div>
                    <span class="kpi-chip kpi-chip-green">Field</span>
                </div>

                <div class="kpi-meta mt-3">
                    <div class="kpi-row">
                        <span class="kpi-key">Inizio</span>
                        <span class="kpi-val">
                            {{ $panelData->sur_date ? \Carbon\Carbon::parse($panelData->sur_date)->format('d/m/Y') : 'N/A' }}
                        </span>
                    </div>
                    <div class="kpi-row">
                        <span class="kpi-key">Fine</span>
                        <span class="kpi-val">
                            {{ $panelData->end_field ? \Carbon\Carbon::parse($panelData->end_field)->format('d/m/Y') : 'N/A' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- CARD: INFO --}}
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card stat-kpi kpi-theme-blue h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <div class="kpi-label">
                           <i class="fas fa-info-circle "></i>
                            Info
                        </div>

                    @php
                        $statusLabel = 'N/A';
                        $statusChipClass = 'kpi-chip-dark';
                        $statusIcon = 'fas fa-question-circle';

                        if (($panelData->stato ?? null) == 0) {
                            $statusLabel = 'Aperta';
                            $statusChipClass = 'kpi-chip-blue';
                            $statusIcon = 'fa-solid fa-door-open';
                        }
                        elseif (($panelData->stato ?? null) == 1) {
                            $statusLabel = 'Chiusa';
                            $statusChipClass = 'kpi-chip-dark';
                            $statusIcon = 'fa-solid fa-door-closed';
                        }

                        $primisStatusLabel = 'N/A';
                        $primisStatusClass = 'primis-status-neutral';
                        $primisStatusIcon = 'fas fa-circle-question';

                        if ($primisSurveyStatus !== null) {
                            $primisSurveyStatus = (int) $primisSurveyStatus;

                            if (in_array($primisSurveyStatus, [1, 2], true)) {
                                $primisStatusLabel = 'Progettazione';
                                $primisStatusClass = 'primis-status-planning';
                                $primisStatusIcon = 'fas fa-drafting-compass';
                            } elseif ($primisSurveyStatus === 3) {
                                $primisStatusLabel = 'Raccolta';
                                $primisStatusClass = 'primis-status-collect';
                                $primisStatusIcon = 'fas fa-database';
                            } elseif ($primisSurveyStatus >= 4) {
                                $primisStatusLabel = 'Chiusa';
                                $primisStatusClass = 'primis-status-closed';
                                $primisStatusIcon = 'fas fa-lock';
                            }
                        }
                    @endphp

                        <div class="kpi-value">
                            {{ $panelData->durata ?? 'N/A' }}
                            <span class="kpi-unit">min</span>
                        </div>
                    </div>

                    <span class="kpi-chip {{ $statusChipClass }}">
                        <i class="{{ $statusIcon }} me-1"></i>
                        {{ $statusLabel }}
                    </span>
                </div>

                <div class="kpi-meta mt-3">
                        <div class="kpi-row">
                        <span class="kpi-key">Primis</span>
                        <span class="primis-status-badge {{ $primisStatusClass }}">
                            <i class="{{ $primisStatusIcon }} me-1"></i>
                            {{ $primisStatusLabel }}
                        </span>
                    </div>
                    <div class="kpi-row">
                        <span class="kpi-key">Panel</span>
                        <span class="kpi-val">
                            @if(!empty($panelCounts))
                                {{ implode(', ', array_keys($panelCounts)) }}
                            @else
                                N/A
                            @endif
                        </span>
                    </div>

                    <div class="kpi-split mt-2">
                        <div class="kpi-mini">
                            <div class="kpi-mini-key">IR</div>
                            <div class="kpi-mini-val">{{ $redemption ?? 0 }}%</div>
                        </div>
                        <div class="kpi-mini">
                            <div class="kpi-mini-key">Complete</div>

                            <div class="kpi-mini-val">
                                {{ $counts['complete'] ?? 0 }}
                                @if(isset($panelData->complete_int) || isset($panelData->complete_ext))/ {{ $goal ?: 'N/A' }}

                                @endif
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

        <!-- prima riga dopo le card con status + filtrate -->

        <div class="row mt-5">

            <div class="col-md-6">
                <div class="d-flex custom-tab-container fc-split">
                    <div class="custom-nav-container fc-side">
                        <ul class="nav flex-column nav-pills custom-nav-pills fc-side-nav" id="menu-tabs">
                            <!-- Tab Home (sempre presente e attivo) -->
                            <li class="nav-item">
                                <a class="nav-link active fc-side-link" id="tab1-tab" data-bs-toggle="pill" href="#tab1">
                                    <i class="fas fa-home me-2"></i> Totale
                                </a>
                            </li>

                            <!-- Generazione dinamica dei tab per i panel -->
                            @if(count($panelCounts) > 1)
                            @foreach ($panelCounts as $panelName => $panelData)
                                <li class="nav-item">
                                    <a class="nav-link fc-side-link" id="tab{{ $loop->index + 2 }}-tab" data-bs-toggle="pill" href="#tab{{ $loop->index + 2 }}">
                                       @if($panelName === 'Interactive')
                                        <span class="fc-panel-tab-icon fc-panel-interactive">
                                            <i class="fa-solid fa-users"></i>
                                        </span>
                                        <span>{{ $panelName }}</span>
                                    @elseif($panelName === 'Da lista')
                                        <span class="fc-panel-tab-icon fc-panel-lista"
                                            data-bs-toggle="tooltip"
                                            title="Interviste con uid da lista, uid GUEST o non classificate con nessun panel">
                                            <i class="fa-solid fa-list-ul"></i>
                                        </span>
                                        <span data-bs-toggle="tooltip"
                                            title="Interviste con uid da lista, uid GUEST o non classificate con nessun panel">
                                            {{ $panelName }}
                                        </span>
                                    @else
                                        <span class="fc-panel-tab-icon fc-panel-external">
                                            <i class="fa-solid fa-building-user"></i>
                                        </span>
                                        <span>{{ $panelName }}</span>
                                    @endif
                                    </a>
                                </li>
                            @endforeach
                        @endif

                        </ul>
                    </div>

                    <!-- Contenuto della tab -->
                    <div class="tab-content custom-tab-content fc-tab-area">
                        <!-- Tab Home - Totale -->
                    <div class="tab-pane fade show active" id="tab1">

                        @php
                        $totContatti = max(1, (int) ($counts['contatti'] ?? 0));

                        $totPerc = [
                            'complete' => round(((int) ($counts['complete'] ?? 0) / $totContatti) * 100, 1),
                            'non_target' => round(((int) ($counts['non_target'] ?? 0) / $totContatti) * 100, 1),
                            'over_quota' => round(((int) ($counts['over_quota'] ?? 0) / $totContatti) * 100, 1),
                            'sospese' => round(((int) ($counts['sospese'] ?? 0) / $totContatti) * 100, 1),
                            'bloccate' => round(((int) ($counts['bloccate'] ?? 0) / $totContatti) * 100, 1),
                            'contatti' => 100.0,
                        ];
                    @endphp

                        <div class="fc-kpi-card">
                            <div class="fc-kpi-head">
                                <div>
                                    <div class="fc-kpi-eyebrow">Riepilogo</div>
                                    <div class="fc-kpi-title">Totale</div>
                                </div>

                                <div class="fc-kpi-ir">
                                    <div class="fc-kpi-ir-label">IR</div>
                                    <div class="fc-kpi-ir-value">{{ $redemption }}%</div>
                                </div>
                            </div>

                            <div class="fc-kpi-progress mt-3" data-ir="{{ $redemption }}">
                                <div class="fc-kpi-progress-track">
                                    <div class="fc-kpi-progress-fill" style="width: {{ $redemption }}%"></div>
                                </div>
                                <div class="fc-kpi-progress-meta">
                                    <span>0%</span>
                                    <span>100%</span>
                                </div>
                            </div>

                            <div class="fc-kpi-grid mt-3">

                            <div class="fc-kpi-item fc-ok">
                                <div class="fc-kpi-top">
                                    <div class="fc-kpi-icon">
                                        <i class="fas fa-circle-check"></i>
                                    </div>
                                    <div class="fc-kpi-label">Complete</div>
                                </div>

                                <div class="fc-kpi-value">{{ $counts['complete'] }}</div>

                                <div class="fc-kpi-meta-row">
                                    <div class="fc-kpi-pill"
                                        data-bs-toggle="tooltip"
                                        title="Percentuale di Complete sui contatti">
                                        {{ rtrim(rtrim(number_format($totPerc['complete'], 1), '0'), '.') }}%
                                    </div>
                                    <div class="fc-kpi-meta-text"></div>
                                </div>
                            </div>

                            <div class="fc-kpi-item fc-warn">
                                <div class="fc-kpi-top">
                                    <div class="fc-kpi-icon">
                                        <i class="fas fa-bullseye"></i>
                                    </div>
                                    <div class="fc-kpi-label">Non in target</div>
                                </div>

                                <div class="fc-kpi-value">{{ $counts['non_target'] }}</div>

                                <div class="fc-kpi-meta-row">
                                    <div class="fc-kpi-pill"
                                        data-bs-toggle="tooltip"
                                        title="Percentuale di Non in target sui contatti">
                                        {{ rtrim(rtrim(number_format($totPerc['non_target'], 1), '0'), '.') }}%
                                    </div>

                                </div>
                            </div>

                            <div class="fc-kpi-item fc-danger">
                                <div class="fc-kpi-top">
                                    <div class="fc-kpi-icon">
                                        <i class="fas fa-arrow-trend-up"></i>
                                    </div>
                                    <div class="fc-kpi-label">Over quota</div>
                                </div>

                                <div class="fc-kpi-value">{{ $counts['over_quota'] }}</div>

                                <div class="fc-kpi-meta-row">
                                    <div class="fc-kpi-pill"
                                        data-bs-toggle="tooltip"
                                        title="Percentuale di Over quota sui contatti">
                                        {{ rtrim(rtrim(number_format($totPerc['over_quota'], 1), '0'), '.') }}%
                                    </div>

                                </div>
                            </div>

                            <div class="fc-kpi-item fc-info">
                                <div class="fc-kpi-top">
                                    <div class="fc-kpi-icon">
                                        <i class="fas fa-pause"></i>
                                    </div>
                                    <div class="fc-kpi-label">Sospese</div>
                                </div>

                                <div class="fc-kpi-value">{{ $counts['sospese'] }}</div>

                                <div class="fc-kpi-meta-row">
                                    <div class="fc-kpi-pill"
                                        data-bs-toggle="tooltip"
                                        title="Percentuale di Sospese sui contatti">
                                        {{ rtrim(rtrim(number_format($totPerc['sospese'], 1), '0'), '.') }}%
                                    </div>

                                </div>
                            </div>

                            <div class="fc-kpi-item fc-dark">
                                <div class="fc-kpi-top">
                                    <div class="fc-kpi-icon">
                                        <i class="fas fa-lock"></i>
                                    </div>
                                    <div class="fc-kpi-label">Bloccate</div>
                                </div>

                                <div class="fc-kpi-value">{{ $counts['bloccate'] }}</div>

                                <div class="fc-kpi-meta-row">
                                    <div class="fc-kpi-pill"
                                        data-bs-toggle="tooltip"
                                        title="Percentuale di Bloccate sui contatti">
                                        {{ rtrim(rtrim(number_format($totPerc['bloccate'], 1), '0'), '.') }}%
                                    </div>

                                </div>
                            </div>

                        <div class="fc-kpi-item fc-base">
                            <div class="fc-kpi-top">
                                <div class="fc-kpi-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="fc-kpi-label">Contatti</div>
                            </div>

                            <div class="fc-kpi-value">{{ $counts['contatti'] }}</div>
                        </div>

                            </div>

                            @if(count($panelCounts) == 1 && array_key_exists('Interactive', $panelCounts))
                                <div class="fc-kpi-extra mt-3">
                                    <div class="fc-kpi-extra-item">
                                        <div class="fc-kpi-label">Abilitati panel</div>
                                        <div class="fc-kpi-value">{{ $abilitati }}</div>
                                    </div>

                                    <div class="fc-kpi-extra-item">
                                        <div class="fc-kpi-label">Utenti disponibili</div>
                                        <div class="fc-kpi-value">{{ $utentiDisponibili }}</div>
                                    </div>

                                    <div class="fc-kpi-extra-item fc-wide">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="fc-kpi-label mb-0">Stima interviste</div>
                                            <i class="fas fa-info-circle text-primary"
                                            data-bs-toggle="tooltip"
                                            title="La stima interviste si intende per interviste totali, non tiene conto di quote o target."></i>
                                        </div>
                                        <div class="fc-kpi-value">{{ $stimaInterviste }} <span class="fc-kpi-note">*</span></div>
                                    </div>
                                </div>
                            @endif
                        </div>

                    </div>

                        <!-- Generazione dinamica dei contenuti dei panel -->
                        @foreach ($panelCounts as $panelName => $panelData)
                            <div class="tab-pane fade" id="tab{{ $loop->index + 2 }}">

                        @php
                            $panelIr = $panelData['redemption'] ?? 0;
                            if (!is_numeric($panelIr)) { $panelIr = 0; }

                            $panelContatti = max(1, (int) ($panelData['contatti'] ?? 0));

                            $panelPerc = [
                                'complete' => round(((int) ($panelData['complete'] ?? 0) / $panelContatti) * 100, 1),
                                'non_target' => round(((int) ($panelData['non_target'] ?? 0) / $panelContatti) * 100, 1),
                                'over_quota' => round(((int) ($panelData['over_quota'] ?? 0) / $panelContatti) * 100, 1),
                                'sospese' => round(((int) ($panelData['sospese'] ?? 0) / $panelContatti) * 100, 1),
                                'bloccate' => round(((int) ($panelData['bloccate'] ?? 0) / $panelContatti) * 100, 1),
                                'contatti' => 100.0,
                            ];
                        @endphp

                                <div class="fc-kpi-card">

                                    <div class="fc-kpi-head">
                                        <div>
                                            <div class="fc-kpi-eyebrow">Panel</div>
                                            <div class="fc-kpi-title fc-panel-title">
                                                @if($panelName === 'Interactive')
                                                    <span class="fc-panel-title-badge fc-panel-interactive">
                                                        <i class="fa-solid fa-users me-1"></i>
                                                        {{ $panelName }}
                                                    </span>
                                                @elseif($panelName === 'Da lista')
                                                    <span class="fc-panel-title-badge fc-panel-lista"
                                                        data-bs-toggle="tooltip"
                                                        title="Interviste con uid da lista, uid GUEST o non classificate con nessun panel">
                                                        <i class="fa-solid fa-list-ul me-1"></i>
                                                        {{ $panelName }}
                                                        <i class="fa-solid fa-circle-info ms-1"></i>
                                                    </span>
                                                @else
                                                    <span class="fc-panel-title-badge fc-panel-external">
                                                        <i class="fa-solid fa-building-user me-1"></i>
                                                        {{ $panelName }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="fc-kpi-ir">
                                            <div class="fc-kpi-ir-label">IR</div>
                                            <div class="fc-kpi-ir-value">{{ $panelIr }}%</div>
                                        </div>
                                    </div>

                                    <div class="fc-kpi-progress mt-3" data-ir="{{ $panelIr }}">
                                        <div class="fc-kpi-progress-track">
                                            <div class="fc-kpi-progress-fill" style="width: {{ $panelIr }}%"></div>
                                        </div>
                                        <div class="fc-kpi-progress-meta">
                                            <span>0%</span>
                                            <span>100%</span>
                                        </div>
                                    </div>

                                    <div class="fc-kpi-grid mt-3">

                                    <div class="fc-kpi-item fc-ok">
                                        <div class="fc-kpi-top">
                                            <div class="fc-kpi-icon">
                                                <i class="fas fa-circle-check"></i>
                                            </div>
                                            <div class="fc-kpi-label">Complete</div>
                                        </div>

                                        <div class="fc-kpi-value">{{ $panelData['complete'] }}</div>

                                        <div class="fc-kpi-meta-row">
                                            <div class="fc-kpi-pill"
                                                data-bs-toggle="tooltip"
                                                title="Percentuale di Complete sui contatti del panel">
                                                {{ rtrim(rtrim(number_format($panelPerc['complete'], 1), '0'), '.') }}%
                                            </div>

                                        </div>
                                    </div>

                                    <div class="fc-kpi-item fc-warn">
                                        <div class="fc-kpi-top">
                                            <div class="fc-kpi-icon">
                                                <i class="fas fa-bullseye"></i>
                                            </div>
                                            <div class="fc-kpi-label">Non in target</div>
                                        </div>

                                        <div class="fc-kpi-value">{{ $panelData['non_target'] }}</div>

                                        <div class="fc-kpi-meta-row">
                                            <div class="fc-kpi-pill"
                                                data-bs-toggle="tooltip"
                                                title="Percentuale di Non in target sui contatti del panel">
                                                {{ rtrim(rtrim(number_format($panelPerc['non_target'], 1), '0'), '.') }}%
                                            </div>

                                        </div>
                                    </div>

                                <div class="fc-kpi-item fc-danger">
                                    <div class="fc-kpi-top">
                                        <div class="fc-kpi-icon">
                                            <i class="fas fa-arrow-trend-up"></i>
                                        </div>
                                        <div class="fc-kpi-label">Over quota</div>
                                    </div>

                                    <div class="fc-kpi-value">{{ $panelData['over_quota'] }}</div>

                                    <div class="fc-kpi-meta-row">
                                        <div class="fc-kpi-pill"
                                            data-bs-toggle="tooltip"
                                            title="Percentuale di Over quota sui contatti del panel">
                                            {{ rtrim(rtrim(number_format($panelPerc['over_quota'], 1), '0'), '.') }}%
                                        </div>

                                    </div>
                                </div>

                                <div class="fc-kpi-item fc-info">
                                    <div class="fc-kpi-top">
                                        <div class="fc-kpi-icon">
                                            <i class="fas fa-pause"></i>
                                        </div>
                                        <div class="fc-kpi-label">Sospese</div>
                                    </div>

                                    <div class="fc-kpi-value">{{ $panelData['sospese'] }}</div>

                                    <div class="fc-kpi-meta-row">
                                        <div class="fc-kpi-pill"
                                            data-bs-toggle="tooltip"
                                            title="Percentuale di Sospese sui contatti del panel">
                                            {{ rtrim(rtrim(number_format($panelPerc['sospese'], 1), '0'), '.') }}%
                                        </div>

                                    </div>
                                </div>

                            <div class="fc-kpi-item fc-dark">
                                <div class="fc-kpi-top">
                                    <div class="fc-kpi-icon">
                                        <i class="fas fa-lock"></i>
                                    </div>
                                    <div class="fc-kpi-label">Bloccate</div>
                                </div>

                                <div class="fc-kpi-value">{{ $panelData['bloccate'] }}</div>

                                <div class="fc-kpi-meta-row">
                                    <div class="fc-kpi-pill"
                                        data-bs-toggle="tooltip"
                                        title="Percentuale di Bloccate sui contatti del panel">
                                        {{ rtrim(rtrim(number_format($panelPerc['bloccate'], 1), '0'), '.') }}%
                                    </div>

                                </div>
                            </div>

                                        <div class="fc-kpi-item fc-base">
                                    <div class="fc-kpi-top">
                                        <div class="fc-kpi-icon">
                                            <i class="fas fa-users"></i>
                                        </div>
                                        <div class="fc-kpi-label">Contatti</div>
                                    </div>

                                    <div class="fc-kpi-value">{{ $panelData['contatti'] }}</div>
                                </div>
                                    </div>

                                    {{-- Extra SOLO per Interactive (come prima), quando ci sono più panel --}}
                                    @if($panelName == "Interactive" && count($panelCounts) > 1)
                                        <div class="fc-kpi-extra mt-3">
                                            <div class="fc-kpi-extra-item">
                                                <div class="fc-kpi-label">Abilitati panel</div>
                                                <div class="fc-kpi-value">{{ $abilitati }}</div>
                                            </div>

                                            <div class="fc-kpi-extra-item">
                                                <div class="fc-kpi-label">Utenti disponibili</div>
                                                <div class="fc-kpi-value">{{ $utentiDisponibili }}</div>
                                            </div>

                                            <div class="fc-kpi-extra-item fc-wide">
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="fc-kpi-label mb-0">Stima interviste</div>
                                                    <i class="fas fa-info-circle text-primary"
                                                    data-bs-toggle="tooltip"
                                                    title="La stima interviste si intende per interviste totali, non tiene conto di quote o target."></i>
                                                </div>
                                                <div class="fc-kpi-value">{{ $stimaInterviste }}</div>
                                            </div>
                                        </div>
                                    @endif

                                </div>
                            </div>
                        @endforeach
                    </div>


                </div>
            </div>

            {{-- FINE MENU SINISTRA CON RISULTATO

            INIZIO MENU DESTRA CON GRAFICI FILTRATE --}}
<div class="col-md-6">
    <div class="custom-tab-container-modern fc-chart-wrap">

   @if($hasFiltrate)

            <!-- Tabs panel -->
            <ul class="nav custom-nav-tabs-modern fc-chart-tabs" id="panel-nav">
                @foreach ($panelCounts as $panelName => $panelData)
                    <li class="nav-item">
                        <a class="nav-link modern-tab-link fc-chart-tab {{ $loop->first ? 'active' : '' }}"
                           id="tab-panel-{{ $loop->index }}-nav"
                           data-bs-toggle="pill"
                           href="#tab-panel-{{ $loop->index }}">
                                @if($panelName === 'Interactive')
                            <span class="fc-panel-tab-icon fc-panel-interactive">
                                <i class="fa-solid fa-users"></i>
                            </span>
                            <span>{{ $panelName }}</span>
                        @elseif($panelName === 'Da lista')
                            <span class="fc-panel-tab-icon fc-panel-lista"
                                data-bs-toggle="tooltip"
                                title="Interviste con uid da lista, uid GUEST o non classificate con nessun panel">
                                <i class="fa-solid fa-list-ul"></i>
                            </span>
                            <span data-bs-toggle="tooltip"
                                title="Interviste con uid da lista, uid GUEST o non classificate con nessun panel">
                                {{ $panelName }}
                            </span>
                        @else
                            <span class="fc-panel-tab-icon fc-panel-external">
                                <i class="fa-solid fa-building-user"></i>
                            </span>
                            <span>{{ $panelName }}</span>
                        @endif
                        </a>
                    </li>
                @endforeach
            </ul>

            <!-- Contenuto -->
            <div class="tab-content custom-tab-content-modern fc-chart-content">
                @foreach ($panelCounts as $panelName => $panelData)
                    <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}"
                         id="tab-panel-{{ $loop->index }}">

                        <div class="fc-chart-card">
                            <div class="fc-chart-head">
                                <div>
                                    <div class="fc-chart-eyebrow">Quality</div>
                                    <div class="fc-chart-title">Analisi filtrate</div>
                                    <div class="fc-chart-subtitle">
                                        Panel: <b>{{ $panelName }}</b> — principali domande di screenout
                                    </div>
                                </div>

                                <div class="fc-chart-badge">
                                    <i class="fas fa-filter me-2"></i> Screenout
                                </div>
                            </div>

                            <div class="fc-chart-body">
                                <div class="fc-chart-canvas">
                                    <canvas id="chart-panel-{{ \Illuminate\Support\Str::slug($panelName, '-') }}"></canvas>
                                </div>
                            </div>
                        </div>

                    </div>
                @endforeach
            </div>

        @else

            <!-- EMPTY STATE (globale) -->
            <div class="fc-empty-card">
                <div class="fc-empty-icon">
                    <i class="fas fa-filter"></i>
                </div>
                <div class="fc-empty-title">Nessuna intervista filtrata da analizzare</div>
                <div class="fc-empty-subtitle">
                    Quando ci saranno screenout, qui vedrai le domande che stanno filtrando maggiormente.
                </div>
            </div>

        @endif


    </div>
</div>


            {{-- FINE PARTE SINISTRA GRAFICI FILTRATE --}}



        </div>

    <!-- fine prima riga dopo le card con status + filtrate -->

    <!-- seconda riga  -->

    <div class="row mt-4">


        <!-- Sezione Quote -->
<!-- Sezione Quote -->
<div class="col-md-6">
    <div class="card shadow-sm quota-card-modern">
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
                            <th>Quota</th>
                            <th class="text-center">Totale</th>
                            <th class="text-center">Entrate / Impatto</th>
                            <th class="text-center">Missing</th>
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
        $totaleIntervisteGenerale = 0;

        foreach ($quotaData as $quotaRow) {
            if ($quotaRow->quota === 'Interviste Totali') {
                $totaleIntervisteGenerale = (int) $quotaRow->entrate;
                break;
            }
        }
    @endphp

    @foreach ($quotaData as $quota)
        @php
            $totale = (int) $quota->totale;
            $entrate = (int) $quota->entrate;
            $missing = (int) $quota->missing;
            $quotaLabel = (string) $quota->quota;

            $isTotalRow = $quotaLabel === 'Interviste Totali';

            $denominator = $totaleIntervisteGenerale;
            $impactPercent = 0;

            if ($entrate > 0 && $denominator > 0) {
                $impactPercent = round(($entrate / $denominator) * 100, 1);
            }

            $ratioText = '';
            if ($entrate > 0 && $denominator > 0) {
                $ratioText = "({$entrate}/{$denominator})";
            }
        @endphp

        <tr class="quota-row {{ $isTotalRow ? 'quota-row-total' : '' }}">
            <td>
                <div class="quota-name-wrap">
                    <div class="quota-name">{{ $quotaLabel }}</div>
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
                        {{ $impactPercent > 0 ? number_format($impactPercent, 1) : '0' }}%
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

        <!-- Fine Sezione Quote -->

        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white d-flex align-items-center">
                    <i class="fas fa-list-alt me-2"></i> <h6 style="color:aliceblue" class="mb-0">Log Attività</h6>
                </div>
                <div class="card-body p-2">
                    <div class="table-responsive log-table-container" style="max-height: 350px; overflow-y: auto;">
                        <table class="table table-sm table-bordered table-hover text-center">
                            <thead class="sticky-header-log bg-secondary text-white">
                                <tr>
                                    <th class="small">IID</th>
                                    <th class="small">UID</th>
                                    <th class="small">Ultimo Update</th>
                                    <th class="small">Ultima Azione</th>
                                    <th class="small">Stato</th>
                                    <th class="small">Durata</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($logData as $log)
                                    <tr class="align-middle">
                                        <td class="small">{{ $log['iid'] }}</td>
                                        <td class="small">{{ $log['uid'] }}</td>
                                        <td class="small">{{ $log['ultimo_update'] }}</td>
                                        <td class="small">
                                            {!! $log['ultima_azione'] !!}
                                        </td>
                                        <td class="small">
                                            @if ($log['stato'] === 'Completa')
                                                <span style="font-size: 10px" class="badge bg-success"><i class="fas fa-check-circle"></i> {{ $log['stato'] }}</span>
                                            @elseif ($log['stato'] === 'Non in target')
                                                <span style="font-size: 10px" class="badge bg-danger"><i class="fas fa-times-circle"></i> {{ $log['stato'] }}</span>
                                            @elseif ($log['stato'] === 'Quotafull')
                                                <span style="font-size: 10px" class="badge bg-warning text-dark"><i class="fas fa-exclamation-triangle"></i> {{ $log['stato'] }}</span>
                                            @elseif ($log['stato'] === 'In Corso')
                                                <span style="font-size: 10px" class="badge bg-primary"><i class="fas fa-hourglass-half"></i> {{ $log['stato'] }}</span>
                                            @elseif ($log['stato'] === 'Bloccata')
                                                <span style="font-size: 10px" class="badge bg-dark"><i class="fas fa-ban"></i> {{ $log['stato'] }}</span>
                                            @else
                                                <span style="font-size: 10px" class="badge bg-secondary">{{ $log['stato'] }}</span>
                                            @endif
                                        </td>
                                        <td class="small">{{ $log['durata'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>




    </div>



    <!-- fine seconda riga  -->

    <!-- Terza riga: Dati giornalieri per panel -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="custom-tab-container-modern">
                <!-- Nav Tabs -->
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

                <!-- Tab Content -->
                <div class="tab-content custom-tab-content-modern">
                    <br/>
                    @foreach ($dataSummaryByPanel as $panelName => $summaryData)
                        <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}"
                             id="tab-date-{{ $loop->index }}">
                            <h4 class="tab-title text-center">Report giornaliero - {{ $panelName }}</h4>

                            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                <table class="table table-hover table-striped custom-table-modern text-center">
                                    <thead class="table-dark sticky-top">
                                        <tr>
                                            <th>Giorno</th>
                                            <th>Contatti</th>
                                            <th>Complete</th>
                                            <th>Non in target</th>
                                            <th>Quotafull</th>
                                            <th>Bloccate</th>
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

                                    @foreach ($rowKeys as $i => $date)
                                        @php
                                            $stats = $rows[$date];

                                            // ==== IR (come già fai) ====
                                            $sospese = $stats['contatti'] - ($stats['non_target'] + $stats['quotafull'] + $stats['complete'] + $stats['bloccate']);
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
                                                $prevSospese = $prev['contatti'] - ($prev['non_target'] + $prev['quotafull'] + $prev['complete'] + $prev['bloccate']);
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
                                            <td><strong>{{ $stats['display_date'] }}</strong></td>
                                            <td>{{ $stats['contatti'] }}</td>
                                            <td class="text-success fw-bold">{{ $stats['complete'] }}</td>
                                            <td class="text-warning fw-bold">{{ $stats['non_target'] }}</td>
                                            <td class="text-danger fw-bold">{{ $stats['quotafull'] }}</td>
                                            <td class="text-danger fw-bold">{{ $stats['bloccate'] }}</td>

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
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>

                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>



 <!-- fine  terza riga  -->



    <!-- fine container -->
</div>


<!-- MODALE DI DOWNLOAD -->
<div class="modal fade" id="downloadModal" tabindex="-1" aria-labelledby="downloadModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="downloadModalLabel">Scarica il file</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
            </div>
            <div class="modal-body">
                <p>Seleziona il panel per il quale vuoi scaricare il file:</p>

                <!-- Opzioni Panel -->
                <form id="downloadForm" action="{{ route('download.csv') }}" method="GET">
                    <input type="hidden" name="prj" value="{{ $prj }}">
                    <input type="hidden" name="sid" value="{{ $sid }}">

                    @foreach($panelCounts as $panelName => $panelData)
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="panel" id="panel_{{ $loop->index }}" value="{{ $panelName }}" required>
                            <label class="form-check-label" for="panel_{{ $loop->index }}">
                                {{ $panelName }}
                            </label>
                        </div>
                    @endforeach
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
                <button type="submit" class="btn btn-primary" form="downloadForm">Scarica CSV</button>
            </div>
        </div>
    </div>
</div>

<!-- MODALE PER RESET BLOCCATE -->
<div class="modal fade" id="resetBloccateModal" tabindex="-1" aria-labelledby="resetBloccateModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="resetBloccateModalLabel">Reset Interviste Bloccate</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
            </div>
            <div class="modal-body">
                <p>Numero di interviste bloccate per questa ricerca: <strong>{{ $counts['bloccate'] }}</strong></p>
                <p>Vuoi resettare tutte le interviste bloccate?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                <button type="button" class="btn btn-warning" id="resetBloccateBtn">Reset</button>
            </div>
        </div>
    </div>
</div>



@endsection

@section('scripts')
<script>
    window.FieldControlConfig = {
        csrfToken: @json(csrf_token()),
        prj: @json($prj),
        sid: @json($sid),
        routes: {
            closeSurvey: @json(route('close.survey')),
            resetBloccate: @json(route('reset.bloccate'))
        },
        filtrateCountsByPanel: @json($filtrateCountsByPanel)
    };
</script>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.5.1/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0"></script>
<script src="{{ asset('js/fieldControl.js') }}"></script>
@endsection
