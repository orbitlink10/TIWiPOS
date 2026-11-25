<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock movements - {{ $product->name }}</title>
    <style>
    *{box-sizing:border-box}body{margin:24px;font-family:Arial,sans-serif;color:#16242b;font-size:12px}h1{font-size:22px;margin:0 0 8px}.context{color:#526473;line-height:1.8;margin:4px 0}.toolbar{display:flex;gap:10px;margin-bottom:22px}.toolbar button,.toolbar a{padding:10px 14px;border:1px solid #cdd8e3;border-radius:6px;background:#fff;color:#19344e;font:inherit;text-decoration:none;cursor:pointer}table{width:100%;border-collapse:collapse;font-size:11px;margin-top:18px}thead{display:table-header-group}th{background:#f0f4f7;text-align:left;font-size:10px;text-transform:uppercase}th,td{padding:10px 8px;border-bottom:1px solid #dfe5eb;vertical-align:top;overflow-wrap:anywhere}.number{text-align:right;white-space:nowrap;font-variant-numeric:tabular-nums}.notes{white-space:pre-wrap;margin-top:4px;color:#526473}.empty{text-align:center;padding:30px}.table-wrap{overflow-x:auto}@page{size:A4 landscape;margin:12mm}@media print{body{margin:0;font-size:10px}.toolbar{display:none}h1{font-size:18px}table{font-size:9px;table-layout:fixed}th,td{padding:7px 5px}tr{break-inside:avoid}.table-wrap{overflow:visible}th:first-child{width:12%}th:nth-child(7){width:29%}}
    </style>
</head>
<body>
    <div class="toolbar"><button type="button" onclick="window.print()">Print / Save as PDF</button><a href="{{ route('products.show', ['product'=>$product] + $movementFilters) }}#stock-movements">Back to product</a></div>
    <h1>Stock movements</h1>
    <p class="context"><strong>{{ $product->name }}</strong> &middot; SKU: {{ $product->sku }}<br>{{ auth()->user()->business->name ?? config('app.name') }} &middot; {{ $currentBranch->name ?? 'Current business' }}</p>
    <p class="context">From: {{ $movementFilters['movement_from'] ?? 'Any date' }} &middot; To: {{ $movementFilters['movement_to'] ?? 'Any date' }} &middot; Type: {{ $movementTypes[$movementFilters['movement_type'] ?? ''] ?? 'All types' }}<br>{{ number_format($movementCount) }} matching movements &middot; Generated {{ now()->format('d M Y H:i') }}</p>
    <div class="table-wrap"><table>
        <thead><tr><th>Date / time</th><th>Type</th><th>Location</th><th class="number">Change</th><th class="number">Before</th><th class="number">After</th><th>Reference / notes</th><th>Recorded by</th></tr></thead>
        <tbody>@forelse($movements as $movement)
            <tr><td>{{ $movement->created_at->format('d M Y H:i') }}</td><td>{{ $movement->type === 'purchase' ? 'Purchase receipt' : ucfirst($movement->type) }}</td><td>{{ $movement->location }}</td><td class="number">{{ $movement->quantity_change > 0 ? '+' : '' }}{{ number_format($movement->quantity_change) }}</td><td class="number">{{ $movement->quantity_before === null ? '—' : number_format($movement->quantity_before) }}</td><td class="number">{{ $movement->quantity_after === null ? '—' : number_format($movement->quantity_after) }}</td><td>@if($movement->reference_type){{ str($movement->reference_type)->replace('_',' ')->title() }}@if($movement->reference_id) #{{ $movement->reference_id }}@endif @endif @if($movement->note)<div class="notes">{{ $movement->note }}</div>@endif</td><td>{{ $movement->user?->name ?? '—' }}</td></tr>
        @empty<tr><td colspan="8" class="empty">No stock movements match these filters.</td></tr>@endforelse</tbody>
    </table></div>
    <script>window.addEventListener('load', () => window.print());</script>
</body>
</html>
