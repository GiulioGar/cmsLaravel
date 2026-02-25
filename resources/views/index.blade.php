@extends('layouts.main')

@section('content')
<main>
    <div class="container-fluid p-0">

        {{-- RIGA CHE CONTIENE LA CARD E LA TABELLA --}}
        <div class="row">
            <div class="col-12 col-lg-12 col-xxl-12 d-flex">
                <div class="card flex-fill">
                    <div class="card-header">
                        <h4 class="card-title mb-0">Progetti in corso</h4>
                    </div>

                    <table class="table table-hover table-striped align-middle">
                        <thead>
                            <tr>
                                <th>Ricerca</th>
                                <th class="d-none d-xl-table-cell">Info</th>
                                <th>IR</th>
                                <th>LOI</th>
                                <th>Andamento</th>
                                <th>Scadenza</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($records as $row)
                                @php
                                    // Calcolo IR e classi colore
                                    $ir = floatval($row->red_surv ?? 0);
                                    $irClass = 'text-success'; // Default verde
                                    if ($ir < 30) {
                                        $irClass = 'text-danger'; // Rosso se sotto 30%
                                    } elseif ($ir < 65) {
                                        $irClass = 'text-warning'; // Giallo se tra 30% e 65%
                                    }

                                    // Calcolo andamento (percentuale tra 'complete' e 'goal', max 100%)
                                    $andamento = 0;
                                    if (!empty($row->goal) && is_numeric($row->goal) && $row->goal > 0) {
                                        $andamento = min(100, round(($row->complete / $row->goal) * 100));
                                    }

                                    // Calcolo scadenza (differenza in giorni dalla data odierna)
                                    // $oggi viene passato dal controller come Carbon::now()
                                    $differenza = null;
                                    if (!empty($row->end_field)) {
                                        $endFieldDate = \Carbon\Carbon::parse($row->end_field);
                                        // diffInDays con secondo parametro "false" per avere numeri negativi se la data è passata
                                        $differenza = $oggi->diffInDays($endFieldDate, false);
                                    }
                                @endphp

                                <tr>
                                    {{-- Ricerca (sur_id) --}}
                                    <td>{{ $row->sur_id }}</td>

                                    {{-- Info (description) visibile solo da XL in su --}}
                                    <td class="d-none d-xl-table-cell">{{ $row->description }}</td>

                                    {{-- IR (incidenza) con colore dinamico --}}
                                    <td>
                                        <span class="fa-solid fa-computer-mouse"></span> &nbsp;
                                        <b>
                                            <span class="{{ $irClass }}">
                                                {{ $row->red_surv }}%
                                            </span>
                                        </b>
                                    </td>

                                    {{-- LOI (durata) con icona --}}
                                    <td>
                                        <span class="fa-solid fa-business-time"></span> &nbsp;
                                        {{ $row->durata }} min.
                                    </td>

                                    {{-- Andamento (grafico a ciambella con Chart.js) --}}
                                        <td>
                                            <canvas
                                                id="chart-{{ $row->sur_id }}"
                                                width="120"
                                                height="70"
                                                data-andamento="{{ $andamento }}">
                                            </canvas>
                                        </td>

                                    {{-- Scadenza --}}
                                    <td>
                                        @if (is_null($differenza))
                                            <span class="badge bg-secondary">N/A</span>
                                        @else
                                            @if ($differenza === 0)
                                                <span class="badge bg-primary">Oggi</span>
                                            @elseif ($differenza < 0)
                                                <span class="badge bg-danger">Scaduto</span>
                                            @else
                                                <span class="badge bg-success">{{ $differenza }} giorni</span>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                </div>
            </div>
        </div>
        {{-- Fine row e card --}}

        <hr class="my-4">

                {{-- ================== SEZIONE DASHBOARD UTENTI ================== --}}
                <div class="row">
                    {{-- Colonna sinistra (card statistiche utenti) --}}
                    <div class="col-xl-6 col-xxl-5 d-flex">
                        <div class="w-100">
                            <div class="row">
                                {{-- Card: Totale Utenti Pannello --}}
                                <div class="col-sm-6">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col mt-0">
                                                    <h5 class="card-title">Total User Panel</h5>
                                                </div>
                                                <div class="col-auto">
                                                    <div class="stat text-primary">
                                                        <i class="align-middle" data-feather="user-check"></i>
                                                    </div>
                                                </div>
                                            </div>
                                            {{-- Numero totale di utenti --}}
                                            <h1 class="mt-1 mb-3">
                                                {{ number_format($totalUsers) }}
                                            </h1>
                                            {{-- Percentuale di attivi sul totale --}}
                                            <div class="mb-0">
                                                <span class="text-success">
                                                    {{-- Icona a piacere, sostituisci se non hai mdi --}}
                                                    <i class="mdi mdi-arrow-bottom-right"></i>
                                                    {{ $activePercentage }}%
                                                </span>
                                                <span class="text-muted">Utenti attivi</span>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Card: Distribuzione Età (grafico) --}}
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col mt-0">
                                                    <h5 class="card-title">Distribuzione Età</h5>
                                                </div>
                                                <div class="col-auto">
                                                    <div class="stat text-primary">
                                                        <i class="align-middle" data-feather="bar-chart-2"></i>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="chart-container" style="position: relative; width: 100%; height: 300px;">
                                                <canvas id="ageChart"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Card: Genere e Distribuzione Aree --}}
                                <div class="col-sm-6">
                                    {{-- Card: Genere (doughnut chart) --}}
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col mt-0">
                                                    <h5 class="card-title">Genere</h5>
                                                </div>
                                                <div class="col-auto">
                                                    <div class="stat text-primary">
                                                        <i class="align-middle" data-feather="users"></i>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="chart-container" style="position: relative; width: 100px; height: 100px; margin: auto;">
                                                <canvas id="genderChart"></canvas>
                                            </div>

                                            <div class="mt-3 text-center">
                                                <span class="text-primary">
                                                    <i class="fas fa-male"></i>
                                                    Uomini Attivi: {{ $activeMenPercentage }}%
                                                </span>
                                                <br>
                                                <span class="text-danger">
                                                    <i class="fas fa-female"></i>
                                                    Donne Attive: {{ $activeWomenPercentage }}%
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Card: Distribuzione per Area (grafico a torta) --}}
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col mt-0">
                                                    <h5 class="card-title">Distribuzione per Area</h5>
                                                </div>
                                                <div class="col-auto">
                                                    <div class="stat text-primary">
                                                        <i class="align-middle" data-feather="map-pin"></i>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="chart-container" style="position: relative; width: 100%; height: 217px;">
                                                <canvas id="areaChart"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>{{-- Fine row interna --}}
                        </div>
                    </div>
                    {{-- Fine colonna sinistra --}}

                    {{-- Colonna destra: Registrazioni e Attività --}}
                    <div class="col-xl-6 col-xxl-7">
                        <div class="card flex-fill w-100">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    Utenti registrati - {{ $currentYear }}
                                </h5>
                            </div>
                            <div class="card-body py-3">
                                {{-- Grafico Registrazioni Mensili --}}
                                <div class="chart-container" style="position: relative; width: 100%; height: 202px;">
                                    <canvas id="registrationsChart"></canvas>
                                </div>
                                <hr>
                                {{-- Grafico Attività ultimi 5 anni --}}
                                <h5 class="card-title mb-0">Attività Utenti ultimi 5 anni</h5>
                                <br/>
                                <div class="chart-container" style="position: relative; width: 100%; height: 202px;">
                                    <canvas id="userActivityChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                  {{-- ================== SEZIONE DASHBOARD RICERCHE ================== --}}
                <div class="row">
                    {{-- Colonna sinistra: Registrazioni e Attività --}}
                    <div class="col-xl-6 col-xxl-7">
                        <div class="card flex-fill w-100">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    Interviste completate - {{ $currentYear }}
                                </h5>
                            </div>
                            <div class="card-body py-3">
                                {{-- Grafico Registrazioni Mensili --}}
                                <div class="chart-container" style="position: relative; width: 100%; height: 202px;">
                                    <canvas id="completesChart"></canvas>
                                </div>
                                <hr>
                                {{-- Grafico Attività ultimi 5 anni --}}
                                <h5 class="card-title mb-0">Progetti Aperti - {{ $currentYear }}</h5>
                                <br/>
                                <div class="chart-container" style="position: relative; width: 100%; height: 202px;">
                                    <canvas id="projectsChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    {{-- Fine colonna sinistra --}}



                    {{-- Colonna destra: Log Attività Recenti su tutta la larghezza --}}
{{-- Colonna destra: Log Attività Recenti su tutta la larghezza --}}
<div class="col-xl-6 col-xxl-5 d-flex">
    <div class="w-100">
        <div class="row">
            {{-- Log Attività Recenti (occupa tutta la larghezza) --}}
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-body p-2">
                        <div class="row">
                            <div class="col">
                                <h6 class="card-title text-uppercase text-center mb-2">Log Attività Recenti</h6>
                            </div>
                        </div>

                        {{-- Contenitore per effetto di scorrimento --}}
                        <div class="table-responsive" style="max-height: 200px; overflow-y: hidden; font-size: 12px;">
                            <table class="table table-sm table-striped text-center" id="activityLogTable">
                                <thead class="table-dark">
                                    <tr>
                                        <th style="width: 25%;">Data</th>
                                        <th>Attività</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($formattedActivities as $activity)
                                    <tr>
                                        <td class="text-muted">{{ $activity['date'] }}</td>
                                        <td class="text-info">{{ $activity['info'] }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="text-muted text-center mt-1" style="font-size: 10px;">Aggiornamento in tempo reale</div>
                    </div>
                </div>
            </div>

            {{-- Sezione con Redemption 2025 e Progetti per Cliente affiancati --}}
            <div class="col-12">
                <div class="row">
                    {{-- Redemption 2025 --}}
                    <div class="col-md-6">
                        <div class="card shadow">
                            <div class="card-body p-3">
                                <h6 class="card-title text-uppercase text-center">Redemption - {{ $currentYear }}</h6>
                                <div class="table-responsive mt-2">
                                    <table class="table table-sm table-bordered text-center">
                                        <thead class="table-primary">
                                            <tr>
                                                <th>Indicatore</th>
                                                <th>Media (%)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><strong>Red Panel</strong></td>
                                                <td class="text-success"><b>{{ number_format($avgRedPanel, 2) }}%</b></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Red Surv</strong></td>
                                                <td class="text-warning"><b>{{ number_format($avgRedSurv, 2) }}%</b></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Progetti per Cliente --}}
                    <div class="col-md-6">
                        <div class="card shadow">
                            <div class="card-body p-3">
                                <h6 class="card-title text-uppercase text-center">Progetti per Cliente (2025)</h6>
                                <div class="table-responsive mt-2">
                                    <table class="table table-sm table-striped text-center">
                                        <thead class="table-info">
                                            <tr>
                                                <th>Cliente</th>
                                                <th>Progetti</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($clientStats as $client => $total)
                                            <tr>
                                                <td><strong>{{ $client }}</strong></td>
                                                <td class="text-primary"><b>{{ number_format($total) }}</b></td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div> {{-- Fine row interna --}}
        </div>
    </div>
