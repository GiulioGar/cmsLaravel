@php
use Illuminate\Support\Str;
@endphp

@extends('layouts.main')

@section('content')
<link rel="stylesheet" href="{{ asset('css/fieldControl.css') }}">

<div class="container field-control-container">

    <nav class="navbar custom-navbar mb-4">
        <div class="container-fluid d-flex align-items-center justify-content-between px-0">
            <a class="navbar-brand d-flex align-items-center" href="{{ url('fieldControl?prj='.$prj.'&sid='.$sid) }}">
                <i class="fas fa-chart-bar me-2"></i>
                <span>Status Field</span>
            </a>
            <ul class="nav custom-nav-links">
                <li class="nav-item dropdown position-relative">
                    <a class="nav-link dropdown-toggle" href="#" id="ongoingResearchDropdown" role="button"
                       data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-tasks me-1"></i> Ricerche in corso
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="ongoingResearchDropdown">
                        @forelse($ricercheInCorso as $ricerca)
                            <li>
                                <a class="dropdown-item"
                                   href="{{ url('fieldControl?prj='.$ricerca->prj.'&sid='.$ricerca->sur_id) }}">
                                    {{ $ricerca->description }}
                                </a>
                            </li>
                        @empty
                            <li><span class="dropdown-item text-muted">Nessuna ricerca attiva</span></li>
                        @endforelse
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="{{ url('surveys') }}"><b>Vedi tutte</b></a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="#">
                        <i class="fas fa-bullseye me-1"></i> Imposta Target
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('fieldQuality.index') }}">
                        <i class="fas fa-check-circle me-1"></i> Controllo Qualità
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link" data-bs-toggle="modal" data-bs-target="#downloadModal">
                        <i class="fas fa-download me-1"></i> Download
                    </a>
                </li>
                <li class="nav-item dropdown position-relative">
                    <a class="nav-link dropdown-toggle" href="#" id="settingsDropdown" role="button"
                       data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-cog me-1"></i> Impostazioni
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="settingsDropdown">
                        <li>
                            <a class="dropdown-item" href="#">
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

    <div class="row mt-4">
        {{-- Colonna sinistra: elenco domande con testo max 70 caratteri e tooltip --}}
        <div class="col-md-4">
            <h5>Domande ({{ count($questions) }})</h5>
            <div class="list-group" style="max-height: 70vh; overflow-y: auto;">
                @forelse($questions as $q)
                    @php
                        $fullText = $q['text'] ?? '';
                        $shortText = Str::limit($fullText, 70, '...');
                    @endphp
                    <a href="#"
                       class="list-group-item list-group-item-action question-item"
                       data-question-id="{{ $q['id'] }}"
                       data-code="{{ $q['code'] }}"
                       data-bs-toggle="tooltip"
                       data-bs-placement="right"
                       title="{{ $fullText }}">
                        <strong class="question-code">{{ $q['code'] }})</strong>
                        {{ $shortText }}
                    </a>
                @empty
                    <div class="alert alert-warning">
                        Nessuna domanda di tipo choice/scale single trovata
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Colonna destra: card con i dettagli della domanda --}}
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 id="questionTitle" class="mb-0">Seleziona una domanda</h5>
                </div>
                <div class="card-body" id="questionDetail">
                    <p class="text-muted">Nessuna domanda selezionata</p>
                </div>
            </div>
        </div>
    </div>
</div>



