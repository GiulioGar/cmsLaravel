            <div class="pq-card">

                <div class="pq-card-header pq-border-amber">
                    <div class="pq-card-header-left">
                        <i class="bi bi-copy" style="font-size:18px;color:oklch(50% 0.14 55);"></i>
                        <div>
                            <div class="pq-card-title" style="color:oklch(38% 0.12 55);">Segnalazioni duplicati</div>
                            <div class="pq-card-sub">
                                {{ $nUidDuplicati }} UID segnalati in {{ $nRicercheDuplicati }} {{ $nRicercheDuplicati === 1 ? 'ricerca' : 'ricerche' }}
                            </div>
                        </div>
                    </div>
                </div>

                @if($duplicati->isEmpty())
                    <div class="pq-empty">Nessuna segnalazione di duplicati.</div>
                @else

                @php
                    $activeByUid = $duplicati->pluck('active', 'uid');
                    $activeLabel = function ($active) {
                        switch ((int) $active) {
                            case 1: return 'Attivo';
                            case 8: return 'Bannato/Sospeso';
                            case 9: return 'Cancellato';
                            default: return 'Non attivo';
                        }
                    };
                @endphp

                <div class="pq-filters">
                    <input type="text" class="pq-filter-input" id="fltDuplicatiSearch"
                           placeholder="Cerca per UID o nome…">
                    <span class="pq-filter-count" id="duplicatiVisibili">{{ $duplicati->count() }} panelisti</span>
                </div>

                <div class="pq-table-wrap">
                    <table class="pq-table" id="tblDuplicati">
                        <thead class="pq-thead">
                            <tr>
                                <th class="pq-th">UID</th>
                                <th class="pq-th">Nome</th>
                                <th class="pq-th">Segnalazioni</th>
                                <th class="pq-th">Simile a</th>
                                <th class="pq-th" style="white-space:nowrap;">Ultima segn.</th>
                            </tr>
                        </thead>
                        <tbody id="bodyDuplicati">
                        @foreach($duplicati as $d)
                        @php
                            $ricercheTooltip = implode('<br>', array_map(
                                fn($key, $desc) => '<span style="font-family:monospace;font-size:11px;">' . e($key) . '</span>'
                                    . ($desc && $desc !== $key ? ' &mdash; ' . e($desc) : ''),
                                array_keys($d['ricerche']),
                                array_values($d['ricerche'])
                            ));
                            $isInactive = $d['active'] !== null && (int) $d['active'] !== 1;
                        @endphp
                        <tr class="pq-row"
                            data-uid="{{ strtolower($d['uid']) }}"
                            data-name="{{ strtolower($d['full_name'] ?? '') }}">
                            <td class="pq-td">
                                <a href="{{ url('user/' . $d['uid']) }}" target="_blank" class="pq-user-link"
                                   title="{{ $isInactive ? $activeLabel($d['active']) : '' }}">
                                    <span class="pq-td-mono" style="font-size:11px;{{ $isInactive ? 'color:#dc2626;font-weight:700;' : '' }}">{{ $d['uid'] }}</span>
                                </a>
                            </td>
                            <td class="pq-td">
                                <span style="font-size:13px;{{ $isInactive ? 'color:#dc2626;font-weight:600;' : '' }}">{{ $d['full_name'] ?: '—' }}</span>
                            </td>
                            <td class="pq-td">
                                <span data-bs-toggle="tooltip" data-bs-html="true"
                                      data-bs-placement="right"
                                      title="{{ $ricercheTooltip }}"
                                      style="display:inline-flex;align-items:center;gap:5px;cursor:default;">
                                    <span style="font-size:15px;font-weight:700;color:oklch(42% 0.14 55);">{{ $d['segnalazioni'] }}</span>
                                    <i class="bi bi-info-circle" style="font-size:11px;color:oklch(60% 0.08 250);"></i>
                                </span>
                            </td>
                            <td class="pq-td">
                                @php
                                    $simList  = $d['simile_a'];
                                    $simTotal = count($simList);
                                    $popLines = [];
                                    foreach ($simList as $sUid => $cnt) {
                                        $sActive = $activeByUid[$sUid] ?? null;
                                        $sInactive = $sActive !== null && (int) $sActive !== 1;
                                        $sColor = $sInactive ? '#dc2626' : '#1a6fc4';
                                        $line = '<a href="' . url('user/' . $sUid) . '" target="_blank"'
                                              . ' style="font-family:monospace;font-size:11px;color:' . $sColor . ';text-decoration:none;' . ($sInactive ? 'font-weight:700;' : '') . '">'
                                              . e($sUid) . '</a>';
                                        if ($cnt > 1) {
                                            $line .= ' <span style="font-size:10px;font-weight:700;color:#b45309;">×' . $cnt . '</span>';
                                        }
                                        $popLines[] = $line;
                                    }
                                    $popContent = implode('<br>', $popLines);
                                @endphp
                                <span class="dup-sim-trigger" tabindex="0"
                                      data-bs-toggle="popover"
                                      data-bs-trigger="click"
                                      data-bs-html="true"
                                      data-bs-placement="left"
                                      data-bs-content="{{ $popContent }}"
                                      style="display:inline-flex;align-items:center;gap:5px;cursor:pointer;padding:3px 8px;background:oklch(95% 0.03 250);border:1px solid oklch(85% 0.05 250);border-radius:5px;font-size:12px;color:oklch(35% 0.10 255);white-space:nowrap;">
                                    <i class="bi bi-people-fill" style="font-size:11px;opacity:.7;"></i>
                                    Simile a <strong style="margin-left:2px;">{{ $simTotal }}</strong>&nbsp;{{ $simTotal === 1 ? 'utente' : 'utenti' }}
                                </span>
                            </td>
                            <td class="pq-td pq-td-muted" style="white-space:nowrap;font-size:12px;">
                                {{ \Carbon\Carbon::parse($d['ultima'])->format('d/m/Y H:i') }}
                            </td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div id="duplicatiPaginator" class="pq-paginator-wrap"></div>

                @endif
            </div>
