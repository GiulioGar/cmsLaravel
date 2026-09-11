@extends('layouts.main')


@section('head')
<link rel="stylesheet" href="{{ asset('css/userProfile.css') }}">
@endsection

@section('content')

<div class="container-fluid mt-3">

    <div class="row g-3">

        {{-- ===== COLONNA SINISTRA: Hero + Informazioni personali ===== --}}
        <div class="col-lg-7">
        <div class="row g-3">

        {{-- ===== HERO PROFILO ===== --}}
        <div class="col-12">
            <div class="card up-hero-card">
                <div class="card-body up-hero-body">
                @php
                    $nameParts  = array_values(array_filter(explode(' ', trim($user->full_name ?? ''))));
                    $initials   = count($nameParts) >= 2
                        ? strtoupper(substr($nameParts[0], 0, 1) . substr(end($nameParts), 0, 1))
                        : strtoupper(substr($nameParts[0] ?? $user->user_id ?? '?', 0, 2));
                    $avatarPalette = ['#3b82f6','#8b5cf6','#10b981','#f59e0b','#ef4444','#ec4899','#14b8a6','#f97316'];
                    $avatarBg   = $avatarPalette[abs(crc32($user->user_id ?? '')) % count($avatarPalette)];
                    $cityStr    = trim(($user->province_name ?? '') . (!empty($user->province_id) ? ' (' . $user->province_id . ')' : ''));
                @endphp

                {{-- Identità (sinistra) --}}
                <div class="up-hero-identity">
                    <div class="up-avatar" style="background:{{ $avatarBg }};">{{ $initials }}</div>
                    <div class="up-hero-info">
                        {{-- Colonna sinistra: nome, email, azioni --}}
                        <div class="up-hero-info-main">
                            <div class="up-hero-name">
                                {{ $user->full_name ?? $user->user_id }}
                                @if($user->active == 1)
                                    <span class="up-status-badge up-status-active"
                                          role="button" data-bs-toggle="modal" data-bs-target="#modalUserActive"
                                          title="Gestisci stato">ATTIVO</span>
                                @elseif($user->active == 8)
                                    <span class="up-status-badge up-status-banned"
                                          role="button" data-bs-toggle="modal" data-bs-target="#modalUserBanned"
                                          title="Gestisci stato">BANNATO</span>
                                @else
                                    <span class="up-status-badge up-status-inactive"
                                          role="button" data-bs-toggle="modal" data-bs-target="#modalUserInactive"
                                          title="Gestisci stato">NON ATTIVO</span>
                                @endif
                            </div>
                            <div class="up-hero-email">
                                <i class="bi bi-envelope"></i> {{ $user->email ?? '—' }}
                                @if(!empty($user->paypalEmail))
                                    <span class="up-hero-sep">·</span>
                                    <i class="bi bi-paypal"></i> {{ $user->paypalEmail }}
                                @endif
                            </div>
                            <div class="up-hero-actions">
                                <button class="btn btn-sm up-action-btn" data-bs-toggle="modal" data-bs-target="#modalBonusMalus">
                                    <i class="bi bi-plus-slash-minus me-1"></i>Bonus / Malus
                                </button>
                                <button type="button" class="btn btn-sm up-action-btn" id="btnRefreshRespintLog">
                                    <i class="bi bi-list-check me-1"></i>Log
                                </button>
                                <button type="button" class="btn btn-sm up-action-btn-icon" id="btnOpenRespintLog"
                                        title="Apri dettaglio log" disabled>
                                    <i class="bi bi-search"></i>
                                </button>
                                <span class="up-log-status"><span id="respintLogStatus">—</span></span>
                            </div>
                        </div>
                        {{-- Colonna destra: info anagrafiche --}}
                        <div class="up-hero-info-side">
                            <div class="up-meta-row">
                                <span class="up-meta-key">ID utente</span>
                                <span class="up-meta-val">{{ $user->user_id }}</span>
                            </div>
                            <div class="up-meta-row">
                                <span class="up-meta-key">Registrato</span>
                                <span class="up-meta-val">{{ $user->reg_date ? \Carbon\Carbon::parse($user->reg_date)->format('d/m/Y') : '—' }}</span>
                            </div>
                            @if($cityStr)
                            <div class="up-meta-row">
                                <span class="up-meta-key">Città</span>
                                <span class="up-meta-val">{{ $cityStr }}</span>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- KPI boxes (destra) --}}
                <div class="up-hero-kpis">
                    <div class="up-hero-kpi-item">
                        <div class="up-kpi-icon-circle" style="background:#ede9fe;">
                            <i class="bi bi-people" style="color:#7c3aed;"></i>
                        </div>
                        <div class="up-kpi-text">
                            <div class="up-kpi-val">{{ number_format($attivita['inviti']) }}</div>
                            <div class="up-kpi-lbl">Inviti totali</div>
                        </div>
                    </div>
                    <div class="up-hero-kpi-item">
                        <div class="up-kpi-icon-circle" style="background:#dbeafe;">
                            <i class="bi bi-database" style="color:#2563eb;"></i>
                        </div>
                        <div class="up-kpi-text">
                            <div class="up-kpi-val" id="userPoints">{{ number_format($user->points ?? 0) }}</div>
                            <div class="up-kpi-lbl">Bytes totali</div>
                        </div>
                    </div>
                    <div class="up-hero-kpi-item">
                        <div class="up-kpi-icon-circle" style="background:#ccfbf1;">
                            <i class="bi bi-calendar3" style="color:#0d9488;"></i>
                        </div>
                        <div class="up-kpi-text">
                            <div class="up-kpi-val up-kpi-val-sm">
                                {{ $attivita['ultima_attivita'] ? \Carbon\Carbon::parse($attivita['ultima_attivita'])->format('d/m/Y') : '—' }}
                            </div>
                            <div class="up-kpi-lbl">Ultima attività</div>
                        </div>
                    </div>
                    <div class="up-hero-kpi-item">
                        <div class="up-kpi-icon-circle" style="background:#ffedd5;">
                            <i class="bi bi-people-fill" style="color:#ea580c;"></i>
                        </div>
                        <div class="up-kpi-text">
                            <div class="up-kpi-val">{{ $attivita['amici_iscritti'] ?? 0 }}</div>
                            <div class="up-kpi-lbl">Amici invitati</div>
                        </div>
                    </div>
                </div>
                </div>{{-- /card-body --}}

                {{-- Elementi nascosti per JS respint --}}
                <span id="respintLogTotal" style="display:none;"></span>
                <div id="respintLogReport" style="display:none;"></div>
            </div>
        </div>

        {{-- ===== INFORMAZIONI PERSONALI ===== --}}
        <div class="col-12">
            <div class="card h-100">
                <div class="card-header up-section-header">
                    <div class="up-section-left">
                        <div class="up-section-icon up-icon-blue"><i class="bi bi-person-lines-fill"></i></div>
                        <h5 class="up-section-title up-title-blue mb-0">Informazioni personali</h5>
                    </div>
                    <button class="btn btn-sm up-action-btn" data-bs-toggle="modal" data-bs-target="#modalEditAnagrafica">
                        <i class="bi bi-pencil me-1"></i>Modifica
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="up-info-2col">
                        <div class="up-info-row">
                            <div class="up-info-key"><i class="bi bi-cake2 up-info-key-icon"></i>Data di nascita</div>
                            <div class="up-info-val">
                                @if($user->birth_date)
                                    {{ \Carbon\Carbon::parse($user->birth_date)->format('d/m/Y') }}
                                    <span class="text-muted small">({{ \Carbon\Carbon::parse($user->birth_date)->age }} anni)</span>
                                @else
                                    —
                                @endif
                            </div>
                        </div>
                        <div class="up-info-row">
                            <div class="up-info-key"><i class="bi bi-mailbox up-info-key-icon"></i>CAP</div>
                            <div class="up-info-val">{{ $user->code ?? '—' }}</div>
                        </div>
                        <div class="up-info-row">
                            <div class="up-info-key"><i class="bi bi-person-badge up-info-key-icon"></i>Genere</div>
                            <div class="up-info-val">{{ $user->gender_label ?? '—' }}</div>
                        </div>
                        <div class="up-info-row">
                            <div class="up-info-key"><i class="bi bi-house up-info-key-icon"></i>Indirizzo</div>
                            <div class="up-info-val">{{ $user->address ?? '—' }}</div>
                        </div>
                        <div class="up-info-row">
                            <div class="up-info-key"><i class="bi bi-mortarboard up-info-key-icon"></i>Istruzione</div>
                            <div class="up-info-val">{{ $user->instr_level_id ?? '—' }}</div>
                        </div>
                        <div class="up-info-row">
                            <div class="up-info-key"><i class="bi bi-telephone up-info-key-icon"></i>Telefono</div>
                            <div class="up-info-val">{{ $user->home_phone ?? '—' }}</div>
                        </div>
                        <div class="up-info-row">
                            <div class="up-info-key"><i class="bi bi-briefcase up-info-key-icon"></i>Lavoro</div>
                            <div class="up-info-val">{{ $user->work_id ?? '—' }}</div>
                        </div>
                        <div class="up-info-row">
                            <div class="up-info-key"><i class="bi bi-phone up-info-key-icon"></i>Cellulare</div>
                            <div class="up-info-val">{{ $user->mobile_phone ?? '—' }}</div>
                        </div>
                        <div class="up-info-row">
                            <div class="up-info-key"><i class="bi bi-heart up-info-key-icon"></i>Stato civile</div>
                            <div class="up-info-val">{{ $user->mar_status_id ?? '—' }}</div>
                        </div>
                        <div class="up-info-row">
                            <div class="up-info-key"><i class="bi bi-envelope up-info-key-icon"></i>Email</div>
                            <div class="up-info-val">{{ $user->email ?? '—' }}</div>
                        </div>
                        <div class="up-info-row">
                            <div class="up-info-key"><i class="bi bi-geo-alt up-info-key-icon"></i>Provincia</div>
                            <div class="up-info-val">
                                {{ $user->province_name ?? '—' }}
                                @if(!empty($user->province_id))<small class="text-muted ms-1">({{ $user->province_id }})</small>@endif
                            </div>
                        </div>
                        <div class="up-info-row">
                            <div class="up-info-key"><i class="bi bi-calendar-check up-info-key-icon"></i>Registrazione</div>
                            <div class="up-info-val">{{ $user->reg_date ?? '—' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>{{-- /col-12 info personali --}}

        </div>{{-- /row g-3 sinistra --}}
        </div>{{-- /col-lg-7 sinistra --}}

        {{-- ===== QUALITÀ INTERVISTE (condensed) ===== --}}
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header up-section-header">
                    <div class="up-section-left">
                        <div class="up-section-icon up-icon-purple"><i class="bi bi-shield-check"></i></div>
                        <h5 class="up-section-title up-title-purple mb-0">Qualità interviste</h5>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger up-qm-header-btn" id="btnQualityMalus"
                            title="Assegna malus qualità">
                        <i class="bi bi-exclamation-triangle me-1"></i>Malus
                    </button>
                </div>
                <div class="card-body">
                    @php
                        $gaugeScore = $quality['media'] !== null ? (int) round($quality['media']) : 0;
                        $gaugeColor = $gaugeScore >= 70 ? '#16a34a' : ($gaugeScore >= 50 ? '#d97706' : '#dc2626');
                        $qRecent    = $quality['lista']->take(5);
                        $qBadgeFn   = function($tier) {
                            $n = in_array($tier, ['alta','regolare']) ? 'regolare'
                               : (in_array($tier, ['accettabile','incerta']) ? 'incerta'
                               : (in_array($tier, ['bassa','anomala']) ? 'anomala' : $tier));
                            return [
                                'norm' => $n,
                                'cls'  => $n === 'regolare' ? 'badge-soft-success'
                                        : ($n === 'incerta'  ? 'badge-soft-warning'
                                        : ($n === 'anomala'  ? 'badge-soft-danger' : 'badge-soft-secondary')),
                            ];
                        };
                    @endphp

                    @if($quality['count'] > 0)
                    <div class="up-quality-top">
                        <div class="up-quality-gauge-wrap">
                            <div class="up-quality-gauge"
                                 style="--gauge-pct:{{ $quality['media'] !== null ? $gaugeScore : 0 }};--gauge-color:{{ $quality['media'] !== null ? $gaugeColor : '#e2e8f0' }};">
                                <span class="up-quality-gauge-label">{{ $quality['media'] ?? '—' }}</span>
                            </div>
                            <div class="up-quality-gauge-sub">Media score</div>
                            <span class="up-quality-malus-pill badge {{ $quality['malusCount'] > 0 ? 'badge-soft-danger' : 'badge-soft-secondary' }}" id="qmCountPill">
                                <i class="bi bi-exclamation-triangle{{ $quality['malusCount'] > 0 ? '-fill' : '' }} me-1"></i>
                                <span id="qmCountPillText">{{ $quality['malusCount'] }} malus assegnat{{ $quality['malusCount'] == 1 ? 'o' : 'i' }}</span>
                            </span>
                        </div>
                        <div class="up-quality-condensed-stats">
                            <div class="up-quality-stat">
                                <div class="up-quality-stat-value">{{ $quality['count'] }}</div>
                                <div class="up-quality-stat-label">Interviste valutate</div>
                            </div>
                            <div class="up-quality-stat up-quality-stat-success">
                                <div class="up-quality-stat-value">{{ $quality['regolari'] }}</div>
                                <div class="up-quality-stat-label">Regolari</div>
                            </div>
                            <div class="up-quality-stat up-quality-stat-danger">
                                <div class="up-quality-stat-value">{{ $quality['anomale'] }}</div>
                                <div class="up-quality-stat-label">Anomalie</div>
                            </div>
                            <div class="up-quality-stat up-quality-stat-warning">
                                <div class="up-quality-stat-value">{{ $quality['incerte'] }}</div>
                                <div class="up-quality-stat-label">Incerte</div>
                            </div>
                        </div>
                    </div>

                    <div class="up-quality-recent-label">Ultime valutazioni</div>
                    <table class="table table-sm align-middle mb-1">
                        <tbody>
                            @foreach($qRecent as $q)
                            @php $qB = $qBadgeFn($q->quality_tier ?? ''); @endphp
                            <tr>
                                <td class="fw-semibold">
                                    <a href="{{ url('fieldControl') }}?prj={{ urlencode($q->prj) }}&sid={{ urlencode($q->sid) }}"
                                       target="_blank" rel="noopener" class="text-decoration-none text-primary">{{ $q->sid }}</a>
                                </td>
                                <td>
                                    @if($q->quality_score !== null)
                                        <span class="badge {{ $qB['cls'] }}">{{ $q->quality_score }}/100</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td><span class="badge {{ $qB['cls'] }}">{{ mb_strtoupper($qB['norm'] ?: '—', 'UTF-8') }}</span></td>
                                <td class="text-muted small">{{ $q->computed_at ? \Carbon\Carbon::parse($q->computed_at)->format('d/m/Y') : '—' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="text-end mt-1">
                        <button type="button" class="up-quality-see-all" data-bs-toggle="modal" data-bs-target="#modalQualityAll">
                            Vedi tutte le valutazioni →
                        </button>
                    </div>
                    @else
                        <p class="text-muted text-center py-3 mb-0">Nessun dato di qualità disponibile.</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- ===== 3) PREMI ===== --}}
        <div class="col-12">
            <div class="card">
                <div class="card-header up-section-header">
                    <div class="up-section-left">
                        <div class="up-section-icon up-icon-amber"><i class="bi bi-gift"></i></div>
                        <h5 class="up-section-title up-title-amber mb-0">Premi</h5>
                    </div>
                    <div class="up-premi-stats-inline">
                        <div class="up-psi-chip up-psi-chip-green">
                            <span class="up-psi-chip-val">{{ $premi['pagati'] }}</span>
                            <span class="up-psi-chip-lbl">Pagati</span>
                        </div>
                        <div class="up-psi-chip {{ $premi['da_pagare'] > 0 ? 'up-psi-chip-amber' : 'up-psi-chip-gray' }}">
                            <span class="up-psi-chip-val">{{ $premi['da_pagare'] }}</span>
                            <span class="up-psi-chip-lbl">Da pagare</span>
                        </div>
                        <div class="up-psi-chip up-psi-chip-blue">
                            <span class="up-psi-chip-val">{{ $premi['totali'] }}</span>
                            <span class="up-psi-chip-lbl">Totali</span>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm align-middle mb-0 up-premi-table">
                        <thead>
                            <tr>
                                <th>Premio</th>
                                <th>Codice</th>
                                <th>Richiesto il</th>
                                <th>Attesa</th>
                                <th>Pagato il</th>
                                <th>Status</th>
                                <th class="text-muted fw-normal">IP</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($premi['lista'] as $p)
                                @php
                                    $isPaid  = $p->pagato == 1;
                                    $giorni  = $p->giorno_paga
                                        ? \Carbon\Carbon::parse($p->giorno_paga)->diffInDays(\Carbon\Carbon::parse($p->event_date))
                                        : \Carbon\Carbon::parse($p->event_date)->diffInDays(now());
                                    $rowCls  = $isPaid ? 'up-premi-row-paid' : 'up-premi-row-pending';
                                    $giorniCls = $isPaid
                                        ? 'text-success'
                                        : ($giorni > 30 ? 'text-danger fw-bold' : ($giorni > 7 ? 'text-warning fw-semibold' : 'text-muted'));
                                @endphp
                                <tr class="{{ $rowCls }}">
                                    <td class="fw-semibold">
                                        <i class="bi bi-gift me-1 text-muted"></i>{{ $p->event_info }}
                                    </td>
                                    <td>
                                        @if($p->codice2)
                                            <span class="up-premi-code">{{ trim($p->codice2) }}</span>
                                            <button class="copy-btn ms-1 copy-code-btn" data-code="{{ trim($p->codice2) }}" title="Copia codice">
                                                <i class="bi bi-clipboard"></i>
                                            </button>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>{{ \Carbon\Carbon::parse($p->event_date)->format('d/m/Y') }}</td>
                                    <td class="{{ $giorniCls }}">{{ $giorni }}g</td>
                                    <td>{{ $p->giorno_paga ? \Carbon\Carbon::parse($p->giorno_paga)->format('d/m/Y') : '—' }}</td>
                                    <td>
                                        @if($isPaid)
                                            <span class="badge badge-soft-success">
                                                <i class="bi bi-check-circle me-1"></i>Pagato
                                            </span>
                                        @else
                                            <span class="badge badge-soft-warning">
                                                <i class="bi bi-hourglass-split me-1"></i>Da pagare
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-muted small">{{ $p->ip ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        <i class="bi bi-gift me-1"></i>Nessun premio trovato
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

{{-- Qualità integrata nella sezione Informazioni (condensed, col-lg-5) --}}
<div style="display:none">
    <div class="card">
        <div>
        <div class="card-body p-0">
            @php
                $gaugeScore  = $quality['media'] !== null ? (int) round($quality['media']) : 0;
                $gaugeColor  = $gaugeScore >= 70 ? '#16a34a' : ($gaugeScore >= 50 ? '#d97706' : '#dc2626');
                $gaugeTier   = $gaugeScore >= 70 ? 'Regolare'  : ($gaugeScore >= 50 ? 'Incerta' : 'Anomala');
                $gaugeBadge  = $gaugeScore >= 70 ? 'badge-soft-success' : ($gaugeScore >= 50 ? 'badge-soft-warning' : 'badge-soft-danger');
                $qTotal      = max($quality['count'], 1);
                $qPctReg     = round($quality['regolari'] / $qTotal * 100);
                $qPctInc     = round($quality['incerte']  / $qTotal * 100);
                $qPctAno     = 100 - $qPctReg - $qPctInc;
            @endphp

            @if($quality['count'] > 0)
            <div class="up-quality-overview">

                {{-- Gauge conic-gradient --}}
                <div class="up-quality-gauge-wrap">
                    <div class="up-quality-gauge"
                         style="--gauge-pct:{{ $quality['media'] !== null ? $gaugeScore : 0 }};--gauge-color:{{ $quality['media'] !== null ? $gaugeColor : '#e2e8f0' }};">
                        <span class="up-quality-gauge-label">{{ $quality['media'] ?? '—' }}</span>
                    </div>
                    <div class="up-quality-gauge-sub">Media score</div>
                    @if($quality['media'] !== null)
                        <span class="badge {{ $gaugeBadge }}">{{ mb_strtoupper($gaugeTier, 'UTF-8') }}</span>
                    @endif
                </div>

                {{-- Stat boxes + distribution bar --}}
                <div class="up-quality-stats">
                    <div class="up-quality-stat-row">
                        <div class="up-quality-stat">
                            <div class="up-quality-stat-value">{{ $quality['count'] }}</div>
                            <div class="up-quality-stat-label">Interviste</div>
                        </div>
                        <div class="up-quality-stat up-quality-stat-success">
                            <div class="up-quality-stat-value">{{ $quality['regolari'] }}</div>
                            <div class="up-quality-stat-label">Regolari</div>
                        </div>
                        <div class="up-quality-stat up-quality-stat-warning">
                            <div class="up-quality-stat-value">{{ $quality['incerte'] }}</div>
                            <div class="up-quality-stat-label">Incerte</div>
                        </div>
                        <div class="up-quality-stat up-quality-stat-danger">
                            <div class="up-quality-stat-value">{{ $quality['anomale'] }}</div>
                            <div class="up-quality-stat-label">Anomale</div>
                        </div>
                    </div>
                    <div class="up-quality-bar">
                        @if($quality['regolari'] > 0)
                            <div class="up-quality-bar-seg up-quality-bar-success"
                                 style="width:{{ $qPctReg }}%"
                                 title="Regolari: {{ $quality['regolari'] }} ({{ $qPctReg }}%)"></div>
                        @endif
                        @if($quality['incerte'] > 0)
                            <div class="up-quality-bar-seg up-quality-bar-warning"
                                 style="width:{{ $qPctInc }}%"
                                 title="Incerte: {{ $quality['incerte'] }} ({{ $qPctInc }}%)"></div>
                        @endif
                        @if($quality['anomale'] > 0)
                            <div class="up-quality-bar-seg up-quality-bar-danger"
                                 style="width:{{ $qPctAno }}%"
                                 title="Anomale: {{ $quality['anomale'] }} ({{ $qPctAno }}%)"></div>
                        @endif
                    </div>
                    <div class="up-quality-bar-legend">
                        <span class="up-quality-legend-dot" style="background:#16a34a;"></span> Regolari
                        <span class="up-quality-legend-dot ms-2" style="background:#d97706;"></span> Incerte
                        <span class="up-quality-legend-dot ms-2" style="background:#dc2626;"></span> Anomale
                    </div>
                </div>
            </div>

            <div class="p-3">
                <table class="table table-sm table-striped text-center align-middle mb-0">
                    <thead>
                        <tr>
                            <th>PRJ</th>
                            <th>SID</th>
                            <th>IID</th>
                            <th>Score</th>
                            <th>Stato</th>
                            <th>Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($quality['lista'] as $q)
                        @php
                            $qTier     = $q->quality_tier ?? '';
                            $qTierNorm = in_array($qTier, ['alta', 'regolare'])        ? 'regolare'
                                       : (in_array($qTier, ['accettabile', 'incerta']) ? 'incerta'
                                       : (in_array($qTier, ['bassa', 'anomala'])       ? 'anomala' : $qTier));
                            $qBadgeCls = $qTierNorm === 'regolare' ? 'badge-soft-success'
                                       : ($qTierNorm === 'incerta'  ? 'badge-soft-warning'
                                       : ($qTierNorm === 'anomala'  ? 'badge-soft-danger' : 'badge-soft-secondary'));
                        @endphp
                        <tr>
                            <td>{{ $q->prj }}</td>
                            <td>
                                <a href="{{ url('fieldControl') }}?prj={{ urlencode($q->prj) }}&sid={{ urlencode($q->sid) }}"
                                   target="_blank" rel="noopener" class="text-decoration-none fw-semibold">
                                    {{ $q->sid }}
                                </a>
                            </td>
                            <td>{{ $q->iid }}</td>
                            <td>
                                @if($q->quality_score !== null)
                                    <span class="badge {{ $qBadgeCls }}">{{ $q->quality_score }}/100</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $qBadgeCls }}">{{ mb_strtoupper($qTierNorm ?: '—', 'UTF-8') }}</span>
                            </td>
                            <td>{{ $q->computed_at ? \Carbon\Carbon::parse($q->computed_at)->format('d/m/Y') : '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
                <p class="text-muted text-center py-4 mb-0">Nessun dato di qualità disponibile per questo utente.</p>
            @endif
        </div>
    </div>
</div>
</div>

{{-- ===== 4) STORICO ===== --}}
<div class="col-12">
    <div class="card">
        <div class="card-header up-section-header up-storico-header">
            <div class="up-section-left">
                <div class="up-section-icon up-icon-purple"><i class="bi bi-person-lines-fill"></i></div>
                <div>
                    <h5 class="up-section-title up-title-purple mb-0">Attività recente</h5>
                    <div class="up-section-subtitle">Eventi, movimenti e partecipazioni dell'utente</div>
                </div>
            </div>
            <div class="up-storico-pills">
                <button class="up-storico-pill active" data-filter="all">Tutte</button>
                <button class="up-storico-pill" data-filter="completate">Completate</button>
                <button class="up-storico-pill" data-filter="screenout">Screenout</button>
                <button class="up-storico-pill" data-filter="premi">Premi</button>
                <button class="up-storico-pill" data-filter="anomalie">Anomalie</button>
            </div>
            <div class="up-storico-search-wrap">
                <i class="bi bi-search up-storico-search-icon"></i>
                <input type="text" id="storicoSearch" class="up-storico-search" placeholder="Cerca per SID o evento…">
            </div>
        </div>

        <div class="card-body p-0">
            <table class="table table-sm align-middle mb-0 up-storico-table">
                <thead>
                    <tr>
                        <th class="up-storico-dot-col"></th>
                        <th>Data e ora</th>
                        <th>Evento</th>
                        <th>Tipologia</th>
                        <th>SID</th>
                        <th>IID</th>
                        <th>PRJ</th>
                        <th>Score</th>
                        <th>Bytes</th>
                    </tr>
                </thead>
                <tbody id="storicoTableBody">
                    @include('partials.userProfileStoricoRows', ['storico' => $storico, 'qualityByIid' => $quality['byIid']])
                </tbody>
            </table>

            @if($storico->count() >= 30 && !request()->query('full'))
                <div class="text-center p-3">
                    <a href="{{ route('user.profile', ['user_id' => $user->user_id, 'full' => 1]) }}"
                       class="btn btn-outline-secondary btn-sm btn-show-all">
                        <i class="bi bi-list-ul me-1"></i> Mostra tutto
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>


    </div>
</div>


{{-- Modal: tutte le valutazioni qualità --}}
<div class="modal fade" id="modalQualityAll" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h6 class="modal-title fw-semibold">
                    <i class="bi bi-shield-check me-1 text-primary"></i>
                    Valutazioni qualità — {{ $user->full_name ?? $user->user_id }}
                    <span class="badge bg-secondary ms-2">{{ $quality['count'] }}</span>
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                @if($quality['count'] > 0)
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light" style="position:sticky;top:0;z-index:1;">
                        <tr>
                            <th class="ps-3">PRJ</th>
                            <th>SID</th>
                            <th>IID</th>
                            <th>Score</th>
                            <th>Tier</th>
                            <th class="pe-3">Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($quality['lista'] as $q)
                        @php
                            $qBm     = $qBadgeFn($q->quality_tier ?? '');
                            $hasSidM = isset($q->sid) && $q->sid !== '-' && $q->sid !== '';
                            $hasPrjM = isset($q->prj) && $q->prj !== '-' && $q->prj !== '';
                        @endphp
                        <tr>
                            <td class="ps-3 text-muted small">{{ $q->prj }}</td>
                            <td>
                                @if($hasSidM && $hasPrjM)
                                    <a href="{{ url('fieldControl') }}?prj={{ urlencode($q->prj) }}&sid={{ urlencode($q->sid) }}"
                                       target="_blank" rel="noopener" class="text-decoration-none fw-semibold text-primary small">{{ $q->sid }}</a>
                                @else
                                    <span class="small">{{ $q->sid ?? '—' }}</span>
                                @endif
                            </td>
                            <td class="text-muted small">{{ $q->iid }}</td>
                            <td>
                                @if($q->quality_score !== null)
                                    <span class="badge {{ $qBm['cls'] }}">{{ $q->quality_score }}/100</span>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                            <td><span class="badge {{ $qBm['cls'] }}">{{ mb_strtoupper($qBm['norm'] ?: '—', 'UTF-8') }}</span></td>
                            <td class="pe-3 text-muted small">{{ $q->computed_at ? \Carbon\Carbon::parse($q->computed_at)->format('d/m/Y') : '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @else
                    <p class="text-muted text-center py-4 mb-0">Nessun dato di qualità disponibile per questo utente.</p>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- 🔹 Modal utente attivo --}}
<div class="modal fade" id="modalUserActive" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
                <h6 class="modal-title"><i class="bi bi-person-gear me-1"></i> Gestione utente attivo</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4 py-3">
                <p class="text-muted small mb-3">Cosa desideri fare con <strong class="text-dark">{{ $user->full_name ?? $user->user_id }}</strong>?</p>
                <div class="d-grid gap-2">
                    <button class="btn btn-outline-warning text-start" id="btnDeactivate">
                        <i class="bi bi-person-dash me-2"></i> Disattiva utente
                        <small class="d-block text-muted fw-normal ms-4" style="font-size:0.75rem;">Imposta active=9, l'utente non può accedere</small>
                    </button>
                    <button class="btn btn-outline-danger text-start" id="btnBanOpen"
                            data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#modalBan">
                        <i class="bi bi-slash-circle me-2"></i> Sospendi / Banna
                        <small class="d-block text-muted fw-normal ms-4" style="font-size:0.75rem;">Imposta active=8, invia notifica email opzionale</small>
                    </button>
                    <button class="btn btn-outline-danger text-start" id="btnDelete">
                        <i class="bi bi-trash me-2"></i> Elimina definitivamente
                        <small class="d-block text-muted fw-normal ms-4" style="font-size:0.75rem;">Rimuove email e dati di contatto</small>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- 🔹 Modal utente non attivo --}}
