{{-- resources/views/campionamento.blade.php --}}
@extends('layouts.main')

<link rel="stylesheet" href="{{ asset('css/campionamento.css') }}">

@section('title', 'Campionamento')

@section('content')
<div class="container my-4">
  <div class="row">
    {{-- colonna form --}}
    <div class="col-lg-6">
      <form id="campionamentoForm" action="{{ route('campionamento') }}" method="GET">

  {{-- RICERCA --}}
<div class="sv-card">
  <div class="sv-card-header">
    <div class="sv-head">
      <div class="sv-head-left">
        <i data-feather="search"></i>
        <h6 class="sv-title">Ricerca</h6>
      </div>

      {{-- Segmented: Standard / Follow-up --}}
      <div class="sv-seg" role="group" aria-label="Modalità invito">
        <input type="radio" id="mode_std" name="mode_invito" checked>
        <label for="mode_std">Standard</label>

        <input type="radio" id="mode_fu" name="mode_invito">
        <label for="mode_fu">Follow-up</label>
      </div>
    </div>
  </div>

  <div class="sv-body">
    <div class="sv-select-wrap">
      <span class="sv-select-icon"><i data-feather="search"></i></span>

      <select id="ricerca" name="sur_id" class="form-control sv-input">
        <option value="">Seleziona ricerca</option>
        @foreach($ricerche as $r)
          <option value="{{ $r->sur_id }}">
        {{ ($r->description ?: 'ID '.$r->sur_id) . ' (' . $r->sur_id . ' - ' . $r->prj . ')' }}
        </option>
        @endforeach
      </select>
    </div>

    <div class="sv-info">
      Se presente, i target della ricerca impostano automaticamente sesso ed età.
    </div>

    {{-- checkbox “reale” che già usi nel JS/back-end --}}
    <input type="checkbox" id="chk-followup" class="d-none">
  </div>
</div>

 {{-- DEMOGRAFIA --}}
<div class="sv-card">
  <div class="sv-card-header">
    <div class="sv-head">
      <div class="sv-head-left">
        <i data-feather="users"></i>
        <h6 class="sv-title">Demografia</h6>
      </div>
    </div>
  </div>

  <div class="sv-body">
    <div class="sv-demo">

      {{-- Sesso --}}
      <div>
        <div class="sv-sex">
          <input type="checkbox" id="sUomo" name="sesso[]" value="Uomo">
          <label for="sUomo" class="sv-male">
            <i class="fas fa-mars" aria-hidden="true"></i>
            Uomo
          </label>

          <input type="checkbox" id="sDonna" name="sesso[]" value="Donna">
          <label for="sDonna" class="sv-female">
            <i class="fas fa-venus" aria-hidden="true"></i>
            Donna
          </label>
        </div>
      </div>

      {{-- Età --}}
      <div class="sv-age">
        <div>
          <input type="number" id="eta_da" name="eta_da" class="form-control sv-input" min="16" max="99" placeholder="Età da">
        </div>
        <div>
          <input type="number" id="eta_a" name="eta_a" class="form-control sv-input" min="16" max="99" placeholder="Età a">
        </div>
      </div>

    </div>
  </div>
</div>



       {{-- Localizzazione --}}
<div class="sv-card sv-card-no-clip mb-3">
  <div class="sv-card-header">
    <div class="sv-head">
      <div class="sv-head-left">
        <i data-feather="map-pin"></i>
        <h6 class="sv-title">Localizzazione</h6>
      </div>
    </div>
  </div>

  <div class="sv-body">
    <div class="row">

      {{-- Regione --}}
      <div class="form-group col-md-6">
        <label class="sv-label">Regione</label>

        <div class="sv-ms" data-ms="regioni">
          <div class="sv-ms-trigger" role="button" tabindex="0">
            <div class="sv-ms-placeholder">
              <i data-feather="map"></i>
              Seleziona regioni
            </div>
            <div style="display:flex;align-items:center;gap:10px;">
              <span class="sv-ms-count" data-ms-count>0</span>
              <span class="sv-ms-caret"></span>
            </div>
          </div>

            <div class="sv-ms-panel">
            <div class="sv-ms-panel-header">
                <input class="sv-ms-search" type="text" placeholder="Cerca regione..." data-ms-search>
            </div>

            <div class="sv-ms-list" data-ms-list></div>

            <div class="sv-ms-footer">
                <button type="button" class="sv-ms-btn" data-ms-clear>Reset</button>
                <button type="button" class="sv-ms-btn sv-ms-btn-primary" data-ms-close>Ok</button>
            </div>
            </div>

          {{-- select reale usato dal tuo codice (nascosto) --}}
