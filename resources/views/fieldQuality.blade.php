@extends('layouts.main')

@section('content')
    <link rel="stylesheet" href="{{ asset('css/fieldControl.css') }}">
    <link rel="stylesheet" href="{{ asset('css/fieldQuality.css') }}">

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
            <li class="nav-item dropdown position-relative">
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
<!-- Bottone Download -->
<a href="#" class="nav-link" data-bs-toggle="modal" data-bs-target="#downloadModal">
    <i class="fas fa-download me-1"></i> Download
</a>


            </li>

            <!-- Impostazioni con dropdown -->
            <li class="nav-item dropdown position-relative">
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


        <!-- Contenuto principale della pagina -->
        <div class="row">
            <!-- COLONNA SINISTRA -->
            <div class="col-md-5">
<div class="card quality-card mb-4">
  <div class="quality-card-header quality-header-left">
    <h5 class="mb-0">Statistiche Generali</h5>
  </div>
  <div class="quality-card-body">
    <div class="row text-center g-3">
      <!-- Totale Interviste -->
<div class="col-12 col-sm-6">
  <div class="stat-card p-3 text-center">
    <div class="stat-icon mb-2">
      <i class="fas fa-users"></i>
    </div>
    <div class="stat-value">{{ $totalInterviews }}</div>
    <div class="stat-label">Interviste</div>
  </div>
</div>
      <!-- Punteggio Medio -->
      <div class="col-12 col-sm-6">
        <div class="stat-card p-3">
          <div class="stat-icon mb-2">
            <i class="fas fa-star"></i>
          </div>
          <div class="stat-value">{{ $averageScore ?? '—' }}</div>
          <div class="stat-label">Score Medio (0–100)</div>
          @if($maxScore !== null && $minScore !== null)
          <div class="fq-stat-minmax text-center">
            <small class="text-success">▲ {{ $maxScore }}</small>
            <small class="text-muted"> · </small>
            <small class="text-danger">▼ {{ $minScore }}</small>
          </div>
          @endif
        </div>
      </div>
      <!-- LOI Mediana -->
      <div class="col-12 col-sm-6">
        <div class="stat-card p-3">
<div class="stat-icon mb-2">
  <i class="fas fa-clock"></i>
</div>
<div class="stat-value">{{ $loiMediaFormatted }}</div>
<div class="stat-label">LOI Mediana (min)</div>
        </div>
      </div>
      <!-- Interviste di qualità -->
      <div class="col-12 col-sm-6">
        <div class="stat-card p-3">
            <div class="stat-icon mb-2">
            <i class="fas fa-chart-pie"></i>
            </div>
          <span class="badge bg-success">Alta qualità: {{ $pctHigh }}%</span><br/>
          <span class="badge bg-warning text-dark mt-1">Accettabile: {{ $pctAccept }}%</span><br/>
          <span class="badge bg-danger mt-1">Bassa qualità: {{ $pctLow }}%</span>
          @if($notEvaluableInterviews > 0)
          <br/><span class="badge bg-secondary mt-1">Non valutabili: {{ $pctNotEvaluable }}%</span>
          @endif
        </div>
      </div>
    </div>
  </div>
