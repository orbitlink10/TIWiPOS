@extends('layouts.app')

@section('title', 'Make a Sale')

@section('header')
    <div class="header-row">
        <h1>Make a Sale</h1>
        <a class="btn" href="{{ route('summary') }}">📄 View Sales Summary</a>
    </div>
@endsection

@section('content')
    <div class="panel">
        <h2>Quick register</h2>
        <p style="color: var(--muted); margin-top:6px;">Select a product, enter quantity, and complete the sale.</p>

        @if ($errors->any())
            <div style="margin-top:10px; padding:10px 12px; border-radius:10px; border:1px solid rgba(239,68,68,0.3); background:rgba(239,68,68,0.08); color:#b91c1c;">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        @if (session('status'))
            <div style="margin-top:10px; padding:10px 12px; border-radius:10px; border:1px solid rgba(16,185,129,0.3); background:rgba(16,185,129,0.1); color:#065f46;">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('sale.store') }}" style="margin-top:14px; display:grid; grid-template-columns:1fr 320px; gap:16px;">
            @csrf
            <div class="panel" style="padding:14px;">
                <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                    Product (stock on hand)
                    <select name="product_id" id="product_id" required style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                        <option value="">Select product</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}" data-price="{{ $product->price }}" data-stock="{{ $product->stock_on_hand }}">
                                {{ $product->name }} ({{ $product->sku }}) — {{ $product->stock_on_hand }} on hand
                            </option>
                        @endforeach
                    </select>
                </label>
                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:12px; margin-top:12px;">
                    <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                        Quantity
                        <input name="quantity" id="quantity" type="number" min="1" value="1" required style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                    </label>
                    <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                        Unit price (KES)
                        <input id="unit_price" type="number" step="0.01" readonly style="padding:12px;border:1px solid #e5e7eb;border-radius:10px; background:#f8fafc;">
                    </label>
                </div>
            </div>

            <div class="panel" style="padding:14px;">
                <h3 style="margin:0 0 8px;">Cart</h3>
                <div style="color:var(--muted);font-size:13px;" id="stock_info">Select a product to see availability.</div>
                <div style="margin-top:14px; display:flex; justify-content:space-between; font-weight:700;">
                    <span>Subtotal</span>
                    <span id="subtotal_text">KES 0.00</span>
                </div>
                <div style="margin-top:6px; display:flex; justify-content:space-between;">
                    <span style="color:var(--muted);">Total</span>
                    <span style="font-weight:700;" id="total_text">KES 0.00</span>
                </div>
                <label style="display:flex; flex-direction:column; gap:6px; font-weight:600; margin-top:12px;">
                    Payment method
                    <select name="method" style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                        <option value="cash">Cash</option>
                        <option value="card">Card</option>
                        <option value="mobile">Mobile</option>
                        <option value="bank">Bank</option>
                        <option value="other">Other</option>
                    </select>
                </label>
                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:12px; margin-top:12px;">
                    <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                        Customer name
                        <input name="customer_name" type="text" placeholder="Walk-in" style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                    </label>
                    <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                        Phone
                        <input name="customer_phone" type="text" placeholder="+254..." style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                    </label>
                    <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                        Location
                        <input name="customer_location" type="text" placeholder="City/Area" style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                    </label>
                </div>
                <button class="btn" type="submit" style="width:100%; justify-content:center; margin-top:14px;">💳 Complete Sale</button>
            </div>
        </form>
    </div>
@push('scripts')
<script>
    const productSelect = document.getElementById('product_id');
    const qtyInput = document.getElementById('quantity');
    const unitInput = document.getElementById('unit_price');
    const subtotalText = document.getElementById('subtotal_text');
    const totalText = document.getElementById('total_text');
    const stockInfo = document.getElementById('stock_info');

    function updateTotals() {
        const option = productSelect.options[productSelect.selectedIndex];
        const price = parseFloat(option?.dataset.price || 0);
        const stock = parseInt(option?.dataset.stock || 0);
        const qty = parseInt(qtyInput.value || 0);
        unitInput.value = price ? price.toFixed(2) : '';
        const subtotal = price * qty;
        subtotalText.textContent = 'KES ' + subtotal.toFixed(2);
        totalText.textContent = 'KES ' + subtotal.toFixed(2);
        stockInfo.textContent = option.value ? `${stock} in stock` : 'Select a product to see availability.';
    }

    productSelect.addEventListener('change', updateTotals);
    qtyInput.addEventListener('input', updateTotals);
    updateTotals();
</script>
@endpush
@endsection
