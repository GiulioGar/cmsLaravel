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
    @endphp
    <div class="fc-quote-fattibilita-row {{ $isQuotaVincolante ? 'is-vincolante' : '' }}">
        <span class="fc-quote-fattibilita-label">
            {{ $quota->quota }}
            @if($isQuotaVincolante)
                <span class="fc-quote-vincolante-badge" data-bs-toggle="tooltip" title="{{ $quotaVincolante }}">
                    <i class="fas fa-triangle-exclamation"></i>
                </span>
            @endif
        </span>

        @if($quota->fattibilita_tipo === 'obiettivo_generale')
            <span class="fc-quote-fattibilita-pill is-obiettivo"
                  data-bs-toggle="tooltip"
                  title="È l'obiettivo generale della ricerca (stesso dato di 'Max raggiungibile' più sopra in questa stessa card), non una quota su un sottoinsieme di persone: non entra nel calcolo di fattibilità.">
                —
            </span>
        @elseif($quota->demographic_confirmed)
            <span class="fc-quote-fattibilita-pill is-demografica"
                  data-bs-toggle="tooltip"
                  title="Stima basata sulle persone disponibili del panel che corrispondono a questo filtro ({{ ucfirst($quota->demographic_confirmed['tipo']) }}), moltiplicate per l'IR del panel e l'IR del campione.">
                +{{ $quota->fattibilita }} casi
            </span>
        @elseif(!empty($quota->demographic_suggestion))
            <div class="fc-quote-demo-suggest"
                 data-quota-name="{{ $quota->quota_raw }}"
                 data-quota-tipo="{{ $quota->demographic_suggestion['tipo'] }}"
                 data-quota-filtro='@json($quota->demographic_suggestion['filtro'])'>
                <span class="fc-quote-demo-suggest-label">{{ $quota->demographic_suggestion['label'] }}?</span>
                <button type="button" class="fc-quote-demo-btn is-confirm" title="Conferma">
                    <i class="fas fa-check"></i>
                </button>
                <button type="button" class="fc-quote-demo-btn is-reject" title="Non è demografica">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>
        @else
            <span class="fc-quote-fattibilita-pill is-generica"
                  data-bs-toggle="tooltip"
                  title="Quota non agganciata a un dato anagrafico noto: {{ $quota->fattibilita_incidenza ?? 0 }}% dei contatti fatti finora è risultato completo e rientrante in questa quota. Stima = disponibili × IR panel × questa percentuale, entro il limite di quanto manca alla quota.">
                +{{ $quota->fattibilita ?? 0 }} casi
            </span>
        @endif
    </div>
@endforeach
