<div class="card">
<div class="card-header panel-table-header {{ $type === 'amazon' ? 'theme-amazon' : 'theme-paypal' }}">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <span class="panel-table-header-icon">
                @if($type === 'amazon')
                    <i class="bi bi-gift"></i>
                @else
                    <i class="bi bi-paypal"></i>
                @endif
            </span>

            <div>
                <h5 class="mb-0">
                    Elenco richieste {{ $type === 'amazon' ? 'Amazon' : 'Paypal' }}
                </h5>
                <small class="text-muted">
                    {{ $type === 'amazon' ? 'Gestione codici e assegnazioni buoni Amazon' : 'Gestione richieste e pagamenti Paypal' }}
                </small>
            </div>
        </div>

        <span class="panel-type-badge {{ $type === 'amazon' ? 'badge-amazon' : 'badge-paypal' }}">
            {{ strtoupper($type) }}
        </span>
    </div>
</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="premi-panel-table" class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Utente</th>
                        <th>Valore</th>
                        <th>Bytes</th>
                        <th>Richiesta</th>

                        @if($type === 'amazon')
                            <th>Codice</th>
                        @else
                            <th>Conto Paypal</th>
                            <th>Note</th>
                        @endif

                <th>Pagato</th>

                @if($type === 'amazon')
                    <th>{{ (string) $status === '1' ? 'Evaso' : 'Giorni' }}</th>
                @else
                    <th>{{ (string) $status === '1' ? 'Evaso' : 'Assegna' }}</th>
                @endif

                <th>Elimina</th>

                    </tr>
                </thead>
<tbody>
    @foreach($richieste as $r)
        <tr id="reward-row-{{ $r->id }}">
            <td>
                <div class="fw-semibold">
                    <a href="{{ route('user.profile', ['user_id' => $r->user_id]) }}" target="_blank" class="text-decoration-none">
                        {{ $r->user_id }}
                    </a>
                </div>
                <div class="small text-muted">{{ $r->email ?? '-' }}</div>
            </td>

            <td>
                @if($r->premio_valore)
                    @php
                        $valueClass = 'badge-soft-neutral';

                        if ((int) $r->premio_valore === 2) {
                            $valueClass = 'badge-value-2';
                        } elseif ((int) $r->premio_valore === 5) {
                            $valueClass = 'badge-value-5';
                        } elseif ((int) $r->premio_valore === 10) {
                            $valueClass = 'badge-value-10';
                        } elseif ((int) $r->premio_valore === 20) {
                            $valueClass = 'badge-value-20';
                        }
                    @endphp

                    <span class="badge {{ $valueClass }}">
                        {{ $r->premio_valore }}€
                    </span>
                @else
                    -
                @endif
            </td>

            <td>{{ $r->points_spesi }}</td>

            <td>
                <div>{{ $r->requested_at }}</div>
                <div class="small text-muted reward-ip-cell">{{ $r->ip ?? '-' }}</div>
            </td>

            @if($type === 'amazon')
                <td class="reward-code-cell">{{ $r->codice2 ?? '-' }}</td>
            @else
                <td class="reward-paypal-account-cell">
                    @if(!empty($r->paypalEmail))
                        <span>{{ $r->paypalEmail }}</span>
                    @else
                        <span class="paypal-missing-icon" data-bs-toggle="tooltip" data-bs-placement="top" title="Conto Paypal non disponibile">
                            <i class="bi bi-slash-circle"></i>
                        </span>
                    @endif
                </td>

                <td class="reward-note-cell text-center">
                    @if(!empty($r->codice2))
                        <button
                            type="button"
                            class="note-action note-action-read btn-read-note"
                            data-id="{{ $r->id }}"
                            data-note="{{ e($r->codice2) }}"
                            data-bs-toggle="tooltip"
                            data-bs-placement="top"
                            title="{{ $r->codice2 }}"
                        >
                            <span class="note-action-icon">
                                <i class="bi bi-eye"></i>
                            </span>
                            <span class="note-action-label">Leggi</span>
                        </button>
                    @else
                        <button
                            type="button"
                            class="note-action note-action-add btn-add-note"
                            data-id="{{ $r->id }}"
                            title="Aggiungi nota"
                        >
                            <span class="note-action-icon">
                                <i class="bi bi-plus-circle"></i>
                            </span>
                            <span class="note-action-label">Aggiungi</span>
                        </button>
                    @endif
                </td>
            @endif

            <td class="payment-status-cell text-center">
                @if((int) $r->pagato === 1)
                    <span class="action-icon success" title="Pagato">
                        <i class="bi bi-check-circle-fill"></i>
                    </span>
                @else
                    <span class="action-icon warning" title="Da pagare">
                        <i class="bi bi-clock-history"></i>
                    </span>
                @endif
            </td>

<td class="payment-action-cell text-center">

    @if($type === 'amazon')

        @if((int) $r->pagato === 0)
            <span class="badge badge-soft-warning">
                {{ $r->giorni_attesa }} gg
            </span>
        @else
            <span class="badge badge-soft-success">
                {{ $r->paid_at }}
            </span>
        @endif

    @else

        @if((int) $r->pagato === 0)
            <button
                type="button"
                class="btn btn-sm btn-icon btn-outline-primary btn-pay-paypal"
                data-id="{{ $r->id }}"
                data-bs-toggle="tooltip"
                data-bs-placement="top"
                title="Segna come pagato"
            >
                <i class="bi bi-cash-coin"></i>
            </button>
        @else
            <span class="badge badge-soft-success">
                {{ $r->paid_at }}
            </span>
        @endif

    @endif

</td>

            <td class="delete-action-cell text-center">
                <button
                    type="button"
                    class="btn btn-sm btn-icon btn-outline-danger btn-delete-reward"
                    data-id="{{ $r->id }}"
                    data-bs-toggle="tooltip"
                    data-bs-placement="top"
                    title="Elimina richiesta"
                >
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>
    @endforeach
</tbody>
            </table>
        </div>
    </div>
</div>