<select id="regioni" name="regioni[]" class="sv-hidden-select" multiple>
<option value="1">Abruzzo</option>
<option value="2">Basilicata</option>
<option value="3">Calabria</option>
<option value="4">Campania</option>
<option value="5">Emilia-Romagna</option>
<option value="6">Friuli-Venezia Giulia</option>
<option value="7">Lazio</option>
<option value="8">Liguria</option>
<option value="9">Lombardia</option>
<option value="10">Marche</option>
<option value="11">Molise</option>
<option value="12">Piemonte</option>
<option value="13">Puglia</option>
<option value="14">Sardegna</option>
<option value="15">Sicilia</option>
<option value="16">Toscana</option>
<option value="17">Trentino-Alto Adige</option>
<option value="18">Umbria</option>
<option value="19">Valle d'Aosta</option>
<option value="20">Veneto</option>
</select>

          <div class="sv-chips" data-ms-chips></div>
        </div>
      </div>

      {{-- Area --}}
      <div class="form-group col-md-6">
        <label class="sv-label">Area</label>

        <div class="sv-ms" data-ms="aree">
          <div class="sv-ms-trigger" role="button" tabindex="0">
            <div class="sv-ms-placeholder">
              <i data-feather="compass"></i>
              Seleziona aree
            </div>
            <div style="display:flex;align-items:center;gap:10px;">
              <span class="sv-ms-count" data-ms-count>0</span>
              <span class="sv-ms-caret"></span>
            </div>
          </div>

<div class="sv-ms-panel">
  <div class="sv-ms-panel-header">
    <input class="sv-ms-search" type="text" placeholder="Cerca area..." data-ms-search>
  </div>

  <div class="sv-ms-list" data-ms-list></div>

  <div class="sv-ms-footer">
    <button type="button" class="sv-ms-btn" data-ms-clear>Reset</button>
    <button type="button" class="sv-ms-btn sv-ms-btn-primary" data-ms-close>Ok</button>
  </div>
</div>

          <select id="aree" name="aree[]" class="sv-hidden-select" multiple>
            <option value="1">Nord-Ovest</option>
            <option value="2">Nord-Est</option>
            <option value="3">Centro</option>
            <option value="4">Sud + Isole</option>
          </select>

          <div class="sv-chips" data-ms-chips></div>
        </div>
      </div>

      {{-- Province --}}
      <div class="form-group col-md-6">
        <label class="sv-label">Province</label>

        <div class="sv-ms" data-ms="province_id">
          <div class="sv-ms-trigger" role="button" tabindex="0">
            <div class="sv-ms-placeholder">
              <i data-feather="flag"></i>
              Seleziona province
            </div>
            <div style="display:flex;align-items:center;gap:10px;">
              <span class="sv-ms-count" data-ms-count>0</span>
              <span class="sv-ms-caret"></span>
            </div>
          </div>

<div class="sv-ms-panel">
  <div class="sv-ms-panel-header">
    <input class="sv-ms-search" type="text" placeholder="Cerca provincia..." data-ms-search>
  </div>

  <div class="sv-ms-list" data-ms-list></div>

  <div class="sv-ms-footer">
    <button type="button" class="sv-ms-btn" data-ms-clear>Reset</button>
    <button type="button" class="sv-ms-btn sv-ms-btn-primary" data-ms-close>Ok</button>
  </div>
