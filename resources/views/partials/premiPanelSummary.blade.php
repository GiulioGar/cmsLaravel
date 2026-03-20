<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Riepilogo</h5>
    </div>

    <div class="card-body">
        {{-- ===========================
             1) RIEPILOGO GENERALE NON PAGATI
        ============================ --}}
        <div class="summary-section mb-4">
<div class="summary-section-title">
    <i class="bi bi-hourglass-split"></i>
    Richieste non pagate {{ $type === 'amazon' ? 'Amazon' : 'Paypal' }}
</div>

            <div class="summary-mini-grid">
                <div class="summary-mini-card">
                    <div class="summary-mini-label">Totali</div>
                    <div class="summary-mini-value" id="summary-total-requests">
                        {{ $sidebar['pending_summary']->total_requests ?? 0 }}
                    </div>
                </div>

                <div class="summary-mini-card">
                    <div class="summary-mini-label">Valore</div>
                    <div class="summary-mini-value" id="summary-total-value">
                        {{ $sidebar['pending_summary']->total_value ?? 0 }}€
                    </div>
                </div>

               @if($type === 'amazon')
                    <div class="summary-mini-card summary-mini-card-value summary-mini-card-2">
                        <div class="summary-mini-label">2€</div>
                        <div class="summary-mini-value" id="summary-total-2">
                            {{ $sidebar['pending_summary']->total_2 ?? 0 }}
                        </div>
                    </div>

                    <div class="summary-mini-card summary-mini-card-value summary-mini-card-5">
                        <div class="summary-mini-label">5€</div>
                        <div class="summary-mini-value" id="summary-total-5">
                            {{ $sidebar['pending_summary']->total_5 ?? 0 }}
                        </div>
                    </div>

                    <div class="summary-mini-card summary-mini-card-value summary-mini-card-10">
                        <div class="summary-mini-label">10€</div>
                        <div class="summary-mini-value" id="summary-total-10">
                            {{ $sidebar['pending_summary']->total_10 ?? 0 }}
                        </div>
                    </div>

                    <div class="summary-mini-card summary-mini-card-value summary-mini-card-20">
                        <div class="summary-mini-label">20€</div>
                        <div class="summary-mini-value" id="summary-total-20">
                            {{ $sidebar['pending_summary']->total_20 ?? 0 }}
                        </div>
                    </div>
                @endif

                @if($type === 'paypal')
                    <div class="summary-mini-card summary-mini-card-value summary-mini-card-5">
                        <div class="summary-mini-label">5€</div>
                        <div class="summary-mini-value" id="summary-total-5">
                            {{ $sidebar['pending_summary']->total_5 ?? 0 }}
                        </div>
                    </div>

                    <div class="summary-mini-card summary-mini-card-value summary-mini-card-10">
                        <div class="summary-mini-label">10€</div>
                        <div class="summary-mini-value" id="summary-total-10">
                            {{ $sidebar['pending_summary']->total_10 ?? 0 }}
                        </div>
                    </div>

                    <div class="summary-mini-card summary-mini-card-warning">
                        <div class="summary-mini-card-head">
                            <div class="summary-mini-label">Email non disponibili</div>

                            @if(($sidebar['pending_summary']->missing_paypal_email ?? 0) > 0)
                                <a
                                    href="{{ route('premi.panel.paypal.export.missing.email', ['status' => $status]) }}"
                                    class="summary-export-btn"
                                    data-bs-toggle="tooltip"
                                    data-bs-placement="top"
                                    title="Esporta CSV"
                                >
                                    <i class="bi bi-file-earmark-spreadsheet"></i>
                                </a>
                            @endif
                        </div>

                        <div class="summary-mini-value" id="summary-missing-paypal-email">
                            {{ $sidebar['pending_summary']->missing_paypal_email ?? 0 }}
                        </div>
                    </div>
                @endif
            </div> {{-- chiusura summary-mini-grid --}}

@if($type === 'amazon' && ($sidebar['pending_summary']->total_requests ?? 0) > 0)
    <div class="summary-action-row">
        <button
            type="button"
            class="btn btn-amazon-bulk-pay"
            id="btnOpenAmazonBulkPayModal"
        >
            <i class="bi bi-gift-fill me-2"></i>
            Paga richieste Amazon
        </button>
    </div>
