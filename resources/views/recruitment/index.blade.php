@extends('layouts.main')

@section('content')

<link rel="stylesheet" href="{{ asset('css/recruitment.css') }}">

<main class="content">
    <div class="container-fluid">

        {{-- HEADER --}}
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1">Recruitment</h2>
                <p class="text-muted mb-0">Monitoraggio campagne di reclutamento e referral</p>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <select id="filter-month" class="form-control">
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ str_pad($m, 2, '0', STR_PAD_LEFT) }}"
                            {{ (int)$currentMonth === $m ? 'selected' : '' }}>
                            {{ str_pad($m, 2, '0', STR_PAD_LEFT) }}
                        </option>
                    @endfor
                </select>

                <select id="filter-year" class="form-control">
                    @for($y = now()->year; $y >= 2021; $y--)
                        <option value="{{ $y }}" {{ $currentYear == $y ? 'selected' : '' }}>
                            {{ $y }}
                        </option>
                    @endfor
                </select>
            </div>
        </div>

        {{-- KPI MINI BOX --}}
        <div class="row mb-4">
            <div class="col-xl-2 col-md-4 col-6 mb-3">
                <div class="card recruitment-mini-card">
                    <div class="card-body">
                        <div class="mini-label">Registrati</div>
                        <div class="mini-value" id="kpi-registered">--</div>
                    </div>
                </div>
            </div>

            <div class="col-xl-2 col-md-4 col-6 mb-3">
                <div class="card recruitment-mini-card">
                    <div class="card-body">
                        <div class="mini-label">Attivi</div>
                        <div class="mini-value" id="kpi-active">--</div>
                    </div>
                </div>
            </div>

            <div class="col-xl-2 col-md-4 col-6 mb-3">
                <div class="card recruitment-mini-card">
                    <div class="card-body">
                        <div class="mini-label">% Attivi</div>
                        <div class="mini-value" id="kpi-active-rate">--</div>
                    </div>
                </div>
            </div>

            <div class="col-xl-2 col-md-4 col-6 mb-3">
                <div class="card recruitment-mini-card">
                    <div class="card-body">
                        <div class="mini-label">Spesa</div>
                        <div class="mini-value" id="kpi-cost">--</div>
                    </div>
                </div>
            </div>

            <div class="col-xl-2 col-md-4 col-6 mb-3">
                <div class="card recruitment-mini-card">
                    <div class="card-body">
                        <div class="mini-label">CPI Medio</div>
                        <div class="mini-value" id="kpi-cpi">--</div>
                    </div>
                </div>
            </div>

            <div class="col-xl-2 col-md-4 col-6 mb-3">
                <div class="card recruitment-mini-card">
                    <div class="card-body">
                        <div class="mini-label">CPA Medio</div>
                        <div class="mini-value" id="kpi-cpa">--</div>
                    </div>
                </div>
            </div>
        </div>

{{-- ANDAMENTO GIORNALIERO --}}
<div class="card mb-4 recruitment-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0">Andamento mensile</h5>
            <small class="text-muted">Vista calendario con referral attivi nel mese</small>
        </div>
        <div id="daily-loader" class="section-loader d-none">Caricamento...</div>
    </div>
    <div class="card-body">

            <div class="daily-summary-box mb-3">
                <div class="daily-total">
                    Registrati Totali:
                    <strong id="daily-total-registered">0</strong>
                </div>
            </div>

            <div class="daily-referrals mb-3" id="daily-referral-badges">
                <div class="text-muted">Nessun referral da mostrare</div>
            </div>

            <div class="daily-month-title mb-3" id="daily-month-title">-</div>

            <div class="table-responsive">
                <table class="table table-bordered daily-calendar-table mb-0" id="daily-calendar-table">
                    <thead>
                        <tr>
                            <th>Lunedì</th>
                            <th>Martedì</th>
                            <th>Mercoledì</th>
                            <th>Giovedì</th>
                            <th>Venerdì</th>
                            <th>Sabato</th>
                            <th>Domenica</th>
                            <th>Iscritti</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">Nessun dato caricato</td>
                        </tr>
                    </tbody>
                </table>
            </div>

    </div>
