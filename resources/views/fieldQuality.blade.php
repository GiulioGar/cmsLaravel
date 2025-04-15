@extends('layouts.main')

@section('content')
    <!-- Importa lo stile personalizzato già usato in fieldControl -->
    <link rel="stylesheet" href="{{ asset('css/fieldControl.css') }}">

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
                                <th class="small">+</th> <!-- Colonna modale 3 puntini -->
                                <th class="small">IID</th>
                                <th class="small">UID</th>
                                <th class="small">Panel</th>
                                <th class="small">Codice</th>
                                <th class="small">Testo</th>
                                <th class="small">Check</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($openQuestionsData as $index => $open)
                                @php
                                    // Un ID univoco per la modale
                                    $modalId = "modalOpen_{$open['iid']}_{$index}";
                                @endphp

                                <tr>
                                    <!-- Colonna con 3 puntini che apre la modale -->
                                    <td class="small">
                                        <!-- Link che apre modale -->
                                        <a href="#" data-bs-toggle="modal" data-bs-target="#{{ $modalId }}">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </a>
                                    </td>

                                    <td class="small">{{ $open['iid'] }}</td>
                                    <td class="small">{{ $open['uid'] }}</td>
                                    <td class="small">{{ $open['panel'] }}</td>
                                    <td class="small">
                                        <span data-bs-toggle="tooltip" title="{{ $open['tooltip'] }}">
                                            {{ $open['codice'] }}
                                        </span>
                                    </td>
                                    <td class="small">{{ $open['openResponse'] }}</td>
                                    <td class="small">
                                        @if(!empty($open['isFake']) && $open['isFake'] === true)
                                           <b> <span class="fas fa-exclamation" style="color: red;" title="Risposta dubbia"></span></b>
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

                                                <hr/>

                                                <!-- Bottoni per whitelist e blacklist -->
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
    <!-- COLONNA SINISTRA (50%) -->
    <div class="col-md-6">
        <div class="quality-card shadow-sm mb-4">
            <div class="quality-card-header quality-header-left">
                <h5 class="mb-0">Qualità domande a griglia singola</h5>
            </div>
            <div class="quality-card-body p-0">
                @if(count($scaleData) > 0)
                    <!-- Contenitore con scroll e font ridotto -->
                    <div class="quality-table-container" style="max-height: 350px; overflow-y: auto;">
                        <table class="table table-hover quality-table-lower">
                            <thead>
                                <tr>
                                    <th class="small">IID</th>
                                    <th class="small">UID</th>
                                    <th class="small">Panel</th>
                                    <th class="small">Domanda</th>
                                    <th class="small">Changes %</th>
                                    <!-- RIMOSSA colonna #Changes -->
                                    <th class="small">Tot Risposte</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($scaleData as $scale)
                                    @php
                                        $totAnswers = count($scale['answers']);
                                    @endphp
                                    <tr>
                                        <!-- IID -->
                                        <td class="small">{{ $scale['iid'] }}</td>

                                        <!-- UID -->
                                        <td class="small">{{ $scale['uid'] }}</td>

                                        <!-- Panel -->
                                        <td class="small">{{ $scale['panel'] }}</td>

                                        <!-- Codice con tooltip -->
                                        <td class="small">
                                            <span data-bs-toggle="tooltip" title="{{ $scale['tooltip'] }}">
                                                {{ $scale['code'] }}
                                            </span>
                                        </td>

                                        <!-- Changes % -->
                                        <td class="small">{{ $scale['changesPct'] }}%</td>

                                        <!-- Totale risposte -->
                                        <td class="small">{{ $totAnswers }}</td>
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

    <!-- COLONNA DESTRA (50%) -->
    <div class="col-md-6">
        <div class="quality-card shadow-sm mb-4">
            <div class="quality-card-header quality-header-right d-flex align-items-center justify-content-between">
                <h5 class="mb-0">Other Controls</h5>

                <!-- Bottone con icona + -->
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addControllerModal">
                    <i class="fas fa-plus"></i>
                </button>
            </div>
            <div class="quality-card-body" style="min-height: 200px;">
                <!-- Contenuto placeholder per ora -->
                <p class="text-muted">[In lavorazione...]</p>
            </div>
        </div>
    </div>
