        @php
            $panelBadgesFn = function($panelInterno, $panelEsterno, $nomeEsterno = null) {
                $icons = '';
                if ((int)$panelInterno > 0) {
                    $icons .= '<i class="bi bi-house-fill pq-panel-icon pq-panel-int"
                                  data-bs-toggle="tooltip" data-bs-placement="top"
                                  title="Interactive"></i>';
                }
                if ((int)$panelEsterno > 0) {
                    $label = htmlspecialchars($nomeEsterno ?? 'Esterno', ENT_QUOTES);
                    $icons .= '<i class="bi bi-airplane-fill pq-panel-icon pq-panel-ext"
                                  data-bs-toggle="tooltip" data-bs-placement="top"
                                  title="' . $label . '"></i>';
                }
                return $icons
                    ? '<div class="pq-panel-icons">' . $icons . '</div>'
                    : '<span class="pq-td-muted">—</span>';
            };
        @endphp

            {{-- ── Sezione A: con dati ────────────────────────────────────── --}}
            <div class="pq-card">
                <div class="pq-card-header pq-border-green">
                    <div class="pq-card-header-left">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="oklch(45% 0.12 255)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                        <div>
                            <div class="pq-card-title">Ricerche con dati qualità</div>
                            <div class="pq-card-sub">Panel Interactive — {{ $ricercheConDati->count() }} ricerche, ordinate per score medio crescente</div>
                        </div>
                    </div>
                </div>

                <div class="pq-filters">
                    <input type="text" class="pq-filter-input" id="fltConDatiSearch"
                           placeholder="Cerca per PRJ o SID…">
                    <span class="pq-filter-count" id="conDatiVisibili">{{ $ricercheConDati->count() }} ricerche</span>
                </div>

                <div class="pq-table-wrap">
                    <table class="pq-table" id="tblConDati">
                        <thead class="pq-thead">
                            <tr>
                                <th class="pq-th">PRJ / SID</th>
                                <th class="pq-th">Descrizione</th>
                                <th class="pq-th">Panel</th>
                                <th class="pq-th">Stato</th>
                                <th class="pq-th">Score medio</th>
                                <th class="pq-th">Distribuzione</th>
                                <th class="pq-th">Interviste val.</th>
                                <th class="pq-th">Ultima val.</th>
                                <th class="pq-th"></th>
                            </tr>
                        </thead>
                        <tbody id="bodyConDati">
                        @forelse($ricercheConDati as $r)
                        @php
                            $score    = (float)($r->score_medio ?? 0);
                            $scoreCls = $score >= 70 ? 'pq-score-high' : ($score >= 50 ? 'pq-score-accept' : 'pq-score-low');
                            $tot      = max(1, $r->regolari + $r->incerte + $r->anomale);
                            $pctR     = round($r->regolari / $tot * 100);
                            $pctI     = round($r->incerte  / $tot * 100);
                            $pctA     = 100 - $pctR - $pctI;
                        @endphp
                        <tr class="pq-row"
                            data-prj="{{ strtolower($r->prj) }}"
                            data-sid="{{ strtolower($r->sid) }}">
                            <td class="pq-td">
                                <div class="pq-td-mono" style="font-size:11px;color:oklch(50% 0.02 250);">{{ $r->prj }}</div>
                                <div class="pq-td-mono fw-semibold">{{ $r->sid }}</div>
                            </td>
                            <td class="pq-td" style="max-width:240px;">
                                <div style="font-weight:500;color:oklch(25% 0.02 250);">{{ $r->description ?? '—' }}</div>
                            </td>
                            <td class="pq-td">{!! $panelBadgesFn($r->panel_interno ?? 0, $r->panel_esterno ?? 0, $r->panel_nome_esterno ?? null) !!}</td>
                            <td class="pq-td">
                                @if(($r->stato ?? 1) == 0)
                                    <span class="pq-stato-aperta"><i class="bi bi-circle-fill me-1" style="font-size:7px;"></i>Aperta</span>
                                @else
                                    <span class="pq-stato-chiusa"><i class="bi bi-check-circle me-1"></i>Chiusa</span>
                                @endif
                            </td>
                            <td class="pq-td">
                                <span class="pq-score {{ $scoreCls }}">
                                    {{ $r->score_medio ?? '—' }}
                                    <span class="pq-score-denom">/100</span>
                                </span>
                            </td>
                            <td class="pq-td">
                                <div class="pq-distrib">
                                    <div class="pq-distrib-seg-high" style="width:{{ $pctR }}%;"></div>
                                    <div class="pq-distrib-seg-mid"  style="width:{{ $pctI }}%;"></div>
                                    <div class="pq-distrib-seg-low"  style="width:{{ $pctA }}%;"></div>
                                </div>
                                <div class="pq-distrib-label">
                                    <span>{{ $r->regolari }} reg</span>
                                    <span>{{ $r->incerte }} inc</span>
                                    <span>{{ $r->anomale }} ano</span>
                                </div>
                            </td>
                            <td class="pq-td pq-td-muted">{{ $r->interviste_valutate }}</td>
                            <td class="pq-td pq-td-muted">
                                {{ $r->ultima_val ? \Carbon\Carbon::parse($r->ultima_val)->format('d/m/Y') : '—' }}
                            </td>
                            <td class="pq-td">
                                <a href="{{ url('fieldQuality') }}?prj={{ urlencode($r->prj) }}&sid={{ urlencode($r->sid) }}"
                                   target="_blank"
                                   class="btn btn-sm btn-outline-secondary" style="font-size:11px;padding:3px 9px;">
                                    Dettaglio
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="pq-empty">Nessuna ricerca con dati di qualità.</td>
                        </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div id="conDatiPaginator" class="pq-paginator-wrap"></div>
            </div>

            {{-- ── Sezione B: senza dati (Interactive + Esterno unificati) ── --}}
            <div class="pq-card">
                <div class="pq-card-header pq-border-amber">
                    <div class="pq-card-header-left">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="oklch(45% 0.12 80)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        <div>
                            <div class="pq-card-title" style="color:oklch(40% 0.12 80);">Ricerche senza dati qualità</div>
                            <div class="pq-card-sub">{{ $ricerceSenzaDati->count() }} ricerche senza valutazione nel {{ $annoSenzaDati }} — aprire fieldQuality per calcolarla</div>
                        </div>
                    </div>
                    <form method="GET" action="{{ route('panelQuality.index') }}" class="d-flex align-items-center gap-2">
                        <input type="hidden" name="#tab-ricerche" value="1">
                        <label style="font-size:12px;color:oklch(50% 0.02 250);margin:0;">Anno:</label>
                        <select name="anno_senza_dati" class="pq-filter-select" style="padding:5px 10px;"
                                onchange="this.form.submit()">
                            @foreach($anniDisponibili as $anno)
                                <option value="{{ $anno }}" {{ $anno == $annoSenzaDati ? 'selected' : '' }}>
                                    {{ $anno }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                </div>

                @if($ricerceSenzaDati->count() > 0)
                <div class="pq-filters">
                    <input type="text" class="pq-filter-input" id="fltSenzaDatiSearch"
                           placeholder="Cerca per PRJ o SID…">
                    <div class="btn-group btn-group-sm ms-2" role="group">
                        <button type="button" class="btn btn-outline-secondary pq-tipo-btn active" data-tipo="tutti">Tutti</button>
                        <button type="button" class="btn btn-outline-secondary pq-tipo-btn" data-tipo="interactive">
                            <i class="bi bi-house-fill me-1"></i>Interactive
                        </button>
                        <button type="button" class="btn btn-outline-secondary pq-tipo-btn" data-tipo="esterno">
                            <i class="bi bi-airplane-fill me-1"></i>Esterno
                        </button>
                    </div>
                    <span class="pq-filter-count" id="senzaDatiVisibili">{{ $ricerceSenzaDati->count() }} ricerche</span>
                </div>

                <div class="pq-table-wrap">
                    <table class="pq-table" id="tblSenzaDati">
                        <thead class="pq-thead">
                            <tr>
                                <th class="pq-th">PRJ / SID</th>
                                <th class="pq-th">Descrizione</th>
                                <th class="pq-th">Panel</th>
                                <th class="pq-th">Stato</th>
                                <th class="pq-th">Completate</th>
                                <th class="pq-th">Target</th>
                                <th class="pq-th">Data inizio</th>
                                <th class="pq-th"></th>
                            </tr>
                        </thead>
                        <tbody id="bodySenzaDati">
                        @foreach($ricerceSenzaDati as $r)
                        <tr class="pq-row"
                            data-prj="{{ strtolower($r->prj) }}"
                            data-sid="{{ strtolower($r->sur_id) }}"
                            data-int="{{ $r->complete_int > 0 ? 1 : 0 }}"
                            data-ext="{{ $r->complete_ext > 0 ? 1 : 0 }}">
                            <td class="pq-td">
                                <div class="pq-td-mono" style="font-size:11px;color:oklch(50% 0.02 250);">{{ $r->prj }}</div>
                                <div class="pq-td-mono fw-semibold">{{ $r->sur_id }}</div>
                            </td>
                            <td class="pq-td" style="max-width:220px;">
                                <div style="font-weight:500;color:oklch(25% 0.02 250);">{{ $r->description ?? '—' }}</div>
                            </td>
                            <td class="pq-td">{!! $panelBadgesFn($r->complete_int, $r->complete_ext, $r->panel_nome_esterno ?? null) !!}</td>
                            <td class="pq-td">
                                @if(($r->stato ?? 1) == 0)
                                    <span class="pq-stato-aperta"><i class="bi bi-circle-fill me-1" style="font-size:7px;"></i>Aperta</span>
                                @else
                                    <span class="pq-stato-chiusa"><i class="bi bi-check-circle me-1"></i>Chiusa</span>
                                @endif
                            </td>
                            <td class="pq-td pq-td-muted">{{ $r->complete ?? '—' }}</td>
                            <td class="pq-td pq-td-muted">{{ $r->goal ?? '—' }}</td>
                            <td class="pq-td pq-td-muted">
                                {{ $r->sur_date ? \Carbon\Carbon::parse($r->sur_date)->format('d/m/Y') : '—' }}
                            </td>
                            <td class="pq-td">
                                <a href="{{ url('fieldQuality') }}?prj={{ urlencode($r->prj) }}&sid={{ urlencode($r->sur_id) }}"
                                   target="_blank"
                                   class="btn btn-sm btn-outline-warning" style="font-size:11px;padding:3px 9px;">
                                    Calcola qualità
                                </a>
                            </td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div id="senzaDatiPaginator" class="pq-paginator-wrap"></div>
                @else
                    <div class="pq-empty">Tutte le ricerche hanno già dati di qualità nel {{ $annoSenzaDati }}.</div>
                @endif

            </div>

