<!-- MODALE DI DOWNLOAD -->
<div class="modal fade" id="downloadModal" tabindex="-1" aria-labelledby="downloadModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="downloadModalLabel">Scarica il file</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
            </div>
            <div class="modal-body">
                <p>Seleziona il panel per il quale vuoi scaricare il file:</p>

                <!-- Opzioni Panel -->
                <form id="downloadForm" action="{{ route('download.csv') }}" method="GET">
                    <input type="hidden" name="prj" value="{{ $prj }}">
                    <input type="hidden" name="sid" value="{{ $sid }}">

                    @foreach($panelCounts as $panelName => $panelRow)
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="panel" id="panel_{{ $loop->index }}" value="{{ $panelName }}" required>
                            <label class="form-check-label" for="panel_{{ $loop->index }}">
                                {{ $panelName }}
                            </label>
                        </div>
                    @endforeach
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
                <button type="submit" class="btn btn-primary" form="downloadForm">Scarica CSV</button>
            </div>
        </div>
    </div>
</div>

<!-- MODALE PER RESET BLOCCATE -->
<div class="modal fade" id="resetBloccateModal" tabindex="-1" aria-labelledby="resetBloccateModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="resetBloccateModalLabel">Reset Interviste Bloccate</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
            </div>
            <div class="modal-body">
                <p>Numero di interviste bloccate per questa ricerca: <strong>{{ $counts['bloccate'] }}</strong></p>
                <p>Vuoi resettare tutte le interviste bloccate?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                <button type="button" class="btn btn-warning" id="resetBloccateBtn">Reset</button>
            </div>
        </div>
    </div>
</div>
