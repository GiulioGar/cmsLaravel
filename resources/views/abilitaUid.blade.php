@extends('layouts.main')

<link rel="stylesheet" href="{{ asset('css/abilitaUid.css') }}">

@section('content')
<div class="container-fluid px-4 au-page">
  <div class="row g-4">

    <!-- COLONNA SINISTRA (principale) -->
    <div class="col-lg-7 col-md-8">

      {{-- CARD 1: GENERATORE --}}
      <div class="au-card">
        <div class="au-card-header">
          <div class="au-title">
            <i class="fa-solid fa-link"></i>
            <span>Generatore Links - Abilita UID</span>
          </div>
        </div>

        <div class="au-card-body">
            @if(!empty($successMessage))
            <div class="alert alert-success mb-3">{{ $successMessage }}</div>
            @endif

          <form action="{{ route('abilita.uid.genera') }}" method="POST" class="row g-3">
            @csrf

            <div class="col-md-6">
              <label class="form-label">SID</label>
              <select name="sid" id="sid" class="form-select" onchange="updatePrjAndGuestLink(this.value)" required>
                <option value="">-- Seleziona SID --</option>
                @foreach($surveys as $s)
                  <option value="{{ $s->sid }}">{{ $s->sid }} ({{ $s->prj_name }})</option>
                @endforeach
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label">PRJ</label>
              <input type="text" name="prj" id="prj" class="form-control" readonly required>
            </div>

            <div class="col-12 au-guest">
              <label class="form-label text-muted mb-1">Link GUEST</label>
              <div class="input-group">
                <input type="text" id="guestLink" class="form-control form-control-sm" readonly
                       placeholder="Seleziona un SID per generare il link...">
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="copyGuestLink()">
                  <i class="fa-regular fa-copy"></i>
                </button>
              </div>
            </div>

            <div class="col-md-8">
              <label class="form-label d-flex align-items-center gap-2">
                <span>Panel</span>
                <button type="button" class="au-btn-icon" title="Gestisci Panel"
                        data-bs-toggle="modal" data-bs-target="#panelModal">
                  <i class="fa-solid fa-rotate"></i>
                </button>
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
              <input type="number" name="num_links" min="1" max="100000" class="form-control" required>
            </div>

            <div class="col-12 text-end mt-2">
<button type="submit" id="btn-genera-links" class="btn btn-primary">
  <i class="fa-solid fa-bolt"></i> Genera Links
