<div
    class="referral-detail-wrapper"
    id="referralDetailWrapper"
    data-ref="{{ $referrer->user_id }}"
    data-ref-email="{{ $referrer->email }}"
>
    <div class="mb-3">
        <h5 class="mb-1">
            Invitati attivi associati a
            <span class="text-primary">{{ $referrer->user_id }}</span>
        </h5>

        <div class="text-muted small">
            Email referrer: {{ $referrer->email ?: '-' }}
        </div>

        <div class="text-muted small mt-1">
            Controlli email: rosso = non valida/sospetta, giallo = simile al referrer, verde = OK
        </div>
    </div>

    @if($invitedUsers->count() > 0)
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0" id="referral-detail-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>User ID</th>
                        <th>Email</th>
                        <th>Registrazione</th>
                        <th>Azioni</th>
                        <th style="width: 160px;">Operazione</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invitedUsers as $index => $user)
                        @php
                            $check = $user->email_check ?? ['status' => 'red', 'label' => 'N/D'];
                            $isSuspect = in_array($check['status'] ?? 'red', ['red', 'yellow'], true);
                        @endphp

                                <tr
                            data-uid="{{ $user->user_id }}"
                            data-actions="{{ (int) $user->actions }}"
                            data-active="{{ (int) $user->active }}"
                            data-email-status="{{ $check['status'] }}"
                            data-is-suspect="{{ $isSuspect ? 1 : 0 }}"
                        >
                            <td>{{ $index + 1 }}</td>

                            <td>
                                <a href="{{ route('user.profile', ['user_id' => $user->user_id]) }}" target="_blank">
                                    {{ $user->user_id }}
                                </a>
                            </td>

                            <td>
                                <div class="referral-email-check-cell">
                                    <span class="referral-email-dot referral-email-dot-{{ $check['status'] }}"></span>
                                    <span>{{ $user->email ?: '-' }}</span>
                                </div>
                                <div class="referral-email-note">
                                    {{ $check['label'] }}
                                </div>
                            </td>

                            <td>{{ $user->reg_date ?: '-' }}</td>

                            <td>{{ (int) $user->actions }}</td>

                            <td>
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-danger btn-remove-referral"
                                >
                                    Rimuovi dal referral
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div id="referralDetailMessage" class="mt-3"></div>
    @else
        <div class="alert alert-info mb-0">
            Nessun invitato attivo trovato per questo referrer.
        </div>
    @endif
</div>
