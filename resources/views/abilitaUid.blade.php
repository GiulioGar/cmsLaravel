@extends('layouts.main')

@push('styles')
<style>
    .card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        margin-bottom: 20px;
    }

    .card-header {
        background: linear-gradient(90deg, #007bff, #00bfff);
        color: #fff;
        font-weight: 600;
        font-size: 16px;
        border-top-left-radius: 10px;
        border-top-right-radius: 10px;
    }

    .btn-add {
        background: none;
        color: #007bff;
        border: none;
        font-size: 20px;
        margin-left: 6px;
        cursor: pointer;
        transition: 0.2s;
    }

    .btn-add:hover {
        transform: scale(1.1);
        color: #0056b3;
    }

    textarea {
        resize: none;
        font-family: monospace;
        background-color: #f8f9fa;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    <div class="row">
        <!-- COLONNA SINISTRA 60% -->
        <div class="col-lg-7 col-md-8">

            {{-- CARD 1: GENERATORE --}}
            <div class="card">
                <div class="card-header">Generatore Links - Abilita UID</div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    <form action="{{ route('abilita.uid.genera') }}" method="POST" class="row g-3">
                        @csrf

                        <div class="col-md-6">
                            <label class="form-label">SID</label>
                            <select name="sid" id="sid" class="form-select" onchange="updatePrj(this.value)" required>
                                <option value="">-- Seleziona SID --</option>
                                @foreach($surveys as $s)
                                    <option value="{{ $s->sid }}">{{ $s->sid }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">PRJ</label>
                            <input type="text" name="prj" id="prj" class="form-control" readonly required>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label">Panel
                                <button type="button" class="btn-add" title="Gestisci Panel" data-bs-toggle="modal" data-bs-target="#panelModal">➕</button>
                            </label>
                            <select name="panel_code" class="form-select" required>
                                <option value="">-- Seleziona Panel --</option>
                                @foreach($panels as $p)
                                    <option value="{{ $p->panel_code }}">{{ $p->panel_code }} - {{ $p->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Numero di Link</label>
                            <input type="number" name="num_links" min="1" max="10000" class="form-control" required>
                        </div>

                        <div class="col-12 text-end mt-2">
                            <button type="submit" class="btn btn-primary">Genera Links</button>
                        </div>
                    </form>
                </div>
            </div>

{{-- CARD 2: LINKS GENERATI --}}
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Links Generati</span>
        @if(session('links'))
        <div>
            <button type="button" class="btn btn-sm btn-outline-primary me-2" onclick="copyLinks()">
                <i class="fa fa-copy"></i> Copia Tutti
            </button>
            <button type="button" class="btn btn-sm btn-outline-success" onclick="exportCSV()">
                <i class="fa fa-file-csv"></i> Esporta CSV
            </button>
        </div>
        @endif
    </div>

    <div class="card-body">
        @if(session('links'))
            <textarea id="generatedLinks" class="form-control" rows="10" readonly>@foreach(session('links') as $l){{ $l['link'] }}&#10;@endforeach</textarea>
        @else
            <p class="text-muted m-0">Nessun link generato ancora.</p>
        @endif
    </div>
</div>

        </div>

        <!-- COLONNA DESTRA (vuota per ora) -->
        <div class="col-lg-5 col-md-4"></div>
    </div>
</div>

<!-- === MODALE PANEL (Bootstrap 5) === -->
<div class="modal fade" id="panelModal" tabindex="-1" aria-labelledby="panelModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content border-0 shadow-lg rounded-3">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="panelModalLabel">Gestione Panel</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Chiudi"></button>
      </div>
      <div class="modal-body">

        <form id="formAddPanel" class="row g-3 mb-4">
            @csrf
            <div class="col-md-3">
                <label class="form-label">Codice Panel</label>
                <input type="number" name="panel_code" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Nome</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Complete</label>
                <input type="number" name="complete" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label">Spesa (€)</label>
                <input type="number" step="0.01" name="spesa" class="form-control">
            </div>
            <div class="col-md-4">
                <label class="form-label">Red_3</label>
                <input type="url" name="red_3" class="form-control">
            </div>
            <div class="col-md-4">
                <label class="form-label">Red_4</label>
                <input type="url" name="red_4" class="form-control">
            </div>
            <div class="col-md-4">
                <label class="form-label">Red_5</label>
                <input type="url" name="red_5" class="form-control">
            </div>
            <div class="col-12 text-end mt-3">
                <button type="submit" class="btn btn-success">Aggiungi Panel</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead class="table-primary">
                    <tr>
                        <th>ID</th>
                        <th>Panel Code</th>
                        <th>Nome</th>
                        <th>Red_3</th>
                        <th>Red_4</th>
                        <th>Red_5</th>
                        <th>Complete</th>
                        <th>Spesa</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($panels as $p)
                    <tr id="panelRow{{ $p->id }}">
                        <td>{{ $p->id }}</td>
                        <td>{{ $p->panel_code }}</td>
                        <td>{{ $p->name }}</td>
                        <td>{{ $p->red_3 }}</td>
                        <td>{{ $p->red_4 }}</td>
                        <td>{{ $p->red_5 }}</td>
                        <td>{{ $p->complete }}</td>
                        <td>{{ $p->spesa }}</td>
                        <td>
                            <button onclick="deletePanel({{ $p->id }})" class="btn btn-sm btn-outline-danger" title="Elimina">
                                <i class="fa fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

      </div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
const surveyData = @json($surveys);

function updatePrj(selectedSid) {
    const s = surveyData.find(item => item.sid === selectedSid);
    document.getElementById('prj').value = s ? s.prj_name : '';
}

// === AJAX INSERIMENTO PANEL ===
document.getElementById('formAddPanel').addEventListener('submit', function(e) {
    e.preventDefault();
    fetch('{{ url("/panel/store") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify(Object.fromEntries(new FormData(this)))
    }).then(res => res.json()).then(data => {
        if (data.success) {
            Swal.fire({ icon: 'success', title: 'Panel aggiunto!', timer: 1500, showConfirmButton: false });
            setTimeout(() => location.reload(), 1600);
        } else {
            Swal.fire({ icon: 'error', title: 'Errore durante il salvataggio' });
        }
    });
});

// === ELIMINA PANEL ===
function deletePanel(id) {
    Swal.fire({
        title: 'Sei sicuro?',
        text: "Questa azione eliminerà il panel!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sì, elimina',
        cancelButtonText: 'Annulla',
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('{{ url("/panel/delete") }}/' + id, {
                method: 'DELETE',
                headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'}
            }).then(res => res.json()).then(data => {
                if (data.success) {
                    document.getElementById('panelRow' + id).remove();
                    Swal.fire('Eliminato!', 'Il panel è stato rimosso.', 'success');
                }
            });
        }
    });
}


// === COPIA LINK GENERATI ===
function copyLinks() {
    const textarea = document.getElementById('generatedLinks');
    textarea.select();
    textarea.setSelectionRange(0, 99999); // per compatibilità mobile
    document.execCommand('copy');
    Swal.fire({
        icon: 'success',
        title: 'Copiati!',
        text: 'Tutti i link sono stati copiati negli appunti.',
        timer: 1500,
        showConfirmButton: false
    });
}

// === ESPORTA CSV ===
function exportCSV() {
    const textarea = document.getElementById('generatedLinks');
    const links = textarea.value.trim().split('\n').filter(l => l !== '');
    if (links.length === 0) {
        Swal.fire({ icon: 'info', title: 'Nessun link da esportare' });
        return;
    }

    // estrai parametri per il nome file
    const sid = new URLSearchParams(new URL(links[0]).search).get('sid') || 'SID';
    const pan = new URLSearchParams(new URL(links[0]).search).get('pan') || 'PANEL';
    const filename = `links_${pan}_${sid}.csv`;

    // costruisci CSV
    let csvContent = "Url;Code\n";
    links.forEach(url => {
        const code = new URLSearchParams(new URL(url).search).get('uid') || '';
        csvContent += `${url};${code}\n`;
    });

    // crea blob e scarica
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = filename;
    link.click();
}



</script>
@endsection
