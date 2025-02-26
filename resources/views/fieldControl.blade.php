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
                <p class="text-muted">{{ $panelData->cliente ?? 'No description available' }}</p>
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
                    Durata: {{ $panelData->durata ?? 'N/A' }} <br/>
                    Panel:
                    @if($panelData->panel == 0)
                        Esterno
                    @elseif($panelData->panel == 1)
                        Interactive
                    @elseif($panelData->panel == 2)
                        Lista
                    @else
                        N/A
                    @endif
                </h3>
            </div>
        </div>
    </div>

        <!-- Sezione centrale sinistra con tab laterale -->


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
                            @foreach ($panelCounts as $panelName => $panelData)
                                <li class="nav-item">
                                    <a class="nav-link" id="tab{{ $loop->index + 2 }}-tab" data-bs-toggle="pill" href="#tab{{ $loop->index + 2 }}">
                                        <i class="fas fa-chart-pie me-2"></i> {{ $panelName }}
                                    </a>
                                </li>
                            @endforeach
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
        </div>






</div>


@endsection
