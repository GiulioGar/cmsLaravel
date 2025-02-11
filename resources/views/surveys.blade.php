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

    <script type="text/javascript">
      $(document).ready(function() {
        $('#surveys-table').DataTable({
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

                // Facoltativo: traduzione in italiano
                language: {
    url: "https://cdn.datatables.net/plug-ins/1.13.4/i18n/it-IT.json"
}
            });
        });
    </script>
@endsection