</button>
            </div>
          </form>
        </div>
      </div>

      {{-- CARD 2: LINKS GENERATI --}}
      <div class="au-card">
        <div class="au-card-header au-card-header--muted">
          <div class="au-title">
            <i class="fa-solid fa-list"></i>
            <span>Links Generati</span>
          </div>
        </div>

        <div class="au-card-body">
          @if(!empty($generatedLinks))
            <div class="au-toolbar">
              <button type="button" class="btn btn-sm btn-outline-primary" onclick="copyLinks()">
                <i class="fa-regular fa-copy"></i> Copia Tutti
              </button>
              <button type="button" class="btn btn-sm btn-outline-success" onclick="exportCSV()">
                <i class="fa-solid fa-file-csv"></i> Esporta CSV
              </button>
            </div>

            <textarea id="generatedLinks" class="form-control au-textarea" rows="10" readonly>@foreach($generatedLinks as $l){{ $l['link'] }}&#10;@endforeach</textarea>
          @else
            <p class="text-muted m-0">Nessun link generato ancora.</p>
          @endif
        </div>
      </div>

    </div>

    <!-- COLONNA DESTRA -->
    <div class="col-lg-5 col-md-4">

      {{-- CARD 1: SELEZIONE SID + PRJ --}}
      <div class="au-card">
        <div class="au-card-header">
          <div class="au-title">
            <i class="fa-solid fa-sliders"></i>
            <span>Gestione UID / IID</span>
          </div>
        </div>

        <div class="au-card-body">
          <div class="row g-3 align-items-end">
            <div class="col-md-7">
              <label class="form-label">SID</label>
              <select id="sidRight" class="form-select" onchange="updatePrjRight(this.value)">
                <option value="">-- Seleziona SID --</option>
                @foreach($surveys as $s)
                  <option value="{{ $s->sid }}">{{ $s->sid }} ({{ $s->prj_name }})</option>
                @endforeach
              </select>
            </div>

            <div class="col-md-5">
              <label class="form-label">PRJ</label>
              <input type="text" id="prjRight" class="form-control" readonly>
            </div>

            <div class="col-12 text-end">
              <button id="btn-refresh-results" class="btn btn-outline-secondary" onclick="refreshResults()">
                <i class="fa-solid fa-rotate"></i> Aggiorna Dati
              </button>
            </div>
          </div>
        </div>
      </div>

      {{-- CARD 2: INSERIMENTO UID/IID --}}
        <div class="au-card" id="uidIidCard" style="display:none;">
        <div class="au-card-header" style="background:linear-gradient(90deg,#0dcaf0,#0aa2c0);">
          <div class="au-title">
            <i class="fa-solid fa-key"></i>
            <span>UID / IID</span>
          </div>
        </div>

        <div class="au-card-body">
          <label class="form-label">Inserisci UID o IID (uno per riga)</label>
          <textarea id="uidInput" class="form-control au-textarea" rows="6"
                    placeholder="Esempio:&#10;IDEXCINABC1234&#10;IDEXDYNXYZ5678&#10;o IID numerici"></textarea>

          <div class="mt-3 d-flex justify-content-end gap-2 flex-wrap">
            <button id="btn-enable-uids" class="btn btn-success" onclick="enableUids()">
              <i class="fa-solid fa-check"></i> Abilita UID
            </button>
            <button id="btn-reset-iids" class="btn btn-danger" onclick="resetIids()" disabled>
            <i class="fa-solid fa-trash"></i> Elimina File + Reset IID
            </button>
          </div>
        </div>
      </div>

            {{-- CARD 3: RISULTATI --}}
      <div class="au-card" id="resultsCard" style="display:none;">
        <div class="au-card-header au-card-header--muted">
          <div class="au-title">
            <i class="fa-solid fa-chart-column"></i>
            <span>Risultati</span>
          </div>
        </div>

        <div class="au-card-body" id="resultsBox">

          {{-- Hint iniziale --}}
          <div id="resultsHint" class="au-results-hint">
            <div class="au-icon">
              <i class="fa-solid fa-circle-info"></i>
            </div>
            <div>
              <div class="au-h-title">Nessun dato caricato</div>
              <div class="au-h-sub">
                Seleziona <b>SID</b> e premi <b>Aggiorna Dati</b> per visualizzare file .sre e conteggi per status.
              </div>
            </div>
          </div>

          {{-- Loader (nascosto di default) --}}
          <div id="resultsLoading" class="au-loading" style="display:none;">
            <div class="au-dots" aria-hidden="true">
              <i></i><i></i><i></i>
            </div>
            <div>
              <div style="font-weight:800;margin-bottom:2px;">Caricamento dati…</div>
              <div style="color:rgba(0,0,0,.55);font-size:.92rem;">
                Sto leggendo filesystem e conteggi da t_respint.
              </div>
            </div>
          </div>

          <div id="resultsContent" style="display:none;">
            <div class="mb-3">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <h6 class="mb-0">Ricerca UID / IID</h6>
                <span class="text-muted small"><i class="fa-solid fa-magnifying-glass"></i></span>
              </div>

              <div class="input-group">
                <input
                  type="text"
                  id="searchUidIid"
                  class="form-control"
                  placeholder="Inserisci UID o IID specifico"
                  autocomplete="off"
                >
                <button type="button" id="btn-search-record" class="btn btn-outline-primary" onclick="searchUidIidRecord()">
                  <i class="fa-solid fa-search"></i> Cerca
                </button>
              </div>

              <div class="form-text">
                Se il valore è numerico cerco per IID, altrimenti per UID.
              </div>

              <div class="table-responsive mt-3">
                <table class="table table-sm table-bordered align-middle mb-0">
                  <thead class="table-light">
                    <tr>
                      <th class="text-start">IID</th>
                      <th class="text-start">UID</th>
                      <th class="text-start">Status</th>
                      <th class="text-start">PRJ</th>
                    </tr>
                  </thead>
                  <tbody id="searchResultsTable">
                    <tr>
                      <td colspan="4" class="text-center text-muted">Nessuna ricerca eseguita</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <hr class="my-3">

            <div class="row g-3">
              <div class="col-6">
                <div class="p-3 rounded-3 border" style="background:rgba(13,110,253,.06);">
                  <div class="small text-muted d-flex align-items-center gap-2">
                    <i class="fa-regular fa-file-lines"></i> Totale file .sre
                  </div>
                  <div class="h3 mb-0" id="totalFiles">0</div>
                </div>
              </div>
              <div class="col-6">
                <div class="p-3 rounded-3 border" style="background:rgba(25,135,84,.06);">
                  <div class="small text-muted d-flex align-items-center gap-2">
                    <i class="fa-solid fa-clock-rotate-left"></i> Ultimo file
                  </div>
                  <div class="fw-semibold" id="lastFile">—</div>
                </div>
              </div>
            </div>

            <hr class="my-3">

            <div class="d-flex align-items-center justify-content-between">
              <h6 class="mb-2">Utenti per Status</h6>
              <span class="text-muted small"><i class="fa-solid fa-table"></i></span>
            </div>

            <div class="table-responsive">
            <table class="table table-sm align-middle au-status-table mb-0">
            <thead>
                <tr>
                <th class="text-start">Status</th>
                <th class="text-end">Conteggio</th>
                </tr>
            </thead>

            <tbody id="statusTable">
                {{-- placeholder iniziale --}}
                <tr>
                <td class="text-start">
                    <span class="au-pill au-pill--muted">
                    <i class="fa-solid fa-minus"></i> —
                    </span>
                </td>
                <td class="text-end text-muted">—</td>
                </tr>
            </tbody>
            </table>
            </div>

            <hr class="my-3">

            <div class="d-flex align-items-center justify-content-between">
              <h6 class="mb-2">Dettaglio record</h6>
              <span class="text-muted small"><i class="fa-solid fa-table-list"></i></span>
            </div>

            <div class="table-responsive mb-3">
              <table class="table table-sm table-bordered align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th class="text-start">IID</th>
                    <th class="text-start">UID</th>
                    <th class="text-start">Status</th>
                  </tr>
                </thead>
                <tbody id="detailTable">
                  <tr>
                    <td colspan="3" class="text-center text-muted">Nessun dato disponibile</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <hr class="my-3">

            <div class="d-flex align-items-center justify-content-between">
              <h6 class="mb-2">Ultime modifiche</h6>
              <span class="text-muted small"><i class="fa-solid fa-list-check"></i></span>
            </div>

            <ul id="lastActions" class="list-group small au-actions">
              <li class="list-group-item text-muted">Nessuna operazione recente</li>
            </ul>

          </div>

        </div>
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

          <div class="col-12 text-end mt-2">
            <button type="submit" class="btn btn-success">
              <i class="fa-solid fa-plus"></i> Aggiungi Panel
            </button>
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
                <th class="text-end">Azioni</th>
              </tr>
            </thead>
            <tbody>
              @foreach($panels as $p)
                <tr id="panelRow{{ $p->id }}">
                  <td>{{ $p->id }}</td>
                  <td>{{ $p->panel_code }}</td>
                  <td>{{ $p->name }}</td>
                  <td class="text-truncate" style="max-width:220px;">{{ $p->red_3 }}</td>
                  <td class="text-truncate" style="max-width:220px;">{{ $p->red_4 }}</td>
                  <td class="text-truncate" style="max-width:220px;">{{ $p->red_5 }}</td>
                  <td>{{ $p->complete }}</td>
                  <td>{{ $p->spesa }}</td>
                  <td class="text-end">
                    <button onclick="deletePanel({{ $p->id }})" class="btn btn-sm btn-outline-danger" title="Elimina">
                      <i class="fa-solid fa-trash"></i>
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