</div>


    </div>{{-- Fine container-fluid --}}
</main>
@endsection

@section('scripts')

<script>
    document.addEventListener("DOMContentLoaded", function() {
        console.log("DOM fully loaded, starting chart setup...");

        // ========== 1) CHART DISTRIBUZIONE ETA' ==========
        let ageData = @json($ageGroups);
        console.log("ageData:", ageData);

        let ageCtx = document.getElementById('ageChart')?.getContext('2d');
        console.log("ageChart Canvas:", ageCtx);

        if (ageCtx) {
            new Chart(ageCtx, {
                type: 'bar',
                data: {
                    labels: Object.keys(ageData),
                    datasets: [{
                        label: 'Numero Utenti',
                        data: Object.values(ageData),
                        backgroundColor: [
                        "rgba(255, 99, 132, 0.7)", // Rosso
                        "rgba(54, 162, 235, 0.7)", // Blu
                        "rgba(255, 206, 86, 0.7)", // Giallo
                        "rgba(75, 192, 192, 0.7)", // Verde
                        "rgba(255, 159, 64, 0.7)", // Arancione
                        "rgba(153, 102, 255, 0.7)", // Viola
                        "rgba(201, 203, 207, 0.7)"  // Grigio
                    ],
                    borderRadius: 10, // Angoli arrotondati
                    borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    legend: {
                        display: false // Nasconde la legenda
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
            console.log("ageChart created successfully!");
        } else {
            console.warn("ageChart canvas not found in the DOM.");
        }

        // ========== 2) CHART GENERE ==========
        let menPercentage = {{ $totalMen }};
        let womenPercentage = {{ $totalWomen }};

        console.log("menPercentage:", menPercentage, "womenPercentage:", womenPercentage);

        let genderCtx = document.getElementById('genderChart')?.getContext('2d');
        console.log("genderChart Canvas:", genderCtx);

        if (genderCtx) {
            new Chart(genderCtx, {
                type: 'doughnut',
                data: {
                    labels: ["Uomini", "Donne"],
                    datasets: [{
                        data: [menPercentage, womenPercentage],
                        backgroundColor: ["rgba(54, 162, 235, 0.7)", "rgba(255, 99, 132, 0.7)"],
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    legend: {
                           display: false
                           },
                    cutout: '80%'
                }
            });
            console.log("genderChart created successfully!");
        } else {
            console.warn("genderChart canvas not found in the DOM.");
        }

        // ========== 3) CHART DISTRIBUZIONE PER AREA ==========
        let areaData = @json($areaGroups);
        console.log("areaData:", areaData);

        let areaCtx = document.getElementById('areaChart')?.getContext('2d');
        console.log("areaChart Canvas:", areaCtx);

        if (areaCtx) {
            new Chart(areaCtx, {
                type: 'pie',
                data: {
                    labels: Object.keys(areaData),
                    datasets: [{
                        data: Object.values(areaData),
                        backgroundColor: ['rgba(201, 203, 207, 0.7)', 'rgba(75, 192, 192, 0.7)','rgba(255, 206, 86, 0.7)', 'rgba(54, 162, 235, 0.7)']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
            console.log("areaChart created successfully!");
        } else {
            console.warn("areaChart canvas not found in the DOM.");
        }

        // ========== 4) CHART REGISTRAZIONI MENSILI ==========
        let monthlyRegistrations = @json($monthlyRegistrations);
        let monthlyActiveRegistrations = @json($monthlyActiveRegistrations);
        console.log("monthlyRegistrations:", monthlyRegistrations);
        console.log("monthlyActiveRegistrations:", monthlyActiveRegistrations);

        let months = Object.keys(monthlyRegistrations);
        let monthNames = ["Gen", "Feb", "Mar", "Apr", "Mag", "Giu",
                          "Lug", "Ago", "Set", "Ott", "Nov", "Dic"];
        let monthLabels = months.map(m => monthNames[m - 1]);

        let regCtx = document.getElementById('registrationsChart')?.getContext('2d');
        console.log("registrationsChart Canvas:", regCtx);

        if (regCtx) {
            new Chart(regCtx, {
                type: 'line',
                data: {
                    labels: monthLabels,
                    datasets: [
                        {
                            label: 'Registrati',
                            data: Object.values(monthlyRegistrations),
                            backgroundColor: 'rgba(75, 192, 192, 0.2)',
                            borderColor: 'rgba(75, 192, 192, 0.7)',
                            fill: true
                        },
                        {
                            label: 'Attivi',
                            data: Object.values(monthlyActiveRegistrations),
                            backgroundColor: 'rgba(255, 159, 64, 0.2)',
                            borderColor: 'rgba(255, 159, 64, 0.7)',
                            fill: true
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
            console.log("registrationsChart created successfully!");
        } else {
            console.warn("registrationsChart canvas not found in the DOM.");
        }

        // ========== 5) CHART ATTIVITA' ULTIMI 5 ANNI ==========
        let userActivityData = @json($activeUsersPerYear);
        console.log("userActivityData:", userActivityData);

        let userActivityCtx = document.getElementById('userActivityChart')?.getContext('2d');
        console.log("userActivityChart Canvas:", userActivityCtx);

        if (userActivityCtx) {
            new Chart(userActivityCtx, {
                type: 'bar',
                data: {
                    labels: Object.keys(userActivityData),
                    datasets: [{
                        label: 'Utenti Attivi',
                        data: Object.values(userActivityData),
                        backgroundColor: [
                        "rgba(255, 99, 132, 0.7)", // Rosso
                        "rgba(54, 162, 235, 0.7)", // Blu
                        "rgba(255, 206, 86, 0.7)", // Giallo
                        "rgba(75, 192, 192, 0.7)", // Verde
                        "rgba(255, 159, 64, 0.7)", // Arancione
                        "rgba(153, 102, 255, 0.7)", // Viola
                        "rgba(201, 203, 207, 0.7)"  // Grigio
                    ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    legend: {
                        display: false // Nasconde la legenda
                    },
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
            console.log("userActivityChart created successfully!");
        } else {
            console.warn("userActivityChart canvas not found in the DOM.");
        }

    });
</script>


<script>
document.addEventListener("DOMContentLoaded", function() {
    let monthlyCompleteMillebytes = @json($monthlyCompleteMillebytes);
    let monthlyCompleteCint = @json($monthlyCompleteCint);

    let months = ["Gen", "Feb", "Mar", "Apr", "Mag", "Giu",
                  "Lug", "Ago", "Set", "Ott", "Nov", "Dic"];

    let completesCanvas = document.getElementById('completesChart');
let ctx = completesCanvas ? completesCanvas.getContext('2d') : null;

    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: months,
                datasets: [
                    {
                        label: 'Millebytes',
                        data: Object.values(monthlyCompleteMillebytes),
                        borderColor: 'rgba(255, 159, 64, 1)', // Blu
                        backgroundColor: 'rgba(255, 159, 64, 0.7)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.3 // Linea leggermente curva
                    },
                    {
                        label: 'Cint',
                        data: Object.values(monthlyCompleteCint),
                        borderColor: 'rgba(54, 162, 235, 1)', // Rosso
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.3 // Linea leggermente curva
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    }
});
</script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        let monthlyOpenProjects = @json($monthlyOpenProjects);

        let months = ["Gen", "Feb", "Mar", "Apr", "Mag", "Giu",
                      "Lug", "Ago", "Set", "Ott", "Nov", "Dic"];

        let projectsCanvas = document.getElementById('projectsChart');
        let ctx = projectsCanvas ? projectsCanvas.getContext('2d') : null;

        if (ctx) {
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: months,
                    datasets: [
                        {
                            label: 'Progetti Aperti',
                            data: Object.values(monthlyOpenProjects),
                            backgroundColor: [
                        "rgba(255, 99, 132, 0.7)", // Rosso
                        "rgba(54, 162, 235, 0.7)", // Blu
                        "rgba(255, 206, 86, 0.7)", // Giallo
                        "rgba(75, 192, 192, 0.7)", // Verde
                        "rgba(255, 159, 64, 0.7)", // Arancione
                        "rgba(153, 102, 255, 0.7)", // Viola
                        "rgba(201, 203, 207, 0.7)"  // Grigio
                    ],
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        }
    });
    </script>

    <script>
document.addEventListener("DOMContentLoaded", function() {
    // Grafico per i Progetti per Cliente
    let clientData = @json($clientStats);
    let clientLabels = Object.keys(clientData);
    let clientValues = Object.values(clientData);

   let clientCanvas = document.getElementById('clientChart');
    if (clientCanvas) {
        let clientCtx = clientCanvas.getContext('2d');
        new Chart(clientCtx, {
            type: 'bar',
            data: {
                labels: clientLabels,
                datasets: [{
                    label: "Progetti",
                    data: clientValues,
                    backgroundColor: [
                        "rgba(255, 99, 132, 0.7)", // Rosso
                        "rgba(54, 162, 235, 0.7)", // Blu
                        "rgba(255, 206, 86, 0.7)", // Giallo
                        "rgba(75, 192, 192, 0.7)", // Verde
                        "rgba(255, 159, 64, 0.7)", // Arancione
                        "rgba(153, 102, 255, 0.7)", // Viola
                        "rgba(201, 203, 207, 0.7)"  // Grigio
                    ],
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: {
                           display: false
                           },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    }
});
</script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        function scrollTable() {
            let table = document.querySelector("#activityLogTable tbody");
            if (table) {
                table.insertBefore(table.lastElementChild, table.firstElementChild);
            }
        }

        setInterval(scrollTable, 3000); // Scorrimento automatico ogni 3 secondi
    });
    </script>

<script>
(function () {

    const centerTextPlugin = {
        id: 'centerText',
        afterDraw(chart) {
            const text = chart.$centerText || '';
            if (!text) return;

            const ctx = chart.ctx;

            // calcolo centro: compatibile v2 e v3/v4
            const x = chart.width / 2;
            const y = chart.height / 2;

            ctx.save();
            ctx.font = '700 13px Inter, Arial, sans-serif';
            ctx.fillStyle = '#333';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText(text, x, y);
            ctx.restore();
        }
    };

    function initRowDoughnuts() {
        const canvases = document.querySelectorAll('canvas[id^="chart-"]');

        if (typeof window.Chart === 'undefined') {
            setTimeout(initRowDoughnuts, 300);
            return;
        }

        const isV2 = !!(Chart.defaults && Chart.defaults.global);

        canvases.forEach(function (canvas) {
            const andamento = parseInt(canvas.dataset.andamento || "0", 10);
            const ctx = canvas.getContext('2d');

            if (canvas._chartInstance && typeof canvas._chartInstance.destroy === 'function') {
                canvas._chartInstance.destroy();
            }

            // 🔥 testo: nascondi per 0 e 100 come richiesto
            const centerText = ( andamento === 100) ? '' : (andamento + '%');

            const chart = new Chart(ctx, {
                type: "doughnut",
                data: {
                    datasets: [{
                        data: [andamento, 100 - andamento],
                        backgroundColor: ["#4CAF50", "#E0E0E0"],
                        borderWidth: 0
                    }]
                },
                options: isV2 ? {
                    responsive: false,
                    maintainAspectRatio: false,
                    cutoutPercentage: 65,
                    legend: { display: false },
                    tooltips: { enabled: false }
                } : {
                    responsive: false,
                    maintainAspectRatio: false,
                    cutout: '65%',
                    plugins: {
                        legend: { display: false },
                        tooltip: { enabled: false }
                    }
                },
                plugins: [centerTextPlugin]
            });

            // ✅ passiamo il testo al plugin in modo compatibile v2/v3/v4
            chart.$centerText = centerText;

            // update per assicurarsi che venga disegnato subito
            chart.update();

            canvas._chartInstance = chart;
        });
    }

    document.addEventListener("DOMContentLoaded", initRowDoughnuts);
    window.addEventListener("load", initRowDoughnuts);

})();
</script>

@endsection

