@php
    $availableRows = $sidebar['available_amazon'] ?? collect();
    $canPay = $sidebar['amazon_bulk_pay_available'] ?? false;
@endphp

<div class="amazon-bulk-summary">
    <div class="amazon-bulk-summary-top mb-3">
        <div class="amazon-bulk-summary-title">Verifica disponibilità codici Amazon</div>
        <div class="amazon-bulk-summary-subtitle">
            Il pagamento massivo assegnerà i codici a tutte le richieste Amazon non pagate.
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0 amazon-bulk-table">
            <thead>
                <tr>
                    <th>Tipologia</th>
                    <th class="text-center">Richieste</th>
                    <th class="text-center">Disponibili</th>
                    <th class="text-center">Esito</th>
                </tr>
            </thead>
            <tbody>
                @foreach($availableRows as $item)
                    <tr>
                        <td>{{ $item['label'] }}</td>
                        <td class="text-center">{{ $item['pending'] }}</td>
                        <td class="text-center">{{ $item['quantity'] }}</td>
                        <td class="text-center">
                            @if($item['is_available'])
                                <span class="availability-badge availability-ok">Disponibili</span>
                            @else
                                <span class="availability-badge availability-ko">Non disponibili</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="amazon-bulk-summary-footer mt-3">
        @if($canPay)
            <div class="amazon-bulk-alert amazon-bulk-alert-success">
                <i class="bi bi-check-circle-fill me-2"></i>
                Tutte le richieste Amazon possono essere pagate.
            </div>
        @else
            <div class="amazon-bulk-alert amazon-bulk-alert-danger">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                I codici disponibili non sono sufficienti per completare il pagamento massivo.
            </div>
        @endif
    </div>
</div>
