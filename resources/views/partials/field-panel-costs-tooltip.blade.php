{{-- Niente tag <table>/<tr>/<td>: il sanitizer HTML di default di Bootstrap
     per i tooltip (data-bs-html="true") non li ammette e li rimuove in
     silenzio - vedi memoria feedback_bootstrap_tooltip_no_table_tags.
     Stessa tabella ricostruita con <div>/<span> in una griglia CSS. --}}
<div class="fc-question-tooltip-content">
    <div class="fc-question-tooltip-label">Costi per panel</div>
    <div class="fc-question-tooltip-divider"></div>
    <div class="fc-cost-tooltip-grid">
        <span class="fc-cost-tooltip-head">Panel</span>
        <span class="fc-cost-tooltip-head">CPI</span>
        <span class="fc-cost-tooltip-head">Totale</span>
        @foreach ($rows as $hc)
            <span class="fc-cost-tooltip-name">{{ $hc['label'] }}</span>
            <span class="fc-cost-tooltip-value">{{ $hc['cpi'] !== null ? '€ ' . number_format($hc['cpi'], 2, ',', '.') : '—' }}</span>
            <span class="fc-cost-tooltip-value">{{ $hc['value'] !== null ? '€ ' . number_format($hc['value'], 2, ',', '.') : ($hc['applicable'] ? 'N/D' : '—') }}</span>
        @endforeach
    </div>
</div>