</div>
            </div>

            <!-- COLONNA DESTRA -->
            <div class="col-md-7">
                <div class="card quality-card mb-4">
                    <div class="quality-card-header quality-header-right">
                        <h5 class="mb-0">Lista interviste (complete)</h5>
                    </div>
                    <div class="quality-card-body p-0">
                        @if(count($completeInterviews) > 0)
                            <!-- Filtri lista interviste -->
                            <div class="fq-filter-bar">
                                <input type="text" id="flt-iv-search" class="form-control form-control-sm" placeholder="Cerca IID / UID…">
                                <select id="flt-iv-rating" class="form-select form-select-sm">
                                    <option value="">Tutte le qualità</option>
                                    <option>Ottima</option>
                                    <option>Molto buona</option>
                                    <option>Buona</option>
                                    <option>Accettabile</option>
                                    <option>Da verificare</option>
                                    <option>Sospetta</option>
                                    <option>Scarsa</option>
                                    <option>Molto scarsa</option>
                                    <option>Probabile fake</option>
                                    <option>Fortemente inattendibile</option>
                                    <option>Non valutabile</option>
                                </select>
                            </div>
                            <!-- Contenitore per scroll e header fisso -->
                            <div class="quality-table-container">
                                <table id="tbl-interviews" class="table table-hover quality-table-interviews">
                                    <thead>
                                        <tr>
                                            <th class="small">IID</th>
                                            <th class="small">UID</th>
                                            <th class="small" style="min-width:120px">Score</th>
                                            <th class="small" style="min-width:200px">Motivazioni</th>
                                            <th class="small" style="width:32px"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($completeInterviews as $interview)
                                        @php
                                            $ivScore    = $interview['score'];
                                            $ivHasScore = $ivScore !== null;
                                            $ivSc       = $ivHasScore ? (int) $ivScore : null;

                                            if      (!$ivHasScore) $ivCls = 'fq-q-unknown';
                                            elseif  ($ivSc >= 90)  $ivCls = 'fq-q-excellent';
                                            elseif  ($ivSc >= 70)  $ivCls = 'fq-q-good';
                                            elseif  ($ivSc >= 50)  $ivCls = 'fq-q-warning';
                                            elseif  ($ivSc >= 30)  $ivCls = 'fq-q-bad';
                                            else                   $ivCls = 'fq-q-critical';

                                            $ivStars  = $interview['stars'] !== null ? (float) $interview['stars'] : null;
                                            $ivFullS  = $ivStars !== null ? (int) floor($ivStars) : 0;
                                            $ivHalfS  = $ivStars !== null && ($ivStars - $ivFullS) >= 0.5;
                                            $ivEmptyS = $ivStars !== null ? (5 - $ivFullS - ($ivHalfS ? 1 : 0)) : 0;

                                            $ivCovLevel = $interview['quality_coverage']['level'] ?? 'none';
                                            $ivCovLabel = $interview['quality_coverage']['label'] ?? 'Non valutabile';

                                            $ivOpenAvail  = !empty($interview['quality_criteria']['open']['available']);
                                            $ivScaleAvail = !empty($interview['quality_criteria']['scale']['available']);
                                            $ivLoiAvail   = !empty($interview['quality_criteria']['loi']['available']);

                                            $ivOpenRisk  = $ivOpenAvail  ? ($interview['quality_risks']['open']  ?? null) : null;
                                            $ivScaleRisk = $ivScaleAvail ? ($interview['quality_risks']['scale'] ?? null) : null;
                                            $ivLoiRisk   = $ivLoiAvail   ? ($interview['quality_risks']['loi']   ?? null) : null;

                                            $ivOpenFakePct  = $ivOpenAvail ? ($interview['quality_criteria']['open']['fake_percentage']   ?? null) : null;
                                            $ivOpenConf     = $ivOpenAvail ? ($interview['quality_criteria']['open']['confidence_level']   ?? null) : null;
                                            $ivOpenEw       = $ivOpenAvail ? ($interview['quality_criteria']['open']['effective_weight']   ?? null) : null;

                                            $ivCapApplied   = !empty($interview['quality_score_cap']['applied']);
                                            $ivCapMax       = $interview['quality_score_cap']['maximum_score'] ?? null;
                                        @endphp
                                            <tr data-iid="{{ $interview['iid'] }}" data-uid="{{ $interview['uid'] }}" data-rating="{{ $interview['rating_label'] ?? 'Non valutabile' }}">
                                                <td class="small">{{ $interview['iid'] }}</td>
                                                <td class="small">{{ $interview['uid'] }}</td>

                                                <!-- Score: numero / stelle / etichetta / copertura -->
                                                <td>
                                                    <div class="fq-score-block {{ $ivCls }}">
                                                        @if($ivHasScore)
                                                            <div class="fq-score-num">
                                                                {{ $ivSc }}<span class="fq-score-denom"> / 100</span>
                                                            </div>
                                                            <div class="fq-stars">
                                                                @for($si = 0; $si < $ivFullS; $si++)<i class="fas fa-star"></i>@endfor
                                                                @if($ivHalfS)<i class="fas fa-star-half-alt"></i>@endif
                                                                @for($si = 0; $si < $ivEmptyS; $si++)<i class="far fa-star"></i>@endfor
                                                            </div>
                                                            <div class="fq-score-label">{{ $interview['rating_context_label'] ?? $interview['rating_label'] }}</div>
                                                            @if($ivCapApplied)
                                                                <div class="fq-cap-badge">
                                                                    <i class="fas fa-exclamation-triangle"></i> cap {{ $ivCapMax }}
                                                                </div>
                                                            @endif
                                                        @else
                                                            <div class="fq-score-num">—</div>
                                                            <div class="fq-score-label">Non valutabile</div>
                                                        @endif
                                                        <div class="fq-coverage-badge fq-cov-{{ $ivCovLevel }}">{{ $ivCovLabel }}</div>
                                                    </div>
                                                </td>

                                                <!-- Motivazioni -->
                                                <td class="fq-motivations">
                                                    @php
                                                        $mot      = $interview['quality_motivation'] ?? [];
                                                        $motLines = array_values(array_filter([
                                                            $mot['open']  ?? null,
                                                            $mot['scale'] ?? null,
                                                            $mot['loi']   ?? null,
                                                        ]));
                                                    @endphp
                                                    @if(!empty($motLines))
                                                        <ul class="fq-mot-list mb-0">
                                                            @foreach($motLines as $line)
                                                                <li>{{ $line }}</li>
                                                            @endforeach
                                                        </ul>
                                                    @else
                                                        <span class="text-muted fst-italic small">Nessun criterio disponibile</span>
                                                    @endif
                                                </td>

                                                <!-- Note: popover motivazioni -->
                                                <td class="text-center align-middle">
                                                    <button type="button" class="fq-reasons-btn"
                                                        data-bs-toggle="popover"
                                                        data-bs-trigger="focus"
                                                        data-bs-placement="auto"
                                                        data-fq-reasons="{{ json_encode($interview['quality_reasons'] ?? []) }}">
                                                        <i class="fas fa-info-circle"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="p-3">
                                <p class="text-muted">Nessuna intervista completa trovata.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div><!-- row -->


