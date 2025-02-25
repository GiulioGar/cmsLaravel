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

.link-sur-id:hover {
    text-decoration: underline;
    color: #007bff; /* ad es. blu */
}

</style>

<main class="content">

    <div class="container">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h1 class="mb-0">Elenco Ricerche</h1>
            <button class="btn btn-success" id="btnOpenCreateModal">Nuovo Progetto</button>
        </div>
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
              <select name="panelMod" required class="custom-select" id="panelMod">
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


<!-- /modale Nuova ricerca -->


<div class="modal fade" id="createSurveyModal" tabindex="-1" aria-labelledby="createSurveyModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="createSurveyForm">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title" id="createSurveyModalLabel">Crea Nuovo Progetto</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
        </div>
        <div class="modal-body">


            <div class="row mb-3">
                <div class="col-6">
                  <div class="input-group">
                    <div class="input-group-prepend">
                      <span class="input-group-text">Codice SID Progetto:</span>
                    </div>
                    <select required class="custom-select" name="sid" id="sid">
                      <!-- popolato via AJAX -->
                    </select>
                  </div>
                </div>
                <div class="col-6">
                  <div class="input-group">
                    <div class="input-group-prepend">
                      <span class="input-group-text">Codice PRJ Progetto:</span>
                    </div>
                    <input type="text" class="form-control" name="prj" id="prj" readonly>
                  </div>
                </div>
              </div>




          <!-- 3) Cliente -->
          <div class="form-group mb-3">
            <label>Cliente:</label>
            <div id="clienteFieldWrapper">
                <!-- Qui inseriremo l'HTML (select o input) via JS -->
            </div>
          </div>

          <div class="row">
            <!-- Colonna per Tipologia -->
            <div class="col-3">
                <div class="input-group mb-3">
                    <div class="input-group-prepend">
                        <label class="input-group-text" for="tipologia">Tipologia:</label>
                    </div>
                    <select name="tipologia" required class="custom-select" id="tipologia">
                        <option value="CAWI">CAWI</option>
                        <option value="CATI">CATI</option>
                        <option value="CAPI">CAPI</option>
                    </select>
                </div>
            </div>

            <!-- Colonna per Panel -->
            <div class="col-3">
                <div class="input-group mb-3">
                    <div class="input-group-prepend">
                        <label class="input-group-text" for="panel">Panel:</label>
                    </div>
                    <select name="panel" required class="custom-select" id="panel">
                        <option value="1">Millebytes</option>
                        <option value="0">Esterno</option>
                        <option value="2">Target</option>
                    </select>
                </div>
            </div>
        </div>


          <!-- 6) IR, Durata (loi), Punti (point), Argomento -->
          <div id="infoRicerca">
            <div class="form-row input-group mb-3">
              <div class="input-group-prepend">
                <span class="input-group-text">IR:</span>
              </div>
              <input  type="number" class="form-control" id="ir" name="ir">

              <div class="input-group-prepend">
                <span class="input-group-text">Durata:</span>
              </div>
              <input  type="number" class="form-control" id="loi" name="loi">

              <div class="input-group-prepend">
                <span class="input-group-text">Punti:</span>
              </div>
              <input type="number" class="form-control" name="point" id="point" placeholder="Infinity">
            </div>

            <div class="input-group mb-3">
              <div class="input-group-prepend">
                <span class="input-group-text">Argomento:</span>
              </div>
              <input id="argomento" type="text" class="form-control" name="argomento">
            </div>
          </div>

          <!-- 7) Genere / Età (sex_target, age1_target, age2_target) -->
          <div id="genderAge">
           <div class="input-group mb-3">
            <div class="input-group-prepend">
              <label class="input-group-text" for="sex_target">Genere:</label>
            </div>
            <select required name="sex_target" class="custom-select" id="sex_target">
              <option value="1">Uomo</option>
              <option value="2">Donna</option>
              <option value="3">Uomo/Donna</option>
            </select>
          </div>

          <div class="form-row input-group mb-3">
            <div class="input-group-prepend">
              <span class="input-group-text">Età:</span>
            </div>
            <input name="age1_target" type="number" class="form-control" placeholder="18">
            <input name="age2_target" type="number" class="form-control" placeholder="65">
          </div>

          </div>
          <!-- 8) Interviste / Chiusura Field / Descrizione / Nazione -->
          <div class="input-group mb-3">
            <div class="input-group-prepend">
              <span class="input-group-text">Interviste:</span>
            </div>
            <input required type="number" class="form-control" name="goal" placeholder="0">
          </div>

          <div class="input-group mb-3">
            <div class="input-group-prepend">
              <span class="input-group-text">Chiusura Field:</span>
            </div>
            <input type="date" class="form-control" name="end_date">
          </div>

          <div class="input-group mb-3">
            <div class="input-group-prepend">
              <span class="input-group-text">Descrizione:</span>
            </div>
            <input required type="text" class="form-control" name="descrizione" placeholder="Inserire descrizione">
          </div>

          <div class="input-group mb-3">
            <div class="input-group-prepend">
              <label class="input-group-text" for="paese">Nazione:</label>
            </div>
            <select required name="paese" class="custom-select" id="paese">
                <option value="Italia">Italia</option>
                <option value="Albania">Albania</option>
                <option value="Algeria">Algeria</option>
                <option value="Arabia Saudita">Arabia Saudita</option>
                <option value="Argentina">Argentina</option>
                <option value="Australia">Australia</option>
                <option value="Austria">Austria</option>
                <option value="Belgio">Belgio</option>
                <option value="Bielorussia">Bielorussia</option>
                <option value="Bolivia">Bolivia</option>
                <option value="Bosnia ed Erzegovina">Bosnia ed Erzegovina</option>
                <option value="Brasile">Brasile</option>
                <option value="Bulgaria">Bulgaria</option>
                <option value="Cambogia">Cambogia</option>
                <option value="Camerun">Camerun</option>
                <option value="Canada">Canada</option>
                <option value="Ciad">Ciad</option>
                <option value="Cile">Cile</option>
                <option value="Cina">Cina</option>
                <option value="Cipro">Cipro</option>
                <option value="Colombia">Colombia</option>
                <option value="Corea del Nord">Corea del Nord</option>
                <option value="Corea del Sud">Corea del Sud</option>
                <option value="Costa d'Avorio">Costa d'Avorio</option>
                <option value="Costa Rica">Costa Rica</option>
                <option value="Croazia">Croazia</option>
                <option value="Cuba">Cuba</option>
                <option value="Danimarca">Danimarca</option>
                <option value="Ecuador">Ecuador</option>
                <option value="Egitto">Egitto</option>
                <option value="El Salvador">El Salvador</option>
                <option value="Emirati Arabi Uniti">Emirati Arabi Uniti</option>
                <option value="Eritrea">Eritrea</option>
                <option value="Estonia">Estonia</option>
                <option value="Etiopia">Etiopia</option>
                <option value="Filippine">Filippine</option>
                <option value="Finlandia">Finlandia</option>
                <option value="Francia">Francia</option>
                <option value="Gabon">Gabon</option>
                <option value="Gambia">Gambia</option>
                <option value="Georgia">Georgia</option>
                <option value="Germania">Germania</option>
                <option value="Ghana">Ghana</option>
                <option value="Giappone">Giappone</option>
                <option value="Giordania">Giordania</option>
                <option value="Grecia">Grecia</option>
                <option value="Guinea">Guinea</option>
                <option value="Honduras">Honduras</option>
                <option value="India">India</option>
                <option value="Indonesia">Indonesia</option>
                <option value="Iran">Iran</option>
                <option value="Iraq">Iraq</option>
                <option value="Irlanda">Irlanda</option>
                <option value="Islanda">Islanda</option>
                <option value="Israele">Israele</option>
                <option value="Jamaica">Jamaica</option>
                <option value="Kazakistan">Kazakistan</option>
                <option value="Kenya">Kenya</option>
                <option value="Kuwait">Kuwait</option>
                <option value="Laos">Laos</option>
                <option value="Lettonia">Lettonia</option>
                <option value="Libano">Libano</option>
                <option value="Libia">Libia</option>
                <option value="Lussemburgo">Lussemburgo</option>
                <option value="Madagascar">Madagascar</option>
                <option value="Malaysia">Malaysia</option>
                <option value="Mali">Mali</option>
                <option value="Malta">Malta</option>
                <option value="Marocco">Marocco</option>
                <option value="Messico">Messico</option>
                <option value="Mozambico">Mozambico</option>
                <option value="Nepal">Nepal</option>
                <option value="Norvegia">Norvegia</option>
                <option value="Nuova Zelanda">Nuova Zelanda</option>
                <option value="Olanda">Olanda</option>
                <option value="Pakistan">Pakistan</option>
                <option value="Panama">Panama</option>
                <option value="Paraguay">Paraguay</option>
                <option value="Perù">Perù</option>
                <option value="Polonia">Polonia</option>
                <option value="Portogallo">Portogallo</option>
                <option value="Regno Unito">Regno Unito</option>
                <option value="Repubblica Ceca">Repubblica Ceca</option>
                <option value="Romania">Romania</option>
                <option value="Russia">Russia</option>
                <option value="Senegal">Senegal</option>
                <option value="Serbia">Serbia</option>
                <option value="Singapore">Singapore</option>
                <option value="Slovenia">Slovenia</option>
                <option value="Spagna">Spagna</option>
                <option value="Stati Uniti">Stati Uniti</option>
                <option value="Sudafrica">Sudafrica</option>
                <option value="Svezia">Svezia</option>
                <option value="Svizzera">Svizzera</option>
                <option value="Thailandia">Thailandia</option>
                <option value="Turchia">Turchia</option>
                <option value="Ucraina">Ucraina</option>
                <option value="Ungheria">Ungheria</option>
                <option value="Uruguay">Uruguay</option>
                <option value="Vietnam">Vietnam</option>
                <option value="Zambia">Zambia</option>
                <option value="Zimbabwe">Zimbabwe</option>
                <option value="Altro">Altro</option>
            </select>
          </div>

        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
          <button type="submit" class="btn btn-primary">Crea Progetto</button>
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
        div.dataTables_wrapper .dataTables_paginate .paginate_button {
            font-size: 0.7rem !important;
        }
        .dataTables_paginate {
            text-align: center !important;
        }
    </style>

