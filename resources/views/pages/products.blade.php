@extends('layouts.app')

@section('title', 'Products')
@push('styles')
<style>
main{min-width:0}.catalog-filters{display:flex;gap:10px;align-items:end;flex-wrap:wrap;margin:18px 0}.catalog-filters label{display:grid;gap:6px;flex:1 1 170px;min-width:0;color:#667885;font-size:11px;font-weight:700}.catalog-filters .catalog-search{flex:2 1 260px}.catalog-filters input,.catalog-filters select{min-width:0;width:100%;padding:11px;border:1px solid #cdd8e3;border-radius:7px;background:#fff;color:#334155;font:inherit;font-size:13px;font-weight:500}.catalog-filters :is(input,select):focus-visible{outline:2px solid #a7c9f4;outline-offset:2px}.catalog-button{display:inline-block;padding:11px 14px;border:1px solid #ccd8e5;border-radius:7px;background:#fff;color:#334155;font:inherit;font-size:12px;font-weight:700;text-decoration:none;cursor:pointer;white-space:nowrap}.catalog-button.primary{background:#1769d1;border-color:#1769d1;color:#fff}.catalog-tools{display:flex;gap:8px;flex-wrap:wrap;margin-top:12px}.catalog-result-count{font-size:11px;color:#718087;margin:12px 0}.catalog-product-meta{display:block;font-size:11px;color:#718087;margin-top:5px;line-height:1.6}.catalog-error{padding:12px;background:#fff0ee;color:#b42318;border-radius:7px;font-size:12px;margin:12px 0}@media(max-width:600px){.catalog-filters input,.catalog-filters select{font-size:16px}.catalog-filters .catalog-search{flex-basis:100%}}
</style>
@endpush

@section('header')
    <div class="header-row">
        <h1>Products</h1>
        @if(auth()->user()->canAccessAbility('manage_catalog'))
            <a class="btn" href="{{ route('products.create') }}">➕ Add Product</a>
        @endif
    </div>
@endsection

@section('content')
    @php($canManageCatalog = auth()->user()->canAccessAbility('manage_catalog'))
    @php($canViewFinancials = auth()->user()->canViewFinancials())
    @php($columnCount = 6 + ($canViewFinancials ? 1 : 0))
    <div class="panel">
        <h2>Product catalogue</h2>
        <p style="color: var(--muted); margin-top:6px;">Manage items available for sale.</p>
        @if (session('status'))
            <div style="margin-top:10px; padding:10px 12px; border-radius:10px; border:1px solid rgba(16,185,129,0.3); background:rgba(16,185,129,0.1); color:#065f46;">
                {{ session('status') }}
            </div>
        @endif
        @if (session('error'))
            <div style="margin-top:10px; padding:10px 12px; border-radius:10px; border:1px solid rgba(239,68,68,0.3); background:rgba(239,68,68,0.08); color:#b91c1c;">
                {{ session('error') }}
            </div>
        @endif
        @if($errors->any())<div class="catalog-error" role="alert">{{ $errors->first() }}</div>@endif
        <form method="GET" action="{{ route('products') }}" class="catalog-filters">
            <label class="catalog-search">Search products<input type="search" name="q" value="{{ $search }}" maxlength="100" placeholder="Name, SKU, barcode or serial…"></label>
            <label>Category<select name="category_id"><option value="">All categories</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected((string)($filters['category_id'] ?? '') === (string)$category->id)>{{ $category->name }}</option>@endforeach</select></label>
            <label>Supplier<select name="supplier_id"><option value="">All suppliers</option><option value="unassigned" @selected(($filters['supplier_id'] ?? '') === 'unassigned')>No supplier assigned</option>@foreach($suppliers as $supplier)<option value="{{ $supplier->id }}" @selected((string)($filters['supplier_id'] ?? '') === (string)$supplier->id)>{{ $supplier->name }}</option>@endforeach</select></label>
            <button type="submit" class="catalog-button primary">Apply filters</button><a class="catalog-button" href="{{ route('products') }}">Reset</a>
        </form>
            @if($canManageCatalog)
                <div class="catalog-tools">
                    <a class="catalog-button" href="{{ route('categories.create') }}">Add category</a>
                    <a class="catalog-button" href="{{ route('suppliers.create') }}">Add supplier</a>
                    @if(auth()->user()->canAccessAbility('adjust_stock'))<a class="catalog-button" href="{{ route('stock-control.adjust') }}">Adjust stock</a>@endif
                </div>
            @endif
        <p class="catalog-result-count">{{ number_format($products->total()) }} matching {{ $products->total() === 1 ? 'product' : 'products' }} &middot; Stock shown for {{ $currentBranch->name ?? 'the current business' }}</p>

        <div style="margin-top:16px; overflow:auto;">
            <table style="width:100%; border-collapse:collapse; border-spacing:0; font-size:14px; min-width:600px;">
                <thead>
                    <tr style="background:#f7f7fb;">
                        <th style="text-align:left; padding:10px;">Name</th>
                        <th style="text-align:left; padding:10px;">SKU</th>
                        <th style="text-align:left; padding:10px;">Serial</th>
                        @if($canViewFinancials)
                            <th style="text-align:right; padding:10px;">Cost</th>
                        @endif
                        <th style="text-align:right; padding:10px;">Price</th>
                        <th style="text-align:right; padding:10px;">Stock</th>
                        <th style="text-align:center; padding:10px; width:100px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        <tr style="border-top:1px solid #e5e7eb;">
                            <td style="padding:10px;">{{ $product->name }}<span class="catalog-product-meta">{{ $product->category?->parent?->name ?? $product->category?->name ?? 'No category' }} &middot; {{ $product->supplier?->name ?? 'No supplier assigned' }}</span></td>
                            <td style="padding:10px;">{{ $product->sku }}</td>
                            <td style="padding:10px;">{{ $product->serial_number }}</td>
                            @if($canViewFinancials)
                                <td style="padding:10px; text-align:right;">KES {{ number_format($product->cost, 2) }}</td>
                            @endif
                            <td style="padding:10px; text-align:right;">KES {{ number_format($product->price, 2) }}</td>
                            <td style="padding:10px; text-align:right;">{{ $product->stock_on_hand ?? 0 }}</td>
                            <td style="padding:10px; text-align:center;"><a href="{{ route('products.show', $product) }}" style="display:inline-block;border:1px solid #bfdbfe;background:#eff6ff;color:#1d4ed8;border-radius:7px;padding:7px 14px;font-weight:700;text-decoration:none;">View</a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $columnCount }}" style="padding:24px; text-align:center; color:var(--muted);">No products match these filters. Try another search or reset the filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $products->links('pagination.app') }}
    </div>
@endsection
