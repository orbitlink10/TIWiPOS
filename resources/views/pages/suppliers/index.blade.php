@extends('layouts.app')
@section('title', 'Supplier')
@section('header')
<div class="header-row"><h1>Supplier</h1><a class="btn" href="{{ route('suppliers.create') }}">Add Supplier</a></div>
@endsection
@section('content')
<div class="supplier-area">
@include('pages.suppliers.style')
<p class="supplier-muted">Manage supplier contacts, supplied products and payments. Financial totals are for the selected branch, in KES.</p>
<div class="supplier-grid">
@foreach(['Total supplied' => $total, 'Outstanding balance' => $balance, 'Overdue balance' => $overdue] as $label => $amount)
<div class="panel"><div class="supplier-muted">{{ $label }}</div><div class="supplier-metric">KES {{ number_format($amount / 100, 2) }}</div></div>
@endforeach
</div>
<div class="panel">
<form method="GET" class="supplier-actions"><label for="supplier-search">Find supplier</label><input id="supplier-search" name="q" value="{{ $search }}" placeholder="Name, contact person or phone" style="max-width:340px"><button class="btn">Search</button><a href="{{ route('suppliers.index') }}">Clear</a></form>
<div class="supplier-scroll"><table><thead><tr><th>Supplier</th><th>Contact</th><th>Phone / email</th><th>Status</th><th>Branch balance (KES)</th><th></th></tr></thead><tbody>
@forelse($suppliers as $supplier)
<tr><td>{{ $supplier->name }}</td><td>{{ $supplier->contact_name ?: '—' }}</td><td>{{ $supplier->phone ?: '—' }}<br>{{ $supplier->email }}</td><td>{{ $supplier->is_active ? 'Active' : 'Inactive' }}</td><td>{{ number_format(($balances[$supplier->id] ?? 0) / 100, 2) }}</td><td><a href="{{ route('suppliers.show', $supplier->id) }}">View account</a></td></tr>
@empty<tr><td colspan="6">No suppliers found. Add a supplier to start recording deliveries and payments.</td></tr>@endforelse
</tbody></table></div>
{{ $suppliers->links() }}
</div></div>
@endsection
