@extends('layouts.app')

@section('title', 'Sales Summary')

@section('header')
    <div class="header-row">
        <h1>Today's Sales Summary</h1>
        <a class="btn" href="{{ route('home') }}">Back to Dashboard</a>
    </div>
@endsection

@section('content')
    <div class="panel">
        <h2>Overview</h2>
        <p style="color: var(--muted); margin-top:6px;">Snapshot of today's performance.</p>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin-top:14px;">
            <div class="kpi-card green">
                <div style="font-size:22px;">KES {{ number_format($todaySalesTotal, 2) }}</div>
                <span>Gross sales</span>
            </div>
            <div class="kpi-card amber">
                <div style="font-size:22px;">{{ $todayOrders }}</div>
                <span>Orders</span>
            </div>
            <div class="kpi-card blue">
                <div style="font-size:22px;">{{ $todayCustomers }}</div>
                <span>Customers</span>
            </div>
            @if($canViewProfit ?? false)
                <div class="kpi-card" style="background:#0ea5e9;">
                    <div style="font-size:22px;">KES {{ number_format($todayProfit, 2) }}</div>
                    <span>Today's Profit</span>
                </div>
            @endif
        </div>

        <div style="margin-top:18px;">
            <h3 style="margin:0 0 8px;">Recent sales</h3>
            @if($recentSales->isEmpty())
                <div style="color:var(--muted); font-size:13px;">No sales recorded yet.</div>
            @else
                <div style="display:grid; gap:10px;">
                    @foreach($recentSales as $sale)
                        <div class="panel" style="padding:12px;">
                            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px;">
                                <div>
                                    <strong>{{ $sale->sale_number }}</strong>
                                    <div style="color:var(--muted); font-size:12px;">{{ $sale->created_at->format('Y-m-d H:i') }}</div>
                                </div>
                                <div style="font-weight:700;">KES {{ number_format($sale->total, 2) }}</div>
                            </div>
                            <div style="margin-top:6px; color:var(--muted); font-size:13px;">
                                @foreach($sale->items as $item)
                                    {{ $item->product->name ?? 'Item' }} x {{ $item->quantity }}@if(!$loop->last), @endif
                                @endforeach
                            </div>
                            <div style="margin-top:8px; display:flex; gap:8px; flex-wrap:wrap;">
                                <a class="btn" style="padding:8px 12px; font-size:13px;" href="{{ route('sale.receipt', $sale) }}">Receipt</a>
                                @if(auth()->user()->role === 'owner')
                                    <a class="btn" style="padding:8px 12px; font-size:13px; background:#f59e0b;" href="{{ route('sales.edit', $sale) }}">Edit</a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        @if($canViewProfit ?? false)
            <div style="margin-top:18px;">
                <h3 style="margin:0 0 8px;">Profit by product (today)</h3>
                @if($profitByProduct->isEmpty())
                    <div style="color:var(--muted); font-size:13px;">No product profits yet.</div>
                @else
                    <div style="overflow:auto;">
                        <table style="width:100%; border-collapse:collapse; font-size:14px; min-width:500px;">
                            <thead>
                                <tr style="background:#f7f7fb;">
                                    <th style="text-align:left; padding:10px;">Product</th>
                                    <th style="text-align:right; padding:10px;">Qty</th>
                                    <th style="text-align:right; padding:10px;">Sales</th>
                                    <th style="text-align:right; padding:10px;">Profit</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($profitByProduct as $row)
                                    <tr style="border-top:1px solid #e5e7eb;">
                                        <td style="padding:10px;">{{ $row->name }} ({{ $row->sku }})</td>
                                        <td style="padding:10px; text-align:right;">{{ $row->qty }}</td>
                                        <td style="padding:10px; text-align:right;">KES {{ number_format($row->sales_total, 2) }}</td>
                                        <td style="padding:10px; text-align:right; font-weight:700;">KES {{ number_format($row->profit_total, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @endif
    </div>
@endsection
