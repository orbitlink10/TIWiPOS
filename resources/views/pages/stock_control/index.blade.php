@extends('layouts.app')
@section('title', 'Stock Control')
@push('styles')@include('pages.stock_control.style')@endpush
@section('header')
<div class="header-row"><div><h1>Stock Control</h1><p class="sc-intro">Stock balances, counts and movement history &middot; {{ $currentBranch->name ?? 'Current branch' }}</p></div><div class="sc-actions">@if(auth()->user()->canViewFinancials())<a class="sc-button" href="{{ route('purchases.index') }}">Purchase receiving</a>@endif @if(auth()->user()->canAccessAbility('adjust_stock'))<a class="sc-button" href="{{ route('stock-control.adjust') }}">Stock adjustment</a><a class="sc-button primary" href="{{ route('stock-control.periods.index',['start'=>1]) }}">+ Start count</a>@endif</div></div>
@endsection
@section('content')
<div class="sc-page">
    @if(session('status'))<div class="sc-message" role="status">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="sc-message error" role="alert">{{ $errors->first() }}</div>@endif
    <nav class="sc-tabs" aria-label="Stock control sections"><a href="{{ route('stock-control.periods.index') }}">Stock periods</a><a class="{{ $tab==='stock' ? 'active' : '' }}" href="{{ route('stock-control.index') }}" @if($tab==='stock') aria-current="page" @endif>Stock balances</a><a class="{{ $tab==='movements' ? 'active' : '' }}" href="{{ route('stock-control.index',['tab'=>'movements']) }}" @if($tab==='movements') aria-current="page" @endif>Movement history</a></nav>
    @if($totals)
    <div class="sc-kpis"><div class="sc-kpi"><span>Units on hand</span><strong>{{ number_format($totals->units) }}</strong></div><div class="sc-kpi"><span>Low-stock products</span><strong>{{ number_format($totals->low_count) }}</strong></div><div class="sc-kpi"><span>Out-of-stock products</span><strong>{{ number_format($totals->out_count) }}</strong></div>@if($canViewValue)<div class="sc-kpi"><span>Stock value at current cost</span><strong>KES {{ number_format($totals->stock_value,2) }}</strong></div>@endif</div>
    @endif
    <section class="sc-card">
        <h2>{{ $tab==='stock' ? 'Stock balances' : 'Stock movement history' }}</h2>
        <p class="sc-muted">{{ number_format($rows->total()) }} matching {{ $tab==='stock' ? 'products' : 'movements' }}@if($tab==='stock') &middot; Totals reflect these filters, including inactive products.@endif</p>
        <form method="GET" action="{{ route('stock-control.index') }}" class="sc-filters">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <label class="sc-field">Search<input type="search" name="q" value="{{ $search }}" maxlength="100" placeholder="{{ $tab==='stock' ? 'Name, SKU or barcode…' : 'Product, SKU or note…' }}"></label>
            @if($tab==='stock')
            <label class="sc-field">Category<select name="category_id"><option value="">All categories</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected((string)($filters['category_id']??'')===(string)$category->id)>{{ $category->name }}</option>@endforeach</select></label>
            <label class="sc-field">Stock status<select name="status"><option value="">All stock</option>@foreach(['in'=>'In stock','low'=>'Low stock','out'=>'Out of stock'] as $key=>$label)<option value="{{ $key }}" @selected(($filters['status']??'')===$key)>{{ $label }}</option>@endforeach</select></label>
            @else
            <label class="sc-field">From date<input type="date" name="from" value="{{ $filters['from']??'' }}"></label><label class="sc-field">To date<input type="date" name="to" value="{{ $filters['to']??'' }}"></label>
            <label class="sc-field">Type<select name="type"><option value="">All movements</option>@foreach(['purchase'=>'Purchase receipts','sale'=>'Sales','adjustment'=>'Adjustments','return'=>'Returns'] as $key=>$label)<option value="{{ $key }}" @selected(($filters['type']??'')===$key)>{{ $label }}</option>@endforeach</select></label>
            @endif
            <button type="submit" class="sc-button primary">Apply</button><a class="sc-button" href="{{ route('stock-control.index',['tab'=>$tab]) }}">Reset</a>
        </form>
        <div class="sc-scroll"><table class="sc-table" @if($tab==='movements') style="min-width:950px" @endif>
            @if($tab==='stock')
            <thead><tr><th>Product</th><th>Category</th><th class="number">On hand</th><th class="number">Reorder</th><th>Stock status</th>@if($canViewValue)<th class="number">Value (KES)</th>@endif<th>Actions</th></tr></thead>
            <tbody>@forelse($rows as $product)
                <tr><td><a href="{{ route('products.show',$product->id) }}">{{ $product->name }}</a><small>{{ $product->sku }}@if(!$product->is_active) &middot; Inactive @endif</small></td><td>{{ $product->category?->parent?->name ?? $product->category?->name ?? '—' }}</td><td class="number">{{ number_format($product->on_hand) }}</td><td class="number">{{ $product->stock_alert }}</td><td>@if($product->on_hand<=0)<span class="sc-badge out">Out of stock</span>@elseif($product->stock_alert>0 && $product->on_hand<=$product->stock_alert)<span class="sc-badge low">Low stock</span>@else<span class="sc-badge">In stock</span>@endif</td>@if($canViewValue)<td class="number">{{ number_format($product->stock_value,2) }}</td>@endif<td>@if(auth()->user()->canAccessAbility('adjust_stock'))<a href="{{ route('stock-control.adjust',['product_id'=>$product->id]) }}">Adjust</a>@else<a href="{{ route('products.show',$product->id) }}">View</a>@endif</td></tr>
            @empty<tr><td colspan="{{ $canViewValue ? 7 : 6 }}" class="sc-empty">No products match these stock filters.</td></tr>@endforelse</tbody>
            @else
            <thead><tr><th>Date / time</th><th>Product</th><th>Type</th><th>Location</th><th class="number">Change</th><th class="number">Before</th><th class="number">After</th><th>Reason / notes</th><th>Staff</th></tr></thead>
            <tbody>@forelse($rows as $movement)
                <tr><td style="white-space:nowrap">{{ $movement->created_at->format('d M Y H:i') }}</td><td>@if($movement->product)<a href="{{ route('products.show',$movement->product->id) }}">{{ $movement->product->name }}</a><small>{{ $movement->product->sku }}</small>@else Product unavailable @endif</td><td>{{ $movement->type==='purchase' ? 'Purchase receipt' : ucfirst($movement->type) }}</td><td>{{ $movement->location }}</td><td class="number" style="font-weight:700;color:{{ $movement->quantity_change<0 ? '#b42318' : '#166534' }}">{{ $movement->quantity_change>0 ? '+' : '' }}{{ $movement->quantity_change }}</td><td class="number">{{ $movement->quantity_before ?? '—' }}</td><td class="number">{{ $movement->quantity_after ?? '—' }}</td><td>{{ $movement->note ?: '—' }}@if($movement->reference_id)<small>{{ str($movement->reference_type)->replace('_',' ')->title() }} #{{ $movement->reference_id }}</small>@endif</td><td>{{ $movement->user->name ?? '—' }}</td></tr>
            @empty<tr><td colspan="9" class="sc-empty">No stock movements match these filters.</td></tr>@endforelse</tbody>
            @endif
        </table></div>
        {{ $rows->links('pagination.app') }}
    </section>
</div>
@endsection
