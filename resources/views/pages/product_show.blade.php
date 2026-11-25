@extends('layouts.app')
@section('title', $product->name)
@push('styles')
<style>
main{min-width:0}.product-view{display:grid;gap:16px;min-width:0}.pv-actions{display:flex;gap:8px;flex-wrap:wrap}.pv-button{display:inline-block;padding:10px 15px;border:1px solid #ccd8e5;border-radius:7px;background:#fff;color:#334155;font:inherit;font-size:12px;font-weight:700;text-decoration:none;cursor:pointer}.pv-button.primary{background:#1769d1;border-color:#1769d1;color:#fff}.pv-button.danger{border-color:#f1ccc6;background:#fff5f3;color:#b42318}.pv-intro{font-size:12px;color:#718087;margin:6px 0 0;overflow-wrap:anywhere}.pv-card{padding:20px;background:#fff;border:1px solid #e2e8ee;border-radius:10px;min-width:0}.pv-card h2{margin:0 0 16px;font-size:16px}.pv-kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px}.pv-kpi small{display:block;font-size:10px;color:#718087;text-transform:uppercase;letter-spacing:.05em;font-weight:700}.pv-kpi strong{display:block;margin-top:7px;font-size:23px;color:#16242b;font-variant-numeric:tabular-nums}.pv-badge{display:inline-block;font-size:11px;font-weight:700;background:#edf8ef;color:#166534;padding:5px 9px;border-radius:5px}.pv-badge.inactive{background:#f0f3f6;color:#667885}.pv-details{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:22px;margin:0}.pv-details dt{font-size:11px;color:#718087;margin-bottom:7px}.pv-details dd{font-size:13px;color:#334155;margin:0;overflow-wrap:anywhere}.pv-details a{color:#1057b0}.pv-description{font-size:13px;color:#526473;white-space:pre-wrap;line-height:1.7;margin:0}.pv-scroll{overflow-x:auto}.pv-table{width:100%;border-collapse:collapse;min-width:350px;font-size:12px}.pv-table th{background:#f7f9fb;text-align:left;color:#718087;text-transform:uppercase;font-size:10px;padding:11px}.pv-table td{padding:12px 11px;border-bottom:1px solid #edf0f4;overflow-wrap:anywhere}.pv-columns{display:grid;grid-template-columns:1fr 1fr;gap:16px;min-width:0}.pv-message{padding:12px 16px;background:#edf8ef;color:#166534;border:1px solid #c7e7ce;border-radius:8px;font-size:13px}.pv-note{font-size:11px;color:#718087;line-height:1.6}.product-view :is(a,button):focus-visible{outline:2px solid #a7c9f4;outline-offset:2px}@media(max-width:1100px){.pv-columns{grid-template-columns:minmax(0,1fr)}.header-row{flex-wrap:wrap;gap:14px}}@media(max-width:600px){.pv-details{grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}.pv-card{padding:16px}.pv-kpi strong{font-size:20px}}
</style>
@endpush
@section('header')
<div class="header-row"><div><h1>{{ $product->name }}</h1><p class="pv-intro">Product details &middot; {{ $product->sku }}</p></div><div class="pv-actions">
    <a class="pv-button" href="{{ route('products') }}">&larr; Products</a>
    @if(auth()->user()->canAccessAbility('manage_catalog'))<a class="pv-button primary" href="{{ route('products.edit', $product) }}">Edit product</a>@endif
    @if(auth()->user()->canAccessAbility('delete_products'))<form method="POST" action="{{ route('products.destroy', $product) }}" onsubmit="return confirm('Delete this product? This action cannot be undone.');">@csrf @method('DELETE')<button type="submit" class="pv-button danger">Delete product</button></form>@endif
</div></div>
@endsection
@section('content')
<div class="product-view">
    @if(session('status'))<div class="pv-message" role="status">{{ session('status') }}</div>@endif
    <div class="pv-kpis">
        <div class="pv-card pv-kpi"><small>Stock on hand</small><strong>{{ number_format($stocks->sum('quantity')) }}</strong><p class="pv-note">{{ $currentBranch->name ?? 'Current business' }}</p></div>
        <div class="pv-card pv-kpi"><small>Selling price</small><strong>KES {{ number_format($product->price,2) }}</strong></div>
        @if($canViewFinancials)<div class="pv-card pv-kpi"><small>Cost price</small><strong>KES {{ number_format($product->cost,2) }}</strong></div>@endif
        <div class="pv-card pv-kpi"><small>Status</small><p><span class="pv-badge {{ $product->is_active ? '' : 'inactive' }}">{{ $product->is_active ? 'Active' : 'Inactive' }}</span></p></div>
    </div>
    <section class="pv-card"><h2>Product information</h2><dl class="pv-details">
        <div><dt>SKU</dt><dd>{{ $product->sku }}</dd></div>
        <div><dt>Category</dt><dd>{{ $product->category?->parent?->name ?? $product->category?->name ?? '—' }}</dd></div>
        <div><dt>Supplier</dt><dd>@if($product->supplier)@if(auth()->user()->canAccessAbility('manage_catalog') && $canViewFinancials)<a href="{{ route('suppliers.show',$product->supplier->id) }}">{{ $product->supplier->name }}</a>@else{{ $product->supplier->name }}@endif @else Not assigned @endif</dd></div>
        <div><dt>Serial number</dt><dd>{{ $product->serial_number ?: '—' }}</dd></div>
        <div><dt>Barcode</dt><dd>{{ $product->barcode ?: '—' }}</dd></div>
        <div><dt>Reorder level</dt><dd>{{ $product->stock_alert }}</dd></div>
        <div><dt>Date recorded</dt><dd>{{ $product->recorded_at?->format('d M Y') ?? '—' }}</dd></div>
    </dl></section>
    @if($product->description)<section class="pv-card"><h2>Description / notes</h2><p class="pv-description">{{ $product->description }}</p></section>@endif
    <div class="pv-columns">
        <section class="pv-card"><h2>Stock by location</h2><div class="pv-scroll"><table class="pv-table"><thead><tr><th>Location</th><th>Quantity</th></tr></thead><tbody>@forelse($stocks as $stock)<tr><td>{{ $stock->location }}</td><td>{{ $stock->quantity }}</td></tr>@empty<tr><td colspan="2">No stock recorded for this branch.</td></tr>@endforelse</tbody></table></div></section>
        <section class="pv-card"><h2>Received serial numbers</h2><p class="pv-note">Serials recorded on purchase receipts for {{ $currentBranch->name ?? 'the current business' }}.</p><div class="pv-scroll"><table class="pv-table"><thead><tr><th>Serial number</th><th>Received</th></tr></thead><tbody>@forelse($serials as $serial)<tr><td>{{ $serial->serial_number }}</td><td>{{ \Carbon\Carbon::parse($serial->received_at)->format('d M Y') }}</td></tr>@empty<tr><td colspan="2">No receipt serials recorded.</td></tr>@endforelse</tbody></table></div>{{ $serials->links('pagination.app') }}</section>
    </div>
    <section class="pv-card" id="stock-movements">
        <h2>Stock movements</h2><p class="pv-note">Recorded stock changes for {{ $currentBranch->name ?? 'the current business' }}, most recent first.</p>
        <div class="pv-scroll"><table class="pv-table" style="min-width:900px">
            <thead><tr><th>Date / time</th><th>Type</th><th>Location</th><th style="text-align:right">Change</th><th style="text-align:right">Before</th><th style="text-align:right">After</th><th>Reference / notes</th><th>Recorded by</th></tr></thead>
            <tbody>@forelse($movements as $movement)
                <tr>
                    <td style="white-space:nowrap">{{ $movement->created_at->format('d M Y H:i') }}</td>
                    <td>{{ ['sale'=>'Sale','purchase'=>'Purchase receipt','adjustment'=>'Adjustment','return'=>'Return'][$movement->type] ?? ucfirst($movement->type) }}</td>
                    <td>{{ $movement->location }}</td>
                    <td style="text-align:right;font-weight:700;color:{{ $movement->quantity_change > 0 ? '#166534' : ($movement->quantity_change < 0 ? '#b42318' : '#526473') }}">{{ $movement->quantity_change > 0 ? '+' : '' }}{{ number_format($movement->quantity_change) }}</td>
                    <td style="text-align:right">{{ $movement->quantity_before === null ? '—' : number_format($movement->quantity_before) }}</td>
                    <td style="text-align:right">{{ $movement->quantity_after === null ? '—' : number_format($movement->quantity_after) }}</td>
                    <td>@if($movement->reference_type)<span>{{ str($movement->reference_type)->replace('_',' ')->title() }}@if($movement->reference_id) #{{ $movement->reference_id }}@endif</span>@endif @if($movement->note)<div class="pv-note" style="white-space:pre-wrap">{{ $movement->note }}</div>@endif</td>
                    <td>{{ $movement->user->name ?? '—' }}</td>
                </tr>
            @empty<tr><td colspan="8">No recorded stock movements for this product in this branch.</td></tr>@endforelse</tbody>
        </table></div>
        {{ $movements->links('pagination.app') }}
    </section>
</div>
@endsection
