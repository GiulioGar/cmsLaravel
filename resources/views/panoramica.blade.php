@php
use Illuminate\Support\Str;

$perQuestion = $summary['per_question'] ?? [];
$typeIcons = [
    'choice' => 'fas fa-list-ul',
    'scale'  => 'fas fa-table-cells',
    'open'   => 'fas fa-pen',
];
$typeLabels = [
    'choice' => 'Scelta',
    'scale'  => 'Griglia',
    'open'   => 'Aperta',
];
$crossVariables = $summary['variables'] ?? [];
$sospese = max(0, ($summary['files'] ?? 0) - ($summary['complete'] ?? 0) - ($summary['screenout'] ?? 0) - ($summary['quotafull'] ?? 0));
$totalFiles = max(1, (int) ($summary['files'] ?? 0));
$heroKpis = [
    ['variant' => 'complete', 'icon' => 'fas fa-circle-check', 'label' => 'Complete', 'value' => $summary['complete'] ?? 0],
    ['variant' => 'screenout', 'icon' => 'fas fa-bullseye', 'label' => 'Screenout', 'value' => $summary['screenout'] ?? 0],
    ['variant' => 'quotafull', 'icon' => 'fas fa-arrow-trend-up', 'label' => 'Quotafull', 'value' => $summary['quotafull'] ?? 0],
    ['variant' => 'pending', 'icon' => 'fas fa-hourglass-half', 'label' => 'Sospese / in corso', 'value' => $sospese],
    ['variant' => '', 'icon' => 'fas fa-file-lines', 'label' => 'Interviste totali', 'value' => $summary['files'] ?? 0],
];

// Menu laterale: le domande in loop (stesso prefisso SDL) diventano una famiglia,
// posizionata dove compare il primo elemento
$sidebarEntries = [];
$familyIndex = [];
foreach ($questions as $q) {
    if (empty($q['loop'])) {
        $sidebarEntries[] = ['type' => 'question', 'question' => $q];
        continue;
    }
    if (!isset($familyIndex[$q['loop']])) {
        $familyIndex[$q['loop']] = count($sidebarEntries);
        $sidebarEntries[] = ['type' => 'family', 'prefix' => $q['loop'], 'questions' => []];
    }
    $sidebarEntries[$familyIndex[$q['loop']]]['questions'][] = $q;
}
// Famiglie con un solo elemento: meglio come voce normale
foreach ($sidebarEntries as $i => $entry) {
    if ($entry['type'] === 'family' && count($entry['questions']) === 1) {
        $sidebarEntries[$i] = ['type' => 'question', 'question' => $entry['questions'][0]];
    }
}
@endphp

@extends('layouts.main')

@section('head')
<link rel="stylesheet" href="{{ asset('css/fieldControl.css') }}">
<link rel="stylesheet" href="{{ asset('css/panoramica.css') }}?v={{ @filemtime(public_path('css/panoramica.css')) }}">
@endsection

@section('content')

