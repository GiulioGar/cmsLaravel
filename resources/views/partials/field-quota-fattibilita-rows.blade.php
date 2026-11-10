{{--
    Righe della modale "Fattibilità per quota" (fieldControl.blade.php),
    estratte in partial cosi' il controller puo' ri-renderizzarle identiche
    dopo conferma/rifiuto di un suggerimento (niente reload della pagina) -
    vedi FieldControlController::confirmQuotaDemographic()/rejectQuotaDemographic()/
    confirmAllQuotaDemographic().
--}}
@foreach ($quotaData as $quota)
    @php
        $isQuotaVincolante = $quotaVincolante && strpos($quotaVincolante, '"' . $quota->quota . '"') !== false;

        $totale = (int) $quota->totale;
        $entrate = (int) $quota->entrate;
        $fattibilita = (int) ($quota->fattibilita ?? 0);
        $copertura = min($totale, $entrate + $fattibilita);

        $entratePct = $totale > 0 ? min(100, round($entrate / $totale * 100)) : 0;
        $proiettatoPct = $totale > 0 ? min(100 - $entratePct, round($fattibilita / $totale * 100)) : 0;

        $haVerdetto = !in_array($quota->fattibilita_tipo, ['obiettivo_generale', null], true);
        $completa = $entrate >= $totale;
        $raggiungibile = $completa || ($entrate + $fattibilita) >= $totale;
        $mancanti = max(0, $totale - $copertura);
    @endphp
    <div class="fc-qf-card {{ $isQuotaVincolante ? 'is-vincolante' : '' }}">
        <div class="fc-qf-card-head">
            <span class="fc-qf-card-title">
                {{ $quota->quota }}
                @if($isQuotaVincolante)
                    <span class="fc-quote-vincolante-badge" data-bs-toggle="tooltip" title="{{ $quotaVincolante }}">
                        <i class="fas fa-triangle-exclamation"></i>
                    </span>
                @endif
            </span>

            @if($quota->fattibilita_tipo === 'obiettivo_generale')
                <span class="fc-qf-tag is-obiettivo" data-bs-toggle="tooltip"
                      title="È l'obiettivo generale della ricerca (stesso dato di 'Max raggiungibile' più sopra in questa stessa card): non entra nel calcolo di fattibilità.">
                    Obiettivo generale
                </span>
            @elseif($quota->demographic_confirmed)
                <span class="fc-qf-tag is-demografica" data-bs-toggle="tooltip"
                      title="Stima basata sulle persone disponibili del panel che corrispondono a questo filtro ({{ ucfirst($quota->demographic_confirmed['tipo']) }}), moltiplicate per l'IR del panel e l'IR del campione.">
                    <i class="fa-solid fa-id-card"></i> {{ ucfirst($quota->demographic_confirmed['tipo']) }}
                </span>
            @elseif(empty($quota->demographic_suggestion))
                <span class="fc-qf-tag is-generica" data-bs-toggle="tooltip"
                      title="Quota non agganciata a un dato anagrafico noto: {{ $quota->fattibilita_incidenza ?? 0 }}% dei contatti fatti finora è risultato completo e rientrante in questa quota.">
                    {{ $quota->fattibilita_incidenza ?? 0 }}% incidenza
                </span>
            @endif
        </div>

        <div class="fc-qf-progress">
            <div class="fc-qf-progress-fill is-entrate" style="width: {{ $entratePct }}%"></div>
            @if($proiettatoPct > 0)
                <div class="fc-qf-progress-fill is-proiettato" style="width: {{ $proiettatoPct }}%; left: {{ $entratePct }}%"></div>
            @endif
        </div>

        <div class="fc-qf-meta">
            <div class="fc-qf-meta-numbers">
                <span class="fc-qf-num is-entrate">Entrate <b>{{ $entrate }}</b></span>
                @if($haVerdetto)
                    <span class="fc-qf-num-op">+</span>
                    <span class="fc-qf-num is-proiettato">Fattibili <b>+{{ $fattibilita }}</b></span>
                    <span class="fc-qf-num-op">=</span>
                    <span class="fc-qf-num is-copertura"><b>{{ $copertura }}</b></span>
                @endif
                <span class="fc-qf-num-total">/ {{ $totale }}</span>
            </div>

            @if($completa)
                <span class="fc-qf-verdict is-ok"><i class="fas fa-circle-check"></i>Completa</span>
            @elseif($haVerdetto && $raggiungibile)
                <span class="fc-qf-verdict is-ok"><i class="fas fa-circle-check"></i>Raggiungibile</span>
            @elseif($haVerdetto)
                <span class="fc-qf-verdict is-risk"><i class="fas fa-triangle-exclamation"></i>Mancherebbero {{ $mancanti }}</span>
            @endif
        </div>

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
@endforeach
