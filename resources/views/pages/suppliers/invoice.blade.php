<details class="supplier-invoice" id="invoice-{{ $invoice->id }}" @if($expandedInvoice ?? false) open @endif>
<summary><span class="supplier-invoice-title">{{ $invoice->reference }}<small>{{ $invoice->supplied_at->format('d M Y') }} &middot; {{ $invoice->items->count() }} {{ $invoice->items->count() === 1 ? 'product' : 'products' }}</small></span><span class="supplier-badge {{ $invoice->status === 'Overdue' ? 'overdue' : '' }}">{{ $invoice->status }}</span><span class="supplier-invoice-balance"><small>Outstanding</small>KES {{ number_format($invoice->balance_cents / 100, 2) }}</span></summary>
<p>{{ ucfirst($invoice->terms) }} &middot; Due: {{ $invoice->due_at?->format('d M Y') ?? '-' }} &middot; {{ $invoice->stock_received ? 'Added to branch stock' : 'Ledger only; stock not changed' }}</p>
<div class="supplier-scroll"><table><thead><tr><th>Product</th><th>Serial</th><th>Quantity</th><th>Unit cost</th><th>Line total</th></tr></thead><tbody>
@foreach($invoice->items as $item)<tr><td>{{ $item->product_name }}</td><td>{{ $item->serial_number ?: '-' }}</td><td>{{ $item->quantity }}</td><td>{{ number_format($item->unit_cost_cents / 100, 2) }}</td><td>{{ number_format($item->quantity * $item->unit_cost_cents / 100, 2) }}</td></tr>@endforeach
</tbody></table></div>
<p><strong>Total: {{ number_format($invoice->total_cents / 100, 2) }} &middot; Paid: {{ number_format($invoice->paid_cents / 100, 2) }} &middot; Balance: {{ number_format($invoice->balance_cents / 100, 2) }}</strong></p>
@if($invoice->notes)<p style="white-space:pre-wrap">{{ $invoice->notes }}</p>@endif
<div class="supplier-scroll"><table><thead><tr><th>Payment date</th><th>Method</th><th>Reference</th><th>Amount</th><th>Notes</th></tr></thead><tbody>
@forelse($invoice->payments->sortBy('paid_at') as $payment)<tr><td>{{ $payment->paid_at->format('d M Y') }}</td><td>{{ $payment->method === 'mpesa' ? 'M-Pesa' : ucfirst($payment->method) }}</td><td>{{ $payment->reference ?: '-' }}</td><td>{{ number_format($payment->amount_cents / 100, 2) }}</td><td>{{ $payment->notes }}</td></tr>@empty<tr><td colspan="5">No payments recorded.</td></tr>@endforelse
</tbody></table></div>
@if($invoice->balance_cents > 0)
<details class="supplier-no-print"><summary>Record payment for {{ $invoice->reference }}</summary>
<form method="POST" action="{{ route('suppliers.pay', [$supplier->id, $invoice->id]) }}">@csrf
<div class="supplier-grid">
<label class="supplier-field">Amount (KES)<input type="number" name="amount" min="0.01" max="{{ number_format($invoice->balance_cents / 100, 2, '.', '') }}" step="0.01" required></label>
<label class="supplier-field">Payment date<input type="date" name="paid_at" value="{{ today()->toDateString() }}" min="{{ $invoice->supplied_at->toDateString() }}" max="{{ today()->toDateString() }}" required></label>
<label class="supplier-field">Method<select name="method">@foreach(['cash' => 'Cash', 'mpesa' => 'M-Pesa', 'bank' => 'Bank', 'cheque' => 'Cheque', 'other' => 'Other'] as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></label>
<label class="supplier-field">Reference<input name="reference" maxlength="100"></label>
</div><label class="supplier-field">Notes<textarea name="notes" maxlength="2000"></textarea></label><button class="btn" style="margin-top:12px">Save payment</button></form></details>
@endif
</details>