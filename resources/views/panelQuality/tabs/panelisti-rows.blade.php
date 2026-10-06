                        @php
                            $avatarPalette = ['#3b82f6','#8b5cf6','#10b981','#f59e0b','#ef4444','#ec4899','#14b8a6','#f97316'];
                        @endphp
                        @forelse($panelistiTable as $p)
                        @php
                            $score    = (float)($p->score_medio ?? 0);
                            $scoreCls = $score >= 70 ? 'pq-score-high' : ($score >= 50 ? 'pq-score-accept' : 'pq-score-low');
                            $tot      = max(1, $p->regolari + $p->incerte + $p->anomale);
                            $pctR     = round($p->regolari / $tot * 100);
                            $pctI     = round($p->incerte  / $tot * 100);
                            $pctA     = 100 - $pctR - $pctI;
                            $tierPrev = $p->anomale >= $p->regolari && $p->anomale >= $p->incerte
                                        ? 'anomala'
                                        : ($p->incerte >= $p->regolari ? 'incerta' : 'regolare');
                            $nameParts = explode(' ', trim($p->full_name));
                            $initials  = strtoupper(
                                substr($nameParts[0] ?? $p->uid, 0, 1) .
                                substr(end($nameParts) ?: '', 0, 1)
                            );
                            $avatarBg = $avatarPalette[abs(crc32($p->uid)) % count($avatarPalette)];
                            $nameDisplay = trim($p->full_name) ?: '—';
                        @endphp
                        <tr class="pq-row"
                            data-uid="{{ strtolower($p->uid) }}"
                            data-name="{{ strtolower($nameDisplay) }}"
                            data-tier="{{ $tierPrev }}"
                            data-score="{{ $score }}"
                            data-interviste="{{ $p->interviste }}">
                            <td class="pq-td">
                                <a href="{{ url('user/' . $p->uid) }}" target="_blank" class="pq-user-cell pq-user-link">
                                    <div class="pq-avatar-mini" style="background:{{ $avatarBg }};">{{ $initials }}</div>
                                    <div>
                                        <div class="pq-user-name">{{ $nameDisplay }}</div>
                                        <div class="pq-user-uid">{{ $p->uid }}</div>
                                    </div>
                                </a>
                            </td>
                            <td class="pq-td">
                                <span class="pq-score {{ $scoreCls }}">
                                    {{ $p->score_medio ?? '—' }}
                                    <span class="pq-score-denom">/100</span>
                                </span>
                            </td>
                            <td class="pq-td">
                                <span class="pq-tier pq-tier-{{ $tierPrev }}">{{ $tierPrev }}</span>
                            </td>
                            <td class="pq-td">
                                <div class="pq-distrib">
                                    <div class="pq-distrib-seg-high" style="width:{{ $pctR }}%;"></div>
                                    <div class="pq-distrib-seg-mid"  style="width:{{ $pctI }}%;"></div>
                                    <div class="pq-distrib-seg-low"  style="width:{{ $pctA }}%;"></div>
                                </div>
                                <div class="pq-distrib-label">
                                    <span>{{ $p->regolari }} reg</span>
                                    <span>{{ $p->incerte }} inc</span>
                                    <span>{{ $p->anomale }} ano</span>
                                </div>
                            </td>
                            <td class="pq-td pq-td-muted">{{ $p->interviste }}</td>
                            <td class="pq-td pq-td-muted">{{ number_format($p->bytes) }}</td>
                            <td class="pq-td">
                                @if($p->malus_count > 0)
                                    <span class="badge bg-danger">{{ $p->malus_count }}</span>
                                @else
                                    <span class="pq-td-muted">—</span>
                                @endif
                            </td>
                            <td class="pq-td pq-td-muted">
                                {{ $p->ultima_val ? \Carbon\Carbon::parse($p->ultima_val)->format('d/m/Y') : '—' }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="pq-empty">Nessun dato di qualità disponibile.</td>
                        </tr>
                        @endforelse
