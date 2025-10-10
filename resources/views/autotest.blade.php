@extends('layouts.main')

@section('content')
<div class="container-fluid px-3">
<div class="row g-3">
  <!-- SINISTRA ~60% -->
  <div class="col-lg-7">
    {{-- CARD FORM --}}
    <div class="card mb-3 shadow-sm">
      <div class="card-header bg-primary text-white">
        <i class="fa fa-vial"></i> Autotest — Simulatore automatico Primisoft
      </div>
      <div class="card-body">
        <form id="autotestForm" class="row g-3">
          @csrf
          <div class="col-md-6">
            <label class="form-label">SID</label>
            <select id="sid" name="sid" class="form-select" onchange="updatePrj(this.value)" required>
              <option value="">-- Seleziona --</option>
              @foreach($surveys as $s)
                <option value="{{ $s->sid }}">{{ $s->sid }}</option>
              @endforeach
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label">PRJ</label>
            <input type="text" id="prj" name="prj" class="form-control" readonly required>
          </div>

          <div class="col-md-6">
            <label class="form-label">Numero test (iterazioni)</label>
            <input type="number" id="num" name="num" class="form-control" min="1" max="9999" value="10" required>
          </div>

          <div class="col-md-6 d-flex align-items-end justify-content-end gap-2">
            <button type="submit" class="btn btn-primary">
              <i class="fa fa-play"></i> Avvia
            </button>
            <button type="button" class="btn btn-outline-danger" id="btnStop" onclick="stopAutotest()" disabled>
              <i class="fa fa-stop"></i> Stop
            </button>
          </div>
        </form>
      </div>
    </div>

    {{-- CARD AVANZAMENTO --}}
    <div class="card shadow-sm">
      <div class="card-header bg-info text-white">
        <i class="fa fa-tachometer-alt"></i> Avanzamento
      </div>
      <div class="card-body">
        <div class="progress mb-2" style="height: 25px;">
          <div id="progressBar" class="progress-bar bg-success" style="width:0%">0%</div>
        </div>
        <div class="d-flex justify-content-between align-items-center">
          <div>
            Eseguiti: <b id="doneCnt">0</b> / <b id="totCnt">0</b>
            <small id="dirInfo" class="text-muted ms-2"></small>
          </div>
          <div class="small text-muted">Aggiorna ogni 2s</div>
        </div>
      </div>
    </div>
  </div>

  <!-- DESTRA: REPORT INTERVISTE -->
  <div class="col-lg-5">
    <div class="card shadow-sm">
      <div class="card-header bg-secondary text-white">
        <i class="fa fa-list"></i> Stato Interviste
      </div>
      <div class="card-body">
        <h6 class="text-muted mb-2">📊 Dati iniziali</h6>
        <ul class="list-group small mb-3">
          <li class="list-group-item d-flex justify-content-between align-items-center">
            Test già presenti
            <span style="font-size: 14px" class="badge bg-primary" id="report-initial">0</span>
          </li>
          <li class="list-group-item d-flex justify-content-between align-items-center">
            Nuovi da ultima sessione
            <span  style="font-size: 14px" class="badge bg-success" id="report-new">0</span>
          </li>
          <li class="list-group-item d-flex justify-content-between align-items-center">
            Totale file .sre
            <span style="font-size: 14px"  class="badge bg-dark" id="report-total">0</span>
          </li>
        </ul>

        <h6 class="text-muted mb-2">⚙️ Stato in tempo reale</h6>
        <ul class="list-group small">
          <li class="list-group-item d-flex justify-content-between align-items-center">
            Complete
            <span style="font-size: 14px" class="badge bg-success" id="count-complete">0</span>
          </li>
          <li class="list-group-item d-flex justify-content-between align-items-center">
            Screenout
            <span style="font-size: 14px" class="badge bg-warning text-dark" id="count-screenout">0</span>
          </li>
          <li class="list-group-item d-flex justify-content-between align-items-center">
            Quotafull
            <span style="font-size: 14px" class="badge bg-danger" id="count-quotafull">0</span>
          </li>
          <li class="list-group-item d-flex justify-content-between align-items-center">
            Sospese
            <span style="font-size: 14px" class="badge bg-secondary" id="count-sospese">0</span>
          </li>
        </ul>
      </div>
    </div>
  </div>
</div>

</div>
@endsection

@section('scripts')
<script>
// Dati survey dal controller
const surveys = @json($surveys);

// Stato client
let polling = null;
let total = 0;
let sid = '', prj = '';
let isRunning = false;
let winRef = null;

// Auto-compile PRJ da SID
function updatePrj(selectedSid) {
  const s = surveys.find(x => x.sid === selectedSid);
  const prjName = s ? s.prj_name : '';
  document.getElementById('prj').value = prjName;

  // se abbiamo sia sid che prj, aggiorna subito il report
  if (selectedSid && prjName) {
    updateInitialStatus(selectedSid, prjName);
  } else {
    resetReport();
  }
}


