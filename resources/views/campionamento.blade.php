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
  <div class="sv-ms-list" data-ms-list>
<div class="sv-ms-item" data-ms-item data-value="1" data-label="Abruzzo"><input type="checkbox"><div>Abruzzo</div></div>
<div class="sv-ms-item" data-ms-item data-value="2" data-label="Basilicata"><input type="checkbox"><div>Basilicata</div></div>
<div class="sv-ms-item" data-ms-item data-value="3" data-label="Calabria"><input type="checkbox"><div>Calabria</div></div>
<div class="sv-ms-item" data-ms-item data-value="4" data-label="Campania"><input type="checkbox"><div>Campania</div></div>
<div class="sv-ms-item" data-ms-item data-value="5" data-label="Emilia-Romagna"><input type="checkbox"><div>Emilia-Romagna</div></div>
<div class="sv-ms-item" data-ms-item data-value="6" data-label="Friuli-Venezia Giulia"><input type="checkbox"><div>Friuli-Venezia Giulia</div></div>
<div class="sv-ms-item" data-ms-item data-value="7" data-label="Lazio"><input type="checkbox"><div>Lazio</div></div>
<div class="sv-ms-item" data-ms-item data-value="8" data-label="Liguria"><input type="checkbox"><div>Liguria</div></div>
<div class="sv-ms-item" data-ms-item data-value="9" data-label="Lombardia"><input type="checkbox"><div>Lombardia</div></div>
<div class="sv-ms-item" data-ms-item data-value="10" data-label="Marche"><input type="checkbox"><div>Marche</div></div>
<div class="sv-ms-item" data-ms-item data-value="11" data-label="Molise"><input type="checkbox"><div>Molise</div></div>
<div class="sv-ms-item" data-ms-item data-value="12" data-label="Piemonte"><input type="checkbox"><div>Piemonte</div></div>
<div class="sv-ms-item" data-ms-item data-value="13" data-label="Puglia"><input type="checkbox"><div>Puglia</div></div>
<div class="sv-ms-item" data-ms-item data-value="14" data-label="Sardegna"><input type="checkbox"><div>Sardegna</div></div>
<div class="sv-ms-item" data-ms-item data-value="15" data-label="Sicilia"><input type="checkbox"><div>Sicilia</div></div>
<div class="sv-ms-item" data-ms-item data-value="16" data-label="Toscana"><input type="checkbox"><div>Toscana</div></div>
<div class="sv-ms-item" data-ms-item data-value="17" data-label="Trentino-Alto Adige"><input type="checkbox"><div>Trentino-Alto Adige</div></div>
<div class="sv-ms-item" data-ms-item data-value="18" data-label="Umbria"><input type="checkbox"><div>Umbria</div></div>
<div class="sv-ms-item" data-ms-item data-value="19" data-label="Valle d'Aosta"><input type="checkbox"><div>Valle d'Aosta</div></div>
<div class="sv-ms-item" data-ms-item data-value="20" data-label="Veneto"><input type="checkbox"><div>Veneto</div></div>
  </div>
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

            <div class="sv-ms-list" data-ms-list>
              <div class="sv-ms-item" data-ms-item data-value="1" data-label="Nord-Ovest"><input type="checkbox"><div>Nord-Ovest</div></div>
              <div class="sv-ms-item" data-ms-item data-value="2" data-label="Nord-Est"><input type="checkbox"><div>Nord-Est</div></div>
              <div class="sv-ms-item" data-ms-item data-value="3" data-label="Centro"><input type="checkbox"><div>Centro</div></div>
              <div class="sv-ms-item" data-ms-item data-value="4" data-label="Sud + Isole"><input type="checkbox"><div>Sud + Isole</div></div>
            </div>

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

            <div class="sv-ms-list" data-ms-list>
