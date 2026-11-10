{{--
    Righe della modale "Fattibilità per quota" (fieldControl.blade.php),
    estratte in partial cosi' il controller puo' ri-renderizzarle identiche
    dopo conferma/rifiuto di un suggerimento (niente reload della pagina) -
    vedi FieldControlController::confirmQuotaDemographic()/rejectQuotaDemographic()/
    confirmAllQuotaDemographic(). La riga "Interviste Totali" non passa di qui:
    e' mostrata a parte, come riepilogo in testa alla modale.
--}}
@php
    $iconePerTipo = [
        'sesso' => 'fa-solid fa-user',
        'eta' => 'fa-solid fa-calendar-days',
        'area' => 'fa-solid fa-location-dot',
        'generica' => 'fa-solid fa-chart-simple',
    ];
@endphp
@foreach ($quotaData as $quota)
    @continue($quota->fattibilita_tipo === 'obiettivo_generale')
    @php
        $isQuotaVincolante = $quotaVincolante && strpos($quotaVincolante, '"' . $quota->quota . '"') !== false;

        $totale = (int) $quota->totale;
        $entrate = (int) $quota->entrate;
        $fattibilita = (int) ($quota->fattibilita ?? 0);
        $copertura = min($totale, $entrate + $fattibilita);

        $entratePct = $totale > 0 ? min(100, round($entrate / $totale * 100)) : 0;
        $proiettatoPct = $totale > 0 ? min(100 - $entratePct, round($fattibilita / $totale * 100)) : 0;

        $haVerdetto = !empty($quota->fattibilita_tipo) && empty($quota->demographic_suggestion);
        $completa = $entrate >= $totale;
        $raggiungibile = $completa || ($entrate + $fattibilita) >= $totale;
        $mancanti = max(0, $totale - $copertura);

        $stato = 'neutro';
        if ($haVerdetto) {
            $stato = $completa ? 'completa' : ($raggiungibile ? 'ok' : 'risk');
        }

        $icona = $iconePerTipo[$quota->fattibilita_tipo] ?? 'fa-solid fa-chart-simple';
    @endphp
    <div class="fc-qf-card is-state-{{ $stato }} {{ $isQuotaVincolante ? 'is-vincolante' : '' }}">
        <div class="fc-qf-row-icon is-state-{{ $stato }}"><i class="{{ $icona }}"></i></div>

        <div class="fc-qf-row-body">
            <div class="fc-qf-card-title">
                {{ $quota->quota }}
                @if($isQuotaVincolante)
                    <span class="fc-quote-vincolante-badge" data-bs-toggle="tooltip" title="{{ $quotaVincolante }}">
                        <i class="fas fa-triangle-exclamation"></i>
                    </span>
                @endif
            </div>

            <div class="fc-qf-progress">
                <div class="fc-qf-progress-fill is-entrate is-state-{{ $stato }}" style="width: {{ $entratePct }}%"></div>
                @if($stato === 'ok' && $proiettatoPct > 0)
                    <div class="fc-qf-progress-fill is-proiettato" style="width: {{ $proiettatoPct }}%; left: {{ $entratePct }}%"></div>
                @endif
            </div>

            <div class="fc-qf-meta-numbers">
                <span class="fc-qf-num is-entrate">Entrate: <b>{{ $entrate }}</b></span>
                @if($haVerdetto)
                    <span class="fc-qf-num-op">+</span>
                    <span class="fc-qf-num is-proiettato">Fattibili: <b>+{{ $fattibilita }}</b></span>
                    <span class="fc-qf-num-op">=</span>
                    <span class="fc-qf-num is-copertura"><b>{{ $copertura }}</b></span>
                @endif
                <span class="fc-qf-num-total">/ {{ $totale }}</span>
            </div>

            @if($haVerdetto && $stato === 'risk')
                <div class="fc-qf-risk-note">
                    <i class="fa-solid fa-circle-info"></i>
                    Mancano {{ $mancanti }} interviste
                    @if($quota->demographic_confirmed)
                        e non sono disponibili utenti sufficienti.
                    @else
                        e al ritmo attuale non si riuscirebbero a coprire.
                    @endif
                </div>
            @endif

            @if(!empty($quota->demographic_suggestion))
                <div class="fc-quote-demo-suggest"
                     data-quota-name="{{ $quota->quota_raw }}"
                     data-quota-tipo="{{ $quota->demographic_suggestion['tipo'] }}"
                     data-quota-filtro='@json($quota->demographic_suggestion['filtro'])'>
                    <span class="fc-quote-demo-suggest-label">Sembra: {{ $quota->demographic_suggestion['label'] }}?</span>
                    <button type="button" class="fc-quote-demo-btn is-confirm" title="Conferma">
                        <i class="fas fa-check"></i>
                    </button>
                    <button type="button" class="fc-quote-demo-btn is-reject" title="Non è demografica">
                        <i class="fas fa-xmark"></i>
                    </button>
                </div>
            @endif
        </div>

        <div class="fc-qf-row-right">
            @if($stato === 'completa')
                <span class="fc-qf-verdict is-completa"><i class="fas fa-circle-check"></i>Completa</span>
            @elseif($stato === 'ok')
                <span class="fc-qf-verdict is-ok"><i class="fas fa-circle-check"></i>Raggiungibile</span>
            @elseif($stato === 'risk')
                <span class="fc-qf-verdict is-risk"><i class="fas fa-circle-xmark"></i>Non raggiungibile</span>
            @endif

            @if($quota->demographic_confirmed)
                <span class="fc-qf-tag is-demografica" data-bs-toggle="tooltip"
                      title="Stima basata sulle persone disponibili del panel che corrispondono a questo filtro ({{ ucfirst($quota->demographic_confirmed['tipo']) }}), moltiplicate per l'IR del panel e l'IR del campione.">
                    <i class="{{ $icona }}"></i> {{ ucfirst($quota->demographic_confirmed['tipo']) }}
                </span>
            @elseif(empty($quota->demographic_suggestion))
                <span class="fc-qf-tag is-generica" data-bs-toggle="tooltip"
                      title="Quota non agganciata a un dato anagrafico noto: {{ $quota->fattibilita_incidenza ?? 0 }}% dei contatti fatti finora è risultato completo e rientrante in questa quota.">
                    {{ $quota->fattibilita_incidenza ?? 0 }}% incidenza
                </span>
            @endif
        </div>
    </div>
@endforeach