@endif


        </div>

        {{-- ===========================
             2) PREMI DISPONIBILI AMAZON
        ============================ --}}
        @if($type === 'amazon')
            <div class="summary-section">
<div class="summary-section-title">
    <i class="bi bi-box-seam"></i>
    Premi disponibili
</div>

                <div class="table-responsive">
                   <table class="table table-sm align-middle mb-0 sidebar-table sidebar-table-stock">
                        <thead>
                            <tr>
                            <th>Tipologia</th>
                            <th class="text-center">Rimasti</th>
                            <th class="text-end">Valore</th>
                            <th class="text-center">Stato</th>
                        </tr>
                        </thead>
                        <tbody>
                            @foreach($sidebar['available_amazon'] as $item)
                            <tr>
                                <td>{{ $item['label'] }}</td>
                                <td class="text-center">{{ $item['quantity'] }}</td>
                                <td class="text-end">{{ $item['total'] }}€</td>
                                <td class="text-center">
                                    @if($item['is_available'])
                                        <span class="availability-badge availability-ok">Disponibili</span>
                                    @else
                                        <span class="availability-badge availability-ko">Non disponibili</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                            <tr class="sidebar-table-total">
                                <td colspan="3"><strong>Cassa totale</strong></td>
                                <td class="text-end"><strong>{{ $sidebar['available_amazon_total'] ?? 0 }}€</strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

                {{-- ===========================
             3) RIEPILOGO COSTI ANNO
        ============================ --}}
{{-- ===========================
     3) RIEPILOGO COSTI ANNO
=========================== --}}
@if($type === 'amazon')
    <div class="summary-section mt-4">
        <div class="summary-section-title">
            <i class="bi bi-bar-chart-line"></i>
            Riepilogo Costi {{ $sidebar['current_year'] }}
        </div>

        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0 sidebar-table sidebar-table-costs">
                <thead>
                    <tr>
                        <th></th>
                        <th class="text-center">€ 2</th>
                        <th class="text-center">€ 5</th>
                        <th class="text-center">€ 10</th>
                        <th class="text-center">€ 20</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="sidebar-subsection-row">
                        <td colspan="5">Pagati</td>
                    </tr>

                    <tr class="value-row">
                        <td><strong>Quantità</strong></td>
                        <td class="text-center">{{ $sidebar['year_paid_summary']->paid_2 }}</td>
                        <td class="text-center">{{ $sidebar['year_paid_summary']->paid_5 }}</td>
                        <td class="text-center">{{ $sidebar['year_paid_summary']->paid_10 }}</td>
                        <td class="text-center">{{ $sidebar['year_paid_summary']->paid_20 }}</td>
                    </tr>

                    <tr class="amount-row">
                        <td><strong>Valore</strong></td>
                        <td class="text-center">{{ $sidebar['year_paid_summary']->paid_2 * 2 }}€</td>
                        <td class="text-center">{{ $sidebar['year_paid_summary']->paid_5 * 5 }}€</td>
                        <td class="text-center">{{ $sidebar['year_paid_summary']->paid_10 * 10 }}€</td>
                        <td class="text-center">{{ $sidebar['year_paid_summary']->paid_20 * 20 }}€</td>
                    </tr>

                    <tr class="sidebar-table-total">
                        <td colspan="4"><strong>TOTALE PAGATI</strong></td>
                        <td class="text-end"><strong>{{ $sidebar['year_paid_summary']->paid_total_value }}€</strong></td>
                    </tr>

                    <tr class="sidebar-subsection-row sidebar-subsection-row-alt">
                        <td colspan="5">Acquistati</td>
                    </tr>

                    <tr class="value-row">
                        <td><strong>Quantità</strong></td>
                        <td class="text-center">{{ $sidebar['year_purchased_summary']->buy_2 }}</td>
                        <td class="text-center">{{ $sidebar['year_purchased_summary']->buy_5 }}</td>
                        <td class="text-center">{{ $sidebar['year_purchased_summary']->buy_10 }}</td>
                        <td class="text-center">{{ $sidebar['year_purchased_summary']->buy_20 }}</td>
                    </tr>

                    <tr class="amount-row">
                        <td><strong>Valore</strong></td>
                        <td class="text-center">{{ $sidebar['year_purchased_summary']->buy_2 * 2 }}€</td>
                        <td class="text-center">{{ $sidebar['year_purchased_summary']->buy_5 * 5 }}€</td>
                        <td class="text-center">{{ $sidebar['year_purchased_summary']->buy_10 * 10 }}€</td>
                        <td class="text-center">{{ $sidebar['year_purchased_summary']->buy_20 * 20 }}€</td>
                    </tr>

                    <tr class="sidebar-table-total">
                        <td colspan="4"><strong>TOTALE ACQUISTATI</strong></td>
                        <td class="text-end"><strong>{{ $sidebar['year_purchased_summary']->buy_total_value }}€</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
