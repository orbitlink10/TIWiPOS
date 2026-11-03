@extends('layouts.app')
@section('title', $supplier->name . ' - Supplier')
@section('header')
<div class="header-row"><h1>{{ $supplier->name }}</h1><a class="btn" href="{{ route('suppliers.index') }}">Back to Supplier</a></div>
@endsection
@section('content')
<div class="supplier-area">
@include('pages.suppliers.style')
<div class="panel">
<div class="supplier-actions"><span class="supplier-badge">{{ $supplier->is_active ? 'Active' : 'Inactive' }}</span><span>{{ $supplier->contact_name }} &middot; {{ $supplier->phone }} &middot; {{ $supplier->email }}</span></div>
<p class="supplier-muted">{{ collect([$supplier->address, $supplier->city, $supplier->country])->filter()->implode(', ') }}</p>
<details class="supplier-no-print"><summary>Edit supplier contact details</summary>
<form method="POST" action="{{ route('suppliers.update', $supplier->id) }}">@csrf @method('PUT')
<div class="supplier-grid">@foreach(['name' => 'Supplier name', 'contact_name' => 'Contact person', 'phone' => 'Phone', 'email' => 'Email', 'address' => 'Address', 'city' => 'City', 'country' => 'Country'] as $field => $label)
<label class="supplier-field">{{ $label }}<input name="{{ $field }}" value="{{ old($field, $supplier->$field) }}" type="{{ $field === 'email' ? 'email' : 'text' }}" @required($field === 'name') maxlength="{{ $field === 'phone' ? 30 : 255 }}"></label>
@endforeach</div>
<input type="hidden" name="is_active" value="0"><label><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $supplier->is_active))> Active supplier</label>
<button class="btn">Save details</button></form></details>
</div>
<p class="supplier-muted">Account for the selected branch &middot; All amounts in KES</p>
<div class="supplier-grid">
@foreach(['Total supplied' => $invoices->sum('total_cents'), 'Total paid' => $invoices->sum('paid_cents'), 'Outstanding' => $invoices->sum('balance_cents'), 'Overdue' => $invoices->where('status', 'Overdue')->sum('balance_cents')] as $label => $amount)
<div class="panel"><div class="supplier-muted">{{ $label }}</div><div class="supplier-metric">{{ number_format($amount / 100, 2) }}</div></div>
@endforeach
</div>
@if($supplier->is_active)
<div class="panel supplier-no-print" id="record-supply"><h2>Record supplied products</h2>
<p class="supplier-muted">Record one supplier invoice or delivery note with all its products. Register new products in Products first. Invoice references must be unique for this supplier.</p>
@if($products->isEmpty())<p><a href="{{ route('products.create') }}">Add a product</a> before recording a supply.</p>@else
<form method="POST" action="{{ route('suppliers.supply', $supplier->id) }}" id="supply-form">@csrf
<div class="supplier-grid">
<label class="supplier-field">Invoice / delivery reference<input name="reference" value="{{ old('reference') }}" required maxlength="100"></label>
<label class="supplier-field">Supply date<input name="supplied_at" type="date" value="{{ old('supplied_at', today()->toDateString()) }}" max="{{ today()->toDateString() }}" required></label>
<label class="supplier-field">Terms<select name="terms"><option value="credit" @selected(old('terms') === 'credit')>Credit</option><option value="cash" @selected(old('terms') === 'cash')>Cash (fully paid)</option></select></label>
<label class="supplier-field">Due date (required for credit)<input name="due_at" type="date" value="{{ old('due_at') }}"></label>
</div>
<div class="supplier-scroll"><table><thead><tr><th>Product / serial</th><th>Quantity</th><th>Unit cost (KES)</th><th></th></tr></thead><tbody id="supply-items">
@foreach(old('items', [['product_id' => '', 'quantity' => 1, 'unit_cost' => '']]) as $index => $item)
<tr><td style="min-width:240px"><select name="items[{{ $index }}][product_id]" required><option value="">Select product</option>@foreach($products as $product)<option value="{{ $product->id }}" @selected((string)($item['product_id'] ?? '') === (string)$product->id)>{{ $product->name }}{{ $product->serial_number ? ' - ' . $product->serial_number : '' }}</option>@endforeach</select></td><td><input name="items[{{ $index }}][quantity]" type="number" min="1" max="1000000" value="{{ $item['quantity'] ?? 1 }}" required></td><td><input name="items[{{ $index }}][unit_cost]" type="number" min="0" max="99999999.99" step="0.01" value="{{ $item['unit_cost'] ?? '' }}" required></td><td><button type="button" class="remove-item">Remove</button></td></tr>
@endforeach
</tbody></table></div>
<div class="supplier-actions" style="margin:12px 0"><button type="button" id="add-item" class="btn">Add product row</button><strong>Invoice total: KES <span id="supply-total">0.00</span></strong></div>
<div class="supplier-grid">
<label class="supplier-field">Amount paid now (0 if unpaid)<input name="amount" type="number" min="0" step="0.01" value="{{ old('amount', '0') }}" required></label>
<label class="supplier-field">Payment method<select name="method">@foreach(['cash' => 'Cash', 'mpesa' => 'M-Pesa', 'bank' => 'Bank', 'cheque' => 'Cheque', 'other' => 'Other'] as $key => $label)<option value="{{ $key }}" @selected(old('method') === $key)>{{ $label }}</option>@endforeach</select></label>
<label class="supplier-field">Payment reference<input name="payment_reference" maxlength="100" value="{{ old('payment_reference') }}"></label>
</div>
<label class="supplier-field">Notes / delivery details<textarea name="notes" maxlength="2000">{{ old('notes') }}</textarea></label>
@if(auth()->user()->canAccessAbility('add_stock'))
<p><label><input type="checkbox" name="receive_stock" value="1" @checked(old('receive_stock'))> Add these quantities to the selected branch's main stock</label><br><span class="supplier-muted">Leave unchecked if the stock was already entered. Saving with this option increases stock immediately.</span></p>
@endif
<button class="btn" style="margin-top:12px">Save supplier invoice</button>
</form>@endif</div>
@endif
<div class="panel"><div class="supplier-actions"><h2>Supply and payment history</h2><button type="button" class="btn supplier-no-print" onclick="window.print()">Print account</button></div>
<p class="supplier-muted">Expand an invoice to view products, payment references and notes. Payments are recorded against individual invoices.</p>
@forelse($invoices as $invoice)
<details open style="border-bottom:1px solid #cbd5e1;padding-bottom:14px;margin-bottom:16px">
<summary>{{ $invoice->reference }} &middot; {{ $invoice->supplied_at->format('d M Y') }} &middot; <span class="supplier-badge {{ $invoice->status === 'Overdue' ? 'overdue' : '' }}">{{ $invoice->status }}</span></summary>
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
@empty<p>No supplies recorded for this supplier in the selected branch.</p>@endforelse
</div></div>
<script>
(() => {
const body = document.getElementById('supply-items');
if (!body) return;
let next = Math.max(...Array.from(body.querySelectorAll('select')).map(el => Number(el.name.match(/\[(\d+)\]/)[1]))) + 1;
const calculate = () => {
let cents = 0;
body.querySelectorAll('tr').forEach(row => { const inputs = row.querySelectorAll('input'); cents += Number(inputs[0].value || 0) * Math.round(Number(inputs[1].value || 0) * 100); });
document.getElementById('supply-total').textContent = (cents / 100).toLocaleString('en-KE', {minimumFractionDigits:2, maximumFractionDigits:2});
};
document.getElementById('add-item').addEventListener('click', () => {
const row = body.firstElementChild.cloneNode(true);
row.querySelectorAll('input,select').forEach(el => { el.name = el.name.replace(/\[\d+\]/, `[${next}]`); el.value = el.name.endsWith('[quantity]') ? '1' : ''; });
next++; body.appendChild(row); calculate();
});
body.addEventListener('click', event => { if(event.target.classList.contains('remove-item') && body.children.length > 1) { event.target.closest('tr').remove(); calculate(); } });
body.addEventListener('input', calculate); calculate();
})();
</script>
@endsection
