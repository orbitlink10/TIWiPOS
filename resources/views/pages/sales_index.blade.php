@extends('layouts.app')

@section('title', 'Sales History')

@section('header')
    <div class="header-row">
        <h1>Sales History</h1>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <a class="btn" href="{{ route('sale') }}">New Sale</a>
            <a class="btn" href="{{ route('summary') }}" style="background:#0ea5e9;">Today Summary</a>
        </div>
    </div>
@endsection

@section('content')
    <div class="panel">
        <h2>All sales</h2>
        <p style="color: var(--muted); margin-top:6px;">Search, view, reprint, or edit past sales.</p>

        <form method="GET" style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:10px; margin-top:10px;">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Search sale number or customer" style="padding:10px; border-radius:10px; border:1px solid #e5e7eb;">
            <input type="date" name="date" value="{{ request('date') }}" style="padding:10px; border-radius:10px; border:1px solid #e5e7eb;">
            <button class="btn" type="submit" style="justify-content:center;">Filter</button>
        </form>

        <div style="margin-top:14px; overflow:auto;">
            <table style="width:100%; border-collapse:collapse; min-width:720px; font-size:14px;">
                <thead>
                    <tr style="background:#f7f7fb;">
                        <th style="text-align:left; padding:10px;">Sale #</th>
                        <th style="text-align:left; padding:10px;">Customer</th>
                        <th style="text-align:left; padding:10px;">Cashier</th>
                        <th style="text-align:right; padding:10px;">Total</th>
                        <th style="text-align:left; padding:10px;">Payment</th>
                        <th style="text-align:left; padding:10px;">Date</th>
                        <th style="text-align:right; padding:10px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sales as $sale)
                        @php $payment = $sale->payments->first(); @endphp
                        <tr style="border-top:1px solid #e5e7eb;">
                            <td style="padding:10px; font-weight:700;">{{ $sale->sale_number }}</td>
                            <td style="padding:10px;">{{ $sale->customer_name ?? 'Walk-in' }}</td>
                            <td style="padding:10px;">{{ $sale->user->name ?? 'N/A' }}</td>
                            <td style="padding:10px; text-align:right;">KES {{ number_format($sale->total, 2) }}</td>
                            <td style="padding:10px;">{{ $payment->method ?? 'N/A' }}</td>
                            <td style="padding:10px;">{{ $sale->created_at->format('Y-m-d H:i') }}</td>
                            <td style="padding:10px; text-align:right; display:flex; gap:8px; justify-content:flex-end; flex-wrap:wrap;">
                                <a class="btn" style="padding:8px 12px; font-size:13px;" href="{{ route('sale.receipt', $sale) }}">Receipt</a>
                                @if(auth()->user()->role === 'owner')
                                    <a class="btn" style="padding:8px 12px; font-size:13px; background:#f59e0b;" href="{{ route('sales.edit', $sale) }}">Edit</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="padding:12px; text-align:center; color:var(--muted);">No sales found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($sales->hasPages())
            <div style="margin-top:12px;">
                {{ $sales->links() }}
            </div>
        @endif
    </div>
@endsection