<div class="modal fade" id="modalUserInactive" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h6 class="modal-title"><i class="bi bi-person-plus me-1"></i> Riattiva utente</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <p class="mb-3">Vuoi riattivare <strong>{{ $user->full_name ?? $user->user_id }}</strong>?</p>
                <button class="btn btn-outline-success" id="btnActivate">
                    <i class="bi bi-person-check me-1"></i> Attiva utente
                </button>
            </div>
        </div>
    </div>
</div>

{{-- 🔹 Modal conferma ban (con motivazione + email) --}}
<div class="modal fade" id="modalBan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h6 class="modal-title"><i class="bi bi-slash-circle me-1"></i> Sospendi / Banna utente</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-3">L'utente verrà sospeso (active=8). Puoi inviare una notifica via email.</p>
                <div class="mb-3">
                    <label class="form-label small mb-1">Motivazione interna</label>
                    <textarea id="banMotivazione" class="form-control form-control-sm" rows="2" maxlength="255">Anomalie rilevate durante i controlli qualitativi e di conformità al Regolamento.</textarea>
                </div>
                <div class="form-check mb-1">
                    <input class="form-check-input" type="checkbox" id="banSendEmail" checked>
                    <label class="form-check-label small" for="banSendEmail">Invia email di notifica all'utente</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Annulla</button>
                <button type="button" class="btn btn-sm btn-danger" id="btnBanConfirm">
                    <i class="bi bi-slash-circle me-1"></i> Conferma sospensione
                </button>
            </div>
        </div>
    </div>
