@if(empty($redirections))
    <div class="fc-empty-inline">
        <div class="fc-empty-icon sm">
            <i class="fa-solid fa-link"></i>
        </div>
        <div>
            <div class="fc-empty-title sm">Nessuna redirection impostata</div>
            <div class="fc-empty-subtitle sm">Il file config.json di questa ricerca non contiene redirection configurate.</div>
        </div>
    </div>
@else
    @php
        $redirectionGroups = [];
        foreach ($redirections as $redirect) {
            $groupKey = $redirect['parameter_name'] . '|' . $redirect['parameter_value'];

            if (!isset($redirectionGroups[$groupKey])) {
                $isPanel = $redirect['parameter_name'] === 'pan';
                $groupLabel = $isPanel
                    ? ($panelNamesMap[$redirect['parameter_value']] ?? ('Panel #' . $redirect['parameter_value']))
                    : ($redirect['parameter_name'] . '=' . $redirect['parameter_value']);

                $redirectionGroups[$groupKey] = [
                    'label' => $groupLabel,
                    'parameter_name' => $redirect['parameter_name'],
                    'parameter_value' => $redirect['parameter_value'],
                    'items' => [],
                ];
            }

            $redirectionGroups[$groupKey]['items'][] = $redirect;
        }
    @endphp

    <div class="fc-redirect-list" id="redirectionsTableBody">
        @foreach ($redirectionGroups as $group)
            <div class="fc-redirect-group">
                <div class="fc-redirect-group-head">
                    <span class="fc-redirect-group-label"><i class="fa-solid fa-layer-group me-1"></i>{{ $group['label'] }}</span>
                    <button type="button" class="btn btn-sm btn-outline-danger fc-redirect-delete-group"
                            data-parameter-name="{{ $group['parameter_name'] }}"
                            data-parameter-value="{{ $group['parameter_value'] }}"
                            data-group-label="{{ $group['label'] }}">
                        <i class="fa-solid fa-trash me-1"></i>Elimina {{ $group['label'] }}
                    </button>
                </div>

                @foreach ($group['items'] as $redirect)
                    @php
                        if ($redirect['exit_code'] === 3) {
                            $exitClass = 'is-ok';
                        } elseif ($redirect['exit_code'] === 4) {
                            $exitClass = 'is-warn';
                        } elseif ($redirect['exit_code'] === 5) {
                            $exitClass = 'is-danger';
                        } else {
                            $exitClass = 'is-neutral';
                        }
                    @endphp
                    <div class="fc-redirect-card" data-redirect-index="{{ $redirect['index'] }}">
                        <span class="fc-redirect-exit-pill {{ $exitClass }}">{{ $redirect['exit_label'] }}</span>
                        <input type="text" class="form-control fc-redirect-input"
                               data-index="{{ $redirect['index'] }}"
                               value="{{ $redirect['redirect_to'] }}"
                               spellcheck="false">
                        <button type="button" class="btn btn-outline-primary fc-redirect-save-row" data-index="{{ $redirect['index'] }}" title="Salva questo link">
                            <i class="fa-solid fa-floppy-disk"></i>
                        </button>
                        <span class="fc-redirect-row-status" data-index="{{ $redirect['index'] }}"></span>
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>
@endif