</div>

          <select id="province_id" name="province_id[]" class="sv-hidden-select" multiple>
            {{-- COPIA QUI IL TUO SELECT COMPLETO DI PROVINCE (come già lo hai) --}}
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

          <div class="sv-chips" data-ms-chips></div>
        </div>
      </div>

      {{-- Ampiezza --}}
      <div class="form-group col-md-6">
        <label class="sv-label">Ampiezza Centro</label>

        <div class="sv-ms" data-ms="ampiezza">
          <div class="sv-ms-trigger" role="button" tabindex="0">
            <div class="sv-ms-placeholder">
              <i data-feather="maximize-2"></i>
              Seleziona ampiezze
            </div>
            <div style="display:flex;align-items:center;gap:10px;">
              <span class="sv-ms-count" data-ms-count>0</span>
              <span class="sv-ms-caret"></span>
            </div>
          </div>

          <div class="sv-ms-panel">
            <div class="sv-ms-panel-header">
              <input class="sv-ms-search" type="text" placeholder="Cerca ampiezza..." data-ms-search>
            </div>

            <div class="sv-ms-list" data-ms-list>
            </div>

            <div class="sv-ms-footer">
              <button type="button" class="sv-ms-btn" data-ms-clear>Reset</button>
              <button type="button" class="sv-ms-btn sv-ms-btn-primary" data-ms-close>Ok</button>
            </div>
          </div>

          <select id="ampiezza" name="ampiezza[]" class="sv-hidden-select" multiple>
            <option value="1-149k">1-149k</option>
            <option value="150-499k">150-499k</option>
            <option value="500-999k">500-999k</option>
            <option value="1milione">1 milione e oltre</option>
          </select>

          <div class="sv-chips" data-ms-chips></div>
        </div>
      </div>

    </div>
  </div>
</div>

{{-- STATO UTENTE --}}
<div class="sv-card mb-3">
  <div class="sv-card-header">
    <div class="sv-head">
      <div class="sv-head-left">
        <i data-feather="users"></i>
        <h6 class="sv-title">Stato utente</h6>
      </div>

      <div class="sv-head-right">
        <span class="badge badge-light" style="opacity:.9;">
          Filtri iscrizione / target
        </span>
      </div>
    </div>
  </div>

  <div class="sv-body">
    <div class="row">

      {{-- Iscritto dal --}}
      <div class="form-group col-md-6">
        <label class="sv-label" for="iscritto_dal">Iscritto dal</label>

        <div class="sv-select-wrap">
          <span class="sv-select-icon"><i data-feather="calendar"></i></span>
          <select id="iscritto_dal" name="iscritto_dal" class="form-control sv-input">
            @foreach(range(1990, date('Y')) as $y)
              <option value="{{ $y }}" {{ $loop->first ? 'selected' : '' }}>
                {{ $y }}
              </option>
            @endforeach
          </select>
        </div>

        <small class="text-muted d-block mt-1">Seleziona l’anno minimo di iscrizione.</small>
      </div>

      {{-- Livello attività (ignorato in query) --}}
      <div class="form-group col-md-6">
        <div class="d-flex align-items-center justify-content-between">
          <label class="sv-label mb-0" for="livello_attivita">Attività</label>
          <span class="badge badge-secondary" style="font-weight:600;">coming soon</span>
        </div>

        <div class="sv-select-wrap" style="opacity:.6;">
          <span class="sv-select-icon"><i data-feather="activity"></i></span>
          <select id="livello_attivita" name="livello_attivita" class="form-control sv-input" disabled>
            <option value="">--</option>
            <option value="Alto">Alto</option>
            <option value="Medio">Medio</option>
            <option value="Basso">Basso</option>
          </select>
        </div>

        <small class="text-muted d-block mt-1">Al momento non impatta la selezione utenti.</small>
      </div>

      {{-- Target --}}
      <div class="form-group col-md-6">
        <label class="sv-label" for="target">Target</label>

        <div class="sv-select-wrap">
          <span class="sv-select-icon"><i data-feather="tag"></i></span>
          <select id="target" name="target" class="form-control sv-input" disabled>
            <option value="">-- Seleziona target --</option>
            @foreach($targets as $t)
              <option value="{{ $t->id }}">{{ $t->tag }}</option>
            @endforeach
          </select>
        </div>

        <small class="text-muted d-block mt-1">Si abilita dopo aver selezionato una ricerca.</small>
      </div>

      {{-- Escludi Ricerche --}}
      <div class="form-group col-md-6">
        <label class="sv-label" for="exclude_ricerche">Escludi ricerche</label>

        <div class="sv-select-wrap">
          <span class="sv-select-icon"><i data-feather="slash"></i></span>
          <input
            type="text"
            id="exclude_ricerche"
            name="exclude_ricerche"
            class="form-control sv-input"
            placeholder=""
            autocomplete="off"
          >
        </div>

        <small class="text-muted d-block mt-1">
          Inserisci codici separati da <strong>;</strong> (senza spazi).
        </small>
      </div>

    </div>
  </div>
