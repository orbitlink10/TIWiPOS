<section class="panel">
    <div class="supplier-history-head" style="flex-wrap:wrap"><div><h2>Receiving</h2><p class="supplier-muted">{{ $invoice->receiving_status }} &middot; {{ $invoice->items->sum('received_quantity') }} of {{ $invoice->items->sum('quantity') }} ordered units received</p></div>@if(!$invoice->stock_received && auth()->user()->canAccessAbility('add_stock'))<a class="supplier-account-button primary" href="{{ route('purchases.receive',$invoice->id) }}">Receive purchase</a>@endif</div>
    <div class="supplier-scroll"><table><thead><tr><th>Product</th><th>Ordered</th><th>Received</th><th>Remaining</th></tr></thead><tbody>@foreach($invoice->items as $item)<tr><td>{{ $item->product_name }}</td><td>{{ $item->quantity }}</td><td>{{ $item->received_quantity }}</td><td>{{ max(0,$item->quantity-$item->received_quantity) }}</td></tr>@endforeach</tbody></table></div>
    <h3 style="font-size:14px;margin-top:20px">Delivery receipts</h3>
    @forelse($invoice->receipts->sortByDesc('id') as $receipt)
        <details class="supplier-invoice" open><summary><span class="supplier-invoice-title">Receipt #{{ $receipt->id }}<small>{{ $receipt->received_at->format('d M Y') }} &middot; {{ $receipt->user->name ?? 'Staff' }}</small></span><span class="supplier-invoice-balance">{{ $receipt->lines->sum('quantity') }} units</span></summary>
            <div class="supplier-scroll"><table><thead><tr><th>Product</th><th>Received</th><th>Serial numbers</th></tr></thead><tbody>@foreach($receipt->lines as $line)<tr><td>{{ $line->item->product_name ?? 'Product' }}</td><td>{{ $line->quantity }}</td><td style="overflow-wrap:anywhere">@forelse($line->serials as $serial)<div>{{ $serial->serial_number }}</div>@empty &mdash; @endforelse</td></tr>@endforeach</tbody></table></div>
            @if($receipt->notes)<p style="white-space:pre-wrap">{{ $receipt->notes }}</p>@endif
        </details>
    @empty<p class="supplier-muted">{{ $invoice->stock_received ? 'Stock was received before delivery receipt tracking was introduced.' : 'No deliveries received yet. Confirm the delivered quantities to add them to stock.' }}</p>@endforelse
</section>
