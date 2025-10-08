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
        font-size: 15px;
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

<!-- COLONNA SINISTRA (principale) -->
<div class="col-lg-7 col-md-8">

    {{-- CARD 1: GENERATORE --}}
    <div class="card mb-3 shadow-sm">
        <div class="card-header bg-primary text-white">
            <i class="fa fa-link"></i> Generatore Links - Abilita UID
        </div>

        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <form action="{{ route('abilita.uid.genera') }}" method="POST" class="row g-3">
                @csrf

                <div class="col-md-6">
                    <label class="form-label">SID</label>
                    <select name="sid" id="sid" class="form-select" onchange="updatePrjAndGuestLink(this.value)" required>
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

                <div class="col-12">
                    <label class="form-label text-muted mb-1">Link GUEST</label>
                    <div class="input-group">
                        <input type="text" id="guestLink" class="form-control form-control-sm" readonly placeholder="Seleziona un SID per generare il link...">
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="copyGuestLink()">
                            <i class="fa fa-copy"></i>
                        </button>
                    </div>
                </div>

                <div class="col-md-8">
                    <label class="form-label">Panel
                        <button type="button" class="btn-add" title="Gestisci Panel" data-bs-toggle="modal" data-bs-target="#panelModal">⟳</button>
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

                <div class="col-12 text-end mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-bolt"></i> Genera Links
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- CARD 2: LINKS GENERATI --}}
    <div class="card shadow-sm">
        <div class="card-header bg-secondary text-white">
            <i class="fa fa-list"></i> Links Generati
        </div>
        <div class="card-body">
            @if(session('links'))
                <div class="d-flex justify-content-end mb-2">
                    <button type="button" class="btn btn-sm btn-outline-primary me-2" onclick="copyLinks()">
                        <i class="fa fa-copy"></i> Copia Tutti
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-success" onclick="exportCSV()">
                        <i class="fa fa-file-csv"></i> Esporta CSV
                    </button>
                </div>
                <textarea id="generatedLinks" class="form-control" rows="10" readonly>@foreach(session('links') as $l){{ $l['link'] }}&#10;@endforeach</textarea>
            @else
                <p class="text-muted m-0">Nessun link generato ancora.</p>
            @endif
        </div>
    </div>

</div>

<!-- COLONNA DESTRA -->
<div class="col-lg-5 col-md-4">

    {{-- CARD 1: SELEZIONE SID + PRJ --}}
    <div class="card mb-3">
        <div class="card-header bg-primary text-white">Gestione UID / IID</div>
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-7">
                    <label class="form-label">SID</label>
                    <select id="sidRight" class="form-select" onchange="updatePrjRight(this.value)">
                        <option value="">-- Seleziona SID --</option>
                        @foreach($surveys as $s)
                            <option value="{{ $s->sid }}">{{ $s->sid }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label">PRJ</label>
                    <input type="text" id="prjRight" class="form-control" readonly>
                </div>
                <div class="col-12 text-end">
                    <button class="btn btn-outline-secondary" onclick="refreshResults()">
                        <i class="fa fa-sync-alt"></i> Aggiorna Dati
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- CARD 2: INSERIMENTO UID/IID --}}
    <div class="card mb-3">
        <div class="card-header bg-info text-white">UID/IID</div>
        <div class="card-body">
            <label class="form-label">Inserisci UID o IID (uno per riga)</label>
            <textarea id="uidInput" class="form-control" rows="6" placeholder="Esempio:&#10;IDEXCINABC1234&#10;IDEXDYNXYZ5678&#10;o IID numerici"></textarea>
            <div class="mt-3 text-end">
                <button class="btn btn-success me-2" onclick="enableUids()">
                    <i class="fa fa-check"></i> Abilita UID
                </button>
                <button class="btn btn-danger" onclick="resetIids()">
                    <i class="fa fa-trash"></i> Elimina File + Reset IID
                </button>
            </div>
        </div>
    </div>

    {{-- CARD 3: FINESTRA RISULTATI --}}
    <div class="card">
        <div class="card-header bg-secondary text-white">Risultati</div>
        <div class="card-body" id="resultsBox">
            <p class="text-muted">Seleziona una ricerca e premi <b>Aggiorna Dati</b> per visualizzare le informazioni.</p>
            <hr>
            <div id="resultsContent" style="display:none;">
                <p><strong>Totale file .sre:</strong> <span id="totalFiles">0</span></p>
                <p><strong>Ultimo file:</strong> <span id="lastFile">—</span></p>
                <hr>
                <h6>Utenti per Status:</h6>
                <table class="table table-sm table-bordered text-center align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Status</th>
                            <th>Conteggio</th>
                        </tr>
                    </thead>
                    <tbody id="statusTable">
                        <tr><td>0</td><td>—</td></tr>
                        <tr><td>1</td><td>—</td></tr>
                        <tr><td>2</td><td>—</td></tr>
                        <tr><td>3</td><td>—</td></tr>
                        <tr><td>4</td><td>—</td></tr>
                        <tr><td>5</td><td>—</td></tr>
                        <tr><td>6</td><td>—</td></tr>
                        <tr><td>7</td><td>—</td></tr>
                    </tbody>
                </table>
                        <h6>Ultime modifiche</h6>
        <ul id="lastActions" class="list-group small">
            <li class="list-group-item text-muted">Nessuna operazione recente</li>
        </ul>
            </div>
        </div>
        <hr>


    </div>

