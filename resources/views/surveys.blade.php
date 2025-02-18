@extends('layouts.main')

@section('content')

<style type="text/css">
    /* Esempio: ridurre l'altezza delle righe */
table.dataTable tbody tr {
    height: 40px;
}

/* Esempio: colorare header di sfondo */
table.dataTable thead th {
    background-color: #f8f9fa; /* grigio chiaro */
    text-align: center;
    vertical-align: middle;
}

/* Esempio di pallino rosso lampeggiante */
.blinking-dot {
    display: inline-block;
    width: 8px;
    height: 8px;
    margin-right: 4px; /* un po' di spazio prima di sur_id */
    border-radius: 50%;
    background-color: red;
    animation: blink 1s infinite;
}

/* Definizione dell'animazione */
@keyframes blink {
  50% { opacity: 0; }
}

</style>

<main class="content">

    <div class="container">
        <h1>Elenco Ricerche</h1>
        <hr>

        <table id="surveys-table"
        class="table table-striped table-bordered table-sm"
        style="width:100%; font-size: 0.7rem; text-align: center;">
     <thead>
         <tr>
             <th>Codice</th>
             <th>Ricerca</th>
             <th>Panel</th>
             <th>Complete</th>
             <th>IR_panel</th>
             <th>IR_surv</th>
             <th>Fine field</th>
             <th>Giorni</th>
             <th>Costo</th>
             <th>Bytes</th>
             <th></th>
         </tr>
     </thead>
 </table>
    </div>

</main>

<main class="content">
    <div class="container">
        <h1>Elenco Ricerche</h1>
        <hr>

        <table id="surveys-table"
               class="table table-striped table-bordered table-sm"
               style="width:100%; font-size: 0.7rem; text-align: center;">
            <thead>
                <tr>
                    <th>Codice</th>
                    <th>Ricerca</th>
                    <th>Panel</th>
                    <th>Complete</th>
                    <th>IR_panel</th>
                    <th>IR_surv</th>
                    <th>Fine field</th>
                    <th>Giorni</th>
                    <th>Costo</th>
                    <th>Bytes</th>
                    <th></th>
                </tr>
            </thead>
        </table>
    </div>
</main>

{{-- Finestra Modale per Modifica --}}
<!-- Modale per Modifica Ricerca -->
<div class="modal fade" id="editSurveyModal" tabindex="-1" aria-labelledby="editSurveyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <!-- Il form punta al tuo controller in AJAX, quindi niente action qui -->
        <form id="editSurveyForm">
          @csrf
          <!-- Se vuoi usare PUT method di Laravel (spoofing) -->
          <input type="hidden" name="_method" value="PUT">
          <!-- Campo hidden per l'ID del record -->
          <input type="hidden" name="id" id="survey-id">

          <div class="modal-header">
            <h5 class="modal-title" id="editSurveyModalLabel">Modifica Ricerca</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
          </div>

          <div class="modal-body">
            <!-- 1) Codice SID Progetto -->
            <div class="input-group mb-3">
              <div class="input-group-prepend">
                <span class="input-group-text">Codice SID Progetto:</span>
              </div>
              <!-- In Laravel, ipotizziamo che sur_id corrisponda a labprj -->
              <input
                required
                type="text"
                class="form-control"
                name="sur_id"
                id="sur_id"
                placeholder="">
            </div>

            <!-- 2) Panel -->
            <div class="input-group mb-3">
              <div class="input-group-prepend">
                <label class="input-group-text" for="panel">Panel:</label>
              </div>
              <!-- Esempio: 1=Millebytes, 0=Esterno, 2=Target -->
              <select name="panel" required class="custom-select" id="panel">
                <option value="1">Millebytes</option>
                <option value="0">Esterno</option>
                <option value="2">Target</option>
              </select>
            </div>

            <!-- 3) Genere -->
            <div class="input-group mb-3">
              <div class="input-group-prepend">
                <label class="input-group-text" for="sex_target">Genere:</label>
              </div>
              <!-- Nel tuo vecchio snippet c’erano 3 codici: 1=Uomo, 2=Donna, 3=M-F -->
              <select required name="sex_target" class="custom-select" id="sex_target">
                <option value="3">Uomo/Donna</option>
                <option value="1">Uomo</option>
                <option value="2">Donna</option>
              </select>
            </div>

            <!-- 4) Età (range) -->
            <!-- Se in Laravel vuoi salvare 2 campi (es. min_age / max_age) devi gestirli nel DB -->
            <div class="form-row input-group mb-3">
              <div class="input-group-prepend">
                <span class="input-group-text">Età:</span>
              </div>
              <div class="col">
                <input name="age1_target" type="number" class="form-control" id="age1_target" placeholder="età minima">
              </div>
              <div class="col">
                <input name="age2_target" type="number" class="form-control" id="age2_target" placeholder="età massima">
              </div>
            </div>

            <!-- 5) Interviste (complete) -->
            <div class="input-group mb-3">
              <div class="input-group-prepend">
                <span class="input-group-text">Interviste:</span>
              </div>
              <input
                required
                type="number"
                class="form-control"
                name="complete"
                id="complete"
                placeholder="0">
            </div>

            <!-- 6) Data di chiusura field (end_date) -->
            <div class="input-group mb-3">
              <div class="input-group-prepend">
                <span class="input-group-text">Chiusura Field:</span>
              </div>
              <input
                type="date"
                class="form-control"
                name="end_field"
                id="end_field"
                placeholder="AAAA-MM-GG">
            </div>

            <!-- 7) Descrizione -->
            <div class="input-group mb-3">
              <div class="input-group-prepend">
                <span class="input-group-text">Descrizione:</span>
              </div>
              <input
                type="text"
                class="form-control"
                name="description"
                id="descrizione"
                placeholder="Es. Ipsos_Pack_Test_per_BAT">
            </div>

            <!-- 8) Stato (Aperto/Chiuso) -->
            <div class="input-group mb-3">
              <div class="input-group-prepend">
                <span class="input-group-text">Stato:</span>
              </div>
              <select name="stato" class="custom-select" id="stato">
                <option value="0">Aperto</option>
                <option value="1">Chiuso</option>
              </select>
            </div>

          </div> <!-- /modal-body -->

          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
            <button type="submit" class="btn btn-primary">Salva</button>
          </div>
        </form>
      </div>
    </div>
  </div>





