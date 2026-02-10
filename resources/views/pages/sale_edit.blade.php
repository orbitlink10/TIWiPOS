@extends('layouts.app')

@section('title', 'Edit Sale')

@section('header')
    <div class="header-row">
        <h1>Edit Sale #{{ $sale->sale_number }}</h1>
        <a class="btn" href="{{ route('sales.index') }}">Back to Sales</a>
    </div>
@endsection

@section('content')
    <div class="panel">
        <h2>Update items and customer</h2>
        <p style="color: var(--muted); margin-top:6px;">Adjust quantities or items. Stock will be restored and recalculated automatically.</p>

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

        <form id="sale_form" method="POST" action="{{ route('sales.update', $sale) }}" style="margin-top:14px; display:grid; grid-template-columns:1fr 360px; gap:16px;">
            @csrf
            @method('PUT')
            <div class="panel" style="padding:14px;">
                <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                    Search Product
                    <div style="display:flex; flex-direction:column; gap:8px;">
                        <input id="search_product" type="text" placeholder="Search product name..." style="padding:12px;border:1px solid #111;border-radius:10px;">
                        <input id="barcode_search" type="text" placeholder="Scan barcode..." style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                    </div>
                    <select id="product_id" style="padding:12px;border:1px solid #e5e7eb;border-radius:10px; margin-top:10px;">
                        <option value="">Select product</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}" data-name="{{ $product->name }}" data-price="{{ $product->price }}" data-stock="{{ $product->stock_on_hand }}" data-barcode="{{ $product->barcode }}" data-serial="{{ $product->serial_number }}">
                                {{ $product->name }} ({{ $product->sku }}) - {{ $product->stock_on_hand }} on hand
                            </option>
                        @endforeach
                    </select>
                </label>
                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:12px; margin-top:12px;">
                    <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                        Quantity
                        <input id="quantity" type="number" min="1" value="1" style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                    </label>
                    <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                        Unit price (KES)
                        <input id="unit_price" type="number" step="0.01" readonly style="padding:12px;border:1px solid #e5e7eb;border-radius:10px; background:#f8fafc;">
                    </label>
                </div>
                <button type="button" id="add_to_cart" class="btn" style="margin-top:12px; width:fit-content;">Add to cart</button>
            </div>

            <div class="panel" style="padding:14px;">
                <h3 style="margin:0 0 8px;">Cart</h3>
                <div style="color:var(--muted);font-size:13px;" id="stock_info">Add items to cart.</div>
                <table style="width:100%; border-collapse:collapse; margin-top:10px;">
                    <thead>
                        <tr style="border-bottom:1px solid #e5e7eb; color:#6b7280;">
                            <th style="text-align:left; padding:6px;">Item</th>
                            <th style="text-align:right; padding:6px;">Qty</th>
                            <th style="text-align:right; padding:6px;">Price</th>
                            <th style="text-align:right; padding:6px;">Sub</th>
                            <th style="text-align:right; padding:6px;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="cart_body"></tbody>
                </table>
                <div style="margin-top:14px; display:flex; justify-content:space-between; font-weight:700;">
                    <span>Subtotal</span>
                    <span id="subtotal_text">KES 0.00</span>
                </div>
                @php
                    $applyTaxOld = old('apply_tax');
                    $shouldApplyTax = $applyTaxOld !== null ? ((string) $applyTaxOld === '1') : ($sale->tax > 0);
                @endphp
                <label style="display:flex; align-items:center; gap:8px; font-weight:600; margin-top:10px;">
                    <input type="hidden" name="apply_tax" value="0">
                    <input id="apply_tax" name="apply_tax" type="checkbox" value="1" @checked($shouldApplyTax) style="width:16px; height:16px;">
                    Apply 16% tax
                </label>
                <div style="margin-top:6px; display:flex; justify-content:space-between;">
                    <span style="color:var(--muted);">Tax (16%)</span>
                    <span id="tax_text">KES 0.00</span>
                </div>
                <div style="margin-top:6px; display:flex; justify-content:space-between;">
                    <span style="color:var(--muted);">Total</span>
                    <span style="font-weight:700;" id="total_text">KES 0.00</span>
                </div>
                <label style="display:flex; flex-direction:column; gap:6px; font-weight:600; margin-top:12px;">
                    Payment method
                    @php $existingMethod = optional($sale->payments->first())->method ?? 'cash'; @endphp
                    <select name="method" style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                        <option value="cash" {{ $existingMethod === 'cash' ? 'selected' : '' }}>Cash</option>
                        <option value="card" {{ $existingMethod === 'card' ? 'selected' : '' }}>Card</option>
                        <option value="mobile" {{ $existingMethod === 'mobile' ? 'selected' : '' }}>Mobile</option>
                        <option value="bank" {{ $existingMethod === 'bank' ? 'selected' : '' }}>Bank</option>
                        <option value="other" {{ $existingMethod === 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                </label>
                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:12px; margin-top:12px;">
                    <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                        Customer name
                        <input name="customer_name" type="text" placeholder="Walk-in" value="{{ old('customer_name', $sale->customer_name) }}" style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                    </label>
                    <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                        Phone
                        <input name="customer_phone" type="text" placeholder="+254..." value="{{ old('customer_phone', $sale->customer_phone) }}" style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                    </label>
                    <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                        Location
                        <input name="customer_location" type="text" placeholder="City/Area" value="{{ old('customer_location', $sale->customer_location) }}" style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                    </label>
                </div>
                <div style="color:var(--muted); font-size:12px; margin-top:8px;">Changes will re-open stock, then deduct based on the updated cart.</div>
                <button class="btn" type="submit" style="width:100%; justify-content:center; margin-top:14px;">Save Changes</button>
            </div>
        </form>
    </div>
@push('scripts')
<script>
    const productSelect = document.getElementById('product_id');
    const qtyInput = document.getElementById('quantity');
    const unitInput = document.getElementById('unit_price');
    const subtotalText = document.getElementById('subtotal_text');
    const taxText = document.getElementById('tax_text');
    const totalText = document.getElementById('total_text');
    const applyTaxInput = document.getElementById('apply_tax');
    const stockInfo = document.getElementById('stock_info');
    const cartBody = document.getElementById('cart_body');
    const addBtn = document.getElementById('add_to_cart');
    const form = document.getElementById('sale_form');
    const searchInput = document.getElementById('search_product');
    const barcodeInput = document.getElementById('barcode_search');
    let idx = 0;

    const existingItems = @json($sale->items->map(fn($item) => [
        'product_id' => $item->product_id,
        'name' => $item->product->name ?? 'Product',
        'quantity' => $item->quantity,
        'unit_price' => $item->unit_price,
        'serial' => $item->product->serial_number ?? 'N/A',
    ]));

    function updateTotalsPreview() {
        const option = productSelect.options[productSelect.selectedIndex];
        const price = parseFloat(option?.dataset.price || 0);
        const stock = parseInt(option?.dataset.stock || 0);
        unitInput.value = price ? price.toFixed(2) : '';
        const serial = option?.dataset.serial;
        stockInfo.textContent = option.value ? `${stock} in stock${serial ? ' | Serial: ' + serial : ''}` : 'Select a product to see availability.';
    }

    function refreshCartTotals() {
        let subtotal = 0;
        cartBody.querySelectorAll('tr').forEach(row => {
            const line = parseFloat(row.dataset.subtotal || 0);
            subtotal += line;
        });
        const tax = applyTaxInput.checked ? (subtotal * 0.16) : 0;
        const total = subtotal + tax;
        subtotalText.textContent = 'KES ' + subtotal.toFixed(2);
        taxText.textContent = 'KES ' + tax.toFixed(2);
        totalText.textContent = 'KES ' + total.toFixed(2);
    }

    function renderRow({ productId, name, qty, price, serial }) {
        const lineSubtotal = price * qty;
        const tr = document.createElement('tr');
        tr.dataset.subtotal = lineSubtotal;
        tr.innerHTML = `
            <td style="padding:6px;">
                <div>${name}</div>
                <div style="color:var(--muted); font-size:12px;">Serial: ${serial}</div>
            </td>
            <td style="padding:6px; text-align:right;">${qty}</td>
            <td style="padding:6px; text-align:right;">${price.toFixed(2)}</td>
            <td style="padding:6px; text-align:right;">${lineSubtotal.toFixed(2)}</td>
            <td style="padding:6px; text-align:right;">
                <button type="button" class="btn" style="padding:6px 10px; font-size:12px; background:#fee2e2; color:#991b1b; border:1px solid #fecdd3;">Remove</button>
            </td>
            <input type="hidden" name="items[${idx}][product_id]" value="${productId}">
            <input type="hidden" name="items[${idx}][quantity]" value="${qty}">
        `;
        idx++;
        tr.querySelector('button').addEventListener('click', () => {
            tr.remove();
            refreshCartTotals();
        });
        cartBody.appendChild(tr);
        refreshCartTotals();
    }

    function addToCart() {
        const option = productSelect.options[productSelect.selectedIndex];
        const productId = option.value;
        const name = option.dataset.name;
        const price = parseFloat(option.dataset.price || 0);
        const stock = parseInt(option.dataset.stock || 0);
        const serial = option.dataset.serial || 'N/A';
        const qty = parseInt(qtyInput.value || 0);
        if (!productId) {
            alert('Select a product first.');
            return;
        }
        if (!qty || qty < 1) {
            alert('Enter a valid quantity.');
            return;
        }
        if (stock && qty > stock) {
            alert('Quantity exceeds stock on hand.');
            return;
        }

        renderRow({ productId, name, qty, price, serial });
    }

    addBtn.addEventListener('click', addToCart);
    form.addEventListener('submit', (e) => {
        if (cartBody.querySelectorAll('tr').length === 0) {
            e.preventDefault();
            alert('Add at least one item to the cart.');
        }
    });

    productSelect.addEventListener('change', updateTotalsPreview);
    qtyInput.addEventListener('input', updateTotalsPreview);
    applyTaxInput.addEventListener('change', refreshCartTotals);

    function filterProducts(term) {
        term = term.toLowerCase();
        for (const opt of productSelect.options) {
            if (!opt.value) continue;
            const text = opt.textContent.toLowerCase();
            opt.hidden = text.indexOf(term) === -1;
        }
        const visible = Array.from(productSelect.options).find(o => !o.hidden && o.value);
        if (visible) {
            productSelect.value = visible.value;
        }
        updateTotalsPreview();
    }

    searchInput.addEventListener('input', (e) => {
        filterProducts(e.target.value);
    });

    barcodeInput.addEventListener('input', (e) => {
        const code = e.target.value.trim();
        if (!code) return;
        const match = Array.from(productSelect.options).find(o => o.dataset.barcode === code);
        if (match) {
            productSelect.value = match.value;
            updateTotalsPreview();
        }
    });

    existingItems.forEach(item => {
        renderRow({
            productId: item.product_id,
            name: item.name,
            qty: item.quantity,
            price: parseFloat(item.unit_price),
            serial: item.serial || 'N/A',
        });
    });

    updateTotalsPreview();
</script>
@endpush
@endsection
