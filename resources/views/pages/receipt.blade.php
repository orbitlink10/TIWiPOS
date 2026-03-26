<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt #{{ $sale->sale_number }}</title>
    <style>
        body {
            margin: 0;
            padding: 24px;
            font-family: "Manrope", "Segoe UI", sans-serif;
            background: #f4f6fb;
            color: #0f172a;
        }
        .receipt-wrap {
            max-width: 760px;
            margin: 0 auto;
        }
        .actions {
            display: flex;
            gap: 10px;
            margin-bottom: 14px;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            border: 1px solid #d7deea;
            border-radius: 10px;
            padding: 10px 14px;
            font-weight: 700;
            background: #fff;
            color: #0f172a;
            cursor: pointer;
        }
        .receipt {
            background: #fff;
            border: 1px solid #d7deea;
            border-radius: 14px;
            padding: 18px;
        }
        .row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            flex-wrap: wrap;
        }
        .muted {
            color: #64748b;
            font-size: 13px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14px;
            font-size: 14px;
        }
        th {
            text-align: left;
            background: #f8fafc;
            color: #334155;
            font-size: 12px;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }
        th, td {
            padding: 10px;
            border-bottom: 1px solid #e2e8f0;
        }
        .right {
            text-align: right;
        }
        .totals {
            margin-top: 14px;
            margin-left: auto;
            width: 300px;
        }
        .totals td {
            border-bottom: 0;
            padding: 6px 0;
        }
        .total-final {
            font-weight: 800;
            font-size: 16px;
        }
        @media print {
            body {
                background: #fff;
                padding: 0;
            }
            .actions {
                display: none;
            }
            .receipt {
                border: 0;
                border-radius: 0;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    @php($payment = $sale->payments->first())
    <div class="receipt-wrap">
        <div class="actions">
            <button type="button" class="btn" onclick="window.print()">Print Receipt</button>
            <a href="{{ route('sale') }}" class="btn">New Sale</a>
            <a href="{{ route('sales.index') }}" class="btn">Sales History</a>
        </div>

        <div class="receipt">
            <div class="row">
                <div>
                    <h1 style="margin:0; font-size:24px;">Sale Receipt</h1>
                    <div class="muted">Sale #{{ $sale->sale_number }}</div>
                    <div class="muted">{{ $sale->created_at->format('Y-m-d H:i') }}</div>
                </div>
                <div style="text-align:right;">
                    <div class="muted">Payment method</div>
                    <div style="font-weight:700;">{{ strtoupper($payment->method ?? 'N/A') }}</div>
                </div>
            </div>

            <div style="margin-top:10px; padding:10px; border-radius:10px; border:1px solid #e2e8f0; background:#f8fafc;">
                <div style="font-weight:700; font-size:13px;">Customer</div>
                <div class="muted">Name: {{ $sale->customer_name ?: 'Walk-in' }}</div>
                <div class="muted">Phone: {{ $sale->customer_phone ?: 'N/A' }}</div>
                @if($sale->customer_location)
                    <div class="muted">Location: {{ $sale->customer_location }}</div>
                @endif
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Serial</th>
                        <th class="right">Qty</th>
                        <th class="right">Price</th>
                        <th class="right">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sale->items as $item)
                        @php($serial = $item->serial_number ?: ($item->product->serial_number ?? null))
                        <tr>
                            <td>{{ $item->product->name ?? 'Product' }}</td>
                            <td>{{ $serial ?: '-' }}</td>
                            <td class="right">{{ $item->quantity }}</td>
                            <td class="right">KES {{ number_format($item->unit_price, 2) }}</td>
                            <td class="right">KES {{ number_format($item->subtotal, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <table class="totals">
                <tr>
                    <td class="muted">Subtotal</td>
                    <td class="right">KES {{ number_format($sale->subtotal, 2) }}</td>
                </tr>
                <tr>
                    <td class="muted">Tax</td>
                    <td class="right">KES {{ number_format($sale->tax, 2) }}</td>
                </tr>
                <tr class="total-final">
                    <td>Total</td>
                    <td class="right">KES {{ number_format($sale->total, 2) }}</td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>
