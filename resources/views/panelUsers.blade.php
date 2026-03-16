@extends('layouts.main')

@section('content')

<link rel="stylesheet" href="{{ asset('css/panelUsers.css') }}">

<main class="content">
    <div class="container-fluid">

        <div class="row">
            {{-- COLONNA SINISTRA --}}
            <div class="col-lg-7">
                <div class="card panel-users-card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="mb-0">Utenti Panel</h4>
                        <small class="text-muted">Consultazione utenti, inviti, attività e partecipazione</small>
                    </div>
                </div>

                    <div class="card-body">
                        <table id="panel-users-table" class="table table-sm table-striped align-middle w-100">
                            <thead>
                                <tr>
                                    <th>UID</th>
                                    <th>Email</th>
                                    <th>Età</th>
                                    <th>Inviti</th>
                                    <th>Attività</th>
                                    <th>%</th>
                                    <th>Iscrizione</th>
                                    <th>Ultima Azione</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>

            {{-- COLONNA DESTRA --}}
            <div class="col-lg-5">
                <div class="card panel-users-card">
                    <div class="card-body text-center text-muted py-5">
                        Area funzioni future
                    </div>
                </div>
            </div>
        </div>

    </div>
</main>

@endsection

@section('scripts')
<script>
$(document).ready(function() {

$('#panel-users-table').DataTable({
    processing: true,
    serverSide: true,
    ajax: '{{ route("panelUsers.data") }}',
    pageLength: 100,
    lengthMenu: [25, 50, 100, 200],
    scrollX: false,
    autoWidth: false,
    order: [[7, 'desc']],
    columnDefs: [
        { targets: 0, width: '110px' },
        { targets: 1, width: '220px' },
        { targets: 2, width: '70px' },
        { targets: 3, width: '80px' },
        { targets: 4, width: '80px' },
        { targets: 5, width: '80px' },
        { targets: 6, width: '100px' },
        { targets: 7, width: '140px' }
    ],
    columns: [
        { data: 'user_id',         name: 'user_id' },
        { data: 'email',           name: 'email' },
        { data: 'birth_date',      name: 'birth_date' },
        { data: 'invites',         name: 'invites' },
        { data: 'activity_count',  name: 'activity_count' },
        { data: 'partecipazione',  name: 'partecipazione', orderable: false, searchable: false },
        { data: 'reg_date',        name: 'reg_date' },
        { data: 'last_event_date', name: 'last_event_date' }
    ],
    language: {
        url: "https://cdn.datatables.net/plug-ins/1.13.4/i18n/it-IT.json",
        search: "Cerca utente:",
        searchPlaceholder: "UID o email..."
    }
});

});
</script>
@endsection