<div class="sv-ms-item" data-ms-item data-value="1" data-label="Alessandria"><input type="checkbox"><div>Alessandria</div></div>
<div class="sv-ms-item" data-ms-item data-value="2" data-label="Crotone"><input type="checkbox"><div>Crotone</div></div>
<div class="sv-ms-item" data-ms-item data-value="3" data-label="Aosta"><input type="checkbox"><div>Aosta</div></div>
<div class="sv-ms-item" data-ms-item data-value="4" data-label="Arezzo"><input type="checkbox"><div>Arezzo</div></div>
<div class="sv-ms-item" data-ms-item data-value="5" data-label="Ascoli"><input type="checkbox"><div>Ascoli</div></div>
<div class="sv-ms-item" data-ms-item data-value="6" data-label="Piceno"><input type="checkbox"><div>Piceno</div></div>
<div class="sv-ms-item" data-ms-item data-value="7" data-label="Asti"><input type="checkbox"><div>Asti</div></div>
<div class="sv-ms-item" data-ms-item data-value="8" data-label="Avellino"><input type="checkbox"><div>Avellino</div></div>
<div class="sv-ms-item" data-ms-item data-value="9" data-label="Bari"><input type="checkbox"><div>Bari</div></div>
<div class="sv-ms-item" data-ms-item data-value="10" data-label="Belluno"><input type="checkbox"><div>Belluno</div></div>
<div class="sv-ms-item" data-ms-item data-value="11" data-label="Benevento"><input type="checkbox"><div>Benevento</div></div>
<div class="sv-ms-item" data-ms-item data-value="12" data-label="Bergamo"><input type="checkbox"><div>Bergamo</div></div>
<div class="sv-ms-item" data-ms-item data-value="13" data-label="Biella"><input type="checkbox"><div>Biella</div></div>
<div class="sv-ms-item" data-ms-item data-value="14" data-label="Bologna"><input type="checkbox"><div>Bologna</div></div>
<div class="sv-ms-item" data-ms-item data-value="15" data-label="Bolzano"><input type="checkbox"><div>Bolzano</div></div>
<div class="sv-ms-item" data-ms-item data-value="16" data-label="Brescia"><input type="checkbox"><div>Brescia</div></div>
<div class="sv-ms-item" data-ms-item data-value="17" data-label="Brindisi"><input type="checkbox"><div>Brindisi</div></div>
<div class="sv-ms-item" data-ms-item data-value="18" data-label="Cagliari"><input type="checkbox"><div>Cagliari</div></div>
<div class="sv-ms-item" data-ms-item data-value="19" data-label="Caltanissetta"><input type="checkbox"><div>Caltanissetta</div></div>
<div class="sv-ms-item" data-ms-item data-value="20" data-label="Campobasso"><input type="checkbox"><div>Campobasso</div></div>
<div class="sv-ms-item" data-ms-item data-value="21" data-label="Caserta"><input type="checkbox"><div>Caserta</div></div>
<div class="sv-ms-item" data-ms-item data-value="22" data-label="Catania"><input type="checkbox"><div>Catania</div></div>
<div class="sv-ms-item" data-ms-item data-value="23" data-label="Catanzaro"><input type="checkbox"><div>Catanzaro</div></div>
<div class="sv-ms-item" data-ms-item data-value="24" data-label="Chieti"><input type="checkbox"><div>Chieti</div></div>
<div class="sv-ms-item" data-ms-item data-value="25" data-label="Como"><input type="checkbox"><div>Como</div></div>
<div class="sv-ms-item" data-ms-item data-value="26" data-label="Cosenza"><input type="checkbox"><div>Cosenza</div></div>
<div class="sv-ms-item" data-ms-item data-value="27" data-label="Cremona"><input type="checkbox"><div>Cremona</div></div>
<div class="sv-ms-item" data-ms-item data-value="29" data-label="Cuneo"><input type="checkbox"><div>Cuneo</div></div>
<div class="sv-ms-item" data-ms-item data-value="30" data-label="Enna"><input type="checkbox"><div>Enna</div></div>
<div class="sv-ms-item" data-ms-item data-value="31" data-label="Ferrara"><input type="checkbox"><div>Ferrara</div></div>
<div class="sv-ms-item" data-ms-item data-value="32" data-label="Firenze"><input type="checkbox"><div>Firenze</div></div>
<div class="sv-ms-item" data-ms-item data-value="33" data-label="Foggia"><input type="checkbox"><div>Foggia</div></div>
<div class="sv-ms-item" data-ms-item data-value="34" data-label="Forli"><input type="checkbox"><div>Forli</div></div>
<div class="sv-ms-item" data-ms-item data-value="35" data-label="Frosinone"><input type="checkbox"><div>Frosinone</div></div>
<div class="sv-ms-item" data-ms-item data-value="36" data-label="Genova"><input type="checkbox"><div>Genova</div></div>
<div class="sv-ms-item" data-ms-item data-value="37" data-label="Gorizia"><input type="checkbox"><div>Gorizia</div></div>
<div class="sv-ms-item" data-ms-item data-value="38" data-label="Grosseto"><input type="checkbox"><div>Grosseto</div></div>
<div class="sv-ms-item" data-ms-item data-value="39" data-label="Imperia Isernia"><input type="checkbox"><div>Imperia Isernia</div></div>
<div class="sv-ms-item" data-ms-item data-value="40" data-label="L'Aquila"><input type="checkbox"><div>L'Aquila</div></div>
<div class="sv-ms-item" data-ms-item data-value="41" data-label="La Spezia"><input type="checkbox"><div>La Spezia</div></div>
<div class="sv-ms-item" data-ms-item data-value="42" data-label="Latina"><input type="checkbox"><div>Latina</div></div>
<div class="sv-ms-item" data-ms-item data-value="43" data-label="Lecce"><input type="checkbox"><div>Lecce</div></div>
<div class="sv-ms-item" data-ms-item data-value="44" data-label="Lecco"><input type="checkbox"><div>Lecco</div></div>
<div class="sv-ms-item" data-ms-item data-value="45" data-label="Livorno"><input type="checkbox"><div>Livorno</div></div>
<div class="sv-ms-item" data-ms-item data-value="46" data-label="Lodi"><input type="checkbox"><div>Lodi</div></div>
<div class="sv-ms-item" data-ms-item data-value="47" data-label="Lucca"><input type="checkbox"><div>Lucca</div></div>
<div class="sv-ms-item" data-ms-item data-value="48" data-label="Macerata"><input type="checkbox"><div>Macerata</div></div>
<div class="sv-ms-item" data-ms-item data-value="49" data-label="Mantova"><input type="checkbox"><div>Mantova</div></div>
<div class="sv-ms-item" data-ms-item data-value="50" data-label="Massa Carrara"><input type="checkbox"><div>Massa Carrara</div></div>
<div class="sv-ms-item" data-ms-item data-value="51" data-label="Matera"><input type="checkbox"><div>Matera</div></div>
<div class="sv-ms-item" data-ms-item data-value="52" data-label="Messina"><input type="checkbox"><div>Messina</div></div>
<div class="sv-ms-item" data-ms-item data-value="53" data-label="Milano"><input type="checkbox"><div>Milano</div></div>
<div class="sv-ms-item" data-ms-item data-value="54" data-label="Modena"><input type="checkbox"><div>Modena</div></div>
<div class="sv-ms-item" data-ms-item data-value="55" data-label="Napoli"><input type="checkbox"><div>Napoli</div></div>
<div class="sv-ms-item" data-ms-item data-value="56" data-label="Novara"><input type="checkbox"><div>Novara</div></div>
<div class="sv-ms-item" data-ms-item data-value="57" data-label="Nuoro"><input type="checkbox"><div>Nuoro</div></div>
<div class="sv-ms-item" data-ms-item data-value="58" data-label="Oristano"><input type="checkbox"><div>Oristano</div></div>
<div class="sv-ms-item" data-ms-item data-value="59" data-label="Padova"><input type="checkbox"><div>Padova</div></div>
<div class="sv-ms-item" data-ms-item data-value="60" data-label="Palermo"><input type="checkbox"><div>Palermo</div></div>
<div class="sv-ms-item" data-ms-item data-value="61" data-label="Parma"><input type="checkbox"><div>Parma</div></div>
<div class="sv-ms-item" data-ms-item data-value="62" data-label="Pavia"><input type="checkbox"><div>Pavia</div></div>
<div class="sv-ms-item" data-ms-item data-value="63" data-label="Perugia"><input type="checkbox"><div>Perugia</div></div>
<div class="sv-ms-item" data-ms-item data-value="64" data-label="Pesaro e Urbino"><input type="checkbox"><div>Pesaro e Urbino</div></div>
<div class="sv-ms-item" data-ms-item data-value="65" data-label="Pescara"><input type="checkbox"><div>Pescara</div></div>
<div class="sv-ms-item" data-ms-item data-value="66" data-label="Piacenza"><input type="checkbox"><div>Piacenza</div></div>
<div class="sv-ms-item" data-ms-item data-value="67" data-label="Pisa"><input type="checkbox"><div>Pisa</div></div>
<div class="sv-ms-item" data-ms-item data-value="68" data-label="Pistoia"><input type="checkbox"><div>Pistoia</div></div>
<div class="sv-ms-item" data-ms-item data-value="69" data-label="Pordenone"><input type="checkbox"><div>Pordenone</div></div>
<div class="sv-ms-item" data-ms-item data-value="70" data-label="Potenza"><input type="checkbox"><div>Potenza</div></div>
<div class="sv-ms-item" data-ms-item data-value="71" data-label="Prato"><input type="checkbox"><div>Prato</div></div>
<div class="sv-ms-item" data-ms-item data-value="72" data-label="Ragusa"><input type="checkbox"><div>Ragusa</div></div>
<div class="sv-ms-item" data-ms-item data-value="73" data-label="Ravenna"><input type="checkbox"><div>Ravenna</div></div>
<div class="sv-ms-item" data-ms-item data-value="74" data-label="Reggio Calabria"><input type="checkbox"><div>Reggio Calabria</div></div>
<div class="sv-ms-item" data-ms-item data-value="76" data-label="Reggio Emilia"><input type="checkbox"><div>Reggio Emilia</div></div>
<div class="sv-ms-item" data-ms-item data-value="77" data-label="Rieti"><input type="checkbox"><div>Rieti</div></div>
<div class="sv-ms-item" data-ms-item data-value="78" data-label="Rimini"><input type="checkbox"><div>Rimini</div></div>
<div class="sv-ms-item" data-ms-item data-value="79" data-label="Roma"><input type="checkbox"><div>Roma</div></div>
<div class="sv-ms-item" data-ms-item data-value="80" data-label="Rovigo"><input type="checkbox"><div>Rovigo</div></div>
<div class="sv-ms-item" data-ms-item data-value="81" data-label="Salerno"><input type="checkbox"><div>Salerno</div></div>
<div class="sv-ms-item" data-ms-item data-value="82" data-label="Sassari"><input type="checkbox"><div>Sassari</div></div>
<div class="sv-ms-item" data-ms-item data-value="83" data-label="Savona"><input type="checkbox"><div>Savona</div></div>
<div class="sv-ms-item" data-ms-item data-value="84" data-label="Siena"><input type="checkbox"><div>Siena</div></div>
<div class="sv-ms-item" data-ms-item data-value="85" data-label="Siracusa"><input type="checkbox"><div>Siracusa</div></div>
<div class="sv-ms-item" data-ms-item data-value="86" data-label="Sondrio"><input type="checkbox"><div>Sondrio</div></div>
<div class="sv-ms-item" data-ms-item data-value="87" data-label="Taranto"><input type="checkbox"><div>Taranto</div></div>
<div class="sv-ms-item" data-ms-item data-value="88" data-label="Teramo"><input type="checkbox"><div>Teramo</div></div>
<div class="sv-ms-item" data-ms-item data-value="89" data-label="Terni"><input type="checkbox"><div>Terni</div></div>
<div class="sv-ms-item" data-ms-item data-value="90" data-label="Torino"><input type="checkbox"><div>Torino</div></div>
<div class="sv-ms-item" data-ms-item data-value="91" data-label="Trapani"><input type="checkbox"><div>Trapani</div></div>
<div class="sv-ms-item" data-ms-item data-value="92" data-label="Trento"><input type="checkbox"><div>Trento</div></div>
<div class="sv-ms-item" data-ms-item data-value="93" data-label="Treviso"><input type="checkbox"><div>Treviso</div></div>
<div class="sv-ms-item" data-ms-item data-value="94" data-label="Trieste"><input type="checkbox"><div>Trieste</div></div>
<div class="sv-ms-item" data-ms-item data-value="95" data-label="Udine"><input type="checkbox"><div>Udine</div></div>
<div class="sv-ms-item" data-ms-item data-value="96" data-label="Varese"><input type="checkbox"><div>Varese</div></div>
<div class="sv-ms-item" data-ms-item data-value="97" data-label="Venezia"><input type="checkbox"><div>Venezia</div></div>
<div class="sv-ms-item" data-ms-item data-value="98" data-label="Verbano-Cusio-Ossola"><input type="checkbox"><div>Verbano-Cusio-Ossola</div></div>
<div class="sv-ms-item" data-ms-item data-value="99" data-label="Vercelli"><input type="checkbox"><div>Vercelli</div></div>
<div class="sv-ms-item" data-ms-item data-value="100" data-label="Verona"><input type="checkbox"><div>Verona</div></div>
<div class="sv-ms-item" data-ms-item data-value="101" data-label="Vibo Valentia"><input type="checkbox"><div>Vibo Valentia</div></div>
<div class="sv-ms-item" data-ms-item data-value="102" data-label="Vicenza"><input type="checkbox"><div>Vicenza</div></div>
<div class="sv-ms-item" data-ms-item data-value="103" data-label="Viterbo"><input type="checkbox"><div>Viterbo</div></div>
<div class="sv-ms-item" data-ms-item data-value="104" data-label="Altro"><input type="checkbox"><div>Altro</div></div>
<div class="sv-ms-item" data-ms-item data-value="105" data-label="Fermo"><input type="checkbox"><div>Fermo</div></div>
            </div>

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
              <div class="sv-ms-item" data-ms-item data-value="1-149k" data-label="1-149k"><input type="checkbox"><div>1-149k</div></div>
              <div class="sv-ms-item" data-ms-item data-value="150-499k" data-label="150-499k"><input type="checkbox"><div>150-499k</div></div>
              <div class="sv-ms-item" data-ms-item data-value="500-999k" data-label="500-999k"><input type="checkbox"><div>500-999k</div></div>
              <div class="sv-ms-item" data-ms-item data-value="1milione" data-label="1 milione e oltre"><input type="checkbox"><div>1 milione e oltre</div></div>
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

  if (selRicerca)
  {

async function applyPanelDefaultsByRicerca(surId) {
  const uomo  = document.getElementById('sUomo');
  const donna = document.getElementById('sDonna');
  const etaDa = document.getElementById('eta_da');
  const etaA  = document.getElementById('eta_a');

  // se non c'è ricerca selezionata, pulisco demografia
  if (!surId) {
    if (uomo)  uomo.checked = false;
    if (donna) donna.checked = false;
    if (etaDa) etaDa.value = '';
    if (etaA)  etaA.value  = '';
    return;
  }

  try {
    const url = `{{ route('campionamento.panel-data', ['sur_id' => '__ID__']) }}`
      .replace('__ID__', encodeURIComponent(surId));

    const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
    if (!res.ok) return;

    const data = await res.json();

    const sex = parseInt(data.sex_target, 10);

    // regole richieste:
    // 1 => Uomo, 2 => Donna, 3 => entrambi
    if (uomo)  uomo.checked  = (sex === 1 || sex === 3);
    if (donna) donna.checked = (sex === 2 || sex === 3);

    if (etaDa) etaDa.value = (data.age1_target ?? '');
    if (etaA)  etaA.value  = (data.age2_target ?? '');
  } catch (e) {
    console.error('applyPanelDefaultsByRicerca error', e);
  }
}

  selRicerca.addEventListener('change', async () => {
    updateRicercaState();

    // ✅ AUTO-COMPILA DEMOGRAFIA
    await applyPanelDefaultsByRicerca(selRicerca.value);

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
  const rightPlaceholder = document.getElementById('right-placeholder');

    if (!window.sottocampioni.length) {
    if (rightPlaceholder) rightPlaceholder.style.display = 'block';

      if (campioneCard) campioneCard.style.display = 'none';
      listEl.innerHTML = '';
      clearDisponibili();
      updateRicercaState();
      return;
    }
    if (campioneCard) campioneCard.style.display = 'block';
    if (rightPlaceholder) rightPlaceholder.style.display = 'none';
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
                <span class="input-group-text">
                <i data-feather="mail"></i>
                </span>

                <input
                type="number"
                class="form-control sc-invite"
                min="1"
                ${Number.isFinite(sc.count) ? `max="${sc.count}"` : ``}
                value="${Number.isFinite(sc.invite) ? sc.invite : 1}"
                ${Number.isFinite(sc.count) && !CAMPIONE_FINAL ? `` : `disabled`}
                inputmode="numeric"
                >

                <button
                type="button"
                class="btn btn-outline-secondary btn-max-invite"
                ${Number.isFinite(sc.count) && !CAMPIONE_FINAL ? `` : `disabled`}
                title="Imposta al massimo disponibile"
                >
                MAX
                </button>

                <span class="input-group-text">/ ${Number.isFinite(sc.count) ? sc.count : '—'}</span>
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

    // binding: MAX button → imposta inviti al massimo disponibile
    listEl.querySelectorAll('.btn-max-invite').forEach(btn => {
    const idx = parseInt(btn.closest('.input-group').dataset.index, 10);
    btn.addEventListener('click', () => {
        const sc = window.sottocampioni[idx];
        const max = Number.isFinite(sc.count) ? sc.count : 1;
        sc.invite = max;

        const inp = btn.closest('.input-group').querySelector('.sc-invite');
        if (inp) inp.value = max;
    });
    });

  }


function showCountingLoading() {
  if (!resultsBox) return;
  resultsBox.style.display = 'block';
resultsBox.innerHTML = `
  <div class="sv-card sv-card-right">
    <div class="sv-body">
      <div class="sv-loading sv-loading-hero">
        <div class="sv-loading-badge">
          <i data-feather="activity"></i>
        </div>

        <div class="sv-loading-content">
          <div class="sv-loading-title">
            Conteggio utenti disponibili in corso
            <span class="sv-dots" aria-hidden="true"><i></i><i></i><i></i></span>
          </div>
          <div class="sv-loading-sub">
            Sto calcolando il totale e i conteggi per sottocampione.
          </div>
        </div>
      </div>
    </div>
  </div>
`;
  if (window.feather) feather.replace();
}

function hideCountingLoadingIfAny() {
  // non serve “svuotare” qui: verrà sostituito da renderTotale
}


  // === Calcolo conteggi e totale (auto) ===
  async function fetchCountsAndRender() {
    if (!selRicerca || !selRicerca.value) { clearDisponibili(); return; }
    if (!window.sottocampioni.length)     { clearDisponibili(); return; }
      showCountingLoading();

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

  // se non ho un numero, nascondo
  if (typeof total !== 'number') { clearDisponibili(); return; }

  const html = `
    <div class="sv-card sv-card-right sv-total-card">
      <div class="sv-card-header">
        <div class="sv-head">
          <div class="sv-head-left">
            <span class="sv-total-icon">
              <i data-feather="users"></i>
            </span>
            <h6 class="sv-title">Totale disponibili</h6>
          </div>
        </div>
      </div>

      <div class="sv-body">
        <div class="sv-total">
          <div class="sv-total-number sv-pop">${total}</div>
        </div>
      </div>
    </div>
  `;

  resultsBox.innerHTML = html;
  resultsBox.style.display = 'block';

  // IMPORTANTISSIMO: feather va chiamato DOPO l'html dinamico
  if (window.feather) feather.replace();
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

const modeStd = document.getElementById('mode_std');
const modeFu  = document.getElementById('mode_fu');
const chkFollowup = document.getElementById('chk-followup');

function syncFollowupFromSeg() {
  if (!chkFollowup) return;
  chkFollowup.checked = !!(modeFu && modeFu.checked);
}
if (modeStd) modeStd.addEventListener('change', syncFollowupFromSeg);
if (modeFu)  modeFu.addEventListener('change', syncFollowupFromSeg);
syncFollowupFromSeg();


</script>

<script>
document.addEventListener('DOMContentLoaded', () => {
  function closeAll(except = null) {
    document.querySelectorAll('.sv-ms.open').forEach(ms => {
      if (except && ms === except) return;
      ms.classList.remove('open');
    });
  }

  function initMultiSelect(ms) {
    const trigger = ms.querySelector('.sv-ms-trigger');
    const panel   = ms.querySelector('.sv-ms-panel');
    const search  = ms.querySelector('[data-ms-search]');
    const list    = ms.querySelector('[data-ms-list]');
    const chipsEl = ms.querySelector('[data-ms-chips]');
    const countEl = ms.querySelector('[data-ms-count]');
    const btnClear= ms.querySelector('[data-ms-clear]');
    const btnClose= ms.querySelector('[data-ms-close]');

    // select hidden (fonte unica)
    const hiddenSelect = ms.querySelector('select.sv-hidden-select');
    if (!hiddenSelect) return;

    // costruisce lista checkbox leggendo le option del select
    function buildList() {
      list.innerHTML = '';
      Array.from(hiddenSelect.options).forEach(opt => {
        const item = document.createElement('div');
        item.className = 'sv-ms-item';
        item.dataset.value = opt.value;
        item.dataset.label = opt.text;
        item.innerHTML = `<input type="checkbox"><div>${opt.text}</div>`;
        // stato check
        item.querySelector('input').checked = opt.selected;
        list.appendChild(item);

        // click riga
        item.addEventListener('click', (e) => {
          // evita doppio toggle se clicco checkbox
          if (e.target && e.target.tagName.toLowerCase() !== 'input') {
            const cb = item.querySelector('input');
            cb.checked = !cb.checked;
          }
          syncToSelect();
          renderChips();
          updateCount();
        });

        // click diretto checkbox
        item.querySelector('input').addEventListener('change', () => {
          syncToSelect();
          renderChips();
          updateCount();
        });
      });
    }

    function syncToSelect() {
      // allinea option.selected ai checkbox
      const map = new Map();
      list.querySelectorAll('.sv-ms-item').forEach(it => {
        map.set(it.dataset.value, it.querySelector('input').checked);
      });

      Array.from(hiddenSelect.options).forEach(opt => {
        opt.selected = !!map.get(opt.value);
      });

      // trigger change (se serve ad altri listener)
      hiddenSelect.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function renderChips() {
      chipsEl.innerHTML = '';
      const selected = Array.from(hiddenSelect.selectedOptions);

      selected.forEach(opt => {
        const chip = document.createElement('span');
        chip.className = 'sv-chip';
        chip.innerHTML = `
          <span>${opt.text}</span>
          <button type="button" aria-label="Rimuovi">×</button>
        `;
        chip.querySelector('button').addEventListener('click', () => {
          opt.selected = false;
          // aggiorno checkbox lista
          const it = list.querySelector(`.sv-ms-item[data-value="${CSS.escape(opt.value)}"]`);
          if (it) it.querySelector('input').checked = false;
          hiddenSelect.dispatchEvent(new Event('change', { bubbles: true }));
          renderChips();
          updateCount();
        });
        chipsEl.appendChild(chip);
      });
    }

    function updateCount() {
      const n = hiddenSelect.selectedOptions.length;
      if (countEl) countEl.textContent = String(n);
    }

    function applySearch() {
      const q = (search?.value || '').trim().toLowerCase();
      list.querySelectorAll('.sv-ms-item').forEach(it => {
        const label = (it.dataset.label || '').toLowerCase();
        it.style.display = (!q || label.includes(q)) ? '' : 'none';
      });
    }

    // open/close
    function toggleOpen() {
      const isOpen = ms.classList.contains('open');
      closeAll(ms);
      ms.classList.toggle('open', !isOpen);
      if (!isOpen && search) {
        search.value = '';
        applySearch();
        // focus input search
        setTimeout(() => search.focus(), 0);
      }
    }

    if (trigger) {
      trigger.addEventListener('click', toggleOpen);
      trigger.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggleOpen(); }
      });
    }

    // close button
    if (btnClose) btnClose.addEventListener('click', () => ms.classList.remove('open'));

    // clear
    if (btnClear) btnClear.addEventListener('click', () => {
      Array.from(hiddenSelect.options).forEach(opt => opt.selected = false);
      list.querySelectorAll('.sv-ms-item input').forEach(cb => cb.checked = false);
      hiddenSelect.dispatchEvent(new Event('change', { bubbles: true }));
      renderChips();
      updateCount();
    });

    // search
    if (search) search.addEventListener('input', applySearch);

    // click outside
// click outside (usa mousedown e cattura prima)
document.addEventListener('mousedown', (e) => {
  if (!ms.contains(e.target)) ms.classList.remove('open');
}, true);

    // init
    buildList();
    renderChips();
    updateCount();
  }

  document.querySelectorAll('.sv-ms').forEach(initMultiSelect);

  // feather icons refresh
  if (window.feather) feather.replace();
});
</script>

@endsection

