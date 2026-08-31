@php $qualityByIid = $qualityByIid ?? []; @endphp
@forelse($storico as $s)
@php
    $hasSid  = isset($s->sid) && $s->sid !== '-' && $s->sid !== '';
    $hasPrj  = isset($s->prj) && $s->prj !== '-' && $s->prj !== '';
    $hasIid  = isset($s->iid) && $s->iid !== '-' && $s->iid !== '';
    $qData   = ($hasIid && isset($qualityByIid[$s->iid])) ? $qualityByIid[$s->iid] : null;
    $qTier   = $qData['tier'] ?? '';
    $qBadge  = $qTier === 'regolare' ? 'badge-soft-success'
             : ($qTier === 'incerta'  ? 'badge-soft-warning'
             : ($qTier === 'anomala'  ? 'badge-soft-danger'  : 'badge-soft-secondary'));
@endphp
    <tr>
        <td>{{ \Carbon\Carbon::parse($s->event_date)->format('d/m/Y H:i') }}</td>
        <td>
            <span class="badge bg-{{ $s->evento_color }} d-inline-flex align-items-center px-2 py-1">
                <i class="bi {{ $s->evento_icon }} me-1"></i>{{ $s->evento_label }}
            </span>
        </td>
        <td class="text-muted small">{{ $s->tipologia }}</td>
        <td>{{ $s->iid }}</td>
        <td>
            @if($hasSid && $hasPrj)
                <a href="{{ url('fieldControl') }}?prj={{ urlencode($s->prj) }}&sid={{ urlencode($s->sid) }}"
                   target="_blank" rel="noopener" class="text-decoration-none fw-semibold">
                    {{ $s->sid }}
                </a>
            @else
                {{ $s->sid }}
            @endif
        </td>
        <td>{{ $s->prj }}</td>
        <td>
            @if($qData !== null)
                <span class="badge {{ $qBadge }}">{{ $qData['score'] ?? '—' }}</span>
            @else
                <span class="text-muted small">—</span>
            @endif
        </td>
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
    <tr>
        <td colspan="8" class="text-muted">Nessun evento registrato</td>
    </tr>
@endforelse
