@extends('layouts.app')
@section('title', 'Stock Adjustment')
@push('styles')@include('pages.stock_control.style')@endpush
@section('header')
<div class="header-row"><div><h1>Stock adjustment</h1><p class="sc-intro">Record a count or quantity correction &middot; {{ $currentBranch->name ?? 'Current branch' }}</p></div><a class="sc-button" href="{{ route('stock-control.index') }}">&larr; Stock Control</a></div>
@endsection
@section('content')
<div class="sc-page">
@if($errors->any())<div class="sc-message error" role="alert">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
<form method="POST" action="{{ route('stock-control.adjust.store') }}" id="stock-adjustment-form" class="sc-adjust-grid">
    @csrf
    <input type="hidden" name="operation_token" value="{{ old('operation_token',(string)\Illuminate\Support\Str::uuid()) }}">
    <input type="hidden" name="expected_quantity" id="expected-quantity">
    <section class="sc-card"><h2>Adjustment details</h2><p class="sc-muted">Use purchase receiving for supplier deliveries. Use adjustments for counts, damage, loss or corrections.</p>
        <div class="sc-form-grid">
            <label class="sc-field sc-wide">Product<select name="product_id" id="adjust-product" required><option value="">Select product…</option>@foreach($products as $product)<option value="{{ $product->id }}" @selected((string)$selectedProduct===(string)$product->id)>{{ $product->name }} &middot; {{ $product->sku }}</option>@endforeach</select></label>
            <label class="sc-field" id="adjust-location-field">Stock location<select name="location" id="adjust-location"><option value="main">main</option></select></label>
            <label class="sc-field">Action<select name="mode" id="adjust-mode">@foreach(['count'=>'Set counted quantity','add'=>'Increase stock','remove'=>'Decrease stock'] as $mode=>$label)<option value="{{ $mode }}" @selected(old('mode','count')===$mode)>{{ $label }}</option>@endforeach</select></label>
            <label class="sc-field"><span id="adjust-quantity-label">Counted quantity</span><input name="quantity" id="adjust-quantity" type="number" min="0" max="1000000" step="1" value="{{ old('quantity') }}" required></label>
            <label class="sc-field">Reason<select name="reason" required><option value="">Select reason…</option>@foreach(['Stock count','Opening balance','Damage','Loss','Correction','Other'] as $reason)<option value="{{ $reason }}" @selected(old('reason')===$reason)>{{ $reason }}</option>@endforeach</select></label>
            <label class="sc-field sc-wide">Notes (required for Other)<textarea name="notes" rows="3" maxlength="1000" placeholder="Explain the stock change">{{ old('notes') }}</textarea></label>
        </div>
        <div class="sc-footer"><a class="sc-button" href="{{ route('stock-control.index') }}">Cancel</a><button class="sc-button primary" type="submit" id="save-stock-adjustment">Save adjustment</button></div>
    </section>
    <aside class="sc-card sc-preview" aria-label="Stock adjustment preview"><p><span class="sc-muted">Current quantity at this location</span><strong id="stock-before">—</strong></p><p><span class="sc-muted">Change</span><strong id="stock-change">—</strong></p><p><span class="sc-muted">New quantity</span><strong id="stock-after">—</strong></p><p class="sc-muted">Every saved change records the reason, your name, and the previous and new quantities.</p><p class="sc-message error" id="stock-preview-error" hidden>The new quantity cannot be negative.</p></aside>
</form>
</div>
@php($stockData = $stocks->map(fn($stock)=>['product_id'=>(string)$stock->product_id,'location'=>$stock->location,'quantity'=>(int)$stock->quantity])->values())
<script>
(() => {
    const stocks = @json($stockData);
    const product = document.getElementById('adjust-product'), location = document.getElementById('adjust-location');
    const mode = document.getElementById('adjust-mode'), quantity = document.getElementById('adjust-quantity');
    const savedLocation = @json(old('location','main'));
    const preview = () => {
        const before = stocks.find(stock=>stock.product_id===product.value && stock.location===location.value)?.quantity || 0;
        const entered = quantity.value === '' ? null : Number(quantity.value);
        const after = entered === null ? null : mode.value==='add' ? before+entered : mode.value==='remove' ? before-entered : entered;
        document.getElementById('expected-quantity').value = before;
        document.getElementById('stock-before').textContent = product.value ? before.toLocaleString() : '—';
        document.getElementById('stock-after').textContent = product.value && after!==null ? after.toLocaleString() : '—';
        const change = after===null ? null : after-before;
        document.getElementById('stock-change').textContent = product.value && change!==null ? (change>0 ? '+' : '')+change.toLocaleString() : '—';
        document.getElementById('stock-preview-error').hidden = after===null || after>=0;
        document.getElementById('adjust-quantity-label').textContent = mode.value==='count' ? 'Counted quantity' : 'Quantity to '+(mode.value==='add' ? 'add' : 'remove');
        quantity.min = mode.value==='count' ? '0' : '1';
    };
    const locations = initial => {
        const names = [...new Set(['main',...stocks.filter(stock=>stock.product_id===product.value).map(stock=>stock.location)])];
        location.replaceChildren(...names.map(name=>new Option(name,name)));
        if (initial && names.includes(savedLocation)) location.value=savedLocation;
        document.getElementById('adjust-location-field').hidden=names.length===1;
        preview();
    };
    product.addEventListener('change',()=>locations(false)); location.addEventListener('change',preview); mode.addEventListener('change',preview); quantity.addEventListener('input',preview);
    document.getElementById('stock-adjustment-form').addEventListener('submit',()=>{document.getElementById('save-stock-adjustment').disabled=true;});
    window.addEventListener('pageshow',()=>{document.getElementById('save-stock-adjustment').disabled=false;});
    locations(true);
})();
</script>
@endsection