@php
    $loiRatioByIid = [];
    foreach ($completeInterviews as $_iv) {
        $loiRatioByIid[$_iv['iid']] = $_iv['quality_criteria']['loi']['ratio'] ?? null;
    }
@endphp
        <!-- SECONDA RIGA -->
<div class="row">
    <!-- COLONNA SINISTRA (30%) -->
    <div class="col-md-4">
        <div class="quality-card shadow-sm mb-4">
            <div class="quality-card-header quality-header-left">
                <h5 class="mb-0">Controllo LOI</h5>
            </div>
            <div class="quality-card-body">
                <div class="fq-filter-bar">
                    <button id="flt-loi-btn" class="btn btn-sm btn-outline-warning" type="button">
                        <i class="fas fa-filter"></i> &lt; 50% mediana
                    </button>
                </div>
                <div class="quality-table-container">
                    <table id="tbl-loi" class="table table-hover quality-table-lower">
                        <thead>
                            <tr>
                                <th class="small">IID</th>
                                <th class="small">UID</th>
                                <th class="small">LOI</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($loiData as $item)
                                <tr data-iid="{{ $item['iid'] }}" data-ratio="{{ $loiRatioByIid[$item['iid']] ?? '' }}">
                                    <td class="small">{{ $item['iid'] }}</td>
                                    <td class="small">{{ $item['uid'] }}</td>
                                    <td class="small">{{ $item['loi'] }} min.</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- COLONNA DESTRA (70%) -->
    <div class="col-md-8">
        <div class="quality-card shadow-sm mb-4">
            <div class="quality-card-header quality-header-right">
                <h5 class="mb-0">Controllo Domande Aperte</h5>
            </div>
            <div class="quality-card-body">
                <div class="fq-filter-bar">
                    <input type="text" id="flt-open-search" class="form-control form-control-sm" placeholder="Cerca IID / UID…">
                </div>
                <div class="quality-table-container">
                    <table id="tbl-open" class="table table-hover quality-table-lower">
                        <thead>
                            <tr>
                                <th class="small">+</th>
                                <th class="small">IID</th>
                                <th class="small">UID</th>
                                <th class="small">Panel</th>
                                <th class="small">Tipologia</th>
                                <th class="small">Codice</th>
                                <th class="small">Testo</th>
                                <th class="small">Fake %</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $openFakeByIid = [];
                                foreach ($openQuestionsData as $row) {
                                    $iid = $row['iid'];
                                    if (!isset($openFakeByIid[$iid])) {
                                        $openFakeByIid[$iid] = ['fake' => 0, 'total' => 0];
                                    }
                                    $openFakeByIid[$iid]['total']++;
                                    if (!empty($row['isFake'])) {
                                        $openFakeByIid[$iid]['fake']++;
                                    }
                                }
                            @endphp
                            @foreach($openQuestionsData as $index => $open)
                                @php
                                    $modalId = "modalOpen_{$open['iid']}_{$index}";
                                @endphp

                                <tr data-iid="{{ $open['iid'] }}" data-uid="{{ $open['uid'] }}">
                                    <td class="small">
                                        <a href="#" data-bs-toggle="modal" data-bs-target="#{{ $modalId }}">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </a>
                                    </td>

                                    <td class="small">{{ $open['iid'] }}</td>
                                    <td class="small">{{ $open['uid'] }}</td>
                                    <td class="small">{{ $open['panel'] }}</td>
                                    <td class="small text-muted">
                                        {{ $open['tipologia'] }}
                                    </td>
                                    <td class="small">
                                        <span class="fq-codice-pop"
                                              data-codice="{{ $open['codice'] }}"
                                              data-qtext="{{ $open['tooltip'] }}"
                                              style="cursor:pointer;text-decoration:underline dotted #aaa;text-underline-offset:3px">
                                            {{ $open['codice'] }}
                                        </span>
                                    </td>
                                    <td class="small">{{ $open['openResponse'] }}</td>
                                    <td class="small">
                                        @php
                                            $iidStats = $openFakeByIid[$open['iid']] ?? null;
                                            $iFake    = $iidStats['fake']  ?? 0;
                                            $iTotal   = $iidStats['total'] ?? 0;
                                            $iPct     = $iTotal > 0 ? round($iFake / $iTotal * 100) : 0;
                                        @endphp
                                        @if($iFake > 0)
                                            <span class="text-danger fw-bold">{{ $iFake }}</span>/{{ $iTotal }} ({{ $iPct }}%)
                                        @endif
                                    </td>
                                </tr>

                                <!-- Modale -->
                                <div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}Label" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-scrollable">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="{{ $modalId }}Label">
                                                    Risposta Aperta (IID: {{ $open['iid'] }})
                                                </h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>

                                            <div class="modal-body">
                                                <p><strong>Testo:</strong></p>
                                                <p>{{ $open['openResponse'] }}</p>

                                                @if(!empty($open['isFake']) && $open['isFake'] === true)
                                                <div class="alert alert-light border py-2 px-3 mb-2" style="font-size:0.85em">
                                                    @php
                                                        $mCat    = $open['category'] ?? 'weak';
                                                        $mReason = $open['reason']   ?? '—';
                                                        $mBadge  = $mCat === 'strong' ? 'danger'
                                                            : ($mCat === 'medium' ? 'warning text-dark' : 'secondary');
                                                        $mLabel  = $mCat === 'strong' ? 'Forte'
                                                            : ($mCat === 'medium' ? 'Medio' : 'Debole');
                                                    @endphp
                                                    <strong>Classificazione:</strong>
                                                    <span class="badge bg-{{ $mBadge }} ms-1">{{ $mLabel }}</span>
                                                    &nbsp;<span class="text-muted">{{ $mReason }}</span>
                                                </div>
                                                @endif

                                                <hr/>

                                                <div class="d-grid gap-2">
                                                    <button class="btn"
                                                    style="background-color: white; color: black; border:1px solid #ccc"
                                                    onclick="addToWhiteList('{{ $open['openResponse'] }}')">
                                                Aggiungi a Whitelist
                                            </button>
                                            <button class="btn"
                                            style="background-color: black; color: white; border:1px solid #000"
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
                                <!-- Fine Modale -->

                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- FINE SECONDA RIGA -->

