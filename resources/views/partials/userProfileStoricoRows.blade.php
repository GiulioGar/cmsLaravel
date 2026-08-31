@php $qualityByIid = $qualityByIid ?? []; @endphp
@forelse($storico as $s)
@php
    $hasSid  = isset($s->sid) && $s->sid !== '-' && $s->sid !== '';
    $hasPrj  = isset($s->prj) && $s->prj !== '-' && $s->prj !== '';
    $hasIid  = isset($s->iid) && $s->iid !== '-' && $s->iid !== '';
    $qData     = ($hasIid && isset($qualityByIid[$s->iid])) ? $qualityByIid[$s->iid] : null;
    $qTier     = $qData['tier'] ?? '';
    $qTierNorm = in_array($qTier, ['alta', 'regolare'])        ? 'regolare'
               : (in_array($qTier, ['accettabile', 'incerta']) ? 'incerta'
               : (in_array($qTier, ['bassa', 'anomala'])       ? 'anomala' : $qTier));
    $qBadge  = $qTierNorm === 'regolare' ? 'badge-soft-success'
             : ($qTierNorm === 'incerta'  ? 'badge-soft-warning'
             : ($qTierNorm === 'anomala'  ? 'badge-soft-danger' : 'badge-soft-secondary'));
    $evLabel = $s->evento_label ?? '';
    $dotCls  = $evLabel === 'COMPLETATA'  ? 'up-storico-dot-completata'
             : ($evLabel === 'SCREENOUT'   ? 'up-storico-dot-screenout'
             : ($evLabel === 'QUOTAFULL'   ? 'up-storico-dot-quotafull'
             : ($evLabel === 'PREMIO'      ? 'up-storico-dot-premio'
             : ($evLabel === 'BONUS'       ? 'up-storico-dot-bonus'
             : ($evLabel === 'BONUS AMICO' ? 'up-storico-dot-bonus'
             : ($evLabel === 'MALUS'       ? 'up-storico-dot-malus' : ''))))));
@endphp
    <tr data-evento="{{ $evLabel }}" data-tier="{{ $qTierNorm }}">
        <td class="up-storico-dot-cell"><span class="up-storico-dot {{ $dotCls }}"></span></td>
        <td class="text-muted small">{{ \Carbon\Carbon::parse($s->event_date)->format('d/m/Y H:i') }}</td>
        <td>
            <span class="badge bg-{{ $s->evento_color }} d-inline-flex align-items-center px-2 py-1">
                <i class="bi {{ $s->evento_icon }} me-1"></i>{{ $s->evento_label }}
            </span>
        </td>
        <td class="text-muted small">{{ $s->tipologia }}</td>
        <td>
            @if($hasSid && $hasPrj)
                <a href="{{ url('fieldControl') }}?prj={{ urlencode($s->prj) }}&sid={{ urlencode($s->sid) }}"
                   target="_blank" rel="noopener" class="text-decoration-none fw-semibold text-primary">
                    {{ $s->sid }}
                </a>
            @else
                {{ $s->sid }}
            @endif
        </td>
        <td class="text-muted small">{{ $s->iid }}</td>
        <td class="text-muted small">{{ $s->prj }}</td>
        <td>
            @if($qData !== null)
                <span class="badge {{ $qBadge }}">{{ $qData['score'] ?? '—' }}</span>
            @else
                <span class="text-muted small">—</span>
            @endif
        </td>
        <td>
            @if($s->bytes > 0)
                <span class="text-success fw-semibold">+{{ $s->bytes }}</span>
                <small class="text-muted">({{ $s->new_level }} → {{ $s->prev_level }})</small>
            @elseif($s->bytes < 0)
                <span class="text-danger fw-semibold">{{ $s->bytes }}</span>
                <small class="text-muted">({{ $s->new_level }} → {{ $s->prev_level }})</small>
            @else
                <span class="text-muted">0</span>
            @endif
        </td>
    </tr>
@empty
    <tr>
        <td colspan="9" class="text-center text-muted py-3">Nessun evento registrato</td>
    </tr>
@endforelse