<!-- Modale per mostrare gli UID target -->
<div class="modal fade" id="modalTarget" tabindex="-1" aria-labelledby="modalTargetLabel" aria-hidden="true">
    <div class="modal-dialog modal-md">
      <div class="modal-content" style="border-radius: 6px;">
        <div class="modal-header" style="background-color: #222E3C; color: #fff;">
          <h5 class="modal-title" id="modalTargetLabel">Target - Opzione selezionata</h5>
          <button type="button" class="btn btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" id="modalTargetBody">

          <!-- Messaggio con conteggio utenti in target -->
          <div id="modalTargetCountBox">
            <p class="text-muted">Nessun dato.</p>
          </div>

          <!-- Seleziona Target esistente -->
          <label for="selectTarget" class="form-label mt-3">Assegna a Target:</label>
          <div class="d-flex align-items-center mb-2" style="gap: 0.5rem;">
            <select id="selectTarget" class="form-select">
              <!-- popolato dinamicamente da JS -->
            </select>
            <!-- Icona per aggiungere un nuovo target -->
            <i class="fas fa-plus-square" id="btnAddNewTarget" style="font-size:1.25rem; cursor:pointer;"
               data-bs-toggle="tooltip" title="Crea nuovo target"></i>
          </div>

          <!-- Campo input nascosto che appare solo quando si clicca l'icona "nuovo target" -->
          <div id="newTargetBox" class="mb-2" style="display:none;">
            <div class="input-group">
              <input type="text" id="newTargetName" class="form-control" placeholder="Nome nuovo target...">
              <button class="btn btn-success" type="button" id="btnSaveNewTarget">Salva</button>
            </div>
          </div>

        </div>
        <div class="modal-footer">
        <button type="button" class="btn btn-primary" id="btnAssignToTarget">Assegna al target</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
        </div>
      </div>
    </div>
  </div>



@endsection