</div>

{{-- 🔹 Modal utente bannato --}}
<div class="modal fade" id="modalUserBanned" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-white" style="background:#9a3412;">
                <h6 class="modal-title"><i class="bi bi-slash-circle me-1"></i> Utente sospeso/bannato</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <p class="mb-3">Cosa desideri fare con <strong>{{ $user->full_name ?? $user->user_id }}</strong>?</p>
                <button class="btn btn-outline-success me-2" id="btnActivateFromBan">
                    <i class="bi bi-person-check me-1"></i> Riattiva
                </button>
                <button class="btn btn-outline-danger" id="btnDeleteFromBan">
                    <i class="bi bi-trash me-1"></i> Elimina definitivamente
                </button>
            </div>
        </div>
    </div>
</div>

{{-- 🔹 Modale modifica anagrafica --}}
<div class="modal fade" id="modalEditAnagrafica" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h6 class="modal-title"><i class="bi bi-person-lines-fill me-1"></i> Modifica anagrafica utente</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <form id="formEditAnagrafica">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" id="editEmail" class="form-control"
                               value="{{ $user->email ?? '' }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">PayPal Email</label>
                        <input type="email" name="paypalEmail" id="editPaypalEmail" class="form-control"
                               value="{{ $user->paypalEmail ?? '' }}">
                    </div>
                    <div class="border rounded-3 p-3 bg-light">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="editResetPassword">
                            <label class="form-check-label fw-semibold" for="editResetPassword">
                                Forza reset password
                            </label>
                        </div>
                        <div class="small text-muted mb-2">
                            Se attivo, la nuova password sarà il testo prima della `@` nell'email inserita.
                        </div>
                        <div>
                            <label class="form-label mb-1">Password impostata</label>
                            <input type="text" id="editPasswordPreview" class="form-control" readonly value="">
                        </div>
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Annulla</button>
                <button type="button" class="btn btn-primary btn-sm" id="btnSaveAnagrafica">
                    <i class="bi bi-save me-1"></i> Salva modifiche
                </button>
            </div>
        </div>
    </div>
