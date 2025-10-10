{{-- resources/views/campionamento.blade.php --}}
@extends('layouts.main')

@push('styles')
<style>
  /* gradient header */
  .card-header.bg-gradient-primary {
    background: linear-gradient(45deg, #0069D9, #6610F2);
    color: #fff;
    padding: .5rem 1rem;
  }
  /* carte compatte */
  .card { transition: transform .3s ease, box-shadow .3s ease; margin-bottom: .75rem; }
  .card:hover { transform: translateY(-3px); box-shadow: 0 15px 30px rgba(0,0,0,.1); }
  /* corpo carte più compatto */
  .card-body { padding: .75rem 1rem; }
  /* focus glow */
  .form-control:focus { border-color: #6610F2 !important; box-shadow: 0 0 4px rgba(102,16,242,.5); }
  /* bottone gradient compatto */
  .btn-primary { background: linear-gradient(45deg, #0069D9, #6610F2); border: none; padding: .5rem 1rem; }
  .btn-primary:hover { background: linear-gradient(45deg, #0053BA, #520DC2); }

  /* Stile per i campioni nel riquadro destro */
  .card-campione .card-body { border-left: 4px solid #6610F2; }
  .card-campione .card-title { font-size: .9rem; color: #6610F2; }
</style>
@endpush

@section('title', 'Campionamento')

@section('content')
<div class="container my-4">
  <div class="row">
    {{-- colonna form --}}
    <div class="col-lg-6">
      <form id="campionamentoForm" action="{{ route('campionamento') }}" method="GET">

        {{-- Ricerca --}}
        <div class="card shadow-sm mb-3">
          <div class="card-header bg-gradient-primary d-flex align-items-center">
            <i data-feather="search" class="mr-2"></i><strong>Ricerca</strong>
          </div>
          <div class="card-body py-2">
            <select id="ricerca" name="sur_id" class="form-control">
              <option value="">Seleziona ricerca</option>
              @foreach($ricerche as $r)
                <option value="{{ $r->sur_id }}">
                  {{ $r->description ?: 'ID '.$r->sur_id }}
                </option>
              @endforeach
            </select>

            <div class="form-check mt-2">
            <input type="checkbox" id="chk-followup" class="form-check-input">
            <label for="chk-followup" class="form-check-label">Rilancia utenti già invitati</label>
            </div>


          </div>
        </div>

        {{-- Demografia --}}
        <div class="card shadow-sm mb-3">
          <div class="card-header bg-gradient-primary d-flex align-items-center">
            <i data-feather="user" class="mr-2"></i><strong>Demografia</strong>
          </div>
          <div class="card-body py-2">
            <div class="row">
              {{-- Sesso --}}
              <div class="form-group col-md-4">
                <label>Sesso</label><br>
                <div class="form-check form-check-inline">
                  <input class="form-check-input" type="checkbox" id="sUomo" name="sesso[]" value="Uomo">
                  <label class="form-check-label" for="sUomo">Uomo</label>
                </div>
                <div class="form-check form-check-inline">
                  <input class="form-check-input" type="checkbox" id="sDonna" name="sesso[]" value="Donna">
                  <label class="form-check-label" for="sDonna">Donna</label>
                </div>
              </div>
              {{-- Età da --}}
              <div class="form-group col-md-4">
                <label for="eta_da">Età da</label>
                <input type="number" id="eta_da" name="eta_da" class="form-control" min="16" max="99" placeholder="16">
              </div>
              {{-- Età a --}}
              <div class="form-group col-md-4">
                <label for="eta_a">Età a</label>
                <input type="number" id="eta_a" name="eta_a" class="form-control" min="16" max="99" placeholder="99">
              </div>
            </div>
          </div>
        </div>

        {{-- Localizzazione --}}
        <div class="card shadow-sm mb-3">
          <div class="card-header bg-gradient-primary d-flex align-items-center">
            <i data-feather="map-pin" class="mr-2"></i><strong>Localizzazione</strong>
          </div>
          <div class="card-body py-2">
            <div class="row">
              {{-- Regione --}}
              <div class="form-group col-md-6">
                <label for="regioni">Regione</label>
                <select id="regioni" name="regioni[]" class="form-control" multiple>
                  @foreach([
                    'Abruzzo','Basilicata','Calabria','Campania','Emilia-Romagna',
                    'Friuli-Venezia Giulia','Lazio','Liguria','Lombardia','Marche',
                    'Molise','Piemonte','Puglia','Sardegna','Sicilia','Toscana',
                    'Trentino-Alto Adige','Umbria',"Valle d'Aosta",'Veneto'
                  ] as $reg)
                    <option value="{{ $reg }}">{{ $reg }}</option>
                  @endforeach
                </select>
              </div>
              {{-- Area --}}
              <div class="form-group col-md-6">
                <label for="aree">Area</label>
                <select id="aree" name="aree[]" class="form-control" multiple>
                  <option value="Nord-Ovest">Nord-Ovest</option>
                  <option value="Nord-Est">Nord-Est</option>
                  <option value="Centro">Centro</option>
                  <option value="Sud">Sud</option>
                  <option value="Isole">Isole</option>
                </select>
              </div>
              {{-- Province --}}
              <div class="form-group col-md-6">
                <label for="province_id">Province</label>
                <select id="province_id" name="province_id[]" class="form-control form-select" multiple>
                  <option value="1">Alessandria</option>
                  <option value="2">Crotone</option>
                  <option value="3">Aosta</option>
                  <option value="4">Arezzo</option>
                  <option value="5">Ascoli</option>
                  <option value="6">Piceno</option>
                  <option value="7">Asti</option>
                  <option value="8">Avellino</option>
                  <option value="9">Bari</option>
                  <option value="10">Belluno</option>
                  <option value="11">Benevento</option>
                  <option value="12">Bergamo</option>
                  <option value="13">Biella</option>
                  <option value="14">Bologna</option>
                  <option value="15">Bolzano</option>
                  <option value="16">Brescia</option>
                  <option value="17">Brindisi</option>
                  <option value="18">Cagliari</option>
                  <option value="19">Caltanissetta</option>
                  <option value="20">Campobasso</option>
                  <option value="21">Caserta</option>
                  <option value="22">Catania</option>
                  <option value="23">Catanzaro</option>
                  <option value="24">Chieti</option>
                  <option value="25">Como</option>
                  <option value="26">Cosenza</option>
                  <option value="27">Cremona</option>
                  <option value="29">Cuneo</option>
                  <option value="30">Enna</option>
                  <option value="31">Ferrara</option>
                  <option value="32">Firenze</option>
                  <option value="33">Foggia</option>
                  <option value="34">Forli'</option>
                  <option value="35">Frosinone</option>
                  <option value="36">Genova</option>
                  <option value="37">Gorizia</option>
                  <option value="38">Grosseto</option>
                  <option value="39">Imperia Isernia</option>
                  <option value="40">L'Aquila</option>
                  <option value="41">La Spezia</option>
                  <option value="42">Latina</option>
                  <option value="43">Lecce</option>
                  <option value="44">Lecco</option>
                  <option value="45">Livorno</option>
                  <option value="46">Lodi</option>
                  <option value="47">Lucca</option>
                  <option value="48">Macerata</option>
                  <option value="49">Mantova</option>
                  <option value="50">Massa Carrara</option>
                  <option value="51">Matera</option>
                  <option value="52">Messina</option>
                  <option value="53">Milano</option>
                  <option value="54">Modena</option>
                  <option value="55">Napoli</option>
                  <option value="56">Novara</option>
                  <option value="57">Nuoro</option>
                  <option value="58">Oristano</option>
                  <option value="59">Padova</option>
                  <option value="60">Palermo</option>
                  <option value="61">Parma</option>
                  <option value="62">Pavia</option>
                  <option value="63">Perugia</option>
                  <option value="64">Pesaro e Urbino</option>
                  <option value="65">Pescara</option>
                  <option value="66">Piacenza</option>
                  <option value="67">Pisa</option>
                  <option value="68">Pistoia</option>
                  <option value="69">Pordenone</option>
                  <option value="70">Potenza</option>
                  <option value="71">Prato</option>
                  <option value="72">Ragusa</option>
                  <option value="73">Ravenna</option>
                  <option value="74">Reggio</option>
                  <option value="75">Calabria</option>
                  <option value="76">Reggio Emilia</option>
                  <option value="77">Rieti</option>
                  <option value="78">Rimini</option>
                  <option value="79">Roma</option>
                  <option value="80">Rovigo</option>
                  <option value="81">Salerno</option>
                  <option value="82">Sassari</option>
                  <option value="83">Savona</option>
                  <option value="84">Siena</option>
                  <option value="85">Siracusa</option>
                  <option value="86">Sondrio</option>
                  <option value="87">Taranto</option>
                  <option value="88">Teramo</option>
                  <option value="89">Terni</option>
                  <option value="90">Torino</option>
                  <option value="91">Trapani</option>
                  <option value="92">Trento</option>
                  <option value="93">Treviso</option>
                  <option value="94">Trieste</option>
                  <option value="95">Udine</option>
                  <option value="96">Varese</option>
                  <option value="97">Venezia</option>
                  <option value="98">Verbano-Cusio-Os</option>
                  <option value="99">Vercelli</option>
                  <option value="100">Verona</option>
                  <option value="101">Vibo Valentia</option>
                  <option value="102">Vicenza</option>
                  <option value="103">Viterbo</option>
                  <option value="104">Altro</option>
                  <option value="105">Fermo</option>
                </select>
              </div>
              {{-- Ampiezza --}}
              <div class="form-group col-md-6">
                <label for="ampiezza">Ampiezza Centro</label>
                <select id="ampiezza" name="ampiezza[]" class="form-control" multiple>
                  <option value="1-149k">1-149k</option>
                  <option value="150-499k">150-499k</option>
                  <option value="500-999k">500-999k</option>
                  <option value="1milione">1 milione e oltre</option>
                </select>
              </div>
            </div>
          </div>
        </div>

        {{-- Stato utente --}}
        <div class="card shadow-sm mb-3">
          <div class="card-header bg-gradient-primary d-flex align-items-center">
            <i data-feather="users" class="mr-2"></i><strong>Stato utente</strong>
          </div>
          <div class="card-body py-2">
            <div class="row">
              {{-- Iscritto dal --}}
              <div class="form-group col-md-6">
                <label for="iscritto_dal">Iscritto dal</label>
                <select id="iscritto_dal" name="iscritto_dal" class="form-control">
                  @foreach(range(1990, date('Y')) as $y)
                    <option value="{{ $y }}" {{ $loop->first ? 'selected' : '' }}>{{ $y }}</option>
                  @endforeach
                </select>
              </div>
              {{-- Livello attività (ignorato in query) --}}
              <div class="form-group col-md-6">
                <label for="livello_attivita">Attività</label>
                <select id="livello_attivita" name="livello_attivita" class="form-control" disabled>
                  <option value="">--</option>
                  <option value="Alto">Alto</option>
                  <option value="Medio">Medio</option>
                  <option value="Basso">Basso</option>
                </select>
              </div>
              {{-- Target --}}
              <div class="form-group col-md-6">
                <label for="target">Target</label>
                <select id="target" name="target" class="form-control">
                  <option value="">-- Seleziona target --</option>
                  @foreach($targets as $t)
                    <option value="{{ $t->id }}">{{ $t->tag }}</option>
                  @endforeach
                </select>
              </div>
              {{-- Escludi Ricerche --}}
              <div class="form-group col-md-6">
                <label for="exclude_ricerche">Escludi Ricerche</label>
                <input type="text" id="exclude_ricerche" name="exclude_ricerche" class="form-control"
                       placeholder="Inserisci codici separati da ‘;’, es. ABC123;XYZ456;DEF789">
              </div>
            </div>
          </div>
        </div>

        {{-- Bottone Aggiungi Campione --}}
        <button type="button" id="btn-add-campione" class="btn btn-primary btn-block">
          <i data-feather="plus-circle" class="mr-1"></i>
          Aggiungi Campione
        </button>

      </form>
    </div>

    {{-- LATO DESTRO (campioni) --}}
    <div class="col-lg-6">
      <div id="campione-card" class="card shadow-sm" style="display:none;">
        <div class="card-header bg-gradient-primary text-white">
          <i data-feather="layers" class="mr-1"></i><strong>Campione</strong>
        </div>
        <div class="card-body py-2">
          {{-- qui mettiamo i sottocampioni --}}
          <div id="sottocampioni-list" class="mb-3"></div>

          {{-- bottoni globali --}}
          <div id="campione-actions" class="text-right">
            <button id="btn-crea-campione" class="btn btn-sm btn-primary">
              Crea Campioni
            </button>
          </div>
        </div>
      </div>

      {{-- SOLO TOTALE --}}
      <div id="disponibili-results" class="mt-3" style="display:none;"></div>
      <div id="crea-campione-results" class="mt-3" style="display:none;"></div>

    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
  if (window.feather) feather.replace();

  // === Riferimenti UI ===
  const selRicerca   = document.getElementById('ricerca');
  const selTarget    = document.getElementById('target');
  const btnAdd       = document.getElementById('btn-add-campione');
  const campioneCard = document.getElementById('campione-card');
  const listEl       = document.getElementById('sottocampioni-list');
  const resultsBox   = document.getElementById('disponibili-results');
  const btnCrea      = document.getElementById('btn-crea-campione');
const chkFollowup  = document.getElementById('chk-followup');


  const MAX          = 3;
  let CAMPIONE_FINAL = false;

  // Array globale dei sottocampioni
  window.sottocampioni = window.sottocampioni || [];

  // === Abilita/Disabilita controlli in base alla Ricerca ===
  function updateRicercaState() {
    const hasRicerca = !!(selRicerca && selRicerca.value);
    if (selTarget) selTarget.disabled = !hasRicerca;
    if (btnAdd)    btnAdd.disabled    = !hasRicerca || window.sottocampioni.length >= MAX || CAMPIONE_FINAL;
  }
  updateRicercaState();

  if (selRicerca) {
    selRicerca.addEventListener('change', () => {
      updateRicercaState();
      // reset conteggi visivi e risultati se cambio ricerca
      window.sottocampioni.forEach(sc => { delete sc.count; sc.invite = 1; });
      renderCampione();
      clearDisponibili();
    });
  }

  // === Utili ===
  function clearDisponibili() {
    if (resultsBox) {
      resultsBox.style.display = 'none';
      resultsBox.innerHTML = '';
    }
  }

  const labels = {
    sesso: 'Sesso',
    eta: 'Età',
    regioni: 'Regione',
    aree: 'Area',
    province: 'Province',
    ampiezza: 'Ampiezza',
    iscritto_dal: 'Iscritto dal',
    livello_attivita: 'Livello attività',
    target: 'Target',
    exclude: 'Escludi Ricerche'
  };

  // === Render lista sottocampioni (sempre visibile se presenti) ===
  function renderCampione() {
    if (!window.sottocampioni.length) {
      if (campioneCard) campioneCard.style.display = 'none';
      listEl.innerHTML = '';
      clearDisponibili();
      updateRicercaState();
      return;
    }
    if (campioneCard) campioneCard.style.display = 'block';

    listEl.innerHTML = '';
    window.sottocampioni.forEach((sc, i) => {
      const wrap = document.createElement('div');
      wrap.className = 'border-left pl-2 mb-2';
      wrap.innerHTML = `
        <div class="d-flex justify-content-between align-items-start">
<strong>
  Sottocampione ${i+1}
  <span class="text-muted ml-2">
    <i data-feather="users" class="align-text-bottom"></i>
    (${Number.isFinite(sc.count) ? sc.count : '…'})
  </span>
  ${sc.followup ? '<span class="badge badge-info ml-1">Follow-up</span>' : ''}
</strong>
          <button data-index="${i}" class="btn btn-sm btn-outline-danger btn-delete">
            <i data-feather="trash-2"></i>
          </button>
        </div>

        <!-- Inviti -->
        <div class="mt-2">
          <label class="small mb-1">Inviti</label>
          <div class="input-group input-group-sm" data-index="${i}">
            <div class="input-group-prepend">
              <span class="input-group-text"><i data-feather="mail"></i></span>
            </div>
            <input
              type="number"
              class="form-control sc-invite"
              min="1"
              ${Number.isFinite(sc.count) ? `max="${sc.count}"` : ``}
              value="${Number.isFinite(sc.invite) ? sc.invite : 1}"
              ${Number.isFinite(sc.count) && !CAMPIONE_FINAL ? `` : `disabled`}
              inputmode="numeric"
            >
            <div class="input-group-append">
              <span class="input-group-text">/ ${Number.isFinite(sc.count) ? sc.count : '—'}</span>
            </div>
          </div>
          <small class="text-muted">Seleziona da 1 al massimo disponibile.</small>
        </div>

        <ul class="small mb-2 mt-2" style="list-style:none;padding-left:0;">
          ${Object.entries(sc)
              .filter(([k,v]) => ['sesso','eta','regioni','aree','province','ampiezza','iscritto_dal','livello_attivita','target','exclude'].includes(k))
              .map(([k,v]) => v ? `<li><strong>${labels[k]}:</strong> ${v}</li>` : '')
              .join('')}
        </ul>
      `;
      listEl.appendChild(wrap);
    });

    if (window.feather) feather.replace();

    // Enforcement blocchi post-finalizzazione
    if (CAMPIONE_FINAL) {
      // rimuovo i pulsanti elimina
      listEl.querySelectorAll('.btn-delete').forEach(btn => btn.remove());
      // nascondo "Crea Campioni"
      if (btnCrea) btnCrea.style.display = 'none';
      // blocco aggiunta sottocampioni
      if (btnAdd) btnAdd.disabled = true;
    } else {
      // bind cancellazione sottocampione (unico punto)
      listEl.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', () => {
          const idx = parseInt(btn.dataset.index, 10);
          window.sottocampioni.splice(idx, 1);
          renderCampione();
          clearDisponibili();
          // ricalcolo conteggi/totale dopo la cancellazione
          fetchCountsAndRender();
        });
      });
      // mostra "Crea Campioni"
      if (btnCrea) btnCrea.style.display = 'inline-block';
      // aggiorna pulsante aggiunta campione
      updateRicercaState();
    }

    // binding: variazione inviti → salva su modello e clamp tra [1, max]
    listEl.querySelectorAll('.sc-invite').forEach(inp => {
      const idx = parseInt(inp.closest('.input-group').dataset.index, 10);
      inp.addEventListener('input', () => {
        const sc = window.sottocampioni[idx];
        const max = Number.isFinite(sc.count) ? sc.count : 1;
        let v = parseInt(inp.value || '1', 10);
        if (isNaN(v) || v < 1) v = 1;
        if (v > max) v = max;
        inp.value = v;
        sc.invite = v;
      });
    });
  }

  // === Calcolo conteggi e totale (auto) ===
  async function fetchCountsAndRender() {
    if (!selRicerca || !selRicerca.value) { clearDisponibili(); return; }
    if (!window.sottocampioni.length)     { clearDisponibili(); return; }

const samples = window.sottocampioni.map(sc => ({
  sesso: (sc.sesso || '').split('/').filter(Boolean),
  eta_da: sc.eta ? parseInt(sc.eta.split('-')[0], 10) : null,
  eta_a:  sc.eta ? parseInt(sc.eta.split('-')[1], 10) : null,
  regioni: sc.regioni ? sc.regioni.split(',').map(s => s.trim()).filter(Boolean) : [],
  aree:    sc.aree    ? sc.aree.split(',').map(s => s.trim()).filter(Boolean) : [],
  province_id: sc.province ? sc.province.split(',').map(s => s.trim()).filter(Boolean) : [],
  ampiezza: sc.ampiezza ? sc.ampiezza.split(',').map(s => s.trim()).filter(Boolean) : [],
  target_id: sc.target_id || null,
  invite: Number.isFinite(sc.count) ? Math.min(sc.invite || 1, sc.count) : (sc.invite || 1),
  followup: sc.followup || false   // ✅ CORRETTO — ogni campione mantiene il suo stato
}));



    const excludeCodes = (document.getElementById('exclude_ricerche')?.value || '').trim();
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    try {
      const res = await fetch(`{{ route('campionamento.utenti') }}`, {
        method: 'POST',
        headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': token, 'Accept':'application/json' },
        body: JSON.stringify({
          sur_id: selRicerca.value,
          exclude_codes: excludeCodes,
          samples: samples,
          debug: true,
          followup: chkFollowup?.checked || false
        })
      });
      const raw = await res.text();
      let data; try { data = JSON.parse(raw); } catch(e) { console.error(raw); return; }

      // aggiorna contatori per ogni sottocampione
      if (Array.isArray(data.items)) {
        data.items.forEach((it, i) => {
          if (window.sottocampioni[i]) window.sottocampioni[i].count = it.count;
        });
      }

      // clamp inviti in base al nuovo count
      window.sottocampioni.forEach(sc => {
        if (!Number.isFinite(sc.count)) return;
        if (!Number.isFinite(sc.invite)) sc.invite = 1;
        if (sc.invite < 1) sc.invite = 1;
        if (sc.invite > sc.count) sc.invite = sc.count;
      });

      renderCampione();
      renderTotale(data.total);
    } catch (err) {
      console.error(err);
    }
  }

  // === Render SOLO totale nel box risultati ===
  function renderTotale(total) {
    if (!resultsBox) return;
    if (typeof total !== 'number') { clearDisponibili(); return; }
    const totalCard = `
      <div class="row">
        <div class="col-12 mb-2">
          <div class="card border-0 shadow" style="background:linear-gradient(45deg,#0069D9,#6610F2);">
            <div class="card-body text-center text-white">
              <div class="small text-white-50">Totale disponibili</div>
              <div class="display-4 font-weight-bold">${total}</div>
            </div>
          </div>
        </div>
      </div>`;
    resultsBox.innerHTML = totalCard;
    resultsBox.style.display = 'block';
  }

  // === CLICK: Aggiungi Campione ===
  if (btnAdd) {
    btnAdd.addEventListener('click', () => {
      if (CAMPIONE_FINAL) { alert('Campione già generato. Usa "Pulisci" per ricominciare.'); return; }
      if (!selRicerca || !selRicerca.value) { alert('Seleziona prima una Ricerca.'); return; }
      if (window.sottocampioni.length >= MAX) return;

      const sesso = Array.from(document.querySelectorAll('input[name="sesso[]"]:checked'))
                         .map(cb => cb.value).join('/');
      const etaDa = document.getElementById('eta_da').value || '';
      const etaA  = document.getElementById('eta_a').value  || '';
      const eta   = (etaDa && etaA) ? `${etaDa}-${etaA}` : '';

      const regioni  = Array.from(document.getElementById('regioni').selectedOptions).map(o => o.value).join(', ');
      const aree     = Array.from(document.getElementById('aree').selectedOptions).map(o => o.value).join(', ');
      const province = Array.from(document.getElementById('province_id').selectedOptions).map(o => o.value).join(', ');
      const ampiezza = Array.from(document.getElementById('ampiezza').selectedOptions).map(o => o.value).join(', ');

      const iscritto_dal     = document.getElementById('iscritto_dal').value || '';
      const livello_attivita = document.getElementById('livello_attivita').value || '';

      const targetText = selTarget && selTarget.selectedIndex >= 0
        ? selTarget.options[selTarget.selectedIndex].text.trim()
        : '';
      const targetId   = selTarget && selTarget.value ? parseInt(selTarget.value, 10) : null;

      const excludeEl = document.getElementById('exclude_ricerche');
      const exclude   = excludeEl ? excludeEl.value.trim() : '';

 const sc = {
  sesso, eta, regioni, aree, province, ampiezza,
  iscritto_dal, livello_attivita,
  target: targetText, target_id: targetId,
  exclude,
  invite: 1,
  followup: chkFollowup?.checked || false // ✅ aggiunto: salva nel sottocampione
};

      window.sottocampioni.push(sc);
      renderCampione();
      // calcola subito i conteggi (badge) e il totale
      fetchCountsAndRender();
    });
  }

  // === Generazione CSV (Crea Campioni) ===
  if (btnCrea) {
    btnCrea.addEventListener('click', async () => {
      if (!selRicerca || !selRicerca.value) { alert('Seleziona prima una Ricerca.'); return; }
      if (!window.sottocampioni.length)     { alert('Aggiungi almeno un sottocampione.'); return; }

const samples = window.sottocampioni.map(sc => ({
  sesso: (sc.sesso || '').split('/').filter(Boolean),
  eta_da: sc.eta ? parseInt(sc.eta.split('-')[0], 10) : null,
  eta_a:  sc.eta ? parseInt(sc.eta.split('-')[1], 10) : null,
  regioni: sc.regioni ? sc.regioni.split(',').map(s => s.trim()).filter(Boolean) : [],
  aree:    sc.aree    ? sc.aree.split(',').map(s => s.trim()).filter(Boolean) : [],
  province_id: sc.province ? sc.province.split(',').map(s => s.trim()).filter(Boolean) : [],
  ampiezza: sc.ampiezza ? sc.ampiezza.split(',').map(s => s.trim()).filter(Boolean) : [],
  target_id: sc.target_id || null,
  invite: Number.isFinite(sc.count) ? Math.min(sc.invite || 1, sc.count) : (sc.invite || 1),
  followup: sc.followup || false   // ✅ CORRETTO — legge il valore interno al sottocampione
}));



      const excludeCodes = (document.getElementById('exclude_ricerche')?.value || '').trim();
      const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

      try {
        const res = await fetch(`{{ route('campionamento.crea') }}`, {
          method: 'POST',
          headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': token, 'Accept':'application/json' },
          body: JSON.stringify({
            sur_id: selRicerca.value,
            exclude_codes: excludeCodes,
            samples: samples,
            followup: chkFollowup?.checked || false
          })
        });

        const raw = await res.text();
        let data; try { data = JSON.parse(raw); } catch(e) { console.error(raw); alert('Risposta non valida'); return; }

        // mostra pannello risultato CSV
        renderCsvResult(data);
        // campione finalizzato → blocca UI sottocampioni e nascondi "Crea Campioni"
        CAMPIONE_FINAL = true;
        renderCampione();
      } catch (err) {
        console.error(err);
        alert('Errore durante la creazione del campione.');
      }
    });
  }

  // primo render
  renderCampione();

  // === Renderer risultato CSV (download, copia, PULISCI) ===
  function renderCsvResult(data) {
    const box = document.getElementById('crea-campione-results');
    if (!box) return;

    const count    = data.enabled_count || 0;
    const filename = data.filename || 'campione.csv';
    const csvText  = data.csv_text || '';
    const csvB64   = data.csv_base64 || '';

    const html = `
      <div class="card shadow-sm">
        <div class="card-body d-flex flex-column flex-md-row align-items-center justify-content-between">
          <div class="mb-2 mb-md-0">
            <div class="small text-muted">Utenti abilitati</div>
            <div class="h3 mb-0">${count}</div>
          </div>
          <div class="d-flex align-items-center">
            <button id="btn-download-csv" class="btn btn-sm btn-primary mr-2">
              <i data-feather="download"></i> Download CSV
            </button>
            <button id="btn-copy-csv" class="btn btn-sm btn-outline-secondary mr-2">
              <i data-feather="copy"></i> Copia contenuto
            </button>
            <button id="btn-clear-all" class="btn btn-sm btn-outline-danger">
              <i data-feather="x-circle"></i> Pulisci
            </button>
          </div>
        </div>
      </div>
    `;

    box.innerHTML = html;
    box.style.display = 'block';
    if (window.feather) feather.replace();

    // Download dal base64
    const btnDl = document.getElementById('btn-download-csv');
    if (btnDl) {
      btnDl.addEventListener('click', () => {
        const bytes = atob(csvB64);
        const arr = new Uint8Array(bytes.length);
        for (let i=0; i<bytes.length; i++) arr[i] = bytes.charCodeAt(i);
        const blob = new Blob([arr], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
      });
    }

    // Copia negli appunti il testo CSV
    const btnCopy = document.getElementById('btn-copy-csv');
    if (btnCopy) {
      btnCopy.addEventListener('click', async () => {
        try {
          await navigator.clipboard.writeText(csvText);
          btnCopy.classList.remove('btn-outline-secondary');
          btnCopy.classList.add('btn-success');
          btnCopy.innerHTML = '<i data-feather="check"></i> Copiato';
          if (window.feather) feather.replace();
          setTimeout(() => {
            btnCopy.classList.remove('btn-success');
            btnCopy.classList.add('btn-outline-secondary');
            btnCopy.innerHTML = '<i data-feather="copy"></i> Copia contenuto';
            if (window.feather) feather.replace();
          }, 2000);
        } catch(e) {
          alert('Impossibile copiare negli appunti.');
        }
      });
    }

    // PULISCI → svuota i due box risultati + reset stato/app (listener AGGREGATO QUI!)
    const btnClear = document.getElementById('btn-clear-all');
    if (btnClear) {
      btnClear.addEventListener('click', () => {
        // svuoto e nascondo i box risultati
        const res1 = document.getElementById('disponibili-results');
        const res2 = document.getElementById('crea-campione-results');
        if (res1) { res1.innerHTML = ''; res1.style.display = 'none'; }
        if (res2) { res2.innerHTML = ''; res2.style.display = 'none'; }

        // reset stato e dati
        window.sottocampioni = [];
        CAMPIONE_FINAL = false;

        // reset form sinistro (se desideri mantenerlo)
        document.getElementById('campionamentoForm')?.reset();

        // UI torna “pulita”
        renderCampione();
      });
    }
  }
});
</script>
@endsection

