@extends('layouts.main')

@section('content')
<div class="container-fluid mt-4">
    <div class="row">
        {{-- Colonna sinistra --}}
        <div class="col-md-8">
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Gestione Utenti Panel</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"></h5>
    <button id="refreshCache" class="btn btn-sm btn-primary">
        🔄 Aggiorna Attività
    </button>
</div>
                    <table id="usersTable" class="table table-striped table-bordered w-100">
                        <thead class="table-dark">
                            <tr>
                                <th>UID</th>
                                <th>Email</th>
                                <th>Età</th>
                                <th>Inviti</th>
                                <th>Attività</th>
                                <th>Partecipazione %</th>
                                <th>Anni Iscrizione</th>
                                <th>Ultima Attività</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>

        {{-- Colonna destra --}}
        <div class="col-md-4">
            <div class="card shadow mb-4">
                <div class="card-header bg-secondary text-white">
                    <h6 class="mb-0">Dettagli Utente</h6>
                </div>
                <div class="card-body">
                    <p>In costruzione...</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection


@section('scripts')
<script>

$('#refreshCache').on('click', function() {
    if (confirm('Vuoi aggiornare i dati di attività? Questa operazione può richiedere alcuni secondi.')) {
        $(this).prop('disabled', true).text('⏳ Aggiornamento in corso...');
        $.post('{{ route("panel.users.refresh") }}', {_token: '{{ csrf_token() }}'}, function(resp) {
            if (resp.success) {
                alert('✅ ' + resp.message);
                $('#usersTable').DataTable().ajax.reload();
            } else {
                alert('❌ Errore: ' + resp.message);
            }
        }).always(() => {
            $('#refreshCache').prop('disabled', false).text('🔄 Aggiorna Attività');
        });
    }
});


$(document).ready(function () {
    let table = $('#usersTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("panel.users.data") }}',
            type: 'GET',
            dataSrc: function (json) {
                console.log('✅ JSON ricevuto', json);
                return json.data;
            }
        },
columns: [
    { data: 'user_id', title: 'UID' },
    {
        data: 'email',
        title: 'Email',
        render: function (data, type, row) {
            if (!row.email_valida) {
                return `<span style="color:red;font-weight:bold;">${data}</span>`;
            }
            return data;
        }
    },
    { data: 'eta', title: 'Età', className: 'text-center', defaultContent: '-' },
    {
    data: 'anzianita',
    title: 'Anni Iscrizione',
    className: 'text-center',
    render: function(data) {
        if (!data) return '-';
        let color = '#6c757d'; // default
        if (data.includes('0-3')) color = '#00bcd4';
        else if (data.includes('3-6')) color = '#4caf50';
        else if (data.includes('6-11')) color = '#8bc34a';
        else if (data.includes('1 anno')) color = '#cddc39';
        else if (data.includes('2 anni')) color = '#ffc107';
        else if (data.includes('3 anni')) color = '#ff9800';
        else if (data.includes('4-5')) color = '#ff5722';
        else if (data.includes('6-9')) color = '#9c27b0';
        else if (data.includes('10')) color = '#f44336';
        return `<span class="badge" style="background:${color};">${data}</span>`;
    }
},
{ data: 'ultima_attivita', title: 'Ultima Attività', className: 'text-center', defaultContent: '-' },
    { data: 'inviti', title: 'Inviti', className: 'text-center' },
    { data: 'attivita', title: 'Attività', className: 'text-center' },
    { data: 'partecipazione', title: 'Partecipazione %', className: 'text-center' }
],

order: [[5, 'desc']],
columnDefs: [
    { orderable: false, targets: [0,1,2,3,4,5,6] },
],


        pageLength: 25,
        responsive: true,
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/it-IT.json'
        }
    });
});
</script>
@endsection

