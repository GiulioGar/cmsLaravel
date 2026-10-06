
            {{-- ── Sezione A: media per panel ────────────────────────────── --}}
            <div class="pq-card">
                <div class="pq-card-header pq-border-green">
                    <div class="pq-card-header-left">
                        <i class="bi bi-globe" style="font-size:18px;color:#6e904b;"></i>
                        <div>
                            <div class="pq-card-title">Valutazione media per panel</div>
                            <div class="pq-card-sub">Esclude panel Interactive — {{ $panelEsterniRollup->count() }} panel monitorati, ordinati per score medio crescente</div>
                        </div>
                    </div>
                </div>

                <div class="pq-table-wrap">
                    <table class="pq-table">
                        <thead class="pq-thead">
                            <tr>
                                <th class="pq-th">Panel</th>
                                <th class="pq-th">Score medio</th>
                                <th class="pq-th">Distribuzione</th>
                                <th class="pq-th">Ricerche</th>
                                <th class="pq-th">Interviste val.</th>
                                <th class="pq-th">Ultima val.</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($panelEsterniRollup as $row)
                        @php
                            $score    = (float)($row->score_medio ?? 0);
                            $scoreCls = $score >= 70 ? 'pq-score-high' : ($score >= 50 ? 'pq-score-accept' : 'pq-score-low');
                            $tot      = max(1, $row->regolari + $row->incerte + $row->anomale);
                            $pctR     = round($row->regolari / $tot * 100);
                            $pctI     = round($row->incerte  / $tot * 100);
                            $pctA     = 100 - $pctR - $pctI;
                        @endphp
                        <tr class="pq-row">
                            <td class="pq-td">
                                <span class="pq-panel-name-badge"><i class="bi bi-globe"></i>{{ $row->panel }}</span>
                            </td>
                            <td class="pq-td">
                                <span class="pq-score {{ $scoreCls }}">
                                    {{ $row->score_medio ?? '—' }}
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
                                    <span>{{ $row->regolari }} reg</span>
                                    <span>{{ $row->incerte }} inc</span>
                                    <span>{{ $row->anomale }} ano</span>
                                </div>
                            </td>
                            <td class="pq-td pq-td-muted">{{ $row->ricerche }}</td>
                            <td class="pq-td pq-td-muted">{{ $row->interviste }}</td>
                            <td class="pq-td pq-td-muted">
                                {{ $row->ultima_val ? \Carbon\Carbon::parse($row->ultima_val)->format('d/m/Y') : '—' }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="pq-empty">Nessun dato di qualità disponibile per panel esterni.</td>
                        </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- ── Sezione B: dettaglio per ricerca ──────────────────────── --}}
            <div class="pq-card">
                <div class="pq-card-header pq-border-blue">
                    <div class="pq-card-header-left">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="oklch(45% 0.12 255)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                        <div>
                            <div class="pq-card-title">Dettaglio per ricerca</div>
                            <div class="pq-card-sub">{{ $panelEsterniPerRicerca->count() }} combinazioni ricerca/panel — ordinate per score medio crescente</div>
                        </div>
                    </div>
                </div>

                <div class="pq-filters">
                    <input type="text" class="pq-filter-input" id="fltPanelEstSearch"
                           placeholder="Cerca per PRJ o SID…">
                    <select class="pq-filter-select" id="fltPanelEstPanel">
                        <option value="">Tutti i panel</option>
                        @foreach($panelEsterniRollup as $row)
                            <option value="{{ strtolower($row->panel) }}">{{ $row->panel }}</option>
                        @endforeach
                    </select>
                    <span class="pq-filter-count" id="panelEstVisibili">{{ $panelEsterniPerRicerca->count() }} righe</span>
                </div>

                <div class="pq-table-wrap">
                    <table class="pq-table" id="tblPanelEst">
                        <thead class="pq-thead">
                            <tr>
                                <th class="pq-th">PRJ / SID</th>
                                <th class="pq-th">Descrizione</th>
                                <th class="pq-th">Panel</th>
                                <th class="pq-th">Score medio</th>
                                <th class="pq-th">Distribuzione</th>
                                <th class="pq-th">Interviste val.</th>
                                <th class="pq-th">Ultima val.</th>
                                <th class="pq-th"></th>
                            </tr>
                        </thead>
                        <tbody id="bodyPanelEst">
                        @forelse($panelEsterniPerRicerca as $r)
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
                            data-sid="{{ strtolower($r->sid) }}"
                            data-panel="{{ strtolower($r->panel) }}">
                            <td class="pq-td">
                                <div class="pq-td-mono" style="font-size:11px;color:oklch(50% 0.02 250);">{{ $r->prj }}</div>
                                <div class="pq-td-mono fw-semibold">{{ $r->sid }}</div>
                            </td>
                            <td class="pq-td" style="max-width:220px;">
                                <div style="font-weight:500;color:oklch(25% 0.02 250);">{{ $r->description ?? '—' }}</div>
                            </td>
                            <td class="pq-td">
                                <span class="pq-panel-name-badge"><i class="bi bi-globe"></i>{{ $r->panel }}</span>
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
                            <td colspan="8" class="pq-empty">Nessuna ricerca con dati di qualità per panel esterni.</td>
                        </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div id="panelEstPaginator" class="pq-paginator-wrap"></div>
            </div>