</div>

{{-- Bottone Aggiungi Campione --}}
<div class="sv-action mt-3">
  <button type="button" id="btn-add-campione" class="sv-btn-primary">
    <i data-feather="plus-circle"></i>
    <span>Aggiungi Campione</span>
  </button>
</div>

      </form>
    </div>

{{-- LATO DESTRO (campioni) --}}
<div class="col-lg-6">

  {{-- PLACEHOLDER (default) --}}
  <div id="right-placeholder" class="sv-card sv-card-right">
    <div class="sv-card-header">
      <div class="sv-head">
        <div class="sv-head-left">
          <i data-feather="info"></i>
          <h6 class="sv-title">Campionamento</h6>
        </div>
      </div>
    </div>

    <div class="sv-body">
      <div class="sv-empty">
        <div class="sv-empty-icon">
          <i data-feather="sliders"></i>
        </div>
        <div class="sv-empty-text">
          <div class="sv-empty-title">Nessun sottocampione ancora</div>
          <div class="sv-empty-sub">
            Seleziona i filtri a sinistra e premi <strong>“Aggiungi Campione”</strong>.
            Qui vedrai l’elenco dei sottocampioni e il totale utenti disponibili.
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- CARD CAMPIONE (quando ci sono sottocampioni) --}}
  <div id="campione-card" class="sv-card sv-card-right" style="display:none;">
    <div class="sv-card-header">
      <div class="sv-head">
        <div class="sv-head-left">
          <i data-feather="layers"></i>
          <h6 class="sv-title">Campione</h6>
        </div>
      </div>
    </div>

    <div class="sv-body">
      <div id="sottocampioni-list" class="sv-right-list"></div>

      <div id="campione-actions" class="sv-right-actions">
        <button id="btn-crea-campione" class="btn btn-sm sv-btn-create">
          <i data-feather="play" class="me-1"></i>
          Crea Campioni
        </button>
      </div>
    </div>
  </div>

  <div id="disponibili-results" class="sv-right-box mt-3" style="display:none;"></div>
  <div id="crea-campione-results" class="sv-right-box mt-3" style="display:none;"></div>

</div>

{{-- FINE LATO DESTRO (campioni) --}}
  </div>
</div>
@endsection

@section('scripts')
<script>
    window.campionamentoUrls = {
        panelDataTemplate: @json(route('campionamento.panel-data', ['sur_id' => '__ID__'])),
        utentiDisponibili: @json(route('campionamento.utenti')),
        creaCampioni: @json(route('campionamento.crea'))
    };
</script>

<script src="{{ asset('js/sv-multiselect.js') }}"></script>
<script src="{{ asset('js/campionamento.js') }}"></script>
@endsection

