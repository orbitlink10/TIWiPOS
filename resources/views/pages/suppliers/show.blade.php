@extends('layouts.app')
@section('title', $supplier->name . ' - Supplier')
@section('header')
<div class="header-row"><div><h1>{{ $supplier->name }}</h1><p class="supplier-account-subtitle">Supplier account &middot; {{ $currentBranch->name ?? 'Current branch' }}</p></div><div class="supplier-account-actions"><a class="supplier-account-button" href="{{ route('suppliers.index') }}">&larr; Suppliers</a><button class="supplier-account-button supplier-no-print" type="button" onclick="window.print()">Print account</button>@if($supplier->is_active)<a class="supplier-account-button primary supplier-no-print" href="{{ route('purchases.create', ['supplier_id'=>$supplier->id]) }}">+ Record purchase</a>@endif</div></div>
@endsection
@section('content')
<div class="supplier-area supplier-account">
@include('pages.suppliers.account-style')
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
@include('pages.suppliers.products')

<div class="panel"><div class="supplier-history-head"><h2>Purchases & payments</h2><span class="supplier-history-count">{{ $invoices->count() }} {{ $invoices->count() === 1 ? 'invoice' : 'invoices' }}</span></div>
<p class="supplier-muted">Open an invoice to view its products or record a payment.</p>
@forelse($invoices as $invoice)
@include('pages.suppliers.invoice')
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
})();
</script>
@endsection
