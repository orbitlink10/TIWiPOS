@extends('layouts.app')

@section('title', 'Receipt')

@section('header')
    <div class="header-row">
        <h1>Receipt #{{ $sale->sale_number }}</h1>
        <a class="btn" href="{{ route('sale') }}">New Sale</a>
    </div>
@endsection

@section('content')
    <div class="panel" id="print-area">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
            <div>
                <h2 style="margin:0;">Tiwi POS</h2>
                <div style="color:var(--muted); font-size:13px;">127.0.0.1 | {{ $sale->created_at->format('Y-m-d H:i') }}</div>
            </div>
            <div style="text-align:right;">
                <div style="font-weight:700;">Receipt</div>
                <div style="color:var(--muted); font-size:13px;">Sale #{{ $sale->sale_number }}</div>
            </div>
        </div>

        <div style="margin-top:14px;">
            <table style="width:100%; border-collapse:collapse; font-size:14px;">
                <thead>
                    <tr style="background:#f7f7fb;">
                        <th style="text-align:left; padding:10px;">Item</th>
                        <th style="text-align:right; padding:10px;">Qty</th>
                        <th style="text-align:right; padding:10px;">Price</th>
                        <th style="text-align:right; padding:10px;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sale->items as $item)
                        <tr style="border-top:1px solid #e5e7eb;">
                            <td style="padding:10px;">
                                <div>{{ $item->product->name ?? 'Product' }}</div>
                                @if($item->product && $item->product->serial_number)
                                    <div style="color:var(--muted); font-size:12px;">Serial: {{ $item->product->serial_number }}</div>
                                @endif
                            </td>
                            <td style="padding:10px; text-align:right;">{{ $item->quantity }}</td>
                            <td style="padding:10px; text-align:right;">KES {{ number_format($item->unit_price, 2) }}</td>
                            <td style="padding:10px; text-align:right;">KES {{ number_format($item->subtotal, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div style="margin-top:12px; display:flex; justify-content:flex-end;">
            <table style="min-width:260px; font-size:14px;">
                <tr>
                    <td style="padding:6px; color:var(--muted);">Subtotal</td>
                    <td style="padding:6px; text-align:right;">KES {{ number_format($sale->subtotal, 2) }}</td>
                </tr>
                <tr>
                    <td style="padding:6px; color:var(--muted);">Tax</td>
                    <td style="padding:6px; text-align:right;">KES {{ number_format($sale->tax, 2) }}</td>
                </tr>
                <tr>
                    <td style="padding:6px; font-weight:700;">Total</td>
                    <td style="padding:6px; text-align:right; font-weight:700;">KES {{ number_format($sale->total, 2) }}</td>
                </tr>
            </table>
        </div>

        <div style="margin-top:16px; color:var(--muted); font-size:13px;">Thank you for your purchase.</div>
    </div>

    <div style="margin-top:12px;">
        <button class="btn" onclick="window.print()">Print Receipt</button>
        <a class="btn" style="background:#e5e7eb; color:#0f172a;" href="{{ route('sales.index') }}">View all sales</a>
    </div>
@endsection
