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
    <tr>
        <td colspan="7" class="text-muted">Nessun evento registrato</td>
    </tr>
@endforelse
