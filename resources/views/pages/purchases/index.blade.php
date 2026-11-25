@extends('layouts.app')
@section('title', 'Purchases')
@section('header')
<div class="header-row"><div><h1>Purchases</h1><p class="supplier-account-subtitle">Supplier invoices, deliveries and payments &middot; {{ $currentBranch->name ?? 'Current branch' }}</p></div><a class="supplier-account-button primary" href="{{ route('purchases.create') }}">+ New purchase</a></div>
@endsection
@section('content')
<div class="supplier-area supplier-account">
@include('pages.suppliers.account-style')
<style>.purchase-filters{display:flex;gap:10px;align-items:end;flex-wrap:wrap;margin-bottom:18px}.purchase-filters .supplier-field{flex:1;min-width:140px}.purchase-register{min-width:950px!important}.purchase-register .money{text-align:right;white-space:nowrap;font-variant-numeric:tabular-nums}.purchase-register a{color:#1057b0;text-decoration:none;font-weight:700}.purchase-register a:hover{text-decoration:underline}.purchase-register .supplier-badge{white-space:nowrap}</style>
<div class="supplier-grid">
@foreach(['Purchases'=>$totals->total,'Paid'=>$totals->paid,'Outstanding'=>$totals->outstanding,'Overdue'=>$totals->overdue] as $label=>$amount)<div class="panel"><div class="supplier-muted">{{ $label }}</div><div class="supplier-metric">KES {{ number_format($amount/100,2) }}</div></div>@endforeach
</div>
<section class="panel">
    <div class="supplier-history-head"><h2>Purchase register</h2><span class="supplier-history-count">{{ number_format($invoices->total()) }} invoices</span></div>
    <p class="supplier-muted">Totals cover all purchases matching your filters. Paid and outstanding balances are current.</p>
    <form method="GET" action="{{ route('purchases.index') }}" class="purchase-filters">
        <label class="supplier-field">Search<input type="search" name="q" value="{{ $search }}" maxlength="100" placeholder="Invoice or supplier…"></label>
        <label class="supplier-field">Supplier<select name="supplier_id"><option value="">All suppliers</option>@foreach($suppliers as $supplier)<option value="{{ $supplier->id }}" @selected((string)request('supplier_id')===(string)$supplier->id)>{{ $supplier->name }}</option>@endforeach</select></label>
        <label class="supplier-field">From<input type="date" name="from" value="{{ request('from') }}"></label>
        <label class="supplier-field">To<input type="date" name="to" value="{{ request('to') }}"></label>
        <button type="submit" class="supplier-account-button primary">Apply</button><a class="supplier-account-button" href="{{ route('purchases.index') }}">Reset</a>
    </form>
    <div class="supplier-scroll"><table class="purchase-register">
        <thead><tr><th>Reference</th><th>Supplier</th><th>Supplied</th><th>Due</th><th class="money">Total (KES)</th><th class="money">Paid (KES)</th><th class="money">Balance (KES)</th><th>Status</th><th>Invoice</th></tr></thead>
        <tbody>@forelse($invoices as $invoice)<tr>
            <td><a href="{{ route('purchases.show',$invoice->id) }}">{{ $invoice->reference }}</a></td><td>{{ $invoice->supplier->name ?? 'Supplier unavailable' }}</td><td>{{ $invoice->supplied_at->format('d M Y') }}</td><td>{{ $invoice->due_at?->format('d M Y') ?? '—' }}</td><td class="money">{{ number_format($invoice->total_cents/100,2) }}</td><td class="money">{{ number_format($invoice->paid_cents/100,2) }}</td><td class="money">{{ number_format($invoice->balance_cents/100,2) }}</td><td><span class="supplier-badge {{ $invoice->status==='Overdue' ? 'overdue' : '' }}">{{ $invoice->status }}</span></td><td><a href="{{ route('purchases.show',$invoice->id) }}">View &rarr;</a></td>
        </tr>@empty<tr><td colspan="9" class="supplier-history-empty">No purchases match these filters. Use New purchase to record a supplier invoice.</td></tr>@endforelse</tbody>
    </table></div>
    {{ $invoices->links('pagination.app') }}
</section>
</div>
@endsection