<!-- TERZA RIGA -->
<div class="row">
    <div class="col-md-12">
        <div class="quality-card shadow-sm mb-4">
            <div class="quality-card-header quality-header-left">
                <h5 class="mb-0">Qualità domande a griglia singola</h5>
            </div>
            <div class="quality-card-body p-0">
                @if(count($scaleData) > 0)
                    @php
                        $scaleQsByIidQid = [];
                        foreach ($completeInterviews as $iv) {
                            foreach ($iv['quality_criteria']['scale']['details'] ?? [] as $d) {
                                $scaleQsByIidQid[$iv['iid']][$d['question_id']] = $d;
                            }
                        }
                    @endphp
                    <div class="fq-filter-bar">
                        <input type="text" id="flt-scale-search" class="form-control form-control-sm" placeholder="Cerca IID / UID…">
                    </div>
                    <div class="quality-table-container" style="max-height: 350px; overflow-y: auto;">
                        <table id="tbl-scale" class="table table-hover quality-table-lower">
                            <thead>
                                <tr>
                                    <th class="small">IID</th>
                                    <th class="small">UID</th>
                                    <th class="small">Panel</th>
                                    <th class="small">Domanda</th>
                                    <th class="small">Qualità</th>
                                    <th class="small">Motivazione</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($scaleData as $scale)
                                    @php
                                        $sqLabel   = ($scale['code'] !== 'unknown')
                                            ? 'Domanda ' . $scale['code']
                                            : 'Domanda ' . $scale['questionId'];
                                        $sqDetail  = $scaleQsByIidQid[$scale['iid']][$scale['questionId']] ?? null;
                                        $sqLevel   = $sqDetail['level'] ?? null;
                                        $sqReasons = $sqDetail['reasons'] ?? [];
                                        $sqBadge = $sqLevel === 'Normale' ? 'success'
                                            : ($sqLevel === 'Sospetta' ? 'warning'
                                            : ($sqLevel === 'Da Verificare' ? 'danger' : 'secondary'));
                                    @endphp
                                    <tr data-iid="{{ $scale['iid'] }}" data-uid="{{ $scale['uid'] }}">
                                        <td class="small">{{ $scale['iid'] }}</td>
                                        <td class="small">{{ $scale['uid'] }}</td>
                                        <td class="small">{{ $scale['panel'] }}</td>
                                        <td class="small">
                                            <span class="fq-codice-pop"
                                                  data-codice="{{ $sqLabel }}"
                                                  data-qtext="{{ $scale['tooltip'] }}"
                                                  data-qid="{{ $scale['questionId'] }}"
                                                  style="cursor:pointer;text-decoration:underline dotted #aaa;text-underline-offset:3px">
                                                {{ $sqLabel }}
                                            </span>
                                        </td>
                                        <td class="small">
                                            @if($sqLevel !== null)
                                                <span class="badge bg-{{ $sqBadge }}">{{ $sqLevel }}</span>
                                            @else
                                                <span class="text-muted">N/D</span>
                                            @endif
                                        </td>
                                        <td class="small text-muted">
                                            {{ implode(' · ', $sqReasons) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="p-3">
                        <p class="text-muted">Nessuna scale da visualizzare.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

<!-- FINE TERZA RIGA -->
</div>


</div><!-- container -->



@endsection

@section('scripts')

<script>
    document.addEventListener("DOMContentLoaded", function () {
        // Dropdown
        document.querySelectorAll('.dropdown-toggle').forEach(function (el) {
            new bootstrap.Dropdown(el);
        });
        document.body.addEventListener("click", function (event) {
            if (event.target.classList.contains("dropdown-toggle")) {
                bootstrap.Dropdown.getOrCreateInstance(event.target).show();
            }
        });

        // Popover codice domanda (tabella open questions)
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

        // Popover motivazioni interviste
        document.querySelectorAll('.fq-reasons-btn').forEach(function (el) {
            var reasons = [];
            try { reasons = JSON.parse(el.dataset.fqReasons || '[]'); } catch(e) {}
            var html = '<ul class="mb-0 ps-3" style="font-size:.78rem;min-width:200px">'
                + reasons.map(function(r) {
                    return '<li>' + r.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;') + '</li>';
                }).join('')
                + '</ul>';
            new bootstrap.Popover(el, {
                html: true,
                content: html,
                trigger: 'focus',
                placement: 'auto',
                title: '<small class="fw-semibold">Motivazioni</small>',
                sanitize: false
            });
        });
    });
</script>

<script>
    function addToWhiteList(responseText) {
        fetch("{{ route('fieldQuality.addToWhiteList') }}", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": "{{ csrf_token() }}"
            },
            body: JSON.stringify({
                text: responseText
            })
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                alert("La parola è stata aggiunta alla whitelist!");
                window.location.reload();
            } else {
                alert("Errore: " + data.message);
            }
        })
        .catch(err => {
            console.error(err);
            alert("Si è verificato un errore durante l'aggiunta alla whitelist.");
        });
    }

    // ---- Filtri tabelle ----

    function filterRows(tableId, testFn) {
        document.querySelectorAll('#' + tableId + ' tbody tr').forEach(function (row) {
            row.style.display = testFn(row) ? '' : 'none';
        });
    }

    function iidUidMatch(row, q) {
        if (!q) return true;
        return (row.dataset.iid || '').toLowerCase().includes(q)
            || (row.dataset.uid || '').toLowerCase().includes(q);
    }

    // Lista interviste
    var fltIvSearch = document.getElementById('flt-iv-search');
    var fltIvRating = document.getElementById('flt-iv-rating');
    function applyIvFilter() {
        var q = fltIvSearch.value.trim().toLowerCase();
        var r = fltIvRating.value.toLowerCase();
        filterRows('tbl-interviews', function (row) {
            return iidUidMatch(row, q)
                && (!r || (row.dataset.rating || '').toLowerCase() === r);
        });
    }
    if (fltIvSearch) fltIvSearch.addEventListener('input', applyIvFilter);
    if (fltIvRating) fltIvRating.addEventListener('change', applyIvFilter);

    // LOI < 50% mediana
    var loiActive = false;
    var loiBtn = document.getElementById('flt-loi-btn');
    if (loiBtn) {
        loiBtn.addEventListener('click', function () {
            loiActive = !loiActive;
            loiBtn.classList.toggle('active', loiActive);
            loiBtn.classList.toggle('btn-warning', loiActive);
            loiBtn.classList.toggle('btn-outline-warning', !loiActive);
            filterRows('tbl-loi', function (row) {
                if (!loiActive) return true;
                var ratio = parseFloat(row.dataset.ratio);
                return !isNaN(ratio) && ratio < 0.5;
            });
        });
    }

    // Domande aperte
    var fltOpenSearch = document.getElementById('flt-open-search');
    if (fltOpenSearch) {
        fltOpenSearch.addEventListener('input', function () {
            var q = fltOpenSearch.value.trim().toLowerCase();
            filterRows('tbl-open', function (row) { return iidUidMatch(row, q); });
        });
    }

    // Qualità griglia
    var fltScaleSearch = document.getElementById('flt-scale-search');
    if (fltScaleSearch) {
        fltScaleSearch.addEventListener('input', function () {
            var q = fltScaleSearch.value.trim().toLowerCase();
            filterRows('tbl-scale', function (row) { return iidUidMatch(row, q); });
        });
    }

    function addToBlackList(responseText) {
        fetch("{{ route('fieldQuality.addToBlackList') }}", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": "{{ csrf_token() }}"
            },
            body: JSON.stringify({
                text: responseText
            })
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                alert("La parola è stata aggiunta alla blacklist!");
                window.location.reload();
            } else {
                alert("Errore: " + data.message);
            }
        })
        .catch(err => {
            console.error(err);
            alert("Si è verificato un errore durante l'aggiunta alla blacklist.");
        });
    }