@else
    <div class="summary-section mt-4">
        <div class="summary-section-title">
            <i class="bi bi-bar-chart-line"></i>
            Riepilogo Costi {{ $sidebar['current_year'] }}
        </div>

        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0 sidebar-table sidebar-table-costs">
                <thead>
                    <tr>
                        <th></th>
                        <th class="text-center">€ 5</th>
                        <th class="text-center">€ 10</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="sidebar-subsection-row">
                        <td colspan="3">Pagati</td>
                    </tr>

                    <tr class="value-row">
                        <td><strong>Quantità</strong></td>
                        <td class="text-center">{{ $sidebar['year_paid_summary']->paid_5 }}</td>
                        <td class="text-center">{{ $sidebar['year_paid_summary']->paid_10 }}</td>
                    </tr>

                    <tr class="amount-row">
                        <td><strong>Valore</strong></td>
                        <td class="text-center">{{ $sidebar['year_paid_summary']->paid_5 * 5 }}€</td>
                        <td class="text-center">{{ $sidebar['year_paid_summary']->paid_10 * 10 }}€</td>
                    </tr>

                    <tr class="sidebar-table-total">
                        <td colspan="2"><strong>TOTALE PAGATI</strong></td>
                        <td class="text-end"><strong>{{ $sidebar['year_paid_summary']->paid_total_value }}€</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
@endif
                {{-- ===========================
             4) STORICO PREMI PAGATI
        ============================ --}}
        <div class="summary-section mt-4">
            <div class="summary-section-title">
                Storico premi pagati
            </div>

            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0 sidebar-table sidebar-table-history">
                    <thead>
                        <tr>
                            @if($type === 'amazon')
                                <th class="text-center">€ 2</th>
                            @endif
                            <th class="text-center">€ 5</th>
                            <th class="text-center">€ 10</th>
                            @if($type === 'amazon')
                                <th class="text-center">€ 20</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            @if($type === 'amazon')
                                <td class="text-center">{{ $sidebar['history_totals']->total_2 }}</td>
                            @endif
                            <td class="text-center">{{ $sidebar['history_totals']->total_5 }}</td>
                            <td class="text-center">{{ $sidebar['history_totals']->total_10 }}</td>
                            @if($type === 'amazon')
                                <td class="text-center">{{ $sidebar['history_totals']->total_20 }}</td>
                            @endif
                        </tr>

                        <tr>
                            @if($type === 'amazon')
                                <td class="text-center">{{ $sidebar['history_totals']->percent_2 }}%</td>
                            @endif
                            <td class="text-center">{{ $sidebar['history_totals']->percent_5 }}%</td>
                            <td class="text-center">{{ $sidebar['history_totals']->percent_10 }}%</td>
                            @if($type === 'amazon')
                                <td class="text-center">{{ $sidebar['history_totals']->percent_20 }}%</td>
                            @endif
                        </tr>

@if($type === 'amazon')
    <tr class="sidebar-table-total">
        <td colspan="3">
            <strong>TOTALE RICHIESTE</strong>
        </td>
        <td class="text-end">
            <strong>{{ $sidebar['history_totals']->grand_total }}</strong>
        </td>
    </tr>
@else
    <tr class="sidebar-table-total">
        <td>
            <strong>TOTALE RICHIESTE</strong>
        </td>
        <td class="text-end">
            <strong>{{ $sidebar['history_totals']->grand_total }}</strong>
        </td>
    </tr>
@endif
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
