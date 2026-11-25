@extends('layouts.app')
@section('title', 'Receive '.$invoice->reference)
@section('header')
<div class="header-row"><div><h1>Receive purchase</h1><p class="supplier-account-subtitle">{{ $invoice->reference }} &middot; {{ $invoice->supplier->name ?? 'Supplier' }} &middot; {{ $currentBranch->name ?? 'Current branch' }}</p></div><a class="supplier-account-button" href="{{ route('purchases.show',$invoice->id) }}">&larr; Purchase</a></div>
@endsection
@section('content')
<div class="supplier-area supplier-account">
@include('pages.suppliers.account-style')
<style>.receiving-lines{display:grid;gap:12px;margin:18px 0}.receiving-line{border:1px solid #e2e8ee;border-radius:8px;padding:16px}.receiving-row{display:grid;grid-template-columns:minmax(180px,1fr) repeat(3,80px) 130px;gap:14px;align-items:center}.receiving-row small{display:block;font-size:10px;font-weight:700;text-transform:uppercase;color:#718087;margin-bottom:5px}.receiving-row strong{font-size:13px}.receiving-serials{margin-top:14px;border-top:1px solid #edf0f4;padding-top:14px}.serial-input{margin-top:10px;max-width:650px}.serial-input textarea{resize:vertical}.receive-check{display:flex;gap:8px;align-items:center}.receive-help{font-size:11px;color:#718087;line-height:1.7;margin:7px 0 0}.receive-count{font-size:11px;color:#1057b0}.receive-footer{display:flex;gap:10px;justify-content:flex-end;margin-top:18px}.receive-meta{max-width:340px}.receiving-line [hidden]{display:none!important}@media(max-width:850px){.receiving-row{grid-template-columns:repeat(4,minmax(0,1fr))}.receiving-row>div:first-child{grid-column:1/-1}.receiving-row .supplier-field{min-width:0}.receiving-line{padding:12px}.receiving-row{gap:10px}.receive-footer{flex-wrap:wrap}}</style>
<section class="panel"><h2>Confirm delivered quantities</h2><p class="supplier-muted">Receive only what arrived. Short deliveries stay open for the next receipt. Stock increases by the quantities confirmed below.</p>
<form method="POST" action="{{ route('purchases.receive.store',$invoice->id) }}">
    @csrf
    <input type="hidden" name="receipt_token" value="{{ old('receipt_token', (string)\Illuminate\Support\Str::uuid()) }}">
    <div class="receive-meta"><label class="supplier-field">Received date<input name="received_at" type="date" value="{{ old('received_at',today()->toDateString()) }}" min="{{ $invoice->supplied_at->toDateString() }}" max="{{ today()->toDateString() }}" required></label></div>
    <div class="receiving-lines">
    @foreach($invoice->items as $item)
        @php($remaining = max(0, $item->quantity - $item->received_quantity))
        @if($remaining > 0)
        @php($requiresSerials = $item->product?->tracks_serials || filled($item->product?->serial_number))
        <section class="receiving-line">
            <div class="receiving-row">
                <div><strong>{{ $item->product_name }}</strong><p class="receive-help">{{ $item->product?->sku }}</p>@if(!$item->product_id)<label class="supplier-field">Link catalogue product<select name="lines[{{ $item->id }}][product_id]" required><option value="">Select product…</option>@foreach($products as $product)<option value="{{ $product->id }}" @selected((string)old('lines.'.$item->id.'.product_id')===(string)$product->id)>{{ $product->name }}</option>@endforeach</select></label>@endif</div>
                <div><small>Ordered</small>{{ $item->quantity }}</div><div><small>Received</small>{{ $item->received_quantity }}</div><div><small>Remaining</small>{{ $remaining }}</div>
                <label class="supplier-field">Receive now<input name="lines[{{ $item->id }}][quantity]" class="receive-quantity" type="number" min="0" max="{{ $remaining }}" step="1" value="{{ old('lines.'.$item->id.'.quantity',$remaining) }}" required></label>
            </div>
            <div class="receiving-serials">
                <label class="receive-check">@if($requiresSerials)<input name="lines[{{ $item->id }}][serialized]" type="hidden" value="1"><span>Serial numbers required for this product</span>@else<input name="lines[{{ $item->id }}][serialized]" class="receive-serialized" type="checkbox" value="1" @checked(old('lines.'.$item->id.'.serialized'))><span>This product has serial numbers</span>@endif</label>
                <div class="serial-input" @if(!$requiresSerials && !old('lines.'.$item->id.'.serialized') && !old('lines.'.$item->id.'.serial_numbers')) hidden @endif>
                    <label class="supplier-field">Serial numbers<textarea name="lines[{{ $item->id }}][serial_numbers]" rows="4" maxlength="100000" placeholder="Scan or enter one serial per line">{{ old('lines.'.$item->id.'.serial_numbers') }}</textarea></label>
                    <p class="receive-help">Enter one serial per received unit, on separate lines or separated by commas. Each serial must be new and unique.</p><span class="receive-count" role="status"></span>
                </div>
            </div>
        </section>
        @endif
    @endforeach
    </div>
    <label class="supplier-field">Delivery notes (optional)<textarea name="notes" rows="2" maxlength="2000" placeholder="Missing items, delivery note or other receiving details">{{ old('notes') }}</textarea></label>
    <div class="receive-footer"><a class="supplier-account-button" href="{{ route('purchases.show',$invoice->id) }}">Cancel</a><button type="submit" class="supplier-account-button primary" id="confirm-receipt">Confirm receipt & add stock</button></div>
</form>
</section>
</div>
<script>
(() => {
document.querySelectorAll('.receiving-line').forEach(line => {
    const toggle = line.querySelector('.receive-serialized');
    const area = line.querySelector('.serial-input');
    const text = line.querySelector('textarea');
    const quantity = line.querySelector('.receive-quantity');
    const update = () => {
        if (toggle) { area.hidden = !toggle.checked; text.disabled = !toggle.checked; }
        const serials = text.value.split(/[\r\n,]+/).map(serial => serial.trim()).filter(Boolean);
        line.querySelector('.receive-count').textContent = serials.length+' serials entered / '+quantity.value+' units to receive';
    };
    toggle?.addEventListener('change', update); text.addEventListener('input', update); quantity.addEventListener('input', update);
    update();
});
document.getElementById('confirm-receipt').form.addEventListener('submit', () => { document.getElementById('confirm-receipt').disabled=true; });
window.addEventListener('pageshow', () => { document.getElementById('confirm-receipt').disabled=false; });
})();
</script>
@endsection
