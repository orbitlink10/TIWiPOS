@extends('layouts.app')

@section('title', 'Stock')

@section('header')
    <div class="header-row">
        <h1>Stock</h1>
        <a class="btn" href="{{ route('products') }}">Manage Products</a>
    </div>
@endsection

@section('content')
    <div class="panel">
        <h2>Inventory at a glance</h2>
        <p style="color: var(--muted); margin-top:6px;">Quick summary of your current stock levels.</p>

        @if (session('status'))
            <div style="margin-top:10px; padding:10px 12px; border-radius:10px; border:1px solid rgba(16,185,129,0.3); background:rgba(16,185,129,0.1); color:#065f46;">
                {{ session('status') }}
            </div>
        @endif

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;margin-top:14px;">
            <div class="kpi-card purple">
                <div style="font-size:22px;">{{ $outOfStock }}</div>
                <span>Out of stock</span>
            </div>
            <div class="kpi-card green">
                <div style="font-size:22px;">{{ $lowStock }}</div>
                <span>Low stock</span>
            </div>
            <div class="kpi-card amber">
                <div style="font-size:22px;">{{ $totalItems }}</div>
                <span>Total units on hand</span>
            </div>
        </div>

        <div style="margin-top:18px;">
            <table style="width:100%; border-collapse:collapse; border-spacing:0; font-size:14px;">
                <thead>
                    <tr style="background:#f7f7fb;">
                        <th style="text-align:left; padding:10px;">Product</th>
                        <th style="text-align:left; padding:10px;">SKU</th>
                        <th style="text-align:left; padding:10px;">Serial</th>
                        <th style="text-align:right; padding:10px;">On hand</th>
                        <th style="text-align:right; padding:10px;">Reorder at</th>
                        <th style="text-align:right; padding:10px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        <tr style="border-top:1px solid #e5e7eb;">
                            <td style="padding:10px;">{{ $product->name }}</td>
                            <td style="padding:10px;">{{ $product->sku }}</td>
                            <td style="padding:10px;">{{ $product->serial_number ?? 'N/A' }}</td>
                            <td style="padding:10px; text-align:right;">{{ $product->stock_on_hand ?? 0 }}</td>
                            <td style="padding:10px; text-align:right;">{{ $product->stock_alert ?? 0 }}</td>
                            <td style="padding:10px; text-align:right;">
                                <a class="btn" style="padding:8px 12px; font-size:13px;" href="{{ route('stock.edit', $product) }}">Edit stock</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="padding:12px; text-align:center; color:var(--muted);">No products recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