// === AGGIORNA PRJ E LINK GUEST (SINISTRA) ===
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

// === COPIA LINK GENERATI ===
function copyLinks() {
  const textarea = document.getElementById('generatedLinks');
  textarea.select();
  textarea.setSelectionRange(0, 99999);
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

  const sid = new URLSearchParams(new URL(links[0]).search).get('sid') || 'SID';
  const pan = new URLSearchParams(new URL(links[0]).search).get('pan') || 'PANEL';
  const filename = `links_${pan}_${sid}.csv`;

  let csvContent = "Url;Code\n";
  links.forEach(url => {
    const code = new URLSearchParams(new URL(url).search).get('uid') || '';
    csvContent += `${url};${code}\n`;
  });

  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  link.href = URL.createObjectURL(blob);
  link.download = filename;
  link.click();
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

// === COLONNA DESTRA: AGGIORNA PRJ ===
const surveyDataRight = @json($surveys);
function updatePrjRight(selectedSid) {
  const s = surveyDataRight.find(item => item.sid === selectedSid);

  const prjInput = document.getElementById('prjRight');
  const uidIidCard = document.getElementById('uidIidCard');
  const resultsCard = document.getElementById('resultsCard');
  const resultsHint = document.getElementById('resultsHint');
  const resultsLoading = document.getElementById('resultsLoading');
  const resultsContent = document.getElementById('resultsContent');

  prjInput.value = s ? s.prj_name : '';

  const hasSid = !!selectedSid;

  if (uidIidCard) {
    uidIidCard.style.display = hasSid ? '' : 'none';
  }

  if (resultsCard) {
    resultsCard.style.display = hasSid ? '' : 'none';
  }

  if (!hasSid) {

    // reset visuale risultati
    if (resultsHint) resultsHint.style.display = 'flex';
    if (resultsLoading) resultsLoading.style.display = 'none';
    if (resultsContent) resultsContent.style.display = 'none';

    const totalFiles = document.getElementById('totalFiles');
    const lastFile = document.getElementById('lastFile');
    const statusTable = document.getElementById('statusTable');
    const lastActions = document.getElementById('lastActions');
    const uidInput = document.getElementById('uidInput');
    const detailTable = document.getElementById('detailTable');
    const searchInput = document.getElementById('searchUidIid');

    if (totalFiles) totalFiles.innerText = '0';
    if (lastFile) lastFile.innerText = '—';

    if (statusTable) {
      statusTable.innerHTML = `
        <tr>
          <td class="text-start">
            <span class="au-pill au-pill--muted">
              <i class="fa-solid fa-minus"></i> —
            </span>
          </td>
          <td class="text-end text-muted">—</td>
        </tr>
      `;
    }

        if (detailTable) {
      detailTable.innerHTML = `
        <tr>
          <td colspan="3" class="text-center text-muted">Nessun dato disponibile</td>
        </tr>
      `;
    }

    if (lastActions) {
      lastActions.innerHTML = '<li class="list-group-item text-muted">Nessuna operazione recente</li>';
    }

    if (uidInput) {
      uidInput.value = '';
    }
     updateResetButtonState();

         if (searchInput) {
      searchInput.value = '';
    }
    resetSearchResultsTable('Nessuna ricerca eseguita');

  }
}


function parseUidIidInputLines() {
  const input = document.getElementById('uidInput');
  if (!input) return [];

  return input.value
    .split(/\r\n|\r|\n/)
    .map(v => v.trim())
    .filter(v => v !== '');
}

function isNumericOnlyLines(lines) {
  if (!lines.length) return false;
  return lines.every(v => /^\d+$/.test(v));
}

function updateResetButtonState() {
  const btnReset = document.getElementById('btn-reset-iids');
  const lines = parseUidIidInputLines();

  if (!btnReset) return;

  btnReset.disabled = !isNumericOnlyLines(lines);
}

// ====== UI HELPERS (solo grafica) ======
function setResultsLoading(isLoading){
  const hint = document.getElementById('resultsHint');
  const loading = document.getElementById('resultsLoading');
  const content = document.getElementById('resultsContent');

  if (isLoading){
    if (hint) hint.style.display = 'none';
    if (content) content.style.display = 'none';
    if (loading) loading.style.display = 'flex';
  } else {
    if (loading) loading.style.display = 'none';
  }
}

function setButtonLoading(buttonId, isLoading, loadingHtml = null) {
  const btn = document.getElementById(buttonId);
  if (!btn) return;

  if (!btn.dataset.originalHtml) {
    btn.dataset.originalHtml = btn.innerHTML;
  }

  if (isLoading) {
    btn.disabled = true;
    btn.innerHTML = loadingHtml || '<span class="spinner-border spinner-border-sm me-2"></span>Caricamento...';
  } else {
    btn.disabled = false;
    btn.innerHTML = btn.dataset.originalHtml;
  }
}

function resetSearchResultsTable(message = 'Nessuna ricerca eseguita') {
  const tbody = document.getElementById('searchResultsTable');
  if (!tbody) return;

  tbody.innerHTML = `
    <tr>
      <td colspan="4" class="text-center text-muted">${message}</td>
    </tr>
  `;
}

// === AGGIORNA FINESTRA RISULTATI ===
function refreshResults() {
  const sid = document.getElementById('sidRight').value;
  const prj = document.getElementById('prjRight').value;
setButtonLoading('btn-refresh-results', true, '<span class="spinner-border spinner-border-sm me-2"></span>Aggiornamento...');

  if (!sid || !prj) {
    Swal.fire({ icon: 'warning', title: 'Attenzione', text: 'Seleziona prima SID e PRJ.' });
    return;
  }

  setResultsLoading(true);

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
    setResultsLoading(false);
    setButtonLoading('btn-refresh-results', false);

    if (!data.success) {
      Swal.fire({ icon: 'error', title: 'Errore', text: data.message });
      return;
    }

    document.getElementById('resultsContent').style.display = 'block';
    document.getElementById('totalFiles').innerText = data.totalFiles;
    document.getElementById('lastFile').innerText = data.lastFile;


            // Render tabella status (solo quelli richiesti)
            const statusTable = document.getElementById('statusTable');
            statusTable.innerHTML = '';

            const STATUS_META = [
            { code: 0, label: 'Sospesa',    icon: 'fa-pause-circle',        cls: 'au-pill--0' },
            { code: 3, label: 'Completata', icon: 'fa-check-circle',        cls: 'au-pill--3' },
            { code: 4, label: 'Screenout',  icon: 'fa-circle-exclamation',  cls: 'au-pill--4' },
            { code: 5, label: 'Quota full', icon: 'fa-ban',                 cls: 'au-pill--5' },
            { code: 6, label: 'Guest',      icon: 'fa-user',                cls: 'au-pill--6' },
            { code: 7, label: 'Bloccata',   icon: 'fa-lock',                cls: 'au-pill--7' },
            ];

            STATUS_META.forEach(s => {
            const count = (data.statusCounts && data.statusCounts[s.code]) ? data.statusCounts[s.code] : 0;

            statusTable.innerHTML += `
                <tr>
                <td class="text-start">
                    <span class="au-pill ${s.cls}">
                    <i class="fa-solid ${s.icon}"></i>
                    ${s.code} · ${s.label}
                    </span>
                </td>
                <td class="text-end fw-semibold">${count}</td>
                </tr>
            `;
            });

        const detailTable = document.getElementById('detailTable');
        if (detailTable) {
        detailTable.innerHTML = '';

        const rows = Array.isArray(data.detailRows) ? data.detailRows : [];

        if (!rows.length) {
            detailTable.innerHTML = `
            <tr>
                <td colspan="3" class="text-center text-muted">Nessun dato disponibile</td>
            </tr>
            `;
        } else {
            rows.forEach(row => {
            detailTable.innerHTML += `
                <tr>
                <td class="text-start">${row.iid ?? '—'}</td>
                <td class="text-start">${row.uid ?? '—'}</td>
                <td class="text-start">${row.status ?? '—'}</td>
                </tr>
            `;
            });
        }
        }
  })
  .catch(() => {
    setResultsLoading(false);
    setButtonLoading('btn-refresh-results', false);
    Swal.fire({ icon: 'error', title: 'Errore di rete' });
  });
}

