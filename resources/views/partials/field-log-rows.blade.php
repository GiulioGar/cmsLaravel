{{--
    Righe <tr> del Log Attivita' (fieldControl.blade.php), estratte in partial
    cosi' il controller puo' ri-renderizzarle identiche per la ricerca AJAX
    su tutte le interviste (non solo le 1000 di default) - vedi
    FieldControlController::searchLog().
--}}
@forelse ($logData as $log)
    <tr class="align-middle">
        <td class="small">
            <span class="fc-iid-link" data-bs-toggle="tooltip" data-bs-html="true"
                  data-bs-custom-class="fc-iid-tooltip-box" title="{{ $log['iid_tooltip'] ?? '' }}">
                {{ $log['iid'] }}
            </span>
        </td>
        <td class="small">{{ $log['uid'] }}</td>
        <td class="small">{!! $log['ultimo_update'] !!}</td>
        <td class="small">
            {!! $log['ultima_azione_sistema'] !!}
        </td>
        <td class="small">
            {!! $log['ultima_azione_file'] !!}
        </td>
        <td class="small">
            @if ($log['stato'] === 'Completa')
                <span class="fc-log-status-icon" data-bs-toggle="tooltip" title="Completa">
                    <i class="fas fa-check-circle text-success"></i>
                </span>
            @elseif ($log['stato'] === 'Non in target')
                <span class="fc-log-status-icon" data-bs-toggle="tooltip" title="Non in target">
                    <i class="fas fa-times-circle text-danger"></i>
                </span>
            @elseif ($log['stato'] === 'Quotafull')
                <span class="fc-log-status-icon" data-bs-toggle="tooltip" title="Quotafull">
                    <i class="fas fa-exclamation-triangle text-warning"></i>
                </span>
            @elseif ($log['stato'] === 'In Corso')
                <span class="fc-log-status-icon" data-bs-toggle="tooltip" title="In Corso">
                    <i class="fas fa-hourglass-half text-primary"></i>
                </span>
            @elseif ($log['stato'] === 'Stop Int')
                <span class="fc-log-status-icon" data-bs-toggle="tooltip" title="Stop Int">
                    <i class="fas fa-ban text-dark"></i>
                </span>
            @elseif ($log['stato'] === 'Bad Quality')
                <span class="fc-log-status-icon" data-bs-toggle="tooltip" title="Bad Quality">
                    <i class="fas fa-flag text-danger"></i>
                </span>
            @else
                <span class="fc-log-status-icon" data-bs-toggle="tooltip" title="{{ $log['stato'] }}">
                    <i class="fas fa-circle text-secondary"></i>
                </span>
            @endif
        </td>
        <td class="small">
            <span class="fc-log-status-icon" data-bs-toggle="tooltip" title="{{ $log['panel'] ?? 'N/A' }}">
                @if (($log['panel'] ?? null) === 'Interactive')
                    <i class="fa-solid fa-house text-success"></i>
                @else
                    <i class="fa-solid fa-earth-americas text-primary"></i>
                @endif
            </span>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="7" class="p-0 border-0">
            <div class="fc-empty-state fc-empty-state--compact">
                <div class="fc-empty-robot">
                    <i class="fa-solid fa-robot"></i>
                </div>
                <div class="fc-empty-title">{{ $emptyTitle ?? 'Log attività ancora vuoto' }}</div>
                <div class="fc-empty-subtitle">{{ $emptySubtitle ?? 'Al momento non ci sono interviste da conteggiare.' }}</div>
            </div>
        </td>
    </tr>
@endforelse