<div class="container field-control-container">

    <nav class="navbar custom-navbar mb-4">
        <div class="container-fluid d-flex align-items-center justify-content-start px-0">
            <a class="navbar-brand d-flex align-items-center" href="{{ url('fieldControl?prj='.$prj.'&sid='.$sid) }}">
                <span class="fc-brand-icon"><i class="fas fa-chart-bar"></i></span>
                <span>Status Field</span>
            </a>

            <div class="fc-navbar-sep d-none d-md-block"></div>

            <ul class="nav custom-nav-links">
                <li class="nav-item dropdown position-relative">
                    <a class="nav-link dropdown-toggle" href="#" id="ongoingResearchDropdown" role="button"
                       data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-tasks me-1"></i> Ricerche in corso
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="ongoingResearchDropdown">
                        @forelse($ricercheInCorso as $ricerca)
                            <li>
                                <a class="dropdown-item"
                                   href="{{ route('panoramica.index', ['prj' => $ricerca->prj, 'sid' => $ricerca->sur_id]) }}">
                                    {{ $ricerca->description }}
                                </a>
                            </li>
                        @empty
                            <li><span class="dropdown-item text-muted">Nessuna ricerca attiva</span></li>
                        @endforelse
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="{{ url('surveys') }}"><b>Vedi tutte</b></a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="#">
                        <i class="fas fa-magnifying-glass-chart me-1"></i> Panoramica
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('targetField.index', ['prj' => $prj, 'sid' => $sid]) }}">
                        <i class="fa-solid fa-arrows-down-to-people me-1"></i> Imposta Target
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('fieldQuality.index', ['prj' => $prj, 'sid' => $sid]) }}">
                        <i class="fas fa-check-circle me-1"></i> Controllo Qualità
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    {{-- Intestazione ricerca + KPI generali --}}
    <div class="pn-hero">
        <div class="pn-hero-id">
            <div class="pn-eyebrow"><i class="fas fa-magnifying-glass-chart me-1"></i>Panoramica domanda per domanda</div>
            <div class="pn-hero-title">#{{ $panelData->sur_id ?? $sid }}</div>
            <div class="pn-hero-sub">{{ $panelData->description ?? '' }}</div>
        </div>
        <div class="pn-kpis">
            @foreach($heroKpis as $kpi)
                @php $kpiPct = round($kpi['value'] / $totalFiles * 100, 1); @endphp
                <div class="pn-kpi{{ $kpi['variant'] ? ' pn-kpi--'.$kpi['variant'] : '' }}">
                    <div class="pn-kpi-top">
                        <span class="pn-kpi-icon"><i class="{{ $kpi['icon'] }}"></i></span>
                        <div>
                            <span class="pn-kpi-val">{{ number_format($kpi['value'], 0, ',', '.') }}</span>
                            <span class="pn-kpi-key">{{ $kpi['label'] }}</span>
                        </div>
                    </div>
                    <div class="pn-kpi-pct">{{ number_format($kpiPct, 1, ',', '.') }}%</div>
                    <div class="pn-kpi-bar"><div class="pn-kpi-bar-fill" style="width: {{ min(100, $kpiPct) }}%"></div></div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="pn-layout">
        {{-- Menu laterale domande --}}
        <aside class="pn-sidebar">
            <div class="pn-sidebar-head">
                <div class="pn-sidebar-title">Domande <span class="pn-count">{{ count($questions) }}</span></div>
                <div class="pn-search">
                    <i class="fas fa-search"></i>
                    <input type="text" id="pnQuestionSearch" placeholder="Cerca codice o testo...">
                </div>
                <div class="pn-filter" role="group">
                    <button type="button" class="pn-filter-btn is-active" data-filter="all">Tutte</button>
                    <button type="button" class="pn-filter-btn" data-filter="exits">
                        <i class="fas fa-door-open me-1"></i>Con uscite
                    </button>
                </div>
            </div>

            <div class="pn-qlist" id="pnQuestionList">
                @forelse($sidebarEntries as $entry)
                    @if($entry['type'] === 'question')
                        @include('partials.panoramica-qitem', ['q' => $entry['question']])
                    @else
                        @php
                            $familyExits = 0;
                            foreach ($entry['questions'] as $fq) {
                                $familyExits += ($perQuestion[$fq['id']]['screenout'] ?? 0) + ($perQuestion[$fq['id']]['quotafull'] ?? 0);
                            }
                            $first = $entry['questions'][0];
                            $familyTextShort = Str::limit($first['text'], 110, '…');
                            $familyTextTruncated = $familyTextShort !== $first['text'];
                        @endphp
                        <div class="pn-qfamily" data-family="{{ $entry['prefix'] }}">
                            <div class="pn-qfamily-row">
                                <button type="button" class="pn-qfamily-head" aria-expanded="false">
                                    <span class="pn-qitem-type"><i class="fas fa-repeat"></i></span>
                                    <span class="pn-qitem-body">
                                        <span class="pn-qitem-code">{{ rtrim($entry['prefix'], '.') }} <span class="pn-qfamily-count">loop · {{ count($entry['questions']) }}</span></span>
                                        <span class="pn-qitem-text"@if($familyTextTruncated) data-bs-toggle="tooltip" data-bs-custom-class="pn-text-tooltip" title="{{ $first['text'] }}"@endif>{{ $familyTextShort }}</span>
                                    </span>
                                    @if($familyExits > 0)
                                        <span class="pn-qitem-exits" title="Uscite su queste domande">{{ $familyExits }}</span>
                                    @endif
                                    <i class="fas fa-chevron-down pn-qfamily-caret"></i>
                                </button>
                                <button type="button" class="pn-qfamily-agg-btn" data-family-agg="{{ $entry['prefix'] }}"
                                        data-bs-toggle="tooltip" data-bs-custom-class="pn-text-tooltip" title="Vista aggregata: confronta tutte le domande del ciclo in un unico grafico">
                                    <i class="fas fa-chart-column"></i>
                                </button>
                            </div>
                            <div class="pn-qfamily-items">
                                @foreach($entry['questions'] as $fq)
                                    @include('partials.panoramica-qitem', ['q' => $fq, 'inFamily' => true])
                                @endforeach
                            </div>
                        </div>
                    @endif
                @empty
                    <div class="pn-empty pn-empty--compact">
                        <i class="fa-solid fa-robot"></i>
                        <span>Nessuna domanda disponibile da Primis</span>
                    </div>
                @endforelse
                <div class="pn-empty pn-empty--compact d-none" id="pnNoMatch">
                    <i class="fa-solid fa-robot"></i>
                    <span>Nessuna domanda corrisponde al filtro</span>
                </div>
            </div>
        </aside>

        {{-- Area analisi --}}
        <main class="pn-main" id="pnMain">
            <div class="pn-empty pn-empty--hero" id="pnPlaceholder">
                <i class="fa-solid fa-robot"></i>
                <div class="pn-empty-title">Seleziona una domanda</div>
                <div class="pn-empty-sub">Scegli una domanda dal menu a sinistra per vedere complete, uscite e andamento.</div>
            </div>

            <div id="pnContent" class="d-none">
                {{-- Domanda selezionata --}}
                <section class="pn-card pn-qcard">
                    <div class="pn-qcard-grid">
                        <div class="pn-qcard-left">
                            <div class="pn-qcard-top">
                                <span class="pn-qcard-code" id="pnQCode"></span>
                                <span class="pn-chip" id="pnQType"></span>
                                <span class="pn-chip pn-chip--cross d-none" id="pnQCtx"></span>
                                <span class="pn-qcard-reach-inline" title="Interviste arrivate a questa domanda">
                                    <i class="fas fa-user-check"></i>
                                    <b id="pnQReached"></b> arrivati alla domanda
                                </span>
                            </div>
                            <div class="pn-qcard-text" id="pnQText"></div>
                        </div>
                        <div class="pn-qcard-right">
                            <div class="pn-qcard-right-head">
                                <span class="pn-qcard-right-title">Esiti della domanda</span>
                                <span class="pn-qcard-right-note">% sugli arrivati</span>
                            </div>
                            <div class="pn-qoutcomes" id="pnQOutcomes"></div>
                        </div>
                    </div>
                </section>

                {{-- Report complete e uscite: affiancati su desktop --}}
                <div class="pn-duo" id="pnDuo">
                    <section class="pn-card" id="pnCompleteCard">
                        <div class="pn-card-head">
                            <div class="pn-card-title"><i class="fas fa-circle-check pn-ico pn-ico--complete"></i>Report complete</div>
                            <div class="pn-card-base" id="pnCompleteNote"></div>
                        </div>
                        <div class="pn-card-body" id="pnCompleteBody"></div>
                    </section>

                    <section class="pn-card d-none" id="pnExitCard">
                        <div class="pn-card-head">
                            <div class="pn-card-title"><i class="fas fa-shuffle pn-ico pn-ico--exit"></i>Uscite e sospensioni</div>
                            <div class="pn-card-base" id="pnExitBase"></div>
                        </div>
                        <div class="pn-card-body">
                            <div class="pn-exit-tabs" id="pnExitTabs"></div>
                            <div class="pn-exit-layout">
                                <div class="pn-exit" id="pnExitDetail"></div>
                                <div class="pn-exit-summary" id="pnExitSummary"></div>
                            </div>
                            <div class="pn-card-foot">Interviste ferme su questa domanda (ultima domanda nel File Dati)</div>
                        </div>
                    </section>
                </div>

                {{-- Incrocio: selettore nell'intestazione della sezione --}}
                <section class="pn-card" id="pnCrossCard">
                    <div class="pn-card-head pn-card-head--cross">
                        <label class="pn-card-title mb-0" for="pnCrossSelect"><i class="fas fa-shuffle pn-ico pn-ico--cross"></i>Incrocio per</label>
                        <div class="pn-cross-picker">
                    <select id="pnCrossSelect" class="pn-select">
                        <option value="">Nessun incrocio</option>
                        @if($hasMultiplePanels || !empty($crossVariables))
                            <optgroup label="Panel e variabili">
                                @if($hasMultiplePanels)
                                    <option value="panel">Panel</option>
                                @endif
                                @foreach($crossVariables as $varKey => $varValues)
                                    <option value="var:{{ $varKey }}">Variabile {{ $varKey }}{{ isset($variableLabels[$varKey]) ? ' · etichette da '.$variableLabels[$varKey]['source'] : '' }} ({{ count($varValues) }} valori)</option>
                                @endforeach
                            </optgroup>
                        @endif
                        <optgroup label="Domande a scelta">
                            @foreach($questions as $q)
                                @if($q['type'] === 'choice' && (($perQuestion[$q['id']]['complete'] ?? 0) > 0))
                                    <option value="q:{{ $q['id'] }}" data-question-id="{{ $q['id'] }}">{{ $q['code'] }}{{ !empty($q['context']) ? ' ['.$q['context'].']' : '' }} - {{ Str::limit($q['text'], 70) }}</option>
                                @endif
                            @endforeach
                        </optgroup>
                    </select>
                            <button type="button" class="pn-icon-btn d-none" id="pnCrossClear" title="Rimuovi incrocio">
                                <i class="fas fa-xmark"></i>
                            </button>
                        </div>
                        <div class="pn-cross-tools d-none" id="pnCrossTools">
                            <span class="pn-tools-label">Vista:</span>
                            <div class="pn-seg pn-seg--view" role="group" id="pnCrossView">
                                <button type="button" class="pn-seg-btn is-active" data-view="table"><i class="fas fa-table-cells me-1"></i>Tabella</button>
                                <button type="button" class="pn-seg-btn" data-view="chart"><i class="fas fa-chart-bar me-1"></i>Grafico</button>
                            </div>
                            <span class="pn-badge" id="pnCrossCount"></span>
                        </div>
                    </div>
                    <div class="pn-card-sub d-none" id="pnCrossSub">
                        <span class="pn-card-subtitle" id="pnCrossTitle"></span>
                        <span class="pn-card-note" id="pnCrossNote"></span>
                    </div>
                    <div class="pn-card-body" id="pnCrossBody"></div>
                </section>

                <section class="pn-card pn-collapsible d-none" id="pnTrendCard">
                    <button type="button" class="pn-card-head pn-collapse-head" id="pnTrendToggle" aria-expanded="false" aria-controls="pnTrendBody">
                        <span class="pn-card-title"><i class="fas fa-chart-column pn-ico pn-ico--trend"></i><span id="pnTrendTitle">Risposte giornaliere</span></span>
                        <i class="fas fa-chevron-down pn-collapse-caret"></i>
                    </button>
                    <div class="pn-card-body" id="pnTrendBody">
                        <div class="pn-trend-bar">
                            <div class="pn-card-note" id="pnTrendNote"></div>
                            <div class="pn-seg" role="group" id="pnTrendMode">
                                <button type="button" class="pn-seg-btn is-active" data-mode="daily">Giornaliero</button>
                                <button type="button" class="pn-seg-btn" data-mode="cumulative">Cumulato</button>
                            </div>
                        </div>
                        <div id="pnTrendControls"></div>
                        <div class="pn-chart" id="pnChartTrend"></div>
                    </div>
                </section>
            </div>

            <div id="pnFamilyContent" class="d-none">
                <section class="pn-card pn-qcard">
                    <div class="pn-qcard-top">
                        <span class="pn-qcard-code" id="pnFamCode"></span>
                        <span class="pn-chip pn-chip--cross"><i class="fas fa-repeat me-1"></i>Vista aggregata · loop</span>
                    </div>
                    <div class="pn-qcard-text" id="pnFamText"></div>
                </section>

                <section class="pn-card">
                    <div class="pn-card-head pn-card-head--cross">
                        <div class="pn-card-title"><i class="fas fa-ranking-star pn-ico pn-ico--cross"></i>Classifica tra le domande del ciclo</div>
                    </div>
                    <div id="pnFamNav"></div>
                    <div class="pn-card-body">
                        <div class="pn-xs-chart" id="pnFamChart"></div>
                        <div class="pn-legend-note" id="pnFamNote"></div>
                    </div>
                </section>
            </div>

            <div class="pn-loading d-none" id="pnLoading">
                <div class="spinner-border" role="status"></div>
                <span>Analisi in corso...</span>
            </div>
        </main>
    </div>
</div>
@endsection

@section('scripts')
@include('partials.field-toolbar-scripts')
<script src="https://cdn.jsdelivr.net/npm/echarts@6/dist/echarts.min.js"></script>
<script>
    window.PanoramicaConfig = {
        prj: @json($prj),
        sid: @json($sid),
        questionUrl: @json(route('panoramica.question')),
        familyUrl: @json(route('panoramica.family'))
    };
</script>
<script src="{{ asset('js/panoramica.js') }}?v={{ @filemtime(public_path('js/panoramica.js')) }}"></script>
@endsection
