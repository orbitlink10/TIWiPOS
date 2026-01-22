@extends('layouts.app')

@section('title', 'Stock')

@section('header')
    <div class="header-row">
        <h1>Stock</h1>
        <a class="btn" href="{{ route('products') }}">📦 Manage Products</a>
    </div>
@endsection

@section('content')
    <div class="panel">
        <h2>Inventory at a glance</h2>
        <p style="color: var(--muted); margin-top:6px;">Quick summary of your current stock levels.</p>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;margin-top:14px;">
            <div class="kpi-card purple">
                <div style="font-size:22px;">0</div>
                <span>Out of stock</span>
            </div>
            <div class="kpi-card green">
                <div style="font-size:22px;">24</div>
                <span>Low stock</span>
            </div>
            <div class="kpi-card amber">
                <div style="font-size:22px;">480</div>
                <span>Total items</span>
            </div>
        </div>

        <div style="margin-top:18px;">
            <table style="width:100%; border-collapse:collapse; border-spacing:0; font-size:14px;">
                <thead>
                    <tr style="background:#f7f7fb;">
                        <th style="text-align:left; padding:10px;">Product</th>
                        <th style="text-align:left; padding:10px;">SKU</th>
                        <th style="text-align:right; padding:10px;">On hand</th>
                        <th style="text-align:right; padding:10px;">Reorder at</th>
                    </tr>
                </thead>
                <tbody>
                    <tr style="border-top:1px solid #e5e7eb;">
                        <td style="padding:10px;">Sample Product</td>
                        <td style="padding:10px;">SKU-001</td>
                        <td style="padding:10px; text-align:right;">12</td>
                        <td style="padding:10px; text-align:right;">5</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
@endsection