<!-- FINE TERZA RIGA -->



    </div><!-- container -->

<!-- Modale "Aggiungi -Controller" -->
<div class="modal fade" id="addControllerModal" tabindex="-1" aria-labelledby="addControllerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg"> <!-- large se servono più campi -->
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addControllerModalLabel">Aggiungi -Controller</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
            </div>

            <div class="modal-body">
                <!-- Form per i filtri -->
                <form id="addControllerForm">
                    <!-- CAMPOS: Domanda 1, Operatore, Risposta 1 -->
                    <div class="mb-3">
                        <label for="question1" class="form-label">Domanda 1</label>
                        <select class="form-select" id="question1" name="question1">
                            <option value="">-- Seleziona domanda --</option>
                            @foreach($questionsFromApi as $q)
                                @if(in_array($q['type'], ['choice','open']))
                                    <option value="{{ $q['id'] }}">
                                        {{ $q['code'] }} - {{ Str::limit($q['text'], 50) }}
                                    </option>
                                @endif
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3 d-flex gap-2 align-items-end">
                        <div>
                            <label for="operator1" class="form-label">Operatore</label>
                            <select class="form-select" id="operator1" name="operator1">
                                <option value="=">=</option>
                                <option value=">">></option>
                                <option value="<"><</option>
                            </select>
                        </div>
                        <div>
                            <label for="answer1" class="form-label">Risposta 1</label>
                            <input type="text" class="form-control" id="answer1" name="answer1" placeholder="Valore risposta">
                        </div>
                    </div>

                    <hr/>

                    <div class="mb-2 d-flex align-items-center">
                        <label class="form-label me-2">Logica:</label>
                        <select class="form-select w-auto" id="logicalOperator" name="logicalOperator">
                            <option value="AND">AND</option>
                            <option value="OR">OR</option>
                        </select>
                        <span class="ms-2">(opzionale se domanda 2 non è selezionata)</span>
                    </div>

                    <div class="mb-3">
                        <label for="question2" class="form-label">Domanda 2 (opzionale)</label>
                        <select class="form-select" id="question2" name="question2">
                            <option value="">-- Nessuna --</option>
                            @foreach($questionsFromApi as $q)
                                @if(in_array($q['type'], ['choice','open']))
                                    <option value="{{ $q['id'] }}">
                                        {{ $q['code'] }} - {{ Str::limit($q['text'], 50) }}
                                    </option>
                                @endif
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3 d-flex gap-2 align-items-end">
                        <div>
                            <label for="operator2" class="form-label">Operatore</label>
                            <select class="form-select" id="operator2" name="operator2">
                                <option value="=">=</option>
                                <option value=">">></option>
                                <option value="<"><</option>
                            </select>
                        </div>
                        <div>
                            <label for="answer2" class="form-label">Risposta 2</label>
                            <input type="text" class="form-control" id="answer2" name="answer2" placeholder="Valore risposta">
                        </div>
                    </div>
                </form>
                <!-- Fine form -->
            </div><!-- modal-body -->

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                <!-- Bottone di salvataggio -->
                <button type="submit" form="addControllerForm" class="btn btn-primary">
                    Salva Filtro
                </button>
            </div>
        </div>
    </div>
</div>

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

<script>
    document.getElementById("addControllerForm").addEventListener("submit", function(e) {
        e.preventDefault();

        // Serializzi i campi
        let formData = new FormData(this);
        let payload = {
            question1:       formData.get('question1'),
            operator1:       formData.get('operator1'),
            answer1:         formData.get('answer1'),
            logicalOperator: formData.get('logicalOperator'),
            question2:       formData.get('question2'),
            operator2:       formData.get('operator2'),
            answer2:         formData.get('answer2')
        };

        fetch("{{ route('fieldQuality.saveFilter') }}", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": "{{ csrf_token() }}"
            },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            if(data.success){
                alert("Filtro salvato correttamente!");
                // Chiudi modale
                var modal = bootstrap.Modal.getInstance(document.getElementById('addControllerModal'));
                modal.hide();
                // location.reload(); // se vuoi ricaricare la pagina
            } else {
                alert("Errore: " + data.message);
            }
        })
        .catch(err => {
            console.error(err);
            alert("Errore generico nel salvataggio del filtro.");
        });
    });
    </script>

@endsection