</div>

{{-- 🔹 Modale Bonus / Malus --}}
<div class="modal fade" id="modalBonusMalus" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-primary text-white">
        <h6 class="modal-title"><i class="bi bi-coin me-1"></i> Assegna Bonus o Malus</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <form id="formBonusMalus">
          @csrf
          <div class="mb-3">
            <label class="form-label">Tipo</label>
            <select id="bmType" class="form-select">
              <option value="Bonus" selected>Bonus (+)</option>
              <option value="Malus">Malus (−)</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Valore punti</label>
            <input type="number" id="bmValue" class="form-control" min="1" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Motivazione</label>
            <textarea id="bmMotivation" class="form-control" rows="3" maxlength="255" required></textarea>
          </div>
        </form>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Annulla</button>
        <button type="button" class="btn btn-primary btn-sm" id="btnSaveBonusMalus">
          <i class="bi bi-check2-circle me-1"></i> Conferma
        </button>
      </div>
    </div>
  </div>
</div>

{{-- Modale Log t_respint --}}
<div class="modal fade" id="modalRespintLog" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content border-0 shadow">
      <div class="modal-header respint-log-modal-header">
        <div>
            <h6 class="modal-title mb-0"><i class="bi bi-list-check me-1"></i> Log utente</h6>
            <small class="respint-log-modal-subtitle">Totale record: <span id="respintLogModalTotal">-</span></small>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div id="respintLogModalReport" class="respint-report-grid respint-report-grid-modal mb-3"></div>

        <div class="respint-log-filter mb-3">
            <label for="respintSidFilter" class="form-label mb-1">Filtra per SID</label>
            <div class="input-group input-group-sm">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="search"
                       id="respintSidFilter"
                       class="form-control"
                       placeholder="Cerca SID..."
                       autocomplete="off">
                <button type="button"
                        class="btn btn-outline-secondary"
                        id="btnClearRespintSidFilter"
                        title="Cancella filtro"
                        aria-label="Cancella filtro">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <div id="respintLogVisibleCount" class="respint-log-filter-count"></div>
        </div>

        <div class="table-responsive respint-log-table-wrapper">
            <table class="table table-sm table-striped text-center align-middle mb-0 respint-log-table">
                <thead>
                    <tr>
                        <th>SID</th>
                        <th>PRJ</th>
                        <th>Fine field</th>
                        <th>Status</th>
                        <th>IID</th>
                    </tr>
                </thead>
                <tbody id="respintLogTableBody">
                    <tr>
                        <td colspan="5" class="text-muted">Log non caricato</td>
                    </tr>
                </tbody>
            </table>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Contenitore Toasts Bootstrap --}}
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 2000">
  <div id="toastContainer"></div>
