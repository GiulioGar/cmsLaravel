@extends('layouts.main')

@section('content')
<div class="container-fluid px-3">
  <div class="row g-3">
    <!-- COLONNA SINISTRA -->
    <div class="col-lg-7">
      <div class="card shadow-sm mb-3">
        <div class="card-header bg-primary text-white">
          <i class="fa fa-lightbulb"></i> CREA CONCETTO
        </div>
        <div class="card-body">
          <form action="{{ route('concept.process') }}" method="POST" class="row g-3">
            @csrf

            <div class="col-md-6">
              <label class="form-label">Tipo</label>
              <select name="tipo" class="form-select">
                <option value="0" {{ old('tipo', $tipo ?? '') == '0' ? 'selected' : '' }}>Evaluator</option>
                <option value="1" {{ old('tipo', $tipo ?? '') == '1' ? 'selected' : '' }}>Zoom</option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label">Codici Selezionabili</label>
              <input type="text" name="codici" class="form-control" placeholder="es: 1,2,3"
                     value="{{ old('codici', $codici ?? '') }}">
            </div>

            <div class="col-md-6">
              <label class="form-label">PRJ</label>
              <input type="text" name="prj" class="form-control" placeholder="Codice progetto"
                     value="{{ old('prj', $prj ?? '') }}">
            </div>

            <div class="col-md-6">
              <label class="form-label">SID</label>
              <input type="text" name="sid" class="form-control" placeholder="Codice ricerca"
                     value="{{ old('sid', $sid ?? '') }}">
            </div>

            <div class="col-12">
              <label class="form-label">Codice HTML</label>
              <textarea name="code" rows="14" class="form-control"
                        placeholder="Incolla qui il codice HTML...">{{ old('code', $code ?? '') }}</textarea>
            </div>

            <div class="col-12 text-end">
              <button type="submit" class="btn btn-primary px-4">Genera</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- COLONNA DESTRA -->
    <div class="col-lg-5">
      <div class="card shadow-sm">
        <div class="card-header bg-info text-white">
          <i class="fa fa-code"></i> RISULTATO
        </div>
        <div class="card-body" style="min-height:400px;">
          @isset($result)
            <pre style="white-space: pre-wrap; font-size: 0.85rem;">{!! $result !!}</pre>
          @else
            <p class="text-muted">Elabora un concetto per visualizzare il risultato.</p>
          @endisset
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
