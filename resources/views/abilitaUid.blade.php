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
              <select name="sid" id="sid" class="form-select" required>
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
                <button type="button" id="btn-copy-guest-link" class="btn btn-outline-secondary btn-sm">
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

    <div class="col-12">
    <label class="form-label">Variabili aggiuntive</label>
    <input
        type="text"
        name="extra_vars"
        class="form-control"
        placeholder="Esempio: lang=1;test=1"
    >
    <div class="form-text">
        Usa ; come separatore. Es: lang=1;test=1 → &lang=1&test=1
    </div>
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
                @if(!empty($totalGeneratedLinks) && !empty($previewLimit) && $totalGeneratedLinks > $previewLimit)
                <div class="alert alert-warning mb-3">
                    Generati <strong>{{ $totalGeneratedLinks }}</strong> link.
                    In anteprima vengono mostrati solo i primi <strong>{{ $previewLimit }}</strong> per motivi di performance.
                </div>
                @endif

            <div class="au-toolbar">
                <button
                    type="button"
                    id="btn-copy-links"
                    class="btn btn-sm btn-outline-primary"
                    @if(!empty($exportToken)) data-copy-token="{{ $exportToken }}" @endif
                >
                    <i class="fa-regular fa-copy"></i> Copia Tutti
                </button>

                @if(!empty($exportToken))
                    <a
                        href="{{ route('abilita.uid.download-links', ['token' => $exportToken, 'filename' => $exportFilename ?? 'links_export.csv']) }}"
                        id="btn-export-csv"
                        class="btn btn-sm btn-outline-success"
                    >
                        <i class="fa-solid fa-file-csv"></i> Esporta CSV
                    </a>
                @endif
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
              <select id="sidRight" class="form-select">
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
             <button id="btn-refresh-results" type="button" class="btn btn-outline-secondary">
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
            <button id="btn-enable-uids" class="btn btn-success">
              <i class="fa-solid fa-check"></i> Abilita UID
            </button>
            <button id="btn-reset-iids" type="button" class="btn btn-danger" disabled>
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
               <button type="button" id="btn-search-record" class="btn btn-outline-primary">
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
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-danger btn-delete-panel"
                        data-panel-id="{{ $p->id }}"
                        title="Elimina"
                    >
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
    window.AbilitaUidConfig = {
        surveys: @json($surveys),
        csrfToken: '{{ csrf_token() }}',
        urls: {
            generate: '{{ route('abilita.uid.genera') }}',
            storePanel: '{{ url('/panel/store') }}',
            deletePanelBase: '{{ url('/panel/delete') }}',
            showData: '{{ url('/abilita-uid/show-data') }}',
            enableUids: '{{ url('/abilita-uid/enable-uids') }}',
            resetIids: '{{ url('/abilita-uid/reset-iids') }}',
            previewResetIids: '{{ url('/abilita-uid/preview-reset-iids') }}',
            searchRecords: '{{ route('abilita.uid.search-records') }}',
            copyLinksBase: '{{ url('/abilita-uid/copy-links') }}'
        }
    };
</script>

<script src="{{ asset('js/abilita-uid.js') }}"></script>
@endsection