</div>

<script>
// Funzione helper per mostrare un toast
function showToast(message, type = 'success') {
    const toastId = 'toast-' + Date.now();
    const bgClass = type === 'error' ? 'bg-danger text-white' :
                    type === 'warning' ? 'bg-warning text-dark' :
                    'bg-success text-white';

    const toastHtml = `
      <div id="${toastId}" class="toast align-items-center ${bgClass} border-0 mb-2" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
          <div class="toast-body fw-semibold">${message}</div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
      </div>
    `;

    const container = document.getElementById('toastContainer');
    container.insertAdjacentHTML('beforeend', toastHtml);

    const toastEl = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastEl, { delay: 3000 });
    toast.show();

    toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
}
</script>


@endsection

@php
    $qualityMalusHistoryData = $quality['malusHistory']->map(function ($m) {
        return [
            'valore' => $m->valore,
            'motivazione' => $m->motivazione,
            'assigned_by' => $m->assigned_by,
            'created_at' => $m->created_at,
            'email_sent' => (bool) $m->email_sent,
        ];
    });
@endphp
@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const userId = "{{ $user->user_id }}";
    const userDeactivateUrl = @json(route('user.deactivate', ['user_id' => $user->user_id]));
    const userDeleteUrl = @json(route('user.delete', ['user_id' => $user->user_id]));
    const userActivateUrl = @json(route('user.activate', ['user_id' => $user->user_id]));
    const userUpdateInfoUrl = @json(route('user.update.info', ['user_id' => $user->user_id]));
    const userBonusMalusUrl = @json(route('user.bonus.malus', ['user_id' => $user->user_id]));
    const userBanUrl = @json(route('user.ban', ['user_id' => $user->user_id]));
    const respintSummaryUrl = @json(route('user.respint.summary', ['user_id' => $user->user_id]));
    const respintDetailUrl = @json(route('user.respint.log', ['user_id' => $user->user_id]));

    function escapeHtml(value) {
        return String(value ?? '-')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatNumber(value) {
        return new Intl.NumberFormat('it-IT').format(Number(value) || 0);
    }

    function formatPercent(count, total) {
        const parsedTotal = Number(total) || 0;

        if (!parsedTotal) {
            return '0%';
        }

        return `${((Number(count) || 0) / parsedTotal * 100).toFixed(1).replace('.', ',')}%`;
    }

    function renderRespintReport(report, targetEl) {
        if (!targetEl) {
            return;
        }

        const items = Array.isArray(report?.items) ? report.items : [];
        const total = Number(report?.total) || items.reduce((sum, item) => sum + (Number(item.count) || 0), 0);

        if (!items.length) {
            targetEl.innerHTML = '';
            return;
        }

        targetEl.innerHTML = items.map(item => `
            <div class="respint-report-card ${escapeHtml(item.report_class || '')}">
                <span class="respint-report-label">${escapeHtml(item.label)}</span>
                <span class="respint-report-value">
                    ${formatNumber(item.count)}
                    <small class="respint-report-percent">(${formatPercent(item.count, total)})</small>
                </span>
            </div>
        `).join('');
    }

    // ===========================
    // 🔹 STORICO FILTER + SEARCH
    // ===========================
    function applyStoricoFilter() {
        const activePill = document.querySelector('.up-storico-pill.active');
        const filter = activePill ? activePill.dataset.filter : 'all';
        const search = (document.getElementById('storicoSearch')?.value || '').toLowerCase().trim();
        const screenoutEvents = ['SCREENOUT', 'QUOTAFULL'];
        const bonusEvents     = ['BONUS', 'BONUS AMICO', 'MALUS'];

        document.querySelectorAll('#storicoTableBody tr').forEach(row => {
            if (!row.dataset.evento) { row.style.display = ''; return; }

            let byPill = true;
            if (filter === 'completate')  byPill = row.dataset.evento === 'COMPLETATA';
            else if (filter === 'screenout')  byPill = screenoutEvents.includes(row.dataset.evento);
            else if (filter === 'premi')      byPill = row.dataset.evento === 'PREMIO';
            else if (filter === 'bonusmalus') byPill = bonusEvents.includes(row.dataset.evento);
            else if (filter === 'anomalie')   byPill = row.dataset.tier === 'anomala';

            const bySearch = !search || row.textContent.toLowerCase().includes(search);
            row.style.display = (byPill && bySearch) ? '' : 'none';
        });
    }

    document.querySelectorAll('.up-storico-pill').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.up-storico-pill').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            applyStoricoFilter();
        });
    });

    document.getElementById('storicoSearch')?.addEventListener('input', applyStoricoFilter);

    // ===========================
    // 🔹 GESTIONE STATO UTENTE
    // ===========================
    document.getElementById('btnDeactivate')?.addEventListener('click', () => {
        if (confirm("Confermi la disattivazione dell'utente?")) {
            sendUserAction(userDeactivateUrl, 'modalUserActive');
        }
    });

    document.getElementById('btnDelete')?.addEventListener('click', () => {
        if (confirm("Confermi l'eliminazione definitiva dell'utente?")) {
            sendUserAction(userDeleteUrl, 'modalUserActive');
        }
    });

    document.getElementById('btnActivate')?.addEventListener('click', () => {
        if (confirm("Confermi la riattivazione dell'utente?")) {
            sendUserAction(userActivateUrl, 'modalUserInactive');
        }
    });

    // Ban
    document.getElementById('btnBanConfirm')?.addEventListener('click', () => {
        const motivazione = document.getElementById('banMotivazione').value.trim();
        const sendEmail   = document.getElementById('banSendEmail').checked;

        if (!motivazione) {
            showToast('Inserisci una motivazione.', 'warning');
            return;
        }

        fetch(userBanUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ motivazione, send_email: sendEmail }),
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('modalBan'))?.hide();
                setTimeout(() => {
                    if (data.email_requested && !data.email_sent) {
                        showToast('Utente sospeso, ma l\'email non è stata inviata.', 'warning');
                    } else {
                        showToast(data.message, 'success');
                    }
                    location.reload();
                }, 300);
            } else {
                showToast(data.message || 'Errore durante la sospensione.', 'error');
            }
        })
        .catch(() => showToast('Errore di connessione.', 'error'));
    });

    // Riattiva da stato bannato
    document.getElementById('btnActivateFromBan')?.addEventListener('click', () => {
        if (confirm('Confermi la riattivazione dell\'utente bannato?')) {
            sendUserAction(userActivateUrl, 'modalUserBanned');
        }
    });

    // Elimina da stato bannato
    document.getElementById('btnDeleteFromBan')?.addEventListener('click', () => {
        if (confirm('Confermi l\'eliminazione definitiva dell\'utente?')) {
            sendUserAction(userDeleteUrl, 'modalUserBanned');
        }
    });

    function sendUserAction(url, modalId) {
        fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const modalEl = document.getElementById(modalId);
                const modalInstance = bootstrap.Modal.getInstance(modalEl);
                modalInstance?.hide();

                setTimeout(() => {
                    showToast(data.message, 'success');
                    location.reload();
                }, 300);
            } else {
                showToast(data.message || 'Errore durante l\'operazione.', 'error');
            }
        })
        .catch(() => showToast('Errore di connessione.', 'error'));
    }

    // ===========================
    // 🔹 MODIFICA ANAGRAFICA
    // ===========================
    document.getElementById('btnSaveAnagrafica')?.addEventListener('click', () => {
        const email = document.getElementById('editEmail').value.trim();
        const paypalEmail = document.getElementById('editPaypalEmail').value.trim();
        const resetPassword = document.getElementById('editResetPassword').checked;

        if (resetPassword && !extractEmailPrefix(email)) {
            showToast('Impossibile generare la password dalla email inserita.', 'warning');
            return;
        }

        fetch(userUpdateInfoUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ email, paypalEmail, resetPassword })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const modalEl = document.getElementById('modalEditAnagrafica');
                const modalInstance = bootstrap.Modal.getInstance(modalEl);
                modalInstance?.hide();

                setTimeout(() => {
                    showToast(data.message, 'success');
                    location.reload();
                }, 300);
            } else {
                showToast(data.message || 'Errore durante l\'operazione.', 'error');
            }
        })
        .catch(() => showToast('Errore di connessione.', 'error'));
    });

    function extractEmailPrefix(email) {
        const normalizedEmail = String(email || '').trim();
        const atIndex = normalizedEmail.indexOf('@');

        if (atIndex <= 0) {
            return '';
        }

        return normalizedEmail.substring(0, atIndex).trim();
    }

    function refreshPasswordPreview() {
        const email = document.getElementById('editEmail')?.value || '';
        const previewEl = document.getElementById('editPasswordPreview');

        if (!previewEl) {
            return;
        }

        previewEl.value = extractEmailPrefix(email);
    }

    document.getElementById('editEmail')?.addEventListener('input', refreshPasswordPreview);
    document.getElementById('editResetPassword')?.addEventListener('change', refreshPasswordPreview);
    refreshPasswordPreview();

    // ===========================
    // 🔹 BONUS / MALUS
    // ===========================
    document.getElementById('btnSaveBonusMalus')?.addEventListener('click', () => {
        const rawType = document.getElementById('bmType').value;
        const type = rawType.toUpperCase();
        const value = parseInt(document.getElementById('bmValue').value.trim(), 10);
        const motivation = document.getElementById('bmMotivation').value.trim();

        if (!value || !motivation) {
            showToast('Compila tutti i campi.', 'warning');
            return;
        }

        fetch(userBonusMalusUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ type, value, motivation })
        })
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                showToast(data.message || 'Errore durante l\'operazione.', 'error');
                return;
            }

            const modalEl = document.getElementById('modalBonusMalus');
            const modalInstance = bootstrap.Modal.getInstance(modalEl);

            const finalizeBonusMalusUI = () => {
                // 1. pulizia completa bootstrap/modal
                document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
                document.body.classList.remove('modal-open');
                document.body.style.removeProperty('overflow');
                document.body.style.removeProperty('padding-right');

                // 2. aggiorna punti
                const userPointsEl = document.getElementById('userPoints');
                if (userPointsEl && typeof data.points !== 'undefined') {
                    userPointsEl.textContent = data.points;
                }

                // 3. aggiorna storico attività via AJAX
                const storicoTableBody = document.getElementById('storicoTableBody');
                if (storicoTableBody && data.storico_html) {
                    storicoTableBody.innerHTML = data.storico_html;
                    applyStoricoFilter();
                }

                // 4. reset form
                document.getElementById('bmType').value = 'Bonus';
                document.getElementById('bmValue').value = '';
                document.getElementById('bmMotivation').value = '';

                // 5. toast finale
                showToast(data.message, 'success');
            };

            if (modalEl) {
                modalEl.addEventListener('hidden.bs.modal', finalizeBonusMalusUI, { once: true });
            }

            if (modalInstance) {
                modalInstance.hide();
            } else {
                finalizeBonusMalusUI();
            }
        })
        .catch(() => {
            document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
            document.body.classList.remove('modal-open');
            document.body.style.removeProperty('overflow');
            document.body.style.removeProperty('padding-right');

            showToast('Errore di connessione.', 'error');
        });
    });

    // ===========================
    // 🔹 MALUS QUALITÀ (popover a livello utente, con storico richiami)
    // ===========================
    const userQualityMalusUrl = @json(route('user.quality.malus', ['user_id' => $user->user_id]));
    let qualityMalusHistory = @json($qualityMalusHistoryData);
    let qmPopoverInstance = null;
    const qmBtn = document.getElementById('btnQualityMalus');
    const qmCountPill = document.getElementById('qmCountPill');
    const qmCountPillText = document.getElementById('qmCountPillText');

    function updateQualityMalusPill() {
        if (!qmCountPill || !qmCountPillText) return;
        const n = qualityMalusHistory.length;
        qmCountPillText.textContent = `${n} malus assegnat${n === 1 ? 'o' : 'i'}`;
        qmCountPill.classList.toggle('badge-soft-danger', n > 0);
        qmCountPill.classList.toggle('badge-soft-secondary', n === 0);
        qmCountPill.querySelector('i').className = `bi bi-exclamation-triangle${n > 0 ? '-fill' : ''} me-1`;
    }

    function formatDateIt(value) {
        if (!value) return '—';
        const d = new Date(String(value).replace(' ', 'T'));
        if (isNaN(d.getTime())) return '—';
        return d.toLocaleDateString('it-IT');
    }

    function closeQualityMalusPopover() {
        if (qmPopoverInstance) {
            qmPopoverInstance.dispose();
            qmPopoverInstance = null;
        }
    }

    function buildQualityMalusHistoryHtml() {
        if (!qualityMalusHistory.length) {
            return '<p class="small text-muted mb-2">Nessun richiamo precedente.</p>';
        }

        const rows = qualityMalusHistory.map(m => `
            <div class="up-qm-history-row">
                <div class="up-qm-history-top">
                    <span class="fw-semibold text-danger">-${escapeHtml(m.valore)} pt</span>
                    <span class="text-muted small">${formatDateIt(m.created_at)}</span>
                </div>
                <div class="small">${escapeHtml(m.motivazione)}</div>
                <div class="up-qm-history-bottom text-muted small">
                    <span>${m.assigned_by ? 'di ' + escapeHtml(m.assigned_by) : ''}</span>
                    <span class="up-qm-email-status" title="${m.email_sent ? 'Email inviata' : 'Email non inviata'}">
                        <i class="bi bi-envelope${m.email_sent ? '-check-fill text-success' : '-slash text-muted'}"></i>
                    </span>
                </div>
            </div>
        `).join('');

        return `<div class="up-qm-history-list">${rows}</div>`;
    }

    function buildQualityMalusPopoverHtml() {
        return `
            <div class="up-qm-popover">
                <div class="up-qm-history-label">Richiami precedenti (${qualityMalusHistory.length})</div>
                ${buildQualityMalusHistoryHtml()}
                <hr class="my-2">
                <div class="mb-2">
                    <label class="form-label small mb-1">Valore malus</label>
                    <input type="number" id="qmValore" class="form-control form-control-sm" min="1" value="1">
                </div>
                <div class="mb-2">
                    <label class="form-label small mb-1">Motivazione</label>
                    <textarea id="qmMotivazione" class="form-control form-control-sm" rows="2" maxlength="255">Rilevate anomalie ricorrenti nella qualità delle interviste</textarea>
                </div>
                <div class="form-check mb-2">
                    <input type="checkbox" id="qmSendEmail" class="form-check-input" checked>
                    <label class="form-check-label small" for="qmSendEmail">Invia email all'utente</label>
                </div>
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-sm btn-secondary" id="qmCancel">Annulla</button>
                    <button type="button" class="btn btn-sm btn-danger" id="qmSubmit">Conferma malus</button>
                </div>
            </div>
        `;
    }

    qmBtn?.addEventListener('click', () => {
        if (qmPopoverInstance) {
            closeQualityMalusPopover();
            return;
        }

        qmPopoverInstance = new bootstrap.Popover(qmBtn, {
            html: true,
            sanitize: false,
            trigger: 'manual',
            container: 'body',
            placement: 'left',
            title: 'Assegna malus qualità',
            content: buildQualityMalusPopoverHtml(),
        });
        qmPopoverInstance.show();

        setTimeout(() => {
            const popEl = document.querySelector('.popover');
            if (!popEl) return;

            popEl.querySelector('#qmCancel')?.addEventListener('click', closeQualityMalusPopover);

            popEl.querySelector('#qmSubmit')?.addEventListener('click', () => {
                const valore = parseInt(popEl.querySelector('#qmValore').value, 10);
                const motivazione = popEl.querySelector('#qmMotivazione').value.trim();
                const sendEmail = popEl.querySelector('#qmSendEmail').checked;

                if (!valore || valore < 1 || !motivazione) {
                    showToast('Compila valore e motivazione.', 'warning');
                    return;
                }

                fetch(userQualityMalusUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ valore, motivazione, send_email: sendEmail }),
                })
                .then(res => res.json())
                .then(data => {
                    if (!data.success) {
                        showToast(data.message || 'Errore durante l\'operazione.', 'error');
                        return;
                    }

                    closeQualityMalusPopover();

                    qualityMalusHistory.unshift({
                        valore,
                        motivazione,
                        assigned_by: '{{ session('user_name') }}',
                        created_at: new Date().toISOString(),
                        email_sent: !!data.email_sent,
                    });

                    updateQualityMalusPill();

                    const userPointsEl = document.getElementById('userPoints');
                    if (userPointsEl && typeof data.points !== 'undefined') {
                        userPointsEl.textContent = data.points;
                    }

                    const storicoTableBody = document.getElementById('storicoTableBody');
                    if (storicoTableBody && data.storico_html) {
                        storicoTableBody.innerHTML = data.storico_html;
                        applyStoricoFilter();
                    }

                    showToast(data.message, 'success');

                    if (data.email_requested && !data.email_sent) {
                        showToast('Malus salvato, ma l\'invio email non è riuscito (controlla la configurazione SMTP).', 'warning');
                    }
                })
                .catch(() => showToast('Errore di connessione.', 'error'));
            });
        }, 0);
    });

    document.addEventListener('click', (e) => {
        if (!qmPopoverInstance) return;
        const insidePopover = e.target.closest('.popover');
        const onTrigger = e.target.closest('#btnQualityMalus');
        if (!insidePopover && !onTrigger) {
            closeQualityMalusPopover();
        }
    });

    // ===========================
    // LOG t_respint
    // ===========================
    const respintTotalEl = document.getElementById('respintLogTotal');
    const respintStatusEl = document.getElementById('respintLogStatus');
    const btnRefreshRespintLog = document.getElementById('btnRefreshRespintLog');
    const btnOpenRespintLog = document.getElementById('btnOpenRespintLog');
    const respintReportEl = document.getElementById('respintLogReport');
    const respintModalReportEl = document.getElementById('respintLogModalReport');
    const respintSidFilterEl = document.getElementById('respintSidFilter');
    const btnClearRespintSidFilter = document.getElementById('btnClearRespintSidFilter');
    const respintLogVisibleCount = document.getElementById('respintLogVisibleCount');
    const respintLogTableBody = document.getElementById('respintLogTableBody');
    const respintLogModalTotal = document.getElementById('respintLogModalTotal');
    const modalRespintLogEl = document.getElementById('modalRespintLog');
    let respintLogTotal = null;
    let respintLogRows = [];

    function setRespintDetailEnabled(enabled) {
        if (!btnOpenRespintLog) {
            return;
        }

        btnOpenRespintLog.disabled = !enabled;
        btnOpenRespintLog.classList.toggle('disabled', !enabled);
    }

    function updateRespintVisibleCount(visibleCount, totalCount) {
        if (!respintLogVisibleCount) {
            return;
        }

        if (!totalCount) {
            respintLogVisibleCount.textContent = '';
            return;
        }

        respintLogVisibleCount.textContent = `${formatNumber(visibleCount)} / ${formatNumber(totalCount)} righe`;
    }

    function renderRespintLogRows() {
        if (!respintLogTableBody) {
            return;
        }

        const filterValue = (respintSidFilterEl?.value || '').trim().toLowerCase();
        const rows = filterValue
            ? respintLogRows.filter(row => String(row.sid || '').toLowerCase().includes(filterValue))
            : respintLogRows;

        updateRespintVisibleCount(rows.length, respintLogRows.length);

        if (!respintLogRows.length) {
            respintLogTableBody.innerHTML = '<tr><td colspan="5" class="text-muted">Nessun log trovato</td></tr>';
            setRespintDetailEnabled(false);
            return;
        }

        if (!rows.length) {
            respintLogTableBody.innerHTML = '<tr><td colspan="5" class="text-muted">Nessun risultato</td></tr>';
            return;
        }

        respintLogTableBody.innerHTML = rows.map(row => {
            const statusValue = escapeHtml(row.status);
            const statusLabel = escapeHtml(row.status_label);
            const badgeClass = escapeHtml(row.status_badge_class || 'respint-status-badge respint-status-other');
            const rowClass = escapeHtml(row.status_row_class || '');

            return `
                <tr class="${rowClass}">
                    <td>${escapeHtml(row.sid)}</td>
                    <td class="text-start">${escapeHtml(row.prj_name)}</td>
                    <td>${escapeHtml(row.end_field)}</td>
                    <td><span class="badge ${badgeClass}">${statusValue} - ${statusLabel}</span></td>
                    <td>${escapeHtml(row.iid)}</td>
                </tr>
            `;
        }).join('');
    }

    respintSidFilterEl?.addEventListener('input', renderRespintLogRows);

    btnClearRespintSidFilter?.addEventListener('click', () => {
        if (!respintSidFilterEl) {
            return;
        }

        respintSidFilterEl.value = '';
        respintSidFilterEl.focus();
        renderRespintLogRows();
    });

    btnRefreshRespintLog?.addEventListener('click', () => {
        const originalHtml = btnRefreshRespintLog.innerHTML;

        btnRefreshRespintLog.disabled = true;
        btnRefreshRespintLog.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Aggiorno';
        if (respintStatusEl) {
            respintStatusEl.textContent = 'Aggiornamento...';
        }

        fetch(respintSummaryUrl, {
            headers: {
                'Accept': 'application/json',
            },
        })
        .then(res => {
            if (!res.ok) {
                throw new Error('Errore risposta server');
            }

            return res.json();
        })
        .then(data => {
            if (!data.success) {
                throw new Error(data.message || 'Errore aggiornamento log');
            }

            respintLogTotal = Number(data.total) || 0;

            if (respintTotalEl) {
                respintTotalEl.textContent = formatNumber(respintLogTotal);
            }

            if (respintStatusEl) {
                respintStatusEl.textContent = 'Aggiornato ora';
            }

            renderRespintReport(data.status_report, respintReportEl);
            renderRespintReport(data.status_report, respintModalReportEl);

            setRespintDetailEnabled(respintLogTotal > 0);
        })
        .catch(() => {
            if (respintStatusEl) {
                respintStatusEl.textContent = 'Errore aggiornamento';
            }

            setRespintDetailEnabled(false);
            showToast('Errore durante l\'aggiornamento del log.', 'error');
        })
        .finally(() => {
            btnRefreshRespintLog.disabled = false;
            btnRefreshRespintLog.innerHTML = originalHtml;
        });
    });

    btnOpenRespintLog?.addEventListener('click', () => {
        if (btnOpenRespintLog.disabled || !modalRespintLogEl) {
            return;
        }

        if (respintLogModalTotal) {
            respintLogModalTotal.textContent = respintLogTotal === null ? '-' : formatNumber(respintLogTotal);
        }

        if (respintSidFilterEl) {
            respintSidFilterEl.value = '';
        }

        updateRespintVisibleCount(0, 0);

        if (respintLogTableBody) {
            respintLogTableBody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-muted">
                        <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                        Caricamento log...
                    </td>
                </tr>
            `;
        }

        const modalInstance = new bootstrap.Modal(modalRespintLogEl);
        modalInstance.show();

        fetch(respintDetailUrl, {
            headers: {
                'Accept': 'application/json',
            },
        })
        .then(res => {
            if (!res.ok) {
                throw new Error('Errore risposta server');
            }

            return res.json();
        })
        .then(data => {
            if (!data.success) {
                throw new Error(data.message || 'Errore caricamento log');
            }

            const rows = Array.isArray(data.rows) ? data.rows : [];
            respintLogTotal = Number(data.total) || rows.length;

            if (respintLogModalTotal) {
                respintLogModalTotal.textContent = formatNumber(respintLogTotal);
            }

            if (respintTotalEl) {
                respintTotalEl.textContent = formatNumber(respintLogTotal);
            }

            renderRespintReport(data.status_report, respintReportEl);
            renderRespintReport(data.status_report, respintModalReportEl);

            respintLogRows = rows;
            renderRespintLogRows();
        })
        .catch(() => {
            if (respintLogTableBody) {
                respintLogTableBody.innerHTML = '<tr><td colspan="5" class="text-danger">Errore nel caricamento del log</td></tr>';
            }

            showToast('Errore durante il caricamento del log.', 'error');
        });
    });

    // ===========================
    // COPIA CODICE PREMIO
    // ===========================
    document.querySelectorAll('.copy-code-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const code = btn.dataset.code || '';

            navigator.clipboard.writeText(code)
                .then(() => showToast('Codice copiato', 'success'))
                .catch(() => showToast('Errore nella copia', 'error'));
        });
    });
});
</script>
@endsection
