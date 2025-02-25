@extends('layouts.main')

@section('content')

<!-- Importazione dello stile personalizzato -->
<link rel="stylesheet" href="{{ asset('css/fieldControl.css') }}">

<div class="container field-control-container">
    <h2 class="mb-4">Dashboard - Progetto {{ $prj }}</h2>

    <div class="row">
        <!-- Card 1: RICERCA -->
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex align-items-center">
                    <div class="stat-badge badge-dark">RICERCA</div>
                    <span class="ms-3 stat-text">Survey ID</span>
                </div>
                <h3 class="mt-2 stat-value">{{ $panelData->sur_id ?? 'N/A' }}</h3>
                <p class="text-muted">{{ $panelData->description ?? 'No description available' }}</p>
            </div>
        </div>

        <!-- Card 2: TARGET -->
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex align-items-center">
                    <div class="stat-badge badge-red">TARGET</div>
                    <span class="ms-3 stat-text">Goal</span>
                </div>
                <h3 class="mt-2 stat-value">{{ $panelData->goal ?? 'N/A' }}</h3>
                <p class="text-muted">
                    {{ $panelData->sex_target ?? 'N/A' }} - {{ $panelData->age1_target ?? 'N/A' }} - {{ $panelData->age2_target ?? 'N/A' }}
                </p>
            </div>
        </div>

        <!-- Card 3: TIMING -->
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex align-items-center">
                    <div class="stat-badge badge-green">TIMING</div>
                    <span class="ms-3 stat-text">Start - End</span>
                </div>
                <h3 class="mt-2 stat-value">
                    {{ $panelData->sur_date ?? 'N/A' }} - {{ $panelData->end_field ?? 'N/A' }}
                </h3>
                <p class="text-muted">Giorni in field: {{ $panelData->giorniInField() ?? 'N/A' }}</p>
            </div>
        </div>

        <!-- Card 4: INFO -->
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex align-items-center">
                    <div class="stat-badge badge-blue">INFO</div>
                    <span class="ms-3 stat-text">Status</span>
                </div>
                <h3 class="mt-2 stat-value">{{ $panelData->status ?? 'N/A' }}</h3>
                <p class="text-muted">Durata: {{ $panelData->durata ?? 'N/A' }} | Panel: {{ $panelData->panel ?? 'N/A' }}</p>
            </div>
        </div>
    </div>
</div>


@endsection