function searchUidIidRecord() {
  const sid = document.getElementById('sidRight').value;
  const term = document.getElementById('searchUidIid').value.trim();

  if (!sid) {
    Swal.fire({
      icon: 'warning',
      title: 'Attenzione',
      text: 'Seleziona prima un SID.'
    });
    return;
  }

  if (!term) {
    Swal.fire({
      icon: 'warning',
      title: 'Attenzione',
      text: 'Inserisci un UID o un IID da cercare.'
    });
    return;
  }

  setButtonLoading('btn-search-record', true, '<span class="spinner-border spinner-border-sm me-2"></span>Ricerca...');

  fetch('{{ url("/abilita-uid/search-records") }}', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': '{{ csrf_token() }}'
    },
    body: JSON.stringify({ sid, term })
  })
  .then(res => res.json())
  .then(data => {
    setButtonLoading('btn-search-record', false);

    const tbody = document.getElementById('searchResultsTable');
    if (!tbody) return;

    if (!data.success) {
      Swal.fire({ icon: 'error', title: 'Errore', text: data.message || 'Errore durante la ricerca.' });
      resetSearchResultsTable('Errore durante la ricerca');
      return;
    }

    const rows = Array.isArray(data.rows) ? data.rows : [];

    if (!rows.length) {
      resetSearchResultsTable('Nessun record trovato');
      return;
    }

    tbody.innerHTML = '';

    rows.forEach(row => {
      tbody.innerHTML += `
        <tr>
          <td class="text-start">${row.iid ?? '—'}</td>
          <td class="text-start">${row.uid ?? '—'}</td>
          <td class="text-start">${row.status ?? '—'}</td>
          <td class="text-start">${row.prj_name ?? '—'}</td>
        </tr>
      `;
    });
  })
  .catch(() => {
    setButtonLoading('btn-search-record', false);
    resetSearchResultsTable('Errore di rete');
    Swal.fire({ icon: 'error', title: 'Errore di rete' });
  });
}