<script>
$(document).ready(function() {

    /********************************************************
     * 1) Inizializziamo la tabella DataTables
     ********************************************************/
    var table = $('#surveys-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route('surveys.data') }}',
        pageLength: 30,
        lengthMenu: [30, 50, 100],
        pagingType: "full_numbers",
        scrollX: true,
        // order: [],
        // ordering: false,
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


    /********************************************************
     * 2) Form di Modifica (#editSurveyForm)
     ********************************************************/
    // Click su bottone "Modifica"
    $('#surveys-table').on('click', '.btn-edit', function() {
        var id = $(this).data('id');

        $.ajax({
            url: '/surveys/' + id + '/edit',
            type: 'GET',
            success: function(response) {
                // Riempi i campi
                $('#survey-id').val(response.id);
                $('#sur_id').val(response.sur_id);
                $('#panel').val(response.panel);
                $('#sex_target').val(response.sex_target);
                $('#age1_target').val(response.age1_target);
                $('#age2_target').val(response.age2_target);
                $('#complete').val(response.complete);

                if (response.end_field) {
                    let dateTimeParts = response.end_field.split(' ');
                    let dateOnly = dateTimeParts[0];
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

    // Submit del form di modifica
    $('#editSurveyForm').on('submit', function(e){
        e.preventDefault();

        var id = $('#survey-id').val();
        var formData = $(this).serialize();

        $.ajax({
            url: '/surveys/' + id,
            type: 'POST', // method spoofing _method=PUT
            data: formData,
            success: function(response) {
                if (response.success) {
                    $('#editSurveyModal').modal('hide');
                    // Ricarica la tabella
                    table.ajax.reload(function() {
                        table.page('first').draw('page');
                    }, false);

                } else {
                    alert('Errore in aggiornamento');
                }
            },
            error: function(xhr) {
                alert('Errore di rete o validazione');
            }
        });
    });


    /********************************************************
     * 3) Bottone "Nuovo Progetto"
     ********************************************************/
    $('#btnOpenCreateModal').on('click', function() {
        // Svuota i campi
        $('#createSurveyForm')[0].reset();

        $.ajax({
            url: '{{ route('surveys.available-sids') }}',
            method: 'GET',
            success: function(response) {
                $('#sid').empty();
                // Aggiungi option
                response.forEach(function(item) {
                    $('#sid').append('<option value="' + item.sid + '">' + item.sid + '</option>');
                });
                $('#createSurveyModal').modal('show');
            },
            error: function(){
                alert('Impossibile caricare i codici SID disponibili.');
            }
        });
    });


    /********************************************************
     * 4) Form di Creazione (#createSurveyForm)
     ********************************************************/
    $('#createSurveyForm').on('submit', function(e){
        e.preventDefault();
        var formData = $(this).serialize();

        $.ajax({
            url: '{{ route('surveys.store') }}',
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    $('#createSurveyModal').modal('hide');

                    // Ricarica la tabella
                    table.ajax.reload(function() {
                        table.page('first');
                        table.search('').draw(false);
                    }, false);

                } else {
                    alert('Errore nella creazione del progetto');
                }
            },
            error: function() {
                alert('Errore di rete o validazione');
            }
        });
    });


    /********************************************************
     * 5) Gestione "sid" -> "prj" -> "cliente"
     ********************************************************/
    $('#sid').on('change', function() {
        var selectedSid = $(this).val();
        if (!selectedSid) {
            $('#prj').val('');
            showClienteAsInput('');
            return;
        }

        // Recupera 'prj_name'
        $.ajax({
            url: '{{ route("surveys.prj-info") }}',
            type: 'GET',
            data: { sid: selectedSid },
            success: function(resp) {
                var prjValue = resp.prj_name || '';
                $('#prj').val(prjValue);

                if (!prjValue) {
                    showClienteAsInput('');
                } else {
                    $.ajax({
                        url: '{{ route("surveys.getClientByPrj") }}',
                        type: 'GET',
                        data: { prj: prjValue },
                        success: function(rsp) {
                            if (rsp.cliente) {
                                showClienteAsSelect(rsp.cliente);
                            } else {
                                showClienteAsInput('');
                            }
                        },
                        error: function() {
                            showClienteAsInput('');
                        }
                    });
                }
            },
            error: function() {
                $('#prj').val('');
                showClienteAsInput('');
            }
        });
    });

    function showClienteAsSelect(clienteVal) {
        var html = '<select name="cliente" id="cliente" class="form-control">'
                 + '<option value="'+ clienteVal +'" selected>'+ clienteVal +'</option>'
                 + '</select>';
        $('#clienteFieldWrapper').html(html);
    }

    function showClienteAsInput(clienteVal) {
        var html = '<input type="text" name="cliente" id="cliente" class="form-control" '
                 + ' placeholder="Inserisci cliente..." value="'+ clienteVal +'" />';
        $('#clienteFieldWrapper').html(html);
    }


    /********************************************************
     * 6) Mostra/Nascondi campi IR, LOI, etc. se panel=1
     ********************************************************/
    $(document).on('change', '#panel', function() {
        var panelVal = $(this).val();
        if (panelVal === '1') {
            $('#ir, #loi, #point, #argomento').prop('required', true);
            $('#infoRicerca').show();
            $('#genderAge').show();
        } else {
            $('#ir, #loi, #point, #argomento').prop('required', false);
            $('#infoRicerca').hide();
            $('#genderAge').hide();
        }
    });


    /********************************************************
     * 7) Calcolo punti
     ********************************************************/
    $(document).on('change', 'input', function() {
        let valIr  = $('input[name="ir"]').val();
        let valLoi = $('input[name="loi"]').val();

        const k     = 125.66;
        const alpha = 0.699;
        const beta  = 0.172;

        let points = k * Math.pow(valLoi, alpha) / Math.pow(valIr, beta);
        points = Math.round(points);

        $('input[name="point"]').attr("placeholder", points);
    });

});
</script>
@endsection

