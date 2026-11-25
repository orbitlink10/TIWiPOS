@extends('layouts.app')
@section('title', $supplier->name . ' - Supplier')
@section('header')
<div class="header-row"><div><h1>{{ $supplier->name }}</h1><p class="supplier-account-subtitle">Supplier account &middot; {{ $currentBranch->name ?? 'Current branch' }}</p></div><div class="supplier-account-actions"><a class="supplier-account-button" href="{{ route('suppliers.index') }}">&larr; Suppliers</a><button class="supplier-account-button supplier-no-print" type="button" onclick="window.print()">Print account</button>@if($supplier->is_active)<button class="supplier-account-button primary supplier-no-print" type="button" onclick="const editor=document.getElementById('record-supply'); editor.open=true; editor.scrollIntoView({behavior:'smooth',block:'start'});">+ Record purchase</button>@endif</div></div>
@endsection
@section('content')
<div class="supplier-area supplier-account">
@include('pages.suppliers.style')
<style>
main{min-width:0}.supplier-account-subtitle{margin:6px 0 0;color:#718087;font-size:12px}.supplier-account-actions{display:flex;gap:8px;flex-wrap:wrap}.supplier-account-button{display:inline-flex;align-items:center;justify-content:center;padding:10px 14px;background:#fff;border:1px solid #ccd8e5;border-radius:7px;color:#334155;text-decoration:none;font:inherit;font-size:12px;font-weight:700;cursor:pointer}.supplier-account-button.primary{background:#1769d1;border-color:#1769d1;color:#fff}
.supplier-account .panel{border:1px solid #e2e8ee;border-radius:10px;box-shadow:none;padding:18px 20px;margin-bottom:16px;background:#fff;min-width:0}.supplier-account h2{margin:0;font-size:17px;letter-spacing:-.2px}.supplier-account .supplier-muted{font-size:12px;line-height:1.6}.supplier-account .supplier-grid{gap:12px;margin:14px 0}.supplier-account>.supplier-grid{grid-template-columns:repeat(4,minmax(0,1fr));margin:0 0 18px}.supplier-account>.supplier-grid .panel{margin:0;padding:15px 18px}.supplier-account>.supplier-grid .supplier-muted{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em}.supplier-account .supplier-metric{font-size:21px;margin-top:5px;color:#16242b;letter-spacing:-.3px;font-variant-numeric:tabular-nums}.supplier-account .supplier-badge{border-radius:5px;font-size:10px;font-weight:700;background:#edf8ef;color:#166534}.supplier-account .supplier-badge.overdue{background:#fff0ee;color:#b42318}.supplier-account .supplier-badge.inactive{background:#f0f3f6;color:#667885}
.supplier-account .supplier-profile{padding:16px 20px}.supplier-contact-line{display:flex;gap:8px 18px;align-items:center;flex-wrap:wrap;color:#526473;font-size:12px}.supplier-account .supplier-profile summary{font-size:12px;color:#1057b0;padding:12px 0 0}.supplier-account label{font-size:11px;color:#526473}.supplier-account input:not([type=checkbox]),.supplier-account select,.supplier-account textarea{font:inherit;font-size:13px;padding:10px 11px;border-color:#cdd8e3;border-radius:6px}.supplier-account :is(input,select,textarea,button,summary):focus-visible{outline:2px solid #a7c9f4;outline-offset:2px}.supplier-account .btn{padding:10px 14px;background:#1769d1;border:1px solid #1769d1;border-radius:7px;box-shadow:none;font-size:12px;font-weight:700}.supplier-account .remove-item{background:#fff;border:1px solid #e2e8ee;color:#b42318;padding:7px 10px;border-radius:6px;font:inherit;font-size:11px;cursor:pointer}
.supplier-account .supplier-purchase{padding:0}.supplier-purchase>summary{padding:17px 20px!important;display:flex;align-items:center;justify-content:space-between;gap:12px;list-style:none}.supplier-purchase>summary::-webkit-details-marker{display:none}.supplier-purchase>summary strong{font-size:15px}.supplier-purchase>summary span{font-size:11px;color:#718087;font-weight:500}.supplier-purchase>summary::after{content:'+';font-size:22px;font-weight:500;color:#1769d1}.supplier-purchase[open]>summary::after{content:'−'}.supplier-purchase-body{padding:0 20px 20px;border-top:1px solid #edf0f4}.supplier-account table{font-size:12px;min-width:550px}.supplier-account th{background:#f7f9fb;padding:11px 12px;font-size:10px;text-transform:uppercase;letter-spacing:.04em;white-space:nowrap}.supplier-account td{padding:12px;border-bottom-color:#edf0f4}.supplier-account #supply-items td:first-child{width:45%}.supplier-account #supply-items td:nth-child(2){width:17%}.supplier-account #supply-items td:nth-child(3){width:25%}.supplier-account .supplier-invoice{border:1px solid #e2e8ee;border-radius:8px;margin-top:10px;padding:0 14px}.supplier-account .supplier-invoice>summary{display:flex;align-items:center;gap:12px;flex-wrap:wrap;padding:14px 0;list-style:none}.supplier-invoice>summary::-webkit-details-marker{display:none}.supplier-invoice-title{font-size:13px;color:#16242b}.supplier-invoice-title small{display:block;margin-top:4px;color:#718087;font-size:11px;font-weight:500}.supplier-invoice-balance{margin-left:auto;text-align:right;font-size:13px;font-variant-numeric:tabular-nums;color:#16242b}.supplier-invoice-balance small{display:block;font-size:10px;color:#718087;font-weight:500;margin-bottom:3px}.supplier-invoice>summary::after{content:'⌄';color:#718087}.supplier-account .supplier-invoice p{font-size:12px;line-height:1.7;color:#526473}.supplier-history-head{display:flex;justify-content:space-between;align-items:center;gap:12px}.supplier-history-count{font-size:11px;color:#718087;padding:4px 8px;border-radius:5px;background:#f0f4f7}.supplier-history-empty{padding:30px 10px;text-align:center;color:#718087;font-size:13px;line-height:1.8}
@media(max-width:1050px){.supplier-account>.supplier-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.header-row{flex-wrap:wrap;gap:14px}}@media(max-width:600px){.supplier-account .panel{padding:15px}.supplier-account .supplier-metric{font-size:17px}.supplier-account>.supplier-grid .panel{padding:13px}.supplier-account>.supplier-grid{gap:8px}.supplier-purchase-body{padding:0 15px 15px}.supplier-purchase>summary{padding:15px!important}.supplier-purchase>summary span{display:block;margin-top:5px}.supplier-account input:not([type=checkbox]),.supplier-account select,.supplier-account textarea{font-size:16px}.supplier-account .supplier-grid{grid-template-columns:minmax(0,1fr)}.supplier-account>.supplier-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.supplier-account-actions{width:100%}.supplier-invoice-balance{font-size:12px}}@media print{.supplier-account-actions{display:none}.supplier-account .supplier-invoice{break-inside:avoid}.supplier-account .supplier-invoice::details-content{content-visibility:visible!important;display:block!important}.supplier-account>.supplier-grid{grid-template-columns:repeat(4,minmax(0,1fr))}}
</style>
<div class="panel supplier-profile">
<div class="supplier-contact-line"><span class="supplier-badge {{ $supplier->is_active ? '' : 'inactive' }}">{{ $supplier->is_active ? 'Active' : 'Inactive' }}</span>@if($supplier->contact_name)<span>{{ $supplier->contact_name }}</span>@endif<span>{{ $supplier->phone ?: 'No phone on file' }}</span>@if($supplier->email)<span>{{ $supplier->email }}</span>@endif</div>
@if($supplier->address || $supplier->city || $supplier->country)<p class="supplier-muted">{{ collect([$supplier->address, $supplier->city, $supplier->country])->filter()->implode(', ') }}</p>@endif
<details class="supplier-no-print" @if($errors->hasAny(['name','contact_name','phone','email','address','city','country','is_active'])) open @endif><summary>Edit contact details</summary>
<form method="POST" action="{{ route('suppliers.update', $supplier->id) }}">@csrf @method('PUT')
<div class="supplier-grid">@foreach(['name' => 'Supplier name', 'contact_name' => 'Contact person', 'phone' => 'Phone', 'email' => 'Email', 'address' => 'Address', 'city' => 'City', 'country' => 'Country'] as $field => $label)
<label class="supplier-field">{{ $label }}<input name="{{ $field }}" value="{{ old($field, $supplier->$field) }}" type="{{ $field === 'email' ? 'email' : 'text' }}" @required($field === 'name') maxlength="{{ $field === 'phone' ? 30 : 255 }}"></label>
@endforeach</div>
<input type="hidden" name="is_active" value="0"><label><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $supplier->is_active))> Active supplier</label>
<button class="btn">Save details</button></form></details>
</div>
<div class="supplier-grid">
@foreach(['Total supplied' => $invoices->sum('total_cents'), 'Total paid' => $invoices->sum('paid_cents'), 'Outstanding' => $invoices->sum('balance_cents'), 'Overdue' => $invoices->where('status', 'Overdue')->sum('balance_cents')] as $label => $amount)
<div class="panel"><div class="supplier-muted">{{ $label }}</div><div class="supplier-metric">KES {{ number_format($amount / 100, 2) }}</div></div>
@endforeach
</div>
@if($supplier->is_active)
<details class="panel supplier-no-print supplier-purchase" id="record-supply" @if(old('items') || $errors->hasAny(['reference','supplied_at','terms','due_at','items','amount','receive_stock'])) open @endif><summary><div><strong>Record a purchase</strong><span>Supplier invoice, products and initial payment</span></div></summary><div class="supplier-purchase-body">
<p class="supplier-muted">Enter one invoice or delivery note. Choose existing products or type a product name. The reference must be unique for this supplier.</p>
<form method="POST" action="{{ route('suppliers.supply', $supplier->id) }}" id="supply-form">@csrf
<div class="supplier-grid">
<label class="supplier-field">Invoice / delivery reference<input name="reference" value="{{ old('reference') }}" required maxlength="100"></label>
<label class="supplier-field">Supply date<input name="supplied_at" type="date" value="{{ old('supplied_at', today()->toDateString()) }}" max="{{ today()->toDateString() }}" required></label>
<label class="supplier-field">Terms<select name="terms"><option value="credit" @selected(old('terms') === 'credit')>Credit</option><option value="cash" @selected(old('terms') === 'cash')>Cash (fully paid)</option></select></label>
<label class="supplier-field">Due date (required for credit)<input name="due_at" type="date" value="{{ old('due_at') }}"></label>
</div>
<div class="supplier-scroll"><table><thead><tr><th>Product / serial</th><th>Quantity</th><th>Unit cost (KES)</th><th></th></tr></thead><tbody id="supply-items">
@foreach(old('items', [['product_id' => '', 'quantity' => 1, 'unit_cost' => '']]) as $index => $item)
<tr><td style="min-width:240px">@php($selectedProduct = $products->firstWhere('id', $item['product_id'] ?? null))
<input type="text" name="items[{{ $index }}][product_search]" class="supplier-product-search" list="supplier-products" placeholder="Type product name or serial" aria-label="Product name or serial" autocomplete="off" maxlength="255" value="{{ $item['product_search'] ?? ($selectedProduct ? $selectedProduct->name . ($selectedProduct->serial_number ? ' - ' . $selectedProduct->serial_number : '') . ' (' . $selectedProduct->sku . ')' : '') }}" required>
<input type="hidden" name="items[{{ $index }}][product_id]" value="{{ $item['product_id'] ?? '' }}"></td><td><input name="items[{{ $index }}][quantity]" type="number" min="1" max="1000000" value="{{ $item['quantity'] ?? 1 }}" required></td><td><input name="items[{{ $index }}][unit_cost]" type="number" min="0" max="99999999.99" step="0.01" value="{{ $item['unit_cost'] ?? '' }}" required></td><td><button type="button" class="remove-item">Remove</button></td></tr>
@endforeach
</tbody></table></div>
<datalist id="supplier-products">@foreach($products as $product)<option value="{{ $product->name }}{{ $product->serial_number ? ' - ' . $product->serial_number : '' }} ({{ $product->sku }})" data-product-id="{{ $product->id }}"></option>@endforeach</datalist>
<div class="supplier-actions" style="margin:12px 0"><button type="button" id="add-item" class="btn">Add product row</button><strong>Invoice total: KES <span id="supply-total">0.00</span></strong></div>
<div class="supplier-grid">
<label class="supplier-field">Amount paid now (0 if unpaid)<input name="amount" type="number" min="0" step="0.01" value="{{ old('amount', '0') }}" required></label>
<label class="supplier-field">Payment method<select name="method">@foreach(['cash' => 'Cash', 'mpesa' => 'M-Pesa', 'bank' => 'Bank', 'cheque' => 'Cheque', 'other' => 'Other'] as $key => $label)<option value="{{ $key }}" @selected(old('method') === $key)>{{ $label }}</option>@endforeach</select></label>
<label class="supplier-field">Payment reference<input name="payment_reference" maxlength="100" value="{{ old('payment_reference') }}"></label>
</div>
<label class="supplier-field">Notes / delivery details<textarea name="notes" maxlength="2000">{{ old('notes') }}</textarea></label>
@if(auth()->user()->canAccessAbility('add_stock'))
<p><label><input type="checkbox" name="receive_stock" value="1" @checked(old('receive_stock'))> Add these quantities to the selected branch's main stock</label><br><span class="supplier-muted">Stock additions require a registered product in every row. Leave unchecked to save typed names or if stock was already entered. Saving with this option increases stock immediately.</span></p>
@endif
<button class="btn" style="margin-top:12px">Save supplier invoice</button>
</form></div></details>
@endif
<div class="panel"><div class="supplier-history-head"><h2>Purchases & payments</h2><span class="supplier-history-count">{{ $invoices->count() }} {{ $invoices->count() === 1 ? 'invoice' : 'invoices' }}</span></div>
<p class="supplier-muted">Open an invoice to view its products or record a payment.</p>
@forelse($invoices as $invoice)
<details class="supplier-invoice">
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
@empty<div class="supplier-history-empty"><strong>No purchases recorded yet</strong><br>Record a purchase to start tracking invoices and payments for this supplier.</div>@endforelse
</div></div>
<script>
(() => {
let printOpenInvoices = [];
window.addEventListener('beforeprint', () => {
printOpenInvoices = Array.from(document.querySelectorAll('.supplier-invoice')).filter(invoice => !invoice.open);
printOpenInvoices.forEach(invoice => { invoice.open = true; });
});
window.addEventListener('afterprint', () => { printOpenInvoices.forEach(invoice => { invoice.open = false; }); });
const body = document.getElementById('supply-items');
if (!body) return;
let next = Math.max(...Array.from(body.querySelectorAll('input[type=hidden]')).map(el => Number(el.name.match(/\[(\d+)\]/)[1]))) + 1;
const calculate = () => {
let cents = 0;
body.querySelectorAll('tr').forEach(row => { const quantity = row.querySelector('input[name$="[quantity]"]'); const cost = row.querySelector('input[name$="[unit_cost]"]'); cents += Number(quantity.value || 0) * Math.round(Number(cost.value || 0) * 100); });
document.getElementById('supply-total').textContent = (cents / 100).toLocaleString('en-KE', {minimumFractionDigits:2, maximumFractionDigits:2});
};
document.getElementById('add-item').addEventListener('click', () => {
const row = body.firstElementChild.cloneNode(true);
row.querySelectorAll('input,select').forEach(el => { el.name = el.name.replace(/\[\d+\]/, `[${next}]`); el.value = el.name.endsWith('[quantity]') ? '1' : ''; el.setCustomValidity(''); });
next++; body.appendChild(row); calculate();
});
body.addEventListener('click', event => { if(event.target.classList.contains('remove-item') && body.children.length > 1) { event.target.closest('tr').remove(); calculate(); } });
const options = Array.from(document.querySelectorAll('#supplier-products option'));
const resolveProduct = input => {
const match = options.find(option => option.value.toLowerCase() === input.value.trim().toLowerCase());
input.closest('td').querySelector('input[type=hidden]').value = match ? match.dataset.productId : '';
input.setCustomValidity('');
};
body.addEventListener('input', event => {
if (event.target.matches('.supplier-product-search')) resolveProduct(event.target);
calculate();
});
body.querySelectorAll('.supplier-product-search').forEach(resolveProduct);
calculate();
})();
</script>
@endsection
