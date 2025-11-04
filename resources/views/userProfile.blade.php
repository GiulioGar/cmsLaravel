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

.badge[role="button"] {
    cursor: pointer;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    transition: all .2s;
}
.badge[role="button"]:hover {
    transform: scale(1.05);
    opacity: .9;
}

.btn-outline-primary.position-absolute {
    background-color: #fff;
    border-color: #0d6efd;
    color: #0d6efd;
    transition: all 0.2s ease;
}
.btn-outline-primary.position-absolute:hover {
    background-color: #0d6efd;
    color: #fff;
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
                        {{-- 👤 Colonna sinistra: Avatar + Info base (versione migliorata) --}}
                        <div class="col-md-6 d-flex align-items-center">
                            <div class="me-3 position-relative">
                                {{-- Avatar ridotto --}}
                                <img src="https://ui-avatars.com/api/?name={{ urlencode($user->full_name ?? $user->user_id) }}&background=0D8ABC&color=fff&size=70"
                                    class="rounded-circle shadow-sm border border-2 border-light" alt="avatar">

                                {{-- Bottone modifica sopra avatar --}}
                                <button class="btn btn-sm btn-outline-primary position-absolute top-0 end-0 translate-middle p-1"
                                        style="border-radius: 50%;"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalEditAnagrafica"
                                        title="Modifica anagrafica">
                                    <i class="bi bi-pencil"></i>
                                </button>
                            </div>

                            <div>
                                <h5 class="fw-bold mb-1">{{ $user->full_name ?? $user->user_id }}</h5>

                                <div class="text-muted small mb-1">
                                    <i class="bi bi-envelope me-1"></i> {{ $user->email ?? '-' }}
                                </div>

                                <div class="text-muted small mb-2">
                                    <i class="bi bi-paypal me-1 text-primary"></i> {{ $user->paypalEmail ?? '-' }}
                                </div>

                                {{-- 🔹 Stato utente --}}
                                <div>
                                    @if($user->active == 1)
                                        <span class="badge bg-success rounded-pill px-3 py-2"
                                            role="button"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalUserActive"
                                            title="Gestisci stato utente">
                                            <i class="bi bi-check-circle me-1"></i> Attivo
                                        </span>
                                    @else
                                        <span class="badge bg-danger rounded-pill px-3 py-2"
                                            role="button"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalUserInactive"
                                            title="Gestisci stato utente">
                                            <i class="bi bi-x-circle me-1"></i> Non Attivo
                                        </span>
                                    @endif
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

            {{-- Bytes totali + gestione Bonus/Malus --}}
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="kpi-box d-flex flex-column justify-content-center align-items-center border rounded-3 shadow-sm bg-white h-100 p-3 text-center">
                        <div class="kpi-icon text-primary mb-2"><i class="bi bi-coin fs-3"></i></div>
                        <div id="userPoints" class="kpi-value fs-4 fw-bold">{{ $user->points ?? 0 }}</div>
                        <div class="kpi-label text-muted small mb-2">Bytes totali</div>

                        {{-- Bottone per assegnare Bonus o Malus --}}
                        <button class="btn btn-sm btn-outline-primary mt-auto"
                                data-bs-toggle="modal"
                                data-bs-target="#modalBonusMalus">
                            <i class="bi bi-plus-slash-minus me-1"></i> Bonus / Malus
                        </button>
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


{{-- 🔹 Modal utente attivo --}}
<div class="modal fade" id="modalUserActive" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
                <h6 class="modal-title"><i class="bi bi-person-gear me-1"></i> Gestione utente attivo</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <p class="mb-3">Cosa desideri fare con <strong>{{ $user->full_name ?? $user->user_id }}</strong>?</p>
                <button class="btn btn-outline-warning me-2" id="btnDeactivate">
                    <i class="bi bi-person-dash me-1"></i> Disattiva
                </button>
                <button class="btn btn-outline-danger" id="btnDelete">
                    <i class="bi bi-trash me-1"></i> Elimina definitivamente
                </button>
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

{{-- 🔹 Contenitore Toasts Bootstrap --}}
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

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const userId = "{{ $user->user_id }}";

    // ===========================
    // 🔹 GESTIONE STATO UTENTE
    // ===========================

    // Disattivazione
    document.getElementById('btnDeactivate')?.addEventListener('click', () => {
        if (confirm('Confermi la disattivazione dell’utente?')) {
            sendUserAction(`/user/${userId}/deactivate`, 'modalUserActive');
        }
    });

    // Eliminazione
    document.getElementById('btnDelete')?.addEventListener('click', () => {
        if (confirm('Confermi l’eliminazione definitiva dell’utente?')) {
            sendUserAction(`/user/${userId}/delete`, 'modalUserActive');
        }
    });

    // Riattivazione
    document.getElementById('btnActivate')?.addEventListener('click', () => {
        if (confirm('Confermi la riattivazione dell’utente?')) {
            sendUserAction(`/user/${userId}/activate`, 'modalUserInactive');
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
                // Chiudi la modale associata
                const modalEl = document.getElementById(modalId);
                const modalInstance = bootstrap.Modal.getInstance(modalEl);
                modalInstance?.hide();

                // Mostra messaggio e ricarica pagina
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

        fetch(`/user/${userId}/update-info`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ email, paypalEmail })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Chiude la modale anagrafica
                const modalEl = document.getElementById('modalEditAnagrafica');
                const modalInstance = bootstrap.Modal.getInstance(modalEl);
                modalInstance?.hide();

                // Feedback e refresh
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
});



// ===========================
// 🔹 BONUS / MALUS
// ===========================
document.getElementById('btnSaveBonusMalus')?.addEventListener('click', () => {
    const userId = "{{ $user->user_id }}";
    const rawType = document.getElementById('bmType').value; // "Bonus" | "Malus"
    const type = rawType.toUpperCase();                      // normalizzato in "BONUS" | "MALUS"
    const value = parseInt(document.getElementById('bmValue').value.trim());
    const motivation = document.getElementById('bmMotivation').value.trim();

    if (!value || !motivation) {
        showToast('Compila tutti i campi.');
        return;
    }

    fetch(`/user/${userId}/bonus-malus`, {
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
        if (data.success) {
            // ✅ Chiudi la modale
            const modalEl = document.getElementById('modalBonusMalus');
            const modalInstance = bootstrap.Modal.getInstance(modalEl);
            modalInstance?.hide();

            // ✅ Messaggio e ricarica
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



</script>
@endsection