</div>




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

// === AGGIORNA PRJ E LINK GUEST ===
function updatePrjAndGuestLink(selectedSid) {
    const s = surveyData.find(item => item.sid === selectedSid);
    const prjField = document.getElementById('prj');
    const guestInput = document.getElementById('guestLink');

    prjField.value = s ? s.prj_name : '';
    if (selectedSid && s) {
        guestInput.value = `https://www.primisoft.com/primis/run.do?sid=${selectedSid}&prj=${s.prj_name}&uid=GUEST`;
    } else {
        guestInput.value = '';
    }
}

// === COPIA LINK GUEST ===
function copyGuestLink() {
    const guestInput = document.getElementById('guestLink');
    if (!guestInput.value) {
        Swal.fire({ icon: 'info', title: 'Nessun link GUEST disponibile' });
        return;
    }
    guestInput.select();
    guestInput.setSelectionRange(0, 99999);
    document.execCommand('copy');
    Swal.fire({
        icon: 'success',
        title: 'Copiato!',
        text: 'Il link GUEST è stato copiato negli appunti.',
        timer: 1500,
        showConfirmButton: false
    });
}



// === COLONNA DESTRA: AGGIORNA PRJ ===

// --- Popola PRJ automaticamente a destra ---
const surveyDataRight = @json($surveys);
function updatePrjRight(selectedSid) {
    const s = surveyDataRight.find(item => item.sid === selectedSid);
    document.getElementById('prjRight').value = s ? s.prj_name : '';
}


// --- Placeholder funzioni (STEP 2 le implementeremo) ---
// === AGGIORNA FINESTRA RISULTATI ===
function refreshResults() {
    const sid = document.getElementById('sidRight').value;
    const prj = document.getElementById('prjRight').value;
    if (!sid || !prj) {
        Swal.fire({ icon: 'warning', title: 'Attenzione', text: 'Seleziona prima SID e PRJ.' });
        return;
    }

    fetch('{{ url("/abilita-uid/show-data") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ sid, prj })
    })
    .then(res => res.json())
    .then(data => {
        if (!data.success) {
            Swal.fire({ icon: 'error', title: 'Errore', text: data.message });
            return;
        }

        document.getElementById('resultsContent').style.display = 'block';
        document.getElementById('totalFiles').innerText = data.totalFiles;
        document.getElementById('lastFile').innerText = data.lastFile;

        // Reset tabella
        const statusTable = document.getElementById('statusTable');
        statusTable.innerHTML = '';
        for (let i = 0; i <= 7; i++) {
            const count = data.statusCounts[i] || 0;
            statusTable.innerHTML += `<tr><td>${i}</td><td>${count}</td></tr>`;
        }
    })
    .catch(() => Swal.fire({ icon: 'error', title: 'Errore di rete' }));
}

// === ABILITA UID (con log dinamico) ===
function enableUids() {
    const sid = document.getElementById('sidRight').value;
    const prj = document.getElementById('prjRight').value;
    const uids = document.getElementById('uidInput').value.trim();

    if (!sid || !prj || !uids) {
        Swal.fire({ icon: 'warning', title: 'Attenzione', text: 'Seleziona SID/PRJ e inserisci almeno un UID.' });
        return;
    }

    fetch('{{ url("/abilita-uid/enable-uids") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ sid, prj, uids })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            Swal.fire({ icon: 'success', title: 'UID abilitati', text: `${data.count} UID inseriti.` });
            updateLog(data.actions);
            refreshResults();
        } else {
            Swal.fire({ icon: 'error', title: 'Errore', text: data.message });
        }
    })
    .catch(() => Swal.fire({ icon: 'error', title: 'Errore di rete' }));
}

// === RESET IID (con log dinamico e gestione errori) ===
function resetIids() {
    const sid = document.getElementById('sidRight').value;
    const prj = document.getElementById('prjRight').value;
    const iids = document.getElementById('uidInput').value.trim();

    if (!sid || !prj || !iids) {
        Swal.fire({ icon: 'warning', title: 'Attenzione', text: 'Seleziona SID/PRJ e inserisci almeno un IID.' });
        return;
    }

    Swal.fire({
        icon: 'warning',
        title: 'Conferma azione',
        text: 'Vuoi davvero eliminare i file .sre e resettare questi IID?',
        showCancelButton: true,
        confirmButtonText: 'Sì, procedi',
        cancelButtonText: 'Annulla'
    }).then(result => {
        if (!result.isConfirmed) return;

        fetch('{{ url("/abilita-uid/reset-iids") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ sid, prj, iids })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Reset completato',
                    html: `Aggiornati <b>${data.updated}</b> record<br>Cancellati <b>${data.deleted}</b> file`
                });
                updateLog(data.actions);
                refreshResults();
            } else {
                Swal.fire({ icon: 'error', title: 'Errore', text: data.message });
            }
        })
        .catch(() => Swal.fire({ icon: 'error', title: 'Errore di rete' }));
    });
}

// === AGGIORNA LOG DINAMICO ===
function updateLog(actions) {
    const list = document.getElementById('lastActions');
    if (!actions || actions.length === 0) {
        list.innerHTML = '<li class="list-group-item text-muted">Nessuna operazione recente</li>';
        return;
    }

    list.innerHTML = '';
    actions.slice().reverse().forEach(a => {
        list.innerHTML += `<li class="list-group-item list-group-item-light">${a}</li>`;
    });
}





</script>
@endsection
