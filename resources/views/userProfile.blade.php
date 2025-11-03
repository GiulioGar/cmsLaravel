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

.kpi-box {
    min-height: 150px;
    transition: all 0.2s ease-in-out;
}
.kpi-box:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 14px rgba(0, 0, 0, 0.08);
}
.kpi-value {
    line-height: 1.1;
}
.kpi-label {
    margin-top: 2px;
}

.badge.bg-success-dark {
    background-color: #0c713d !important;
}
.badge.bg-orange {
    background-color: #ff8c00 !important;
}

.header-storico {
    background: linear-gradient(90deg, #e68c26, #e6ab0b);
    color: #fff;
    padding: 0.75rem 1rem;
    border-bottom: none;
    border-radius: 6px 6px 0 0;
}
.badge.bg-success-dark {
    background-color: #0d5c36 !important;
}
.badge.bg-info-light {
    background-color: #5bc0de !important; /* azzurro più chiaro rispetto a verde */
    color: #fff !important;
}
.badge.bg-orange {
    background-color: #ff8c00 !important;
    color: #fff !important;
}
.table thead th {
    font-size: 0.85rem;
    text-transform: uppercase;
    color: #6c757d;
}
.table td {
    vertical-align: middle;
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

                        {{-- 🔹 CARD PROFILO UTENTE --}}
                        <div class="card shadow-sm mb-4 border-0">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    {{-- 👤 Colonna sinistra: Avatar + Info base --}}
                                    <div class="col-md-6 d-flex align-items-center">
                                        <div class="me-3">
                                            <img src="https://ui-avatars.com/api/?name={{ urlencode($user->full_name ?? $user->user_id) }}&background=0D8ABC&color=fff&size=90"
                                                class="rounded-circle shadow-sm border border-2 border-light" alt="avatar">
                                        </div>
                                        <div>
                                            <h4 class="fw-bold mb-1">{{ $user->full_name ?? $user->user_id }}</h4>
                                            <div class="text-muted small mb-1">
                                                <i class="bi bi-envelope me-1"></i> {{ $user->email }}
                                            </div>
                                            <div>
                                                <span class="badge bg-success d-inline-flex align-items-center px-3 py-2 shadow-sm">
                                                    <i class="bi bi-check-circle me-1"></i> Attivo
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- 📋 Colonna destra: Info aggiuntive --}}
                                    <div class="col-md-6 text-md-end mt-3 mt-md-0">
                                        <div class="small text-muted">
                                            Registrato il: {{ $user->reg_date ? \Carbon\Carbon::parse($user->reg_date)->format('d/m/Y') : '-' }}
                                            <div><i class="bi bi-clock-history me-1"></i> Ultima attività: <strong>{{ $attivita['ultima_attivita'] ? \Carbon\Carbon::parse($attivita['ultima_attivita'])->format('d/m/Y') : '-' }}</strong></div>
                                        </div>
                                    </div>
                                </div>
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

                    {{-- 🔹 SEZIONE ATTIVITÀ --}}
{{-- 🔹 SEZIONE ATTIVITÀ --}}
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white border-0 pb-0">
        <h5 class="fw-semibold mb-0">
            <i class="bi bi-activity text-primary me-2"></i> Attività utente
        </h5>
    </div>

    <div class="card-body">
        <div class="row g-3">

            {{-- Inviti con dettaglio CINT / MILLEBYTES --}}
            <div class="col-6 col-md-4 col-lg-3">
                <div class="kpi-box d-flex flex-column justify-content-center align-items-center border rounded-3 shadow-sm bg-white h-100 p-3 text-center">
                    <div class="kpi-icon text-secondary mb-2"><i class="bi bi-envelope-paper fs-3"></i></div>
                    <div class="kpi-value fs-4 fw-bold">{{ $attivita['inviti'] }}</div>
                    <div class="kpi-label text-muted small">Inviti totali</div>

                    {{-- Mini dettaglio CINT/MILLEBYTES --}}
                    <div class="pt-2 mt-1 border-top w-100">
                        <div class="d-flex justify-content-between px-2 text-muted small">
                            <span><i class="bi bi-circle-fill text-success small me-1"></i>CINT</span>
                            <span>{{ $attivita['cint_inviti'] }}</span>
                        </div>
                        <div class="d-flex justify-content-between px-2 text-muted small">
                            <span><i class="bi bi-circle-fill text-primary small me-1"></i>MIL</span>
                            <span>{{ $attivita['millebytes_inviti'] }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Click --}}
            <div class="col-6 col-md-4 col-lg-3">
                <div class="kpi-box d-flex flex-column justify-content-center align-items-center border rounded-3 shadow-sm bg-white h-100 p-3 text-center">
                    <div class="kpi-icon text-success mb-2"><i class="bi bi-mouse fs-3"></i></div>
                    <div class="kpi-value fs-4 fw-bold">{{ $attivita['click'] }}</div>
                    <div class="kpi-label text-muted small">Click</div>
                </div>
            </div>

            {{-- Complete --}}
            <div class="col-6 col-md-4 col-lg-3">
                <div class="kpi-box d-flex flex-column justify-content-center align-items-center border rounded-3 shadow-sm bg-white h-100 p-3 text-center">
                    <div class="kpi-icon text-info mb-2"><i class="bi bi-trophy fs-3"></i></div>
                    <div class="kpi-value fs-4 fw-bold">{{ $attivita['complete_totali'] }}</div>
                    <div class="kpi-label text-muted small">Complete</div>
                </div>
            </div>

            {{-- Sospese --}}
            <div class="col-6 col-md-4 col-lg-3">
                <div class="kpi-box d-flex flex-column justify-content-center align-items-center border rounded-3 shadow-sm bg-white h-100 p-3 text-center">
                    <div class="kpi-icon text-warning mb-2"><i class="bi bi-hourglass-split fs-3"></i></div>
                    <div class="kpi-value fs-4 fw-bold">{{ $attivita['sospese'] }}</div>
                    <div class="kpi-label text-muted small">Sospese</div>
                </div>
            </div>

            {{-- Non Target --}}
            <div class="col-6 col-md-4 col-lg-3">
                <div class="kpi-box d-flex flex-column justify-content-center align-items-center border rounded-3 shadow-sm bg-white h-100 p-3 text-center">
                    <div class="kpi-icon text-muted mb-2"><i class="bi bi-x-octagon fs-3"></i></div>
                    <div class="kpi-value fs-4 fw-bold">{{ $attivita['non_target'] }}</div>
                    <div class="kpi-label text-muted small">Non Target</div>
                </div>
            </div>

            {{-- Partecipazione --}}
            <div class="col-6 col-md-4 col-lg-3">
                <div class="kpi-box d-flex flex-column justify-content-center align-items-center border rounded-3 shadow-sm bg-white h-100 p-3 text-center">
                    <div class="kpi-icon text-primary mb-2"><i class="bi bi-graph-up fs-3"></i></div>
                    <div class="kpi-value fs-4 fw-bold">{{ $attivita['partecipazione'] }}%</div>
                    <div class="kpi-label text-muted small">Partecipazione</div>
                </div>
            </div>

        </div>
    </div>
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
                                        <span class="badge bg-light text-dark">{{ $p->codice2 ?? '-' }}</span>
                                        @if($p->codice2)
                                            <button class="copy-btn ms-1" onclick="navigator.clipboard.writeText('{{ $p->codice2 }}')"><i class="bi bi-clipboard"></i></button>
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
                <thead>
                    <tr>
                        <th>Data Evento</th>
                        <th>Evento</th>
                        <th>Tipologia</th>
                        <th>IID</th>
                        <th>SID</th>
                        <th>PRJ</th>
                        <th>Bytes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($storico as $s)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($s->event_date)->format('d/m/Y H:i') }}</td>
                            <td>
                                <span class="badge bg-{{ $s->evento_color }} d-inline-flex align-items-center px-2 py-1">
                                    <i class="bi {{ $s->evento_icon }} me-1"></i>{{ $s->evento_label }}
                                </span>
                            </td>
                            <td class="text-muted small">{{ $s->tipologia }}</td>
                            <td>{{ $s->iid }}</td>
                            <td>{{ $s->sid }}</td>
                            <td>{{ $s->prj }}</td>
<td>
    @if($s->bytes > 0)
        <span class="text-success fw-semibold">
            +{{ $s->bytes }}
            <small class="text-muted">({{ $s->new_level }} - {{ $s->prev_level }})</small>
        </span>
    @elseif($s->bytes < 0)
        <span class="text-danger fw-semibold">
            {{ $s->bytes }}
            <small class="text-muted">({{ $s->new_level }} - {{ $s->prev_level }})</small>
        </span>
    @else
        <span class="text-muted">0</span>
    @endif
</td>

                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-muted">Nessun evento registrato</td></tr>
                    @endforelse
                </tbody>
            </table>

            @if($storico->count() >= 20)
                <div class="text-center mt-3">
                    <a href="{{ url('/panel/user/' . $user->user_id . '?full=1') }}"
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

@endsection
