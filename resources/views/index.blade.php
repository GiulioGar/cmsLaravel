@extends('layouts.main')

<style>

.project-link {
    font-weight: 600;
    font-size: 0.86rem;
    line-height: 1.15;
    color: #212529;
    text-decoration: none;
    position: relative;
    transition: color 0.2s ease;

    font-family: inherit; /* 👈 aggiungi questo */
}

#activityLogTable {
    font-size: 12px;
    font-family: inherit;
}

body {
    font-family: 'Inter', Arial, sans-serif;
}

/* linea animata sotto */
.project-link::after {
    content: "";
    position: absolute;
    left: 0;
    bottom: -2px;
    width: 0%;
    height: 2px;
    background-color: #0d6efd;
    transition: width 0.25s ease;
}

/* hover */
.project-link:hover {
    color: #0d6efd;
}

.project-link:hover::after {
    width: 100%;
}

.project-row:hover {
    background-color: #f8f9fa;
    cursor: pointer;
}

#tblProjects td,
#tblProjects th {
    padding-top: 0.42rem;
    padding-bottom: 0.42rem;
}

.project-meta {
    font-size: 0.68rem;
    line-height: 1.05;
    margin-top: 0.12rem;
}

.project-open-link {
    font-size: 1.15rem;
    line-height: 1;
}

.project-status-box {
    display: inline-flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-width: 84px;
    padding: 0.45rem 0.7rem;
    border-radius: 12px;
    background: linear-gradient(180deg, #fbfcfe 0%, #f3f6fa 100%);
    border: 1px solid #e4eaf1;
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.8);
}

.project-status-current {
    font-size: 0.95rem;
    line-height: 1;
    font-weight: 700;
    color: #1f2937;
}