</script>

<style>

/* Costringe le stat-card ad avere altezza uniforme nella riga */
.row.text-center.g-3 {
  display: flex;
  flex-wrap: wrap;
}
.row.text-center.g-3 > [class*="col-"] {
  display: flex;
  align-items: stretch;
}
/* Icone */
.stat-icon i {
  font-size: 1.75rem;
  color: #078107;
}

/* Allinea icona, valore e label verticalmente */
.stat-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  width: 100%;
}

/* Spazio sotto l'icona */
.stat-icon {
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Rende più consistente la badge area */
.stat-card .badge {
  font-size: 0.85rem;
  padding: 0.4em 0.6em;
}

.fq-motivations {
  vertical-align: middle;
}

.fq-mot-list {
  margin: 0;
  padding-left: 1rem;
  font-size: 0.74rem;
  color: #333;
  line-height: 1.5;
}

.fq-mot-list li {
  margin-bottom: 1px;
}

.fq-cap-badge {
  font-size: 0.68rem;
  color: #b45309;
  background: #fef3c7;
  border: 1px solid #fbbf24;
  border-radius: 4px;
  padding: 1px 5px;
  margin-top: 3px;
  display: inline-block;
}

.fq-filter-bar {
  display: flex;
  gap: 6px;
  align-items: center;
  padding: 6px 10px;
  background: #f8f9fa;
  border-bottom: 1px solid #dee2e6;
}
.fq-filter-bar .form-control-sm,
.fq-filter-bar .form-select-sm {
  font-size: 0.78rem;
  max-width: 180px;
}



</style>

@endsection