</div>

        {{-- SPESE + DETTAGLIO --}}
        <div class="row mb-4">
            <div class="col-xl-7 mb-4">
                <div class="card recruitment-card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">Spese referral</h5>
                            <small class="text-muted">Registrati, attivi, costi e KPI</small>
                        </div>
                        <div id="costs-loader" class="section-loader d-none">Caricamento...</div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-striped align-middle mb-0" id="costs-table">
                                <thead>
                                    <tr>
                                        <th>Referral</th>
                                        <th>Registrati</th>
                                        <th>Attivi</th>
                                        <th>%</th>
                                        <th>Costo</th>
                                        <th>CPI</th>
                                        <th>CPA</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">Nessun dato caricato</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-5 mb-4">
                <div class="card recruitment-card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">Statistiche sintetiche</h5>
                            <small class="text-muted">Panoramica annuale rapida</small>
                        </div>
                        <div id="stats-loader" class="section-loader d-none">Caricamento...</div>
                    </div>
                    <div class="card-body">
                        <div id="stats-summary-box" class="stats-summary-box">
                            <div class="text-muted text-center py-5">Dati non ancora caricati</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- DETTAGLIO ATTIVITÀ --}}
        <div class="card mb-4 recruitment-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">Dettaglio attività per referral</h5>
                    <small class="text-muted">Distribuzione iscritti per fascia attività</small>
                </div>
                <div id="activity-loader" class="section-loader d-none">Caricamento...</div>
            </div>
            <div class="card-body">
                <div class="row" id="activity-boxes">
                    <div class="col-12">
                        <div class="text-center text-muted py-5">Nessun dato caricato</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- DEMOGRAFICA --}}
        <div class="row mb-4">
            <div class="col-xl-4 mb-4">
                <div class="card recruitment-card h-100">
                    <div class="card-header">
                        <h5 class="mb-0">Genere</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="genderChart" height="220"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 mb-4">
                <div class="card recruitment-card h-100">
                    <div class="card-header">
                        <h5 class="mb-0">Età</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="ageChart" height="220"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 mb-4">
                <div class="card recruitment-card h-100">
                    <div class="card-header">
                        <h5 class="mb-0">Area</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="areaChart" height="220"></canvas>
                    </div>
                </div>
            </div>
        </div>

    </div>
</main>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.4"></script>

<script>
    window.recruitmentUrls = {
        daily: "{{ route('recruitment.daily') }}",
        costs: "{{ route('recruitment.costs') }}",
        activity: "{{ route('recruitment.activity') }}",
        stats: "{{ route('recruitment.stats') }}"
    };
</script>