@endsection

@section('scripts')
    <!-- jQuery (necessario per DataTables) -->
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>

    <!-- DataTables base + Bootstrap 5 style -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

    <style>
        /* Riduci ulteriormente il font anche nella paginazione, se vuoi */
        div.dataTables_wrapper .dataTables_paginate .paginate_button {
            font-size: 0.7rem !important;
        }
        /* Centra eventualmente la paginazione: */
        .dataTables_paginate {
            text-align: center !important;
        }
    </style>

<script>
    $(document).ready(function () {
        var table = $('#surveys-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{ route('surveys.data') }}',
            pageLength: 30,
            lengthMenu: [30, 50, 100],
            pagingType: "full_numbers",
            scrollX: true,
            columns: [
                { data: 'sur_id',            name: 'sur_id' },
                { data: 'description',       name: 'description' },
                { data: 'panel',             name: 'panel' },
                { data: 'complete',          name: 'complete' },
                { data: 'red_panel',         name: 'red_panel' },
                { data: 'red_surv',          name: 'red_surv' },
                { data: 'end_field',         name: 'end_field' },
                { data: 'giorni_rimanenti',  name: 'giorni_rimanenti' },
                { data: 'Costo',             name: 'Costo' },
                { data: 'bytes',             name: 'bytes' },
                {
                    data: 'campo_edit',
                    name: 'campo_edit',
                    orderable: false,
                    searchable: false
                },
            ],
            language: {
                url: "https://cdn.datatables.net/plug-ins/1.13.4/i18n/it-IT.json"
            }
        });

        $('#surveys-table').on('click', '.btn-edit', function() {
  var id = $(this).data('id');

  // GET /surveys/{id}/edit per avere JSON con tutti i campi
                $.ajax({
                    url: '/surveys/' + id + '/edit',
                    type: 'GET',
                    success: function(response) {
                    // Riempie i campi
                    $('#survey-id').val(response.id); // hidden
                    $('#sur_id').val(response.sur_id);
                    $('#panel').val(response.panel);
                    $('#sex_target').val(response.sex_target);
                    $('#age1_target').val(response.age1_target);
                    $('#age2_target').val(response.age2_target);
                    $('#complete').val(response.complete);
                    if (response.end_field) {
                    // Esempio: "2024-10-09 00:00:00"
                    // Divido in base allo spazio
                    let dateTimeParts = response.end_field.split(' ');
                    let dateOnly = dateTimeParts[0]; // "2024-10-09"
                    $('#end_field').val(dateOnly);
                } else {
                    $('#end_field').val('');
                }
                    $('#descrizione').val(response.description);
                    $('#stato').val(response.stato);

                    // Mostra la modale
                    $('#editSurveyModal').modal('show');
                    },
                    error: function() {
                    alert('Errore nel caricamento dati');
                    }
                });
                });


        // 3) Submit del form modale per salvare modifiche
        $('#editSurveyForm').on('submit', function(e){
            e.preventDefault();

            var id = $('#survey-id').val();
            var formData = $(this).serialize(); // include _method=PUT e csrf

            $.ajax({
                url: '/surveys/' + id,
                type: 'POST', // se stai usando PUT, allora usi method spoofing
                data: formData,
                success: function(response) {
                    if (response.success) {
                        // Chiudi la modale
                        $('#editSurveyModal').modal('hide');
                        // Ricarica la tabella
                        table.ajax.reload(null, false);
                    } else {
                        alert('Errore in aggiornamento');
                    }
                },
                error: function(xhr) {
                    alert('Errore di rete o validazione');
                }
            });
        });
    });
    </script>




@endsection
