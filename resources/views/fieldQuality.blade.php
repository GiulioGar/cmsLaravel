@extends('layouts.main')

@section('content')
    <!-- Importa lo stile personalizzato già usato in fieldControl -->
    <link rel="stylesheet" href="{{ asset('css/fieldControl.css') }}">

    <div class="container field-control-container">

<!-- NAVBAR MODERNA CON MENU A TENDINA -->
<nav class="navbar custom-navbar mb-4">
    <div class="container-fluid d-flex align-items-center justify-content-between px-0">
        <!-- Brand a sinistra -->
        <a class="navbar-brand d-flex align-items-center" href="#">
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
                        <p><strong>Punteggio medio:</strong> {{ number_format($averageScore, 1) }}</p>
                        <p><strong>Migliore:</strong> {{ number_format($maxScore, 1) }}</p>
                        <p><strong>Peggiore:</strong> {{ number_format($minScore, 1) }}</p>
                        <p><strong>LOI media:</strong> {{ $loiMediaFormatted }} minuti</p>
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
                            <!-- Contenitore per scroll e header fisso -->
                            <div class="quality-table-container">
                                <table class="table table-hover quality-table-interviews">
                                    <thead>
                                        <tr>
                                            <th>IID</th>
                                            <th>UID</th>
                                            <th>Punteggio</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($completeInterviews as $interview)
                                            <tr>
                                                <td>{{ $interview['iid'] }}</td>
                                                <td>{{ $interview['uid'] }}</td>
                                                <!-- punteggio con decimale fisso -->
                                                <td>{{ number_format($interview['score'], 1) }}</td>
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


        <!-- SECONDA RIGA -->
<div class="row">
    <!-- COLONNA SINISTRA (30%) -->
    <div class="col-md-4">
        <div class="quality-card shadow-sm mb-4">
            <div class="quality-card-header quality-header-left">
                <h5 class="mb-0">Controllo LOI</h5>
            </div>
            <div class="quality-card-body">
                <div class="quality-table-container">
                    <table class="table table-hover quality-table-lower">
                        <thead>
                            <tr>
                                <th class="small">IID</th>
                                <th class="small">UID</th>
                                <th class="small">LOI</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($loiData as $item)
                                <tr>
                                   <!-- IID -->
                                    <td class="small">{{ $item['iid'] }}</td>

                                    <!-- UID -->
                                    <td class="small">{{ $item['uid'] }}</td>

                                    <!-- LOI (minuti.secondi) -->
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
                <div class="quality-table-container">
                    <table class="table table-hover quality-table-lower">
                        <thead>
                            <tr>
                                <th class="small">IID</th>
                                <th class="small">UID</th>
                                <th class="small">Codice</th>
                                <th class="small">Testo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($openQuestionsData as $open)
                                            <tr>
                                                <td class="small">{{ $open['iid'] }}</td>
                                                <td class="small">{{ $open['uid'] }}</td>

                                                <!-- Codice con tooltip -->
                                                <td class="small">
                                                    <span data-bs-toggle="tooltip"
                                                        title="{{ $open['tooltip'] }}">
                                                        {{ $open['codice'] }}
                                                    </span>
                                                </td>

                                                <!-- Risposta open -->
                                                <td class="small">{{ $open['openResponse'] }}</td>
                                            </tr>
                                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- FINE SECONDA RIGA -->


    </div><!-- container -->
@endsection

@section('scripts')

<script>
    document.addEventListener("DOMContentLoaded", function () {
            var dropdownElements = document.querySelectorAll('.dropdown-toggle');

            // Inizializza tutti i dropdown
            dropdownElements.forEach(function (dropdown) {
                new bootstrap.Dropdown(dropdown);
            });

            console.log("✅ Bootstrap Dropdown inizializzato correttamente.");

            // Aggiungiamo un event listener globale ai dropdown-toggle
            document.body.addEventListener("click", function (event) {
                if (event.target.classList.contains("dropdown-toggle")) {
                    var dropdown = bootstrap.Dropdown.getOrCreateInstance(event.target);
                    dropdown.show();
                }
            });
        });
    </script>

@endsection
