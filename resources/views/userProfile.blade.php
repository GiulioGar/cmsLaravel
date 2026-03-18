@extends('layouts.main')


@section('content')

<link rel="stylesheet" href="{{ asset('css/userProfile.css') }}">

<div class="container-fluid mt-4">

    <div class="row g-4">
        {{-- ===== 1) ANAGRAFICA ===== --}}
        <div class="col-lg-6">
            <div class="card">
            <div class="card-header section-header header-anagrafica">
                <div class="section-header-left">
                    <div class="section-icon">
                        <i class="bi bi-person-lines-fill"></i>
                    </div>
                    <div>
                        <h5 class="section-title mb-0">Anagrafica</h5>
                        <div class="section-subtitle">Dati personali e informazioni di contatto</div>
                    </div>
                </div>
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
                        <tr><th>Provincia:</th><td>{{ $user->province_name ?? '-' }} @if(!empty($user->province_id)) <small class="text-muted">(#{{ $user->province_id }})</small> @endif</td></tr>
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

<div class="card-header section-header header-attivita">
    <div class="section-header-left">
        <div class="section-icon">
            <i class="bi bi-activity"></i>
        </div>
        <div>
            <h5 class="section-title mb-0">Attività</h5>
            <div class="section-subtitle">Metriche di partecipazione e performance utente</div>
        </div>
    </div>
</div>

                <div class="card-body p-3">

                    {{-- 🔹 SEZIONE ATTIVITÀ --}}
{{-- 🔹 SEZIONE ATTIVITÀ --}}
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">

        <div class="row g-3">

    {{-- Inviti totali --}}
    <div class="col-12 col-md-4">
        <div class="kpi-box d-flex flex-column justify-content-center align-items-center border rounded-3 shadow-sm bg-white h-100 p-3 text-center">
            <div class="kpi-icon text-secondary mb-2">
                <i class="bi bi-envelope-paper fs-3"></i>
            </div>
            <div class="kpi-value fs-4 fw-bold">{{ $attivita['inviti'] }}</div>
            <div class="kpi-label text-muted small">Inviti totali</div>
        </div>
    </div>

    {{-- Bytes totali + Bonus/Malus --}}
    <div class="col-12 col-md-4">
        <div class="kpi-box d-flex flex-column justify-content-center align-items-center border rounded-3 shadow-sm bg-white h-100 p-3 text-center">
            <div class="kpi-icon text-primary mb-2">
                <i class="bi bi-coin fs-3"></i>
            </div>
            <div id="userPoints" class="kpi-value fs-4 fw-bold">{{ $user->points ?? 0 }}</div>
            <div class="kpi-label text-muted small mb-2">Bytes totali</div>

            <button class="btn btn-sm btn-outline-primary mt-auto"
                    data-bs-toggle="modal"
                    data-bs-target="#modalBonusMalus">
                <i class="bi bi-plus-slash-minus me-1"></i> Bonus / Malus
            </button>
        </div>
    </div>

    {{-- Ultima attività --}}
    <div class="col-12 col-md-4">
        <div class="kpi-box d-flex flex-column justify-content-center align-items-center border rounded-3 shadow-sm bg-white h-100 p-3 text-center">
            <div class="kpi-icon text-success mb-2">
                <i class="bi bi-clock-history fs-3"></i>
            </div>
            <div class="kpi-value fs-6 fw-bold">
                {{ $attivita['ultima_attivita'] ? \Carbon\Carbon::parse($attivita['ultima_attivita'])->format('d/m/Y') : '-' }}
            </div>
            <div class="kpi-label text-muted small">Ultima attività</div>
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
            <div class="card-header section-header header-premi">
                <div class="section-header-left">
                    <div class="section-icon">
                        <i class="bi bi-gift"></i>
                    </div>
                    <div>
                        <h5 class="section-title mb-0">Premi</h5>
                        <div class="section-subtitle">Storico richieste premio e stato dei pagamenti</div>
                    </div>
                </div>

                <div class="header-stats">
                    <div class="header-stat-card stat-success">
                        <span class="header-stat-label">Pagati</span>
                        <span class="header-stat-value">{{ $premi['pagati'] }}</span>
                    </div>

                    <div class="header-stat-card stat-warning">
                        <span class="header-stat-label">Da pagare</span>
                        <span class="header-stat-value">{{ $premi['da_pagare'] }}</span>
                    </div>

                    <div class="header-stat-card stat-neutral">
                        <span class="header-stat-label">Totali</span>
                        <span class="header-stat-value">{{ $premi['totali'] }}</span>
                    </div>
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
                                           <button class="copy-btn ms-1 copy-code-btn" data-code="{{ trim($p->codice2) }}"> <i class="bi bi-clipboard"></i> </button>
                                        @endif
                                    </td>
                                    <td>{{ \Carbon\Carbon::parse($p->event_date)->format('d/m/Y') }}</td>
                                    <td>{{ $giorni }}</td>
                                    <td>{{ $p->giorno_paga ? \Carbon\Carbon::parse($p->giorno_paga)->format('d/m/Y') : '-' }}</td>
                                    <td>
                                        @if($p->pagato == 1)
                                            <span class="badge badge-soft-success">Pagato</span>
                                        @else
                                            <span class="badge badge-soft-warning">Da pagare</span>
                                        @endif
                                    </td>
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
<div class="card-header section-header header-storico">
    <div class="section-header-left">
        <div class="section-icon">
            <i class="bi bi-clock-history"></i>
        </div>
        <div>
            <h5 class="section-title mb-0">Storico Attività</h5>
            <div class="section-subtitle">Eventi recenti, movimenti punti e premi</div>
        </div>
    </div>
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

            <tbody id="storicoTableBody">
                @include('partials.userProfileStoricoRows', ['storico' => $storico])
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
    document.getElementById('btnDeactivate')?.addEventListener('click', () => {
        if (confirm('Confermi la disattivazione dell’utente?')) {
            sendUserAction(`/user/${userId}/deactivate`, 'modalUserActive');
        }
    });

    document.getElementById('btnDelete')?.addEventListener('click', () => {
        if (confirm('Confermi l’eliminazione definitiva dell’utente?')) {
            sendUserAction(`/user/${userId}/delete`, 'modalUserActive');
        }
    });

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
    // 🔹 COPIA CODICE PREMIO
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
