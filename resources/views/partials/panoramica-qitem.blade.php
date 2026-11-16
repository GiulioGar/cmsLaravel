@php
    $stats = $perQuestion[$q['id']] ?? ['reached' => 0, 'screenout' => 0, 'quotafull' => 0];
    $exits = $stats['screenout'] + $stats['quotafull'];
    $inFamily = $inFamily ?? false;

    // Testo lungo: troncato con tooltip grafico invece di wrap illimitato
    $itemText = $inFamily && !empty($q['context']) ? $q['code'] : $q['text'];
    $itemTextShort = Str::limit($itemText, 110, '…');
    $itemTextTruncated = $itemTextShort !== $itemText;
@endphp
<button type="button"
        class="pn-qitem {{ $stats['reached'] === 0 ? 'is-empty' : '' }} {{ $inFamily ? 'is-child' : '' }}"
        data-question-id="{{ $q['id'] }}"
        data-exits="{{ $exits }}"
        data-search="{{ Str::lower($q['code'].' '.($q['context'] ?? '').' '.$q['text']) }}">
    <span class="pn-qitem-type" title="{{ $typeLabels[$q['type']] ?? $q['type'] }}"><i class="{{ $typeIcons[$q['type']] ?? 'fas fa-question' }}"></i></span>
    <span class="pn-qitem-body">
        @if($inFamily && !empty($q['context']))
            <span class="pn-qitem-code">{{ $q['context'] }}</span>
            <span class="pn-qitem-text"@if($itemTextTruncated) data-bs-toggle="tooltip" data-bs-custom-class="pn-text-tooltip" title="{{ $itemText }}"@endif>{{ $itemTextShort }}</span>
        @else
            <span class="pn-qitem-code">{{ $q['code'] }}@if(!empty($q['context'])) <span class="pn-qitem-ctx">{{ $q['context'] }}</span>@endif</span>
            <span class="pn-qitem-text"@if($itemTextTruncated) data-bs-toggle="tooltip" data-bs-custom-class="pn-text-tooltip" title="{{ $itemText }}"@endif>{{ $itemTextShort }}</span>
        @endif
    </span>
    @if($exits > 0)
        <span class="pn-qitem-exits" title="Uscite su questa domanda">{{ $exits }}</span>
    @endif
</button>
