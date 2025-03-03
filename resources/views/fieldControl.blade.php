@extends('layouts.main')

@section('content')
<!-- Importazione dello stile personalizzato -->
<link rel="stylesheet" href="{{ asset('css/fieldControl.css') }}">

<div class="container field-control-container">


    <div class="row">
        <!-- Card 1: RICERCA -->
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex align-items-center">
                    <div class="stat-badge badge-dark">RICERCA</div>
                    <span class="ms-3 stat-text">{{ $panelData->sur_id ?? 'N/A' }}</span>
                </div>
                <h3 class="mt-2 stat-value">{{ $panelData->description ?? 'No description available' }}</h3>
                <p class="text-muted">Cliente: {{ $panelData->cliente ?? 'No description available' }}</p>
            </div>
        </div>

        <!-- Card 2: TARGET -->
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex align-items-center">
                    <div class="stat-badge badge-red">TARGET</div>
                    <span class="ms-3 stat-text">{{ $panelData->paese ?? 'N/A' }}</span>
                </div>
                <h3 class="mt-2 stat-value">{{ $panelData->goal ?? 'N/A' }} interviste</h3>
                <p class="text-muted">
                    @if($panelData->sex_target == 1)
                        Uomo
                    @elseif($panelData->sex_target == 2)
                        Donna
                    @elseif($panelData->sex_target == 3)
                        Uomo/Donna
                    @else
                        N/A
                    @endif
                    {{ $panelData->age1_target ?? 'N/A' }} - {{ $panelData->age2_target ?? 'N/A' }} anni
                </p>
            </div>
        </div>

        <!-- Card 3: TIMING -->
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex align-items-center">
                    <div class="stat-badge badge-green">TIMING</div>
                    <span class="ms-3 stat-text">
                        Giorni in field:
                        <b>
                            @if($panelData->stato == 1)
                                {{ $panelData->sur_date ? \Carbon\Carbon::parse($panelData->sur_date)->diffInDays(now()) : 'N/A' }}
                            @elseif($panelData->stato == 0 && $panelData->end_field)
                                {{ \Carbon\Carbon::parse($panelData->sur_date)->diffInDays(\Carbon\Carbon::parse($panelData->end_field)) }}
                            @else
                                N/A
                            @endif
                        </b>
                    </span>
                </div>
                <h3 class="mt-2 stat-value">
                    <span> Inizio: {{ $panelData->sur_date ? \Carbon\Carbon::parse($panelData->sur_date)->format('d/m/Y') : 'N/A' }}</span>
                    <br/><br/>
                    <span>Fine: {{ $panelData->end_field ? \Carbon\Carbon::parse($panelData->end_field)->format('d/m/Y') : 'N/A' }}</span>
                </h3>
            </div>
        </div>

        <!-- Card 4: INFO -->
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex align-items-center">
                    <div class="stat-badge badge-blue">INFO</div>
                    <span class="ms-3 stat-text">
                        @if($panelData->stato == 0)
                            Chiusa
                        @elseif($panelData->stato == 1)
                            Aperta
                        @else
                            N/A
                        @endif
                    </span>
                </div>
                <h3 class="mt-2 stat-value">
                    Durata: {{ $panelData->durata ?? 'N/A' }} minuti <br/>
                    Panel:
                    @if(!empty($panelCounts))
                        {{ implode(', ', array_keys($panelCounts)) }}
                    @else
                        N/A
                    @endif

                </h3>
            </div>
        </div>
    </div>

        <!-- prima riga dopo le card con status + filtrate -->

        <div class="row mt-5">

            <div class="col-md-6">
                <div class="d-flex custom-tab-container">
                    <!-- Menu laterale -->
                    <div class="custom-nav-container">
                        <ul class="nav flex-column nav-pills custom-nav-pills" id="menu-tabs">
                            <!-- Tab Home (sempre presente e attivo) -->
                            <li class="nav-item">
                                <a class="nav-link active" id="tab1-tab" data-bs-toggle="pill" href="#tab1">
                                    <i class="fas fa-home me-2"></i> Totale
                                </a>
                            </li>

                            <!-- Generazione dinamica dei tab per i panel -->
                            @if(count($panelCounts) > 1)
                            @foreach ($panelCounts as $panelName => $panelData)
                                <li class="nav-item">
                                    <a class="nav-link" id="tab{{ $loop->index + 2 }}-tab" data-bs-toggle="pill" href="#tab{{ $loop->index + 2 }}">
                                        <i class="fas fa-chart-pie me-2"></i> {{ $panelName }}
                                    </a>
                                </li>
                            @endforeach
                        @endif

                        </ul>
                    </div>

                    <!-- Contenuto della tab -->
                    <div class="tab-content custom-tab-content">
                        <!-- Tab Home - Totale -->
                        <div class="tab-pane fade show active" id="tab1">
                            <h4>Totale</h4>
                            <table class="table custom-table">
                                <tbody>
                                    <tr><td><strong>Complete:</strong></td><td>{{ $counts['complete'] }}</td></tr>
                                    <tr><td><strong>Non in target:</strong></td><td>{{ $counts['non_target'] }}</td></tr>
                                    <tr><td><strong>Over Quota:</strong></td><td>{{ $counts['over_quota'] }}</td></tr>
                                    <tr><td><strong>Sospese:</strong></td><td>{{ $counts['sospese'] }}</td></tr>
                                    <tr><td><strong>Bloccate:</strong></td><td>{{ $counts['bloccate'] }}</td></tr>
                                    <tr><td><strong>Contatti:</strong></td><td>{{ $counts['contatti'] }}</td></tr>
                                    <tr><td><strong>Redemption (IR):</strong></td><td>{{ $redemption }}%</td></tr>

                                    <!-- Mostra "Abilitati Panel" e "Utenti Disponibili" SOLO se c'è un solo panel -->
                                    @if(count($panelCounts) == 1 && array_key_exists('Interactive', $panelCounts))
                                        <tr><td><strong>Abilitati Panel:</strong></td><td>{{ $abilitati }}</td></tr>
                                        <tr><td><strong>Utenti Disponibili:</strong></td><td>{{ $utentiDisponibili }}</td></tr>
                                        <tr>
                                            <td>
                                                <strong>Stima Interviste:</strong>
                                                <i class="fas fa-info-circle text-primary" data-bs-toggle="tooltip" data-bs-placement="top"
                                                    title="La stima interviste si intende per interviste totali, non tiene conto di quote o target.">
                                                </i>
                                            </td>
                                            <td>{{ $stimaInterviste }} *</td>
                                        </tr>

                                    @endif


                                </tbody>
                            </table>
                        </div>

                        <!-- Generazione dinamica dei contenuti dei panel -->
                        @foreach ($panelCounts as $panelName => $panelData)
                            <div class="tab-pane fade" id="tab{{ $loop->index + 2 }}">
                                <h4>{{ $panelName }}</h4>
                                <table class="table custom-table">
                                    <tbody>
                                        <tr><td><strong>Complete:</strong></td><td>{{ $panelData['complete'] }}</td></tr>
                                        <tr><td><strong>Non in target:</strong></td><td>{{ $panelData['non_target'] }}</td></tr>
                                        <tr><td><strong>Over Quota:</strong></td><td>{{ $panelData['over_quota'] }}</td></tr>
                                        <tr><td><strong>Sospese:</strong></td><td>{{ $panelData['sospese'] }}</td></tr>
                                        <tr><td><strong>Bloccate:</strong></td><td>{{ $panelData['bloccate'] }}</td></tr>
                                        <tr><td><strong>Contatti:</strong></td><td>{{ $panelData['contatti'] }}</td></tr>
                                        <tr><td><strong>Redemption (IR):</strong></td><td>{{ $panelData['redemption'] ?? 'N/A' }}%</td></tr>

                                        <!-- Mostra "Abilitati Panel" e "Utenti Disponibili" SOLO nel tab Interactive -->
                                        @if($panelName == "Interactive" && count($panelCounts) > 1)
                                            <tr><td><strong>Abilitati Panel:</strong></td><td>{{ $abilitati }}</td></tr>
                                            <tr><td><strong>Utenti Disponibili:</strong></td><td>{{ $utentiDisponibili }}</td></tr>
                                            <tr>
                                                <td>
                                                    <strong>Stima Interviste:</strong>
                                                    <i class="fas fa-info-circle text-primary" data-bs-toggle="tooltip" data-bs-placement="top"
                                                        title="La stima interviste si intende per interviste totali, non tiene conto di quote o target.">
                                                    </i>
                                                </td>
                                                <td>{{ $stimaInterviste }}</td>
                                            </tr>

                                        @endif


                                    </tbody>
                                </table>
                            </div>
                        @endforeach
                    </div>


                </div>
            </div>

            {{-- FINE MENU SINISTRA CON RISULTATO

            INIZIO MENU DESTRA CON GRAFICI FILTRATE --}}
            <div class="col-md-6">
                <div class="custom-tab-container-modern">
                    <!-- Menu di navigazione effetto scheda -->
                    <ul class="nav custom-nav-tabs-modern" id="panel-nav">
                        @foreach ($panelCounts as $panelName => $panelData)
                            <li class="nav-item">
                                <a class="nav-link modern-tab-link {{ $loop->first ? 'active' : '' }}" id="tab-panel-{{ $loop->index }}-nav" data-bs-toggle="pill" href="#tab-panel-{{ $loop->index }}">
                                    <i class="fas fa-chart-pie me-2"></i> {{ $panelName }}
                                </a>
                            </li>
                        @endforeach
                    </ul>

                    <!-- Contenuto delle tab -->
                    <div class="tab-content custom-tab-content-modern">
                        @foreach ($panelCounts as $panelName => $panelData)
                            <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="tab-panel-{{ $loop->index }}">
                                <br/>
                                <h5 class="mt-3">Analisi Filtrate</h5>
                                <canvas id="chart-panel-{{ $loop->index }}"></canvas> <!-- Canvas univoco per ogni panel -->
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>


            {{-- FINE PARTE SINISTRA GRAFICI FILTRATE --}}



        </div>

    <!-- fine prima riga dopo le card con status + filtrate -->

    <!-- seconda riga  -->

    <div class="row mt-4">


        <!-- Sezione Quote -->
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div style="background-color: #9ECE6C" class="card-header text-white d-flex align-items-center">
                    <i class="fas fa-chart-line me-2"></i> <h6 style="color:#212529" class="mb-0"><b>Controllo Quote</b></h6>
                </div>
                <div class="card-body p-2">
                    <div class="table-responsive quota-table-container" style="max-height: 350px; overflow-y: auto;">
                        <table class="table table-sm table-bordered table-hover text-center">
                            <thead style="color:aliceblue!important" class="sticky-header">
                                <tr>
                                    <th class="small">Quota</th>
                                    <th class="small">Totale</th>
                                    <th class="small">Entrate</th>
                                    <th class="small">Missing</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($quotaData as $quota)
                                    <tr class="align-middle">
                                        <td class="small">{{ $quota->quota }}</td>
                                        <td class="small fw-bold">{{ $quota->totale }}</td>
                                        <td class="small">
                                            <span style="font-size: 12px" class="badge bg-success"><i class="fas fa-check-circle"></i> {{ $quota->entrate }}</span>
                                        </td>
                                        <td class="small">
                                            @if ($quota->missing > 0)
                                                <span style="font-size: 12px" class="badge bg-danger"><i class="fas fa-exclamation-circle"></i> {{ $quota->missing }}</span>
                                            @else
                                                <span style="font-size: 12px" class="badge bg-success"><i class="fas fa-check"></i> 0</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
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
                                <a class="nav-link modern-tab-link {{ $loop->first ? 'active' : '' }}" id="tab-date-{{ $loop->index }}-nav" data-bs-toggle="pill" href="#tab-date-{{ $loop->index }}">
                                    <i class="fas fa-calendar-day me-2"></i> {{ $panelName }}
                                </a>
                            </li>
                        @endforeach
                    @endif
                </ul>

                <!-- Tab Content -->
                <div class="tab-content custom-tab-content-modern">
                    @foreach ($dataSummaryByPanel as $panelName => $summaryData)
                        <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="tab-date-{{ $loop->index }}">
                            <h4 class="tab-title text-center">{{ $panelName }}</h4>

                            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                <table class="table table-hover table-striped custom-table-modern text-center">
                                    <thead class="table-dark sticky-top">
                                        <tr>
                                            <th>Giorno</th>
                                            <th>Contatti</th>
                                            <th>Complete</th>
                                            <th>Non in target</th>
                                            <th>Quotafull</th>
                                            <th>IR (%)</th>
                                            <th>LOI</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($summaryData as $date => $stats)
                                            @php
                                                $denominator = $stats['contatti'] - $stats['non_target'] - $stats['quotafull'];
                                                $ir = ($denominator > 0) ? round(($stats['complete'] / $denominator) * 100, 2) : 0;
                                                $loi = isset($stats['total_duration']) && $stats['complete'] > 0
                                                    ? round(($stats['total_duration'] / $stats['complete']) / 60, 1) . " min."
                                                    : 'N/A';
                                                    $formattedDate = is_numeric(strtotime($date)) ? \Carbon\Carbon::parse($date)->locale('it')->isoFormat('dddd D MMMM YY') : "Data non disponibile";
                                            @endphp
                                            <tr>
                                                <td><strong>{{ ucfirst($formattedDate) }}</strong></td>
                                                <td>{{ $stats['contatti'] }}</td>
                                                <td class="text-success fw-bold">{{ $stats['complete'] }}</td>
                                                <td class="text-warning fw-bold">{{ $stats['non_target'] }}</td>
                                                <td class="text-danger fw-bold">{{ $stats['quotafull'] }}</td>
                                                <td class="text-primary fw-bold">{{ $ir }}%</td>
                                                <td>{{ $loi }}</td>
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