// === ABILITA UID (con log dinamico) ===
function enableUids() {
  const sid = document.getElementById('sidRight').value;
  const prj = document.getElementById('prjRight').value;
  const uids = document.getElementById('uidInput').value.trim();
    setButtonLoading('btn-enable-uids', true, '<span class="spinner-border spinner-border-sm me-2"></span>Abilitazione...');

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
    setButtonLoading('btn-enable-uids', false);
    if (data.success) {
      Swal.fire({ icon: 'success', title: 'UID abilitati', text: `${data.count} UID inseriti.` });
      updateLog(data.actions);
      refreshResults();
    } else {
      Swal.fire({ icon: 'error', title: 'Errore', text: data.message });
    }
  })
    .catch(() => {
    setButtonLoading('btn-enable-uids', false);
    Swal.fire({ icon: 'error', title: 'Errore di rete' });
  });
}

// === RESET IID (con log dinamico e gestione errori) ===
function resetIids() {
  const sid = document.getElementById('sidRight').value;
  const prj = document.getElementById('prjRight').value;
  const iids = document.getElementById('uidInput').value.trim();

  if (!sid || !prj || !iids) {
    Swal.fire({
      icon: 'warning',
      title: 'Attenzione',
      text: 'Seleziona SID/PRJ e inserisci almeno un IID.'
    });
    return;
  }

  setButtonLoading('btn-reset-iids', true, '<span class="spinner-border spinner-border-sm me-2"></span>Verifica file...');

  fetch('{{ url("/abilita-uid/preview-reset-iids") }}', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': '{{ csrf_token() }}'
    },
    body: JSON.stringify({ sid, prj, iids })
  })
  .then(res => res.json())
  .then(data => {
    setButtonLoading('btn-reset-iids', false);

    if (!data.success) {
      Swal.fire({ icon: 'error', title: 'Errore', text: data.message });
      return;
    }

    const files = Array.isArray(data.files) ? data.files : [];
    const maxPreview = 12;

    let filesHtml = '';
    if (files.length > 0) {
      const previewList = files.slice(0, maxPreview)
        .map(f => `<li style="text-align:left;">${f}</li>`)
        .join('');

      const extra = files.length > maxPreview
        ? `<div class="text-muted mt-2">...e altri ${files.length - maxPreview} file</div>`
        : '';

      filesHtml = `
        <div class="mt-3 text-start">
          <div><strong>Stai per eliminare questi file:</strong></div>
          <ul class="mt-2 mb-1" style="max-height:220px; overflow:auto; padding-left:18px;">
            ${previewList}
          </ul>
          ${extra}
        </div>
      `;
    } else {
      filesHtml = `
        <div class="mt-3 text-start text-muted">
          Nessun file .sre trovato per gli IID inseriti. Verrà eseguito solo il reset nel database.
        </div>
      `;
    }

    Swal.fire({
      icon: 'warning',
      title: 'Conferma eliminazione',
      html: `
        <div>
          <div>Stai per resettare gli IID selezionati.</div>
          ${filesHtml}
        </div>
      `,
      showCancelButton: true,
      confirmButtonText: 'Sì, procedi',
      cancelButtonText: 'Annulla',
      width: 700
    }).then(result => {
      if (!result.isConfirmed) return;

      setButtonLoading('btn-reset-iids', true, '<span class="spinner-border spinner-border-sm me-2"></span>Reset...');

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
        setButtonLoading('btn-reset-iids', false);

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
      .catch(() => {
        setButtonLoading('btn-reset-iids', false);
        Swal.fire({ icon: 'error', title: 'Errore di rete' });
      });
    });
  })
  .catch(() => {
    setButtonLoading('btn-reset-iids', false);
    Swal.fire({ icon: 'error', title: 'Errore di rete' });
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

document.addEventListener('DOMContentLoaded', function () {
  const sidRight = document.getElementById('sidRight');
  if (sidRight) {
    updatePrjRight(sidRight.value);
  }

  const searchInput = document.getElementById('searchUidIid');
  if (searchInput) {
    searchInput.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        searchUidIidRecord();
      }
    });
  }
});

document.addEventListener('DOMContentLoaded', function () {
  const uidInput = document.getElementById('uidInput');
  if (uidInput) {
    uidInput.addEventListener('input', updateResetButtonState);
    updateResetButtonState();
  }
});



</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const form = document.querySelector('form[action="{{ route('abilita.uid.genera') }}"]');
  const btn = document.getElementById('btn-genera-links');

  if (!form || !btn) return;

  form.addEventListener('submit', function () {
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Generazione...';
  });
});
</script>

@endsection