.project-status-separator {
    font-size: 0.62rem;
    line-height: 1;
    color: #94a3b8;
    margin: 0.16rem 0;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.project-status-target {
    font-size: 0.84rem;
    line-height: 1;
    font-weight: 600;
    color: #2563eb;
}

</style>

@section('content')
<main>
    <div class="container-fluid p-0">

        {{-- RIGA CHE CONTIENE LA CARD E LA TABELLA --}}
        <div class="row">
            <div class="col-12 col-lg-12 col-xxl-12 d-flex">
<div class="card flex-fill shadow-sm border-0">
    <div class="card-header bg-white border-0 pb-0">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <h4 class="card-title mb-1">Progetti in corso</h4>
                <div class="text-muted small">Elenco ricerche aperte e stato avanzamento</div>
            </div>
            <div class="text-muted small">
                <i class="fa-solid fa-circle-info"></i> Clicca sulla ricerca per visualizzare il dettaglio sul Field
            </div>
        </div>
    </div>

    <div class="card-body pt-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tblProjects">
                <thead class="table-light">
                    <tr>
    <th style="min-width: 260px;">Ricerca</th>
    <th class="text-center" style="min-width: 90px;">IR</th>
    <th class="text-center" style="min-width: 90px;">LOI</th>
    <th class="text-center" style="min-width: 140px;">Status</th>
    <th class="text-center" style="min-width: 160px;">Andamento</th>
    <th class="text-center" style="min-width: 120px;">Scadenza</th>
                    </tr>
                </thead>

                <tbody>
                    @php
$prjColors = [
    'COS' => '#3270A0',
    'FER' => '#59200A',
    'IPS' => '#121B5A',
    'BRS' => '#10394E',
    'ABO' => '#28754E',
    'AST' => '#FFBF00',
    'STR' => '#D01E5C',
    'SRM' => '#213259',
    'LME' => '#A9CA48',
    'RCK' => '#FF0B77',
    'ROS' => '#15636E',
    'ATN' => '#0071BD',
    'LMI' => '#cd2927',
    'UNB' => '#004071',
    'HAI' => '#000000',
    'PAR' => '#184785',
    'AMP' => '#CE285C',
];

$defaultColor = '#9DCE6B';
@endphp

                    @foreach($records as $row)
                        @php
                            $ir = floatval($row->red_surv ?? 0);
                            $irClass = 'text-success';
                            if ($ir < 30) $irClass = 'text-danger';
                            elseif ($ir < 65) $irClass = 'text-warning';

                            $andamento = 0;
                            if (!empty($row->goal) && is_numeric($row->goal) && $row->goal > 0) {
                                $andamento = min(100, round(($row->complete / $row->goal) * 100));
                            }

                            $differenza = null;
                            if (!empty($row->end_field)) {
                                $endFieldDate = \Carbon\Carbon::parse($row->end_field);
                                $differenza = $oggi->diffInDays($endFieldDate, false);
                            }

                            $fieldUrl = url('fieldControl') . '?prj=' . urlencode($row->prj) . '&sid=' . urlencode($row->sur_id);
                        @endphp

                        <tr class="project-row">

{{-- RICERCA --}}
<td style="min-width:260px;">
    @php
        $prj = strtoupper($row->prj ?? '');
        $badgeColor = $prjColors[$prj] ?? $defaultColor;
        $fieldUrl = url('fieldControl') . '?prj=' . urlencode($row->prj) . '&sid=' . urlencode($row->sur_id);
    @endphp

    <div class="d-flex justify-content-between align-items-start">
        <div class="pe-2">
            <a href="{{ $fieldUrl }}" class="project-link">
                {{ $row->sur_id }} - {{ $row->description ?: 'N.D.' }}
            </a>

                <div class="text-muted project-meta">
                    ({{ $prj }} - {{ $row->cliente ?? 'N.D.' }})
                </div>
        </div>

        <a href="{{ $fieldUrl }}"
           class="text-primary ms-2 flex-shrink-0 project-open-link"
           title="Apri Field Control">
            <i class="bi bi-folder-symlink"></i>
        </a>
    </div>
</td>


                            {{-- IR --}}
                            <td class="text-center">
                                <span class="fw-semibold {{ $irClass }}">
                                    {{ $row->red_surv }}%
                                </span>
                            </td>

                            {{-- LOI --}}
                            <td class="text-center">
                                <span class="text-muted">{{ $row->durata }} min</span>
                            </td>

{{-- COMPLETE / GOAL --}}
<td class="text-center align-middle">
    <div class="project-status-box">
        <span class="project-status-current">
            {{ number_format($row->complete ?? 0) }}
        </span>
        <span class="project-status-separator">di</span>
        <span class="project-status-target">
            {{ number_format($row->goal ?? 0) }}
        </span>
    </div>
</td>

{{-- ANDAMENTO --}}
<td class="align-middle">
    <div class="d-flex justify-content-center align-items-center" style="height:70px;">
        <canvas
            class="row-doughnut"
            id="chart-{{ $row->sur_id }}"
            width="120"
            height="70"
            data-andamento="{{ $andamento }}">
        </canvas>
    </div>
</td>

        {{-- SCADENZA --}}
        <td class="text-center align-middle">
            @if (is_null($differenza))
                <span class="badge bg-secondary">N/A</span>
            @elseif ($differenza === 0)
                <span class="badge bg-primary">Oggi</span>
            @elseif ($differenza < 0)
                <span class="badge bg-danger">+ {{ abs($differenza) }} gg</span>
            @elseif ($differenza <= 7)
                <span class="badge bg-warning text-dark">- {{ $differenza }} gg</span>
            @else
                <span class="badge bg-success">- {{ $differenza }} gg</span>
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
                                                <div id="ageChart" style="width: 100%; height: 100%;"></div>
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
                                                <div id="genderChart" style="width: 100%; height: 100%;"></div>
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
                                                <div id="areaChart" style="width: 100%; height: 100%;"></div>
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
                                    <div id="registrationsChart" style="width: 100%; height: 100%;"></div>
                                </div>
                                <hr>
                                {{-- Grafico Attività ultimi 5 anni --}}
                                <h5 class="card-title mb-0">Attività Utenti ultimi 5 anni</h5>
                                <br/>
                                <div class="chart-container" style="position: relative; width: 100%; height: 202px;">
                                    <div id="userActivityChart" style="width: 100%; height: 100%;"></div>
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
                                    <div id="completesChart" style="width: 100%; height: 100%;"></div>
                                </div>
                                <hr>
                                {{-- Grafico Attività ultimi 5 anni --}}
                                <h5 class="card-title mb-0">Progetti Aperti - {{ $currentYear }}</h5>
                                <br/>
                                <div class="chart-container" style="position: relative; width: 100%; height: 202px;">
                                    <div id="projectsChart" style="width: 100%; height: 100%;"></div>
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
                        <div class="table-responsive" style="max-height: 200px; overflow-y: hidden; font-size: 10px;">
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
                                <h6 class="card-title text-uppercase text-center">Progetti per Cliente - {{ $currentYear }}</h6>
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

<script src="https://cdn.jsdelivr.net/npm/echarts@6/dist/echarts.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    if (typeof window.echarts === 'undefined') {
        return;
    }

    const monthNames = ["Gen", "Feb", "Mar", "Apr", "Mag", "Giu", "Lug", "Ago", "Set", "Ott", "Nov", "Dic"];
    const ageData = @json($ageGroups);
    const areaData = @json($areaGroups);
    const monthlyRegistrations = @json($monthlyRegistrations);
    const monthlyActiveRegistrations = @json($monthlyActiveRegistrations);
    const userActivityData = @json($activeUsersPerYear);
    const monthlyCompleteMillebytes = @json($monthlyCompleteMillebytes);
    const monthlyCompleteCint = @json($monthlyCompleteCint);
    const monthlyOpenProjects = @json($monthlyOpenProjects);
    const totalMen = {{ (int) $totalMen }};
    const totalWomen = {{ (int) $totalWomen }};

    const dashboardCharts = [];

    function registerChart(elementId, option) {
        const element = document.getElementById(elementId);
        if (!element) {
            return;
        }

        const chart = echarts.init(element);
        chart.setOption(option);
        dashboardCharts.push(chart);
    }

    const commonAxisLabel = {
        color: '#64748b',
        fontSize: 11
    };

    const commonGridLine = {
        lineStyle: {
            color: '#edf2f7'
        }
    };

    registerChart('ageChart', {
        animationDuration: 700,
        color: ['#4f46e5', '#0ea5e9', '#14b8a6', '#f59e0b', '#ef4444', '#8b5cf6', '#94a3b8'],
        grid: {
            top: 18,
            right: 12,
            bottom: 28,
            left: 34
        },
        tooltip: {
            trigger: 'axis',
            axisPointer: {
                type: 'shadow'
            }
        },
        xAxis: {
            type: 'category',
            data: Object.keys(ageData),
            axisTick: {
                show: false
            },
            axisLabel: commonAxisLabel,
            axisLine: {
                lineStyle: {
                    color: '#d7dee7'
                }
            }
        },
        yAxis: {
            type: 'value',
            minInterval: 1,
            axisLabel: commonAxisLabel,
            splitLine: commonGridLine
        },
        series: [{
            name: 'Utenti',
            type: 'bar',
            barMaxWidth: 34,
            data: Object.values(ageData),
            itemStyle: {
                borderRadius: [10, 10, 4, 4]
            }
        }]
    });

    registerChart('genderChart', {
        animationDuration: 700,
        color: ['#2563eb', '#f43f5e'],
        tooltip: {
            trigger: 'item',
            formatter: '{b}: <b>{c}</b> ({d}%)'
        },
        series: [{
            type: 'pie',
            radius: ['58%', '82%'],
            avoidLabelOverlap: true,
            label: {
                show: false
            },
            labelLine: {
                show: false
            },
            itemStyle: {
                borderColor: '#ffffff',
                borderWidth: 3
            },
            data: [
                { value: totalMen, name: 'Uomini' },
                { value: totalWomen, name: 'Donne' }
            ]
        }]
    });

    registerChart('areaChart', {
        animationDuration: 700,
        color: ['#0f766e', '#0891b2', '#eab308', '#7c3aed', '#f97316'],
        tooltip: {
            trigger: 'item',
            formatter: '{b}: <b>{c}</b> ({d}%)'
        },
        series: [{
            name: 'Area',
            type: 'pie',
            radius: ['36%', '74%'],
            center: ['50%', '52%'],
            label: {
                color: '#475569',
                fontSize: 11
            },
            labelLine: {
                length: 12,
                length2: 8
            },
            itemStyle: {
                borderColor: '#ffffff',
                borderWidth: 2
            },
            data: Object.keys(areaData).map(function (label) {
                return {
                    name: label,
                    value: areaData[label]
                };
            })
        }]
    });

    const registrationMonths = Object.keys(monthlyRegistrations);
    const registrationLabels = registrationMonths.map(function (month) {
        return monthNames[month - 1];
    });

    registerChart('registrationsChart', {
        animationDuration: 700,
        color: ['#14b8a6', '#f59e0b'],
        tooltip: {
            trigger: 'axis'
        },
        legend: {
            top: 0,
            textStyle: {
                color: '#64748b'
            }
        },
        grid: {
            top: 34,
            right: 14,
            bottom: 24,
            left: 34
        },
        xAxis: {
            type: 'category',
            boundaryGap: false,
            data: registrationLabels,
            axisLabel: commonAxisLabel,
            axisLine: {
                lineStyle: {
                    color: '#d7dee7'
                }
            }
        },
        yAxis: {
            type: 'value',
            minInterval: 1,
            axisLabel: commonAxisLabel,
            splitLine: commonGridLine
        },
        series: [
            {
                name: 'Registrati',
                type: 'line',
                smooth: true,
                symbol: 'circle',
                symbolSize: 7,
                areaStyle: {
                    opacity: 0.18
                },
                lineStyle: {
                    width: 3
                },
                data: Object.values(monthlyRegistrations)
            },
            {
                name: 'Attivi',
                type: 'line',
                smooth: true,
                symbol: 'circle',
                symbolSize: 7,
                areaStyle: {
                    opacity: 0.12
                },
                lineStyle: {
                    width: 3
                },
                data: Object.values(monthlyActiveRegistrations)
            }
        ]
    });

    registerChart('userActivityChart', {
        animationDuration: 700,
        color: ['#2563eb'],
        tooltip: {
            trigger: 'axis',
            axisPointer: {
                type: 'shadow'
            }
        },
        grid: {
            top: 18,
            right: 12,
            bottom: 24,
            left: 34
        },
        xAxis: {
            type: 'category',
            data: Object.keys(userActivityData),
            axisTick: {
                show: false
            },
            axisLabel: commonAxisLabel,
            axisLine: {
                lineStyle: {
                    color: '#d7dee7'
                }
            }
        },
        yAxis: {
            type: 'value',
            minInterval: 1,
            axisLabel: commonAxisLabel,
            splitLine: commonGridLine
        },
        series: [{
            name: 'Utenti attivi',
            type: 'bar',
            barMaxWidth: 44,
            data: Object.values(userActivityData),
            itemStyle: {
                borderRadius: [10, 10, 4, 4],
                color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                    { offset: 0, color: '#60a5fa' },
                    { offset: 1, color: '#2563eb' }
                ])
            }
        }]
    });

    registerChart('completesChart', {
        animationDuration: 700,
        color: ['#f59e0b', '#0ea5e9'],
        tooltip: {
            trigger: 'axis'
        },
        legend: {
            top: 0,
            textStyle: {
                color: '#64748b'
            }
        },
        grid: {
            top: 34,
            right: 14,
            bottom: 24,
            left: 34
        },
        xAxis: {
            type: 'category',
            boundaryGap: false,
            data: monthNames,
            axisLabel: commonAxisLabel,
            axisLine: {
                lineStyle: {
                    color: '#d7dee7'
                }
            }
        },
        yAxis: {
            type: 'value',
            minInterval: 1,
            axisLabel: commonAxisLabel,
            splitLine: commonGridLine
        },
        series: [
            {
                name: 'Millebytes',
                type: 'line',
                smooth: true,
                symbolSize: 7,
                lineStyle: {
                    width: 3
                },
                areaStyle: {
                    opacity: 0.15
                },
                data: Object.values(monthlyCompleteMillebytes)
            },
            {
                name: 'Cint',
                type: 'line',
                smooth: true,
                symbolSize: 7,
                lineStyle: {
                    width: 3
                },
                areaStyle: {
                    opacity: 0.1
                },
                data: Object.values(monthlyCompleteCint)
            }
        ]
    });

    const projectValues = monthNames.map(function (_, i) {
        const monthIndex = i + 1;
        return monthlyOpenProjects[monthIndex] ?? 0;
    });

    registerChart('projectsChart', {
        animationDuration: 700,
        grid: {
            top: 18,
            right: 8,
            bottom: 24,
            left: 34
        },
        tooltip: {
            trigger: 'axis',
            axisPointer: {
                type: 'shadow'
            },
            backgroundColor: '#1f2937',
            borderWidth: 0,
            textStyle: {
                color: '#f8fafc'
            },
            formatter: function (params) {
                const item = params && params[0] ? params[0] : null;
                if (!item) {
                    return '';
                }

                return item.axisValue + '<br>Progetti aperti: <b>' + item.value + '</b>';
            }
        },
        xAxis: {
            type: 'category',
            data: monthNames,
            axisTick: {
                show: false
            },
            axisLine: {
                lineStyle: {
                    color: '#d7dee7'
                }
            },
            axisLabel: commonAxisLabel
        },
        yAxis: {
            type: 'value',
            minInterval: 1,
            axisLine: {
                show: false
            },
            axisTick: {
                show: false
            },
            splitLine: commonGridLine,
            axisLabel: {
                color: '#94a3b8',
                fontSize: 11
            }
        },
        series: [{
            name: 'Progetti aperti',
            type: 'bar',
            data: projectValues,
            barWidth: '48%',
            itemStyle: {
                borderRadius: [8, 8, 3, 3],
                color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                    { offset: 0, color: '#9DCE6B' },
                    { offset: 1, color: '#4F7A46' }
                ])
            },
            emphasis: {
                itemStyle: {
                    color: '#7fb857'
                }
            }
        }]
    });

    window.addEventListener('resize', function () {
        dashboardCharts.forEach(function (chart) {
            chart.resize();
        });
    });
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
