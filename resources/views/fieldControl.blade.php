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
                        <!-- Tab Home -->
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
                                    <tr><td><strong>Abilitati Panel:</strong></td><td>{{ $abilitati }}</td></tr>
                                    <tr><td><strong>Redemption (IR):</strong></td><td>{{ $redemption }}%</td></tr>
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
            <div class="quote-section">
                <h4 class="section-title">Controllo Quote</h4>
                <div class="quota-table-container">
                    <table class="table custom-table">
                        <thead class="sticky-header">
                            <tr>
                                <th>Quota</th>
                                <th>Totale</th>
                                <th>Entrate</th>
                                <th>Missing</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($quotaData as $quota)
                                <tr>
                                    <td>{{ $quota->quota }}</td>
                                    <td>{{ $quota->totale }}</td>
                                    <td>{{ $quota->entrate }}</td>
                                    <td>{{ $quota->missing }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>


            </div>
        </div>
    </div>



    <!-- fine seconda riga  -->


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






@endsection
