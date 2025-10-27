@extends('layouts.main')

@section('content')

<style>
    body { background: #f5f6fa; }

    .card {
        border: none;
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        background: #fff;
        transition: all 0.3s ease-in-out;
    }
    .card:hover { transform: translateY(-3px); box-shadow: 0 6px 18px rgba(0,0,0,0.12); }

    .card-header {
        border: none;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.8rem 1rem;
    }

    .header-anagrafica { background: linear-gradient(90deg, #007bff, #00bfff); color: #fff; }
    .header-attivita { background: linear-gradient(90deg, #28a745, #58d68d); color: #fff; }
    .header-premi { background: linear-gradient(90deg, #ffc107, #ffcd39); color: #000; }
    .header-storico { background: linear-gradient(90deg, #6c757d, #adb5bd); color: #fff; }

    .profile-info {
        background: #f1f8ff;
        border-radius: 10px;
        padding: 1rem;
        margin-bottom: 1rem;
        text-align: center;
    }
    .profile-name { font-size: 1.6rem; font-weight: 700; color: #333; }
    .profile-meta { font-size: 0.9rem; color: #555; }

    .table-anagrafica th {
        width: 35%;
        background: #f8f9fa;
        color: #555;
        font-weight: 600;
    }
    .table-anagrafica td { color: #333; }

    /* KPI layout */
    .kpi-box {
        display: flex;
        align-items: center;
        justify-content: start;
        gap: 0.75rem;
        padding: 0.6rem 0.8rem;
        border-radius: 10px;
        background: #f8f9fa;
        transition: 0.3s;
    }
    .kpi-box:hover { background: #e9f5ff; }
    .kpi-icon { font-size: 1.4rem; width: 28px; text-align: center; }
    .kpi-value { font-size: 1.2rem; font-weight: 700; }
    .kpi-label { font-size: 0.75rem; color: #666; margin-top: -2px; }

    .badge-status {
        font-size: 0.9rem;
        border-radius: 8px;
        padding: 5px 10px;
        font-weight: 600;
    }

    .text-green { color: #28a745; font-weight: 600; }
    .text-red { color: #dc3545; font-weight: 600; }

    .copy-btn {
        border: none;
        background: none;
        color: #007bff;
        cursor: pointer;
        padding: 0;
    }
    .copy-btn:hover { color: #0056b3; }

    .premi-stats small { display: block; font-weight: 500; }
    .btn-show-all {
        border-radius: 20px;
        font-weight: 500;
    }
</style>

<div class="container-fluid mt-4">

    <div class="row g-4">
        {{-- ===== 1) ANAGRAFICA ===== --}}
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header header-anagrafica">
                    <h6><i class="bi bi-person-lines-fill me-1"></i> Anagrafica</h6>
                </div>
                <div class="card-body">
                    {{-- Nome, UID, Email in evidenza --}}
                    <div class="profile-info">
                        <div class="profile-name">{{ $user->first_name }} {{ $user->second_name }}</div>
                        <div class="profile-meta mt-1">
                            <i class="bi bi-person-badge me-1"></i> <strong>{{ $user->user_id }}</strong><br>
                            <i class="bi bi-envelope me-1"></i> {{ $user->email }}
                        </div>
                    </div>

                    <table class="table table-sm table-bordered table-anagrafica mb-0">
                        <tr><th>Data di nascita:</th><td>{{ $user->birth_date ? \Carbon\Carbon::parse($user->birth_date)->format('d/m/Y') : '-' }}</td></tr>
                        <tr><th>Registrazione:</th><td>{{ $user->reg_date ?? '-' }}</td></tr>
                        <tr><th>Genere:</th><td>{{ $user->gender == 1 ? 'Maschile' : 'Femminile' }}</td></tr>
                        <tr><th>Istruzione:</th><td>{{ $user->instr_level_id ?? '-' }}</td></tr>
                        <tr><th>Lavoro:</th><td>{{ $user->work_id ?? '-' }}</td></tr>
                        <tr><th>Stato civile:</th><td>{{ $user->mar_status_id ?? '-' }}</td></tr>
                        <tr><th>Provincia:</th><td>{{ $user->province_id ?? '-' }}</td></tr>
                        <tr><th>CAP:</th><td>{{ $user->code ?? '-' }}</td></tr>
                        <tr><th>Indirizzo:</th><td>{{ $user->address ?? '-' }}</td></tr>
                        <tr><th>Telefono:</th><td>{{ $user->home_phone ?? '-' }}</td></tr>
                        <tr><th>Mobile:</th><td>{{ $user->mobile_phone ?? '-' }}</td></tr>
                    </table>
                </div>
            </div>
        </div>

        {{-- ===== 2) ATTIVITÀ ===== --}}
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header header-attivita">
                    <h6><i class="bi bi-activity me-1"></i> Attività</h6>
                </div>
                <div class="card-body p-3">
                    <div class="text-center mb-3">
                        @if($user->active == 1)
                            <span class="badge-status bg-success"><i class="bi bi-check-circle me-1"></i> Attivo</span>
                        @else
                            <span class="badge-status bg-danger"><i class="bi bi-x-circle me-1"></i> Disattivato</span>
                        @endif
                    </div>

                    <div class="row g-2">
                        <div class="col-6"><div class="kpi-box"><div class="kpi-icon text-primary"><i class="bi bi-coin"></i></div><div><div class="kpi-value">{{ $user->points ?? 0 }}</div><div class="kpi-label">Bytes</div></div></div></div>
                        <div class="col-6"><div class="kpi-box"><div class="kpi-icon text-secondary"><i class="bi bi-envelope-paper"></i></div><div><div class="kpi-value">{{ $attivita['inviti'] }}</div><div class="kpi-label">Inviti</div></div></div></div>
                        <div class="col-6"><div class="kpi-box"><div class="kpi-icon text-success"><i class="bi bi-mouse"></i></div><div><div class="kpi-value">{{ $attivita['click'] }}</div><div class="kpi-label">Click</div></div></div></div>
                        <div class="col-6"><div class="kpi-box"><div class="kpi-icon text-info"><i class="bi bi-trophy"></i></div><div><div class="kpi-value">{{ $attivita['complete_totali'] }}</div><div class="kpi-label">Complete</div></div></div></div>
                        <div class="col-6"><div class="kpi-box"><div class="kpi-icon text-warning"><i class="bi bi-hourglass-split"></i></div><div><div class="kpi-value">{{ $attivita['sospese'] }}</div><div class="kpi-label">Sospese</div></div></div></div>
                        <div class="col-6"><div class="kpi-box"><div class="kpi-icon text-muted"><i class="bi bi-x-octagon"></i></div><div><div class="kpi-value">{{ $attivita['non_target'] }}</div><div class="kpi-label">Non Target</div></div></div></div>
                        <div class="col-12"><div class="kpi-box"><div class="kpi-icon text-primary"><i class="bi bi-graph-up"></i></div><div><div class="kpi-value">{{ $attivita['partecipazione'] }}%</div><div class="kpi-label">Partecipazione</div></div></div></div>
                    </div>

                    <div class="text-center mt-3">
                        <small class="text-muted"><i class="bi bi-clock-history me-1"></i> Ultima attività: {{ $attivita['ultima_attivita'] ? \Carbon\Carbon::parse($attivita['ultima_attivita'])->format('d/m/Y H:i') : '-' }}</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== 3) PREMI ===== --}}
        <div class="col-12">
            <div class="card">
                <div class="card-header header-premi">
                    <h6><i class="bi bi-gift"></i> Premi</h6>
                    <div class="premi-stats text-end small">
                        <small><strong>Pagati:</strong> {{ $premi['pagati'] }}</small>
                        <small><strong>Da pagare:</strong> {{ $premi['da_pagare'] }}</small>
                        <small><strong>Totali:</strong> {{ $premi['totali'] }}</small>
                    </div>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-striped text-center align-middle">
                        <thead><tr><th>Premio</th><th>Codice</th><th>Richiesto</th><th>Giorni</th><th>Pagato</th><th>Status</th><th>IP</th></tr></thead>
                        <tbody>
                            @forelse($premi['lista'] as $p)
                                @php
                                    $giorni = $p->giorno_paga
                                        ? \Carbon\Carbon::parse($p->giorno_paga)->diffInDays(\Carbon\Carbon::parse($p->event_date))
                                        : \Carbon\Carbon::parse($p->event_date)->diffInDays(now());
                                @endphp
                                <tr>
                                    <td>{{ $p->event_info }}</td>
                                    <td>
                                        <span class="badge bg-light text-dark">{{ $p->codice ?? '-' }}</span>
                                        @if($p->codice)
                                            <button class="copy-btn ms-1" onclick="navigator.clipboard.writeText('{{ $p->codice }}')"><i class="bi bi-clipboard"></i></button>
                                        @endif
                                    </td>
                                    <td>{{ \Carbon\Carbon::parse($p->event_date)->format('d/m/Y') }}</td>
                                    <td>{{ $giorni }}</td>
                                    <td>{{ $p->giorno_paga ? \Carbon\Carbon::parse($p->giorno_paga)->format('d/m/Y') : '-' }}</td>
                                    <td>@if($p->pagato == 1)<span class="badge bg-success">Pagato</span>@else<span class="badge bg-warning text-dark">Da pagare</span>@endif</td>
                                    <td>{{ $p->ip ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-muted">Nessun premio trovato</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ===== 4) STORICO ===== --}}
        <div class="col-12">
            <div class="card">
                <div class="card-header header-storico">
                    <h6><i class="bi bi-clock-history"></i> Storico Attività</h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-striped text-center align-middle">
                        <thead><tr><th>Giorno</th><th>Evento</th><th>Bytes</th></tr></thead>
                        <tbody>
                            @forelse($storico as $s)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($s->event_date)->format('d/m/Y H:i') }}</td>
                                    <td>{{ $s->event_info }}</td>
                                    <td>
                                        @if($s->delta > 0)
                                            <span class="text-green">+{{ $s->delta }}</span>
                                        @elseif($s->delta < 0)
                                            <span class="text-red">{{ $s->delta }}</span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-muted">Nessun evento registrato</td></tr>
                            @endforelse
                        </tbody>
                    </table>

                    @if($storico->count() >= 20)
                        <div class="text-center mt-3">
                            <a href="{{ url('/panel/user/' . $user->user_id . '?full=1') }}" class="btn btn-outline-secondary btn-sm btn-show-all">
                                <i class="bi bi-list-ul me-1"></i> Mostra tutto
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
