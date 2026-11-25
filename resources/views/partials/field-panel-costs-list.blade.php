@if(empty($panelCosts))
    <div class="fc-empty-inline">
        <div class="fc-empty-icon sm">
            <i class="fa-solid fa-coins"></i>
        </div>
        <div>
            <div class="fc-empty-title sm">Nessun panel esterno coinvolto</div>
            <div class="fc-empty-subtitle sm">I costi si possono impostare solo per i panel esterni presenti nella ricerca.</div>
        </div>
    </div>
@else
    <div class="fc-cost-list" id="panelCostsListBody">
        @foreach ($panelCosts as $row)
            <div class="fc-cost-card" data-panel-name="{{ $row['panel_name'] }}">
                <div class="fc-cost-card-head">
                    <span class="fc-cost-panel-label"><i class="fa-solid fa-building-user me-1"></i>{{ $row['panel_name'] }}</span>
                    <span class="fc-cost-complete-badge"><i class="fa-solid fa-circle-check me-1"></i>{{ number_format($row['complete'], 0, ',', '.') }} complete</span>
                </div>

                <div class="fc-cost-card-body">
                    <div class="fc-cost-field">
                        <label class="fc-redirect-import-label">CPI (€)</label>
                        <input type="number" step="0.01" min="0"
                               class="form-control form-control-sm fc-cost-input-cpi"
                               data-panel="{{ $row['panel_name'] }}"
                               placeholder="es. 3,50"
                               value="{{ $row['cpi'] !== null ? $row['cpi'] : '' }}"
                               {{ $row['costo_totale'] !== null ? 'disabled' : '' }}>
                        @if($row['cpi_is_derived'] && $row['cpi_display'] !== null)
                            <span class="fc-cost-derived-hint">calcolato: {{ number_format($row['cpi_display'], 2, ',', '.') }} €</span>
                        @endif
                    </div>

                    <div class="fc-cost-field">
                        <label class="fc-redirect-import-label">Costo totale (€)</label>
                        <input type="number" step="0.01" min="0"
                               class="form-control form-control-sm fc-cost-input-totale"
                               data-panel="{{ $row['panel_name'] }}"
                               placeholder="es. 450,00"
                               value="{{ $row['costo_totale'] !== null ? $row['costo_totale'] : '' }}"
                               {{ $row['cpi'] !== null ? 'disabled' : '' }}>
                        @if($row['costo_is_derived'] && $row['costo_display'] !== null)
                            <span class="fc-cost-derived-hint">calcolato: {{ number_format($row['costo_display'], 2, ',', '.') }} €</span>
                        @endif
                    </div>

                    <div class="fc-cost-field">
                        <label class="fc-redirect-import-label">Referente <span class="fc-cost-optional">(opzionale)</span></label>
                        <input type="text" class="form-control form-control-sm fc-cost-input-referente"
                               data-panel="{{ $row['panel_name'] }}"
                               placeholder="es. Mario Rossi"
                               value="{{ $row['referente'] }}">
                    </div>

                    <div class="fc-cost-field fc-cost-field-note">
                        <label class="fc-redirect-import-label">Note <span class="fc-cost-optional">(opzionale)</span></label>
                        <textarea class="form-control form-control-sm fc-cost-input-note"
                                  data-panel="{{ $row['panel_name'] }}"
                                  rows="2" placeholder="Note interne">{{ $row['note'] }}</textarea>
                    </div>
                </div>

                <div class="fc-cost-card-actions">
                    <span class="fc-redirect-row-status" data-panel-status="{{ $row['panel_name'] }}"></span>
                    <button type="button" class="btn btn-outline-primary btn-sm fc-cost-save-row" data-panel="{{ $row['panel_name'] }}">
                        <i class="fa-solid fa-floppy-disk me-1"></i>Salva
                    </button>
                    <button type="button" class="btn btn-outline-danger btn-sm fc-cost-delete-row" data-panel="{{ $row['panel_name'] }}">
                        <i class="fa-solid fa-trash me-1"></i>Elimina
                    </button>
                </div>
            </div>
        @endforeach
    </div>
@endif
