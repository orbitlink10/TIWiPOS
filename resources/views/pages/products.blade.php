@extends('layouts.app')

@section('title', 'Products')

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
    @php($columnCount = 5 + ($canViewFinancials ? 1 : 0) + ($canManageCatalog ? 1 : 0))
    <div class="panel">
        <h2>Catalog</h2>
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
        <div style="display:flex; gap:10px; margin-top:12px; flex-wrap:wrap;">
            <input type="text" placeholder="Search products..." style="flex:1; min-width:220px; padding:12px; border-radius:10px; border:1px solid #e5e7eb;">
            @if($canManageCatalog)
                <div style="display:flex; gap:8px; flex-wrap:wrap;">
                    <a class="btn" href="{{ route('products.create') }}">➕ Add Product</a>
                    <a class="btn" href="{{ route('categories.create') }}" style="background:#0ea5e9;">🗂️ Add Category</a>
                    <a class="btn" href="{{ route('suppliers.create') }}" style="background:#22c55e;">🏭 Add Supplier</a>
                    <a class="btn" href="{{ route('stock.adjust') }}" style="background:#f59e0b;">📦 Adjust Stock</a>
                </div>
            @endif
        </div>

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
                        @if($canManageCatalog)
                            <th style="text-align:center; padding:10px; width:220px;">Action</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        <tr style="border-top:1px solid #e5e7eb;">
                            <td style="padding:10px;">{{ $product->name }}</td>
                            <td style="padding:10px;">{{ $product->sku }}</td>
                            <td style="padding:10px;">{{ $product->serial_number }}</td>
                            @if($canViewFinancials)
                                <td style="padding:10px; text-align:right;">KES {{ number_format($product->cost, 2) }}</td>
                            @endif
                            <td style="padding:10px; text-align:right;">KES {{ number_format($product->price, 2) }}</td>
                            <td style="padding:10px; text-align:right;">{{ $product->stock_on_hand ?? 0 }}</td>
                            @if($canManageCatalog)
                                <td style="padding:10px; text-align:center;">
                                    <div style="display:flex; gap:8px; justify-content:center; flex-wrap:wrap;">
                                        <a href="{{ route('products.edit', $product) }}" style="border:1px solid #bfdbfe; background:#eff6ff; color:#1d4ed8; border-radius:8px; padding:6px 12px; font-weight:700; text-decoration:none;">
                                            Edit
                                        </a>
                                        <form method="POST" action="{{ route('products.destroy', $product) }}" onsubmit="return confirm('Delete this product? This action cannot be undone.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" style="border:1px solid #fecaca; background:#fff1f2; color:#b91c1c; border-radius:8px; padding:6px 12px; font-weight:700; cursor:pointer;">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $columnCount }}" style="padding:12px; text-align:center; color:var(--muted);">No products yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