{{-- JavaScript --}}
@section('scripts')
<script>
document.addEventListener("DOMContentLoaded", function() {

    let currentQuestionId = null;
    let currentOptionIndex = null;

    // Tooltip
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.forEach(function (tooltipTriggerEl) {
        new bootstrap.Tooltip(tooltipTriggerEl, { placement: 'left' });
    });

    // Click su .option-target-icon
    document.body.addEventListener("click", function(e) {
        if (e.target.classList.contains("option-target-icon")) {
            e.preventDefault();

            currentQuestionId = e.target.getAttribute("data-question-id");
            currentOptionIndex = e.target.getAttribute("data-option-index");

            let urlCount = "{{ route('targetField.getTargetUIDs') }}"
                         + `?prj={{ $prj }}&sid={{ $sid }}`
                         + `&question_id=${currentQuestionId}`
                         + `&option_index=${currentOptionIndex}`;

            let modalBodyCount = document.getElementById("modalTargetCountBox");
            modalBodyCount.innerHTML = "<p class='text-muted'>Caricamento in corso...</p>";

            fetch(urlCount)
            .then(r => r.json())
            .then(d => {
                if(!d.success){
                    modalBodyCount.innerHTML = `<p class="text-danger">${d.message || 'Errore'}</p>`;
                } else {
                    let n = d.validCount || 0;
                    if(n === 0) {
                        modalBodyCount.innerHTML = `<p class="text-danger">Non ci sono utenti in target</p>`;
                    } else if(n === 1) {
                        modalBodyCount.innerHTML = `<p>C'è 1 utente in target</p>`;
                    } else {
                        modalBodyCount.innerHTML = `<p>Ci sono ${n} utenti in target</p>`;
                    }
                }
            })
            .catch(err => {
                modalBodyCount.innerHTML = `<p class="text-danger">Errore di rete: ${err}</p>`;
            });

            loadExistingTargets();

            let myModal = new bootstrap.Modal(document.getElementById("modalTarget"), {});
            myModal.show();
        }
    });

    function loadExistingTargets(){
        fetch("{{ route('targetField.fetchTargets') }}")
        .then(r => r.json())
        .then(d => {
            let sel = document.getElementById("selectTarget");
            if(!d.success){
                sel.innerHTML = "<option value=''>Errore</option>";
                return;
            }
            sel.innerHTML = "";
            let targets = d.targets || [];
            if(targets.length === 0){
                let opt = document.createElement("option");
                opt.value = "";
                opt.textContent = "Nessun target disponibile";
                sel.appendChild(opt);
            } else {
                targets.forEach(t => {
                    let opt = document.createElement("option");
                    opt.value = t.id;
                    opt.textContent = t.tag;
                    sel.appendChild(opt);
                });
            }
        })
        .catch(err => {
            document.getElementById("selectTarget").innerHTML = "<option value=''>Errore rete</option>";
        });
    }

    let btnAdd = document.getElementById("btnAddNewTarget");
    let newTargetBox = document.getElementById("newTargetBox");
    btnAdd.addEventListener("click", function(){
        newTargetBox.style.display = (newTargetBox.style.display === "none") ? "block" : "none";
    });

    let btnSave = document.getElementById("btnSaveNewTarget");
    btnSave.addEventListener("click", function(){
        let nameField = document.getElementById("newTargetName");
        let newName = nameField.value.trim();
        if(!newName){
            Swal.fire({
                icon: 'warning',
                title: 'Attenzione',
                text: 'Inserisci un nome per il nuovo target.'
            });
            return;
        }

        let formData = new FormData();
        formData.append("targetName", newName);

        fetch("{{ route('targetField.addTarget') }}", {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": "{{ csrf_token() }}"
            },
            body: formData
        })
        .then(r => r.json())
        .then(d => {
            if(!d.success){
                Swal.fire({
                    icon: 'error',
                    title: 'Errore',
                    text: d.message || 'Errore nell\'aggiunta del target.'
                });
            } else {
                nameField.value = "";
                newTargetBox.style.display = "none";
                loadExistingTargets();
                Swal.fire({
                    icon: 'success',
                    title: 'Fatto!',
                    text: 'Nuovo target creato con successo.'
                });
            }
        })
        .catch(err=>{
            Swal.fire({
                icon: 'error',
                title: 'Errore di rete',
                text: err
            });
        });
    });

    let btnAssign = document.getElementById("btnAssignToTarget");
    btnAssign.addEventListener("click", function(){
        let targetId = document.getElementById("selectTarget").value;
        if(!targetId){
            Swal.fire({
                icon: 'warning',
                title: 'Attenzione',
                text: 'Seleziona un target!'
            });
            return;
        }
        if(!currentQuestionId || !currentOptionIndex){
            Swal.fire({
                icon: 'warning',
                title: 'Attenzione',
                text: 'Nessuna opzione selezionata.'
            });
            return;
        }

        let url = "{{ route('targetField.getTargetUIDs') }}"
                + `?prj={{ $prj }}&sid={{ $sid }}`
                + `&question_id=${currentQuestionId}`
                + `&option_index=${currentOptionIndex}`;

        fetch(url)
        .then(r => r.json())
        .then(d => {
            if(!d.success){
                Swal.fire({
                    icon: 'error',
                    title: 'Errore',
                    text: d.message || 'Errore nel recupero UID'
                });
                return;
            }
            let uidList = d.uids || [];
            if(uidList.length === 0){
                Swal.fire({
                    icon: 'info',
                    title: 'Nessun UID',
                    text: 'Non ci sono utenti da assegnare.'
                });
                return;
            }

            fetch("{{ route('targetField.assignTarget') }}", {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": "{{ csrf_token() }}",
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    targetId: targetId,
                    uids:     uidList
                })
            })
            .then(rr => rr.json())
            .then(resp => {
                if(!resp.success){
                    Swal.fire({
                        icon: 'error',
                        title: 'Errore',
                        text: resp.message || 'Errore durante l\'assegnazione'
                    });
                } else {
                    Swal.fire({
                        icon: 'success',
                        title: 'Assegnazione completata',
                        text: 'Nuovi inserimenti: ' + resp.insertedCount
                    });
                }
            })
            .catch(e => {
                Swal.fire({
                    icon: 'error',
                    title: 'Errore di rete',
                    text: e
                });
            });
        })
        .catch(er => {
            Swal.fire({
                icon: 'error',
                title: 'Errore di rete',
                text: er
            });
        });
    });


    function truncateText(str, maxLen=50) {
        if (!str) return "";
        if (str.length <= maxLen) return str;
        return str.substring(0, maxLen) + "...";
    }

    var items   = document.querySelectorAll(".question-item");
    var titleEl = document.getElementById("questionTitle");
    var detailEl= document.getElementById("questionDetail");

    items.forEach(function(item){
        item.addEventListener("click", function(e){
            e.preventDefault();

            titleEl.textContent = "Caricamento...";
            detailEl.innerHTML  = "";

            var qid = this.getAttribute("data-question-id");
            var url = "{{ route('targetField.getQuestionDetail') }}"
                    + "?prj={{ $prj }}&sid={{ $sid }}&question_id=" + qid;

            fetch(url)
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    titleEl.textContent = "Errore";
                    detailEl.innerHTML = "<p class='text-danger'>"
                                       + (data.message || "Errore generico")
                                       + "</p>";
                    return;
                }

                var q      = data.question || {};
                var dist   = data.distribution || {};
                var qType  = q.type || "";
                var qText  = q.text || "";
                var qCode  = q.code || "";
                var nInt   = data.countInterviews || 0;

                var fullTitle = qCode + " - " + qText;
                titleEl.innerHTML = `
                  <span data-bs-toggle="tooltip" title="${fullTitle.replace(/"/g,'&quot;')}">
                    ${truncateText(fullTitle, 50)}
                  </span>
                `;
                var newTooltips = [].slice.call(titleEl.querySelectorAll('[data-bs-toggle="tooltip"]'));
                newTooltips.forEach(function (el) {
                    new bootstrap.Tooltip(el);
                });

                if (qType === "choice") {
                    var opts = q.options || [];
                    if (opts.length > 0) {
                        var html = "";
                        opts.forEach(function(opt, idx){
                            var count = dist[idx] || 0;
                            var fullOptText = opt || "";
                            var shortOptText = truncateText(fullOptText, 50);
                            var perc = (nInt>0) ? Math.round((count/nInt)*100) : 0;

                            html += `
                            <div class="option-row">
                                <div class="option-text" data-bs-toggle="tooltip"
                                     title="${fullOptText.replace(/"/g,'&quot;')}">
                                    ${shortOptText}
                                </div>
                                <div class="option-progress">
                                    <div class="option-progress-fill" style="width: ${perc}%;">
                                    </div>
                                </div>
                                <span class="option-badge">${count}</span>
                                <i class="fas fa-bullseye option-target-icon"
                                    data-question-id="${qid}"
                                    data-option-index="${idx}"
                                    data-bs-toggle="tooltip"
                                    title="Crea target">
                                </i>
                            </div>
                            `;
                        });
                        detailEl.innerHTML = html;
                        var optTooltips = [].slice.call(detailEl.querySelectorAll('[data-bs-toggle="tooltip"]'));
                        optTooltips.forEach(function (el) {
                            new bootstrap.Tooltip(el, { placement: 'left' });
                        });

                    } else {
                        detailEl.innerHTML = "<p>Nessuna opzione.</p>";
                    }

                } else if (qType === "scale") {
                    var rows = q.rows || [];
                    var cols = q.cols || [];
                    if (!rows.length || !cols.length) {
                        detailEl.innerHTML = "<p>Scale mancante di rows o cols.</p>";
                        return;
                    }
                    var tableHtml = `<table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th></th>`;
                    cols.forEach(function(colVal){
                        tableHtml += `<th>${colVal}</th>`;
                    });
                    tableHtml += `</tr></thead><tbody>`;
                    rows.forEach(function(rowVal, rIndex){
                        tableHtml += `<tr>
                                        <td><strong>${rowVal}</strong></td>`;
                        cols.forEach(function(_, cIndex){
                            var val = (dist[rIndex] && dist[rIndex][cIndex])
                                      ? dist[rIndex][cIndex]
                                      : 0;
                            tableHtml += `<td>${val}</td>`;
                        });
                        tableHtml += `</tr>`;
                    });
                    tableHtml += `</tbody></table>`;
                    detailEl.innerHTML = tableHtml;

                } else {
                    detailEl.innerHTML = "<p>Tipo non gestito.</p>";
                }
            })
            .catch(err => {
                titleEl.textContent="Errore di rete";
                detailEl.innerHTML="<p class='text-danger'>"+err+"</p>";
            });
        });
    });
});
</script>


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