// Gestione submit
$('#autotestForm').on('submit', function(e) {
  e.preventDefault();

  sid = $('#sid').val();
  prj = $('#prj').val();
  total = parseInt($('#num').val(), 10);

  if (!sid || !prj || !total) {
    Swal.fire('Attenzione', 'Compila correttamente tutti i campi.', 'warning');
    return;
  }

  // Reset UI
  $('#totCnt').text(total);
  $('#doneCnt').text(0);
  $('#dirInfo').text('');
  setBar(0);
  disableForm(true);

  // Avvio: salva stato iniziale e restituisce link
  $.post("{{ route('autotest.start') }}", { sid, prj, num: total, _token: '{{ csrf_token() }}' })
   .done(function(resp) {
      if (!resp.success) { fail('Impossibile avviare l’autotest.'); return; }

      // Info dir per debug
      $('#dirInfo').text(resp.exists ? `(${resp.dir})` : '(directory risultati non trovata)');

      // Apri finestra test
      const stile = "top=120,left=120,width=300,height=200,status=no,menubar=no,toolbar=no,scrollbars=no";
      try { winRef = window.open(resp.link, "primisAutoTest", stile); }
      catch(e){ winRef = null; }

      isRunning = true;
      $('#btnStop').prop('disabled', false);

      startPolling();
   })
   .fail(function() { fail('Errore di rete nel bootstrap dei test.'); });
});

// Polling avanzamento
function startPolling() {
  stopPolling(); // safety

  polling = setInterval(() => {
    // Se l'utente chiude la finestra → stop immediato
    if (winRef && winRef.closed && isRunning) {
      stopAutotest(true);
      Swal.fire('Interrotto', 'La finestra dei test è stata chiusa manualmente.', 'info');
      return;
    }

    $.post("{{ route('autotest.progress') }}", { sid, prj, num: total, _token: '{{ csrf_token() }}' })
.done(function(resp) {
  // Aggiorna barra/contatore
  $('#doneCnt').text(resp.done);
  setBar(resp.percent);

  // 🔹 Aggiorna report in tempo reale
  $('#report-initial').text(resp.initial);
  $('#report-total').text(resp.totFiles);
  $('#report-new').text(resp.totFiles - resp.initial);

  $('#count-complete').text(resp.ecodeStats.complete);
  $('#count-screenout').text(resp.ecodeStats.screenout);
  $('#count-quotafull').text(resp.ecodeStats.quotafull);
  $('#count-sospese').text(resp.ecodeStats.sospese);

  if (!resp.exists) $('#dirInfo').text('(directory risultati non trovata)');

  if (resp.finished || resp.done >= total) {
    stopAutotest();
    Swal.fire('Completato', 'Autotest terminato correttamente.', 'success');
  }
})
     .fail(function() {
        stopAutotest(true);
        Swal.fire('Errore', 'Impossibile leggere l’avanzamento.', 'error');
     });
  }, 2000);
}

function stopAutotest(silent = false) {
  isRunning = false;
  stopPolling();

  if (winRef && !winRef.closed) {
    try { winRef.close(); } catch(e) {}
  }
  $('#btnStop').prop('disabled', true);
  disableForm(false);

  if (!silent) {
    // messaggio opzionale già gestito dal caller
  }
}

function stopPolling() {
  if (polling) { clearInterval(polling); polling = null; }
}

// Helpers UI
function setBar(pct) {
  const p = Math.max(0, Math.min(100, Number(pct) || 0));
  const el = document.getElementById('progressBar');
  el.style.width = p + '%';
  el.textContent = p + '%';
}

function disableForm(disabled) {
  $('#sid').prop('disabled', disabled);
  // #prj è readonly – non serve disabilitarlo
  $('#num').prop('disabled', disabled);
  $('#autotestForm button[type="submit"]').prop('disabled', disabled);
}

function fail(msg) {
  disableForm(false);
  $('#btnStop').prop('disabled', true);
  setBar(0);
  Swal.fire('Errore', msg || 'Operazione non riuscita.', 'error');
}


function updateInitialStatus(sid, prj) {
  $.post("{{ route('autotest.status') }}", { sid, prj, _token: '{{ csrf_token() }}' })
    .done(function(resp) {
      if (!resp.exists) {
        $('#dirInfo').text('(directory risultati non trovata)');
        resetReport();
        return;
      }

      // aggiorna pannello destra
      $('#dirInfo').text(`(${resp.dir})`);
      $('#report-initial').text(resp.totFiles);
      $('#report-new').text(0);
      $('#report-total').text(resp.totFiles);
      $('#count-complete').text(resp.ecodeStats.complete);
      $('#count-screenout').text(resp.ecodeStats.screenout);
      $('#count-quotafull').text(resp.ecodeStats.quotafull);
      $('#count-sospese').text(resp.ecodeStats.sospese);
    })
    .fail(() => resetReport());
}

function resetReport() {
  $('#report-initial, #report-new, #report-total').text(0);
  $('#count-complete, #count-screenout, #count-quotafull, #count-sospese').text(0);
}


</script>
@endsection