<script>
$(document).ready(function () {



    function loadDaily() {
        let year = $('#filter-year').val();
        let month = $('#filter-month').val();

        $('#daily-loader').removeClass('d-none');

        $.ajax({
            url: window.recruitmentUrls.daily,
            type: 'GET',
            data: {
                year: year,
                month: month
            },
            success: function(response) {
                $('#daily-loader').addClass('d-none');

                if (!response.success) {
                    return;
                }

updateDailySummary(
    response.month_label || '',
    response.total_registered || 0,
    response.referrals || []
);
updateDailyCalendar(response.calendar || []);

            },
            error: function(xhr) {
                $('#daily-loader').addClass('d-none');
                console.error('Errore loadDaily', xhr.responseText);
            }
        });
    }

function updateDailySummary(monthLabel, totalRegistered, referrals) {
    $('#daily-total-registered').text(formatNumber(totalRegistered));
    $('#daily-month-title').text(monthLabel || '-');

    let html = '';

    if (!referrals.length) {
        html = '<div class="text-muted">Nessun referral con più di 1 iscritto</div>';
        $('#daily-referral-badges').html(html);
        return;
    }

    referrals.forEach(function(item) {
        html += `
            <span class="daily-referral-badge">
                ${item.title}
                <span class="daily-referral-count">${formatNumber(item.total)}</span>
            </span>
        `;
    });

    $('#daily-referral-badges').html(html);
}

    function updateDailyCalendar(rows) {
        let html = '';

        if (!rows.length) {
            html = '<tr><td colspan="8" class="text-center text-muted py-4">Nessun dato trovato</td></tr>';
            $('#daily-calendar-table tbody').html(html);
            return;
        }

        rows.forEach(function(week) {
            html += '<tr>';

            week.days.forEach(function(cell) {
                if (!cell) {
                    html += '<td class="daily-empty-cell"></td>';
                    return;
                }

                html += `
                    <td class="daily-day-cell">
                        <div class="daily-day-number">${cell.day}</div>
                    </td>
                `;
            });

            html += `<td class="daily-week-total">${formatNumber(week.week_total)}</td>`;
            html += '</tr>';
        });

        $('#daily-calendar-table tbody').html(html);
    }

        function renderDailyChart(labels, series) {
        let ctx = document.getElementById('dailyChart').getContext('2d');

        if (dailyChart) {
            dailyChart.destroy();
        }

        let datasets = series.map(function(item, index) {
            let palette = [
                '#4e73df',
                '#1cc88a',
                '#f6c23e',
                '#e74a3b',
                '#36b9cc',
                '#6f42c1',
                '#fd7e14',
                '#20c997',
                '#6610f2',
                '#198754',
                '#0d6efd',
                '#dc3545',
                '#ffc107',
                '#6c757d',
                '#17a2b8'
            ];

            let color = palette[index % palette.length];

            return {
                label: item.title,
                data: item.data,
                borderColor: color,
                backgroundColor: color,
                fill: false,
                borderWidth: 2,
                pointRadius: 2,
                pointHoverRadius: 4,
                lineTension: 0.25
            };
        });

        dailyChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels.map(function(label) {
                    let parts = label.split('-');
                    return parts[2];
                }),
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: {
                    position: 'bottom'
                },
                tooltips: {
                    mode: 'index',
                    intersect: false
                },
                hover: {
                    mode: 'nearest',
                    intersect: true
                },
                scales: {
                    yAxes: [{
                        ticks: {
                            beginAtZero: true,
                            precision: 0
                        }
                    }],
                    xAxes: [{
                        ticks: {
                            autoSkip: true,
                            maxTicksLimit: 31
                        }
                    }]
                }
            }
        });
    }

    function formatNumber(value) {
        return new Intl.NumberFormat('it-IT').format(value || 0);
    }

    function formatCurrency(value) {
        let number = parseFloat(value || 0);
        return new Intl.NumberFormat('it-IT', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(number) + '€';
    }

    function loadCosts() {
        let year = $('#filter-year').val();

        $('#costs-loader').removeClass('d-none');
        $('#stats-loader').removeClass('d-none');

        $.ajax({
            url: window.recruitmentUrls.costs,
            type: 'GET',
            data: { year: year },
            success: function(response) {
                $('#costs-loader').addClass('d-none');
                $('#stats-loader').addClass('d-none');

                if (!response.success) {
                    return;
                }

                updateKpi(response.kpi || {});
                updateCostsTable(response.rows || []);
                updateStatsSummary(response.kpi || {});
            },
            error: function() {
                $('#costs-loader').addClass('d-none');
                $('#stats-loader').addClass('d-none');
            }
        });
    }

    function updateKpi(kpi) {
        $('#kpi-registered').text(formatNumber(kpi.registered));
        $('#kpi-active').text(formatNumber(kpi.active));
        $('#kpi-active-rate').text((kpi.active_rate || 0) + '%');
        $('#kpi-cost').text(formatCurrency(kpi.cost));
        $('#kpi-cpi').text(formatCurrency(kpi.cpi));
        $('#kpi-cpa').text(formatCurrency(kpi.cpa));
    }

    function updateCostsTable(rows) {
        let html = '';

        if (!rows.length) {
            html = '<tr><td colspan="7" class="text-center text-muted py-4">Nessun dato trovato</td></tr>';
            $('#costs-table tbody').html(html);
            return;
        }

        rows.forEach(function(row) {
            html += `
                <tr>
                    <td>
                        <span class="referral-name">${row.title}</span>
                        <span class="referral-sub">${row.code}</span>
                    </td>
                    <td>${formatNumber(row.registered)}</td>
                    <td>${formatNumber(row.active)}</td>
                    <td>${row.active_rate}%</td>
                    <td>${formatCurrency(row.cost)}</td>
                    <td>${formatCurrency(row.cpi)}</td>
                    <td>${formatCurrency(row.cpa)}</td>
                </tr>
            `;
        });

        $('#costs-table tbody').html(html);
    }

    function updateStatsSummary(kpi) {
        let html = `
            <div class="stats-grid">
                <div class="stats-item">
                    <div class="stats-item-label">Registrati anno</div>
                    <div class="stats-item-value">${formatNumber(kpi.registered)}</div>
                </div>
                <div class="stats-item">
                    <div class="stats-item-label">Attivi anno</div>
                    <div class="stats-item-value">${formatNumber(kpi.active)}</div>
                </div>
                <div class="stats-item">
                    <div class="stats-item-label">% Attivi</div>
                    <div class="stats-item-value">${kpi.active_rate || 0}%</div>
                </div>
                <div class="stats-item">
                    <div class="stats-item-label">Spesa totale</div>
                    <div class="stats-item-value">${formatCurrency(kpi.cost)}</div>
                </div>
                <div class="stats-item">
                    <div class="stats-item-label">CPI medio</div>
                    <div class="stats-item-value">${formatCurrency(kpi.cpi)}</div>
                </div>
                <div class="stats-item">
                    <div class="stats-item-label">CPA medio</div>
                    <div class="stats-item-value">${formatCurrency(kpi.cpa)}</div>
                </div>
            </div>
        `;

        $('#stats-summary-box').html(html);
    }

    function loadActivity() {
        let year = $('#filter-year').val();

        $('#activity-loader').removeClass('d-none');

        $.ajax({
            url: window.recruitmentUrls.activity,
            type: 'GET',
            data: { year: year },
            success: function(response) {
                $('#activity-loader').addClass('d-none');

                if (!response.success) {
                    return;
                }

                updateActivityBoxes(response.rows || []);
            },
            error: function(xhr) {
                $('#activity-loader').addClass('d-none');
                console.error('Errore loadActivity', xhr.responseText);
            }
        });
    }

    function updateActivityBoxes(rows) {
        let html = '';

        if (!rows.length) {
            html = `
                <div class="col-12">
                    <div class="text-center text-muted py-5">Nessun dato trovato</div>
                </div>
            `;
            $('#activity-boxes').html(html);
            return;
        }

        rows.forEach(function(row) {
            html += `
                <div class="col-xl-3 col-lg-4 col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="activity-card-header">
                            <h6 class="activity-card-title">${row.title}</h6>
                            <small class="text-muted">${row.code} · ${formatNumber(row.total_registered)} iscritti</small>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm activity-table mb-0">
                                    <thead>
                                        <tr>
                                            <th>Fascia</th>
                                            <th>Utenti</th>
                                            <th>%</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><span class="activity-badge activity-none">0</span></td>
                                            <td>${formatNumber(row.act_0)}</td>
                                            <td>${row.perc_0}%</td>
                                        </tr>
                                        <tr>
                                            <td><span class="activity-badge activity-low">1-2</span></td>
                                            <td>${formatNumber(row.act_1_2)}</td>
                                            <td>${row.perc_1_2}%</td>
                                        </tr>
                                        <tr>
                                            <td><span class="activity-badge activity-mid">3-5</span></td>
                                            <td>${formatNumber(row.act_3_5)}</td>
                                            <td>${row.perc_3_5}%</td>
                                        </tr>
                                        <tr>
                                            <td><span class="activity-badge activity-good">6-9</span></td>
                                            <td>${formatNumber(row.act_6_9)}</td>
                                            <td>${row.perc_6_9}%</td>
                                        </tr>
                                        <tr>
                                            <td><span class="activity-badge activity-top">10+</span></td>
                                            <td>${formatNumber(row.act_10_plus)}</td>
                                            <td>${row.perc_10_plus}%</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });

        $('#activity-boxes').html(html);
    }

    $('#filter-year').on('change', function () {
        loadCosts();
        loadActivity();
        loadDaily();
    });

    $('#filter-month').on('change', function () {
        loadDaily();
    });

    loadCosts();
    loadActivity();
    loadDaily();

});
</script>
@endsection