@endsection

@section('scripts')

<script>


    document.addEventListener("DOMContentLoaded", function () {
        const chartsData = @json($filtrateCountsByPanel); // Passiamo i dati da Laravel a JS


        Object.keys(chartsData).forEach((panelName, index) => {
            let labels = [];
            let values = [];
            let tooltips = [];

            if (chartsData[panelName]) {
                Object.entries(chartsData[panelName]).forEach(([question, count]) => {
                    let parts = question.split(" - ");
                    // console.log("📊 Dati ricevuti per i panel:", parts);
                    let questionCode = parts[0]; // Codice della domanda
                    let questionText = parts[1] ?? "Testo non disponibile"; // Tooltip con testo domanda

                    labels.push(questionCode);
                    values.push(count);
                    tooltips.push(questionText);
                });

                // Tutti i canvas seguono il pattern "chart-panel-X"
                let canvasID = `chart-panel-${index}`;
                let canvas = document.getElementById(canvasID);

                if (canvas) {
                    new Chart(canvas, {
                        type: 'bar', // Grafico a barre
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'Filtrate',
                                data: values,
                                backgroundColor: '#7bd87d', // Colore verde
                                borderColor: '#2E7D32',
                                borderWidth: 1
                            }]
                        },

                        options: {
                                    responsive: true,
                                    plugins: {
                                        tooltip: {
                                            callbacks: {
                                                title: () => null, // Rimuove il titolo predefinito del tooltip
                                                label: function (context) {
                                                    let dataIndex = context.dataIndex;
                                                    let questionData = tooltips[dataIndex];

                                                    console.log("🔍 Tooltip Data:", questionData); // DEBUG

                                                    if (questionData) {
                                                        let truncatedText = questionData.text.length > 50
                                                            ? questionData.text.substring(0, 50) + "..."
                                                            : questionData.text;

                                                        return `${questionData.code}: ${truncatedText}`;
                                                    } else {
                                                        return "Dati non disponibili";
                                                    }
                                                }
                                            }
                                        }
                                    },
                                    scales: {
                                        x: {
                                            beginAtZero: true
                                        }
                                    }
                                }


                    });
                } else {
                    console.warn(`⚠️ Nessun canvas trovato per ${panelName} (ID: ${canvasID})`);
                }
            }
        });
    });
</script>






<script>
    document.addEventListener("DOMContentLoaded", function () {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
</script>



@endsection
