@extends('layouts.app')

@section('title', 'Make a Sale')

@push('styles')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap');

    .sale-shell {
        display: grid;
        gap: 14px;
        font-family: 'Plus Jakarta Sans', 'Manrope', 'Segoe UI', sans-serif;
        line-height: 1.4;
    }

    .sale-layout {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 380px;
        gap: 18px;
        align-items: start;
    }

    .sale-card {
        background: #f7f9fc;
        border: 1px solid #d7deea;
        border-radius: 22px;
        padding: 22px;
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.7);
    }

    .sale-summary-btn {
        background: linear-gradient(135deg, #139b98 0%, #0f827f 100%);
        box-shadow: 0 10px 22px rgba(16, 136, 133, 0.28);
    }

    .sale-card h2,
    .sale-card h3 {
        margin: 0;
        font-size: 28px;
        font-weight: 800;
        letter-spacing: -0.02em;
        line-height: 1.15;
    }

    .quick-register h2,
    .checkout h3 {
        font-size: 28px;
    }

    .sale-lead {
        margin: 8px 0 0;
        color: #607086;
        font-size: 15px;
        font-weight: 500;
        line-height: 1.5;
    }

    .sale-lead a {
        color: #148b88;
        text-decoration: none;
        font-weight: 800;
    }

    .sale-alert {
        padding: 11px 13px;
        border-radius: 12px;
        border: 1px solid;
        font-weight: 700;
        font-size: 14px;
        line-height: 1.4;
    }

    .sale-alert.error {
        border-color: rgba(220,53,69,0.35);
        background: rgba(220,53,69,0.08);
        color: #b42318;
    }

    .sale-alert.success {
        border-color: rgba(16,185,129,0.35);
        background: rgba(16,185,129,0.1);
        color: #057046;
    }

    .category-pills {
        margin-top: 16px;
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .category-pill {
        border: 1px solid #cfd6e2;
        background: #fff;
        color: #6a778b;
        border-radius: 999px;
        padding: 9px 16px;
        font-size: 14px;
        font-weight: 700;
        letter-spacing: -0.01em;
        cursor: pointer;
        transition: all 0.18s ease;
    }

    .category-pill:hover {
        border-color: #19a2a0;
        color: #166864;
    }

    .category-pill.is-active {
        background: #149a97;
        border-color: #149a97;
        color: #fff;
        box-shadow: 0 9px 18px rgba(20,154,151,0.25);
    }

    .field-grid {
        margin-top: 14px;
        display: grid;
        gap: 12px;
    }

    .field-grid.two-col {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .field {
        display: flex;
        flex-direction: column;
        gap: 7px;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: #5e6d82;
    }

    .field .control {
        height: 58px;
        border: 2px solid #d2d9e4;
        border-radius: 14px;
        background: #fff;
        color: #122033;
        font-size: 17px;
        font-weight: 600;
        letter-spacing: 0;
        text-transform: none;
        font-family: 'Plus Jakarta Sans', 'Manrope', 'Segoe UI', sans-serif;
        padding: 0 14px;
        line-height: 1.2;
    }

    .field .control::placeholder {
        color: #a8b1bf;
    }

    #quantity.control {
        text-align: center;
        font-weight: 700;
    }

    .add-btn {
        width: 100%;
        margin-top: 14px;
        display: inline-flex;
        justify-content: center;
        align-items: center;
        height: 60px;
        border-radius: 14px;
        border: 2px solid #0e2a39;
        background: linear-gradient(180deg, #169895 0%, #0f8481 100%);
        color: #fff;
        font-size: 20px;
        font-weight: 800;
        letter-spacing: -0.02em;
        cursor: pointer;
        transition: transform 0.18s ease, box-shadow 0.18s ease;
    }

    .add-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 10px 22px rgba(15,132,129,0.24);
    }

    .stock-info {
        margin-top: 10px;
        color: #5d6d83;
        font-size: 14px;
        font-weight: 500;
    }

    .checkout-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 12px;
    }

    .cart-count {
        border-radius: 999px;
        background: #149a97;
        color: #fff;
        padding: 6px 12px;
        font-size: 13px;
        font-weight: 700;
    }

    .cart-table-wrap {
        border: 1px solid #d8dfeb;
        border-radius: 12px;
        overflow: auto;
        background: #fff;
    }

    .cart-table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
        font-size: 13px;
    }

    .cart-table th {
        text-align: left;
        padding: 9px 10px;
        background: #edf2f8;
        color: #64748b;
        font-size: 11px;
        letter-spacing: 0.07em;
        text-transform: uppercase;
        font-weight: 700;
        line-height: 1.15;
    }

    .cart-table td {
        padding: 9px 10px;
        border-top: 1px solid #edf2f8;
        font-size: 13px;
        line-height: 1.25;
    }

    .cart-table td.align-right,
    .cart-table th.align-right {
        text-align: right;
    }

    .cart-table th:nth-child(1),
    .cart-table td:nth-child(1) {
        width: 42%;
    }

    .cart-table th:nth-child(2),
    .cart-table td:nth-child(2) {
        width: 11%;
        white-space: nowrap;
    }

    .cart-table th:nth-child(3),
    .cart-table td:nth-child(3) {
        width: 16%;
        white-space: nowrap;
    }

    .cart-table th:nth-child(4),
    .cart-table td:nth-child(4) {
        width: 16%;
        white-space: nowrap;
    }

    .cart-table th.action-col,
    .cart-table td.action-col {
        width: 15%;
        text-align: center;
        white-space: nowrap;
    }

    .line-item-name {
        font-weight: 700;
        color: #162537;
        font-size: 13px;
    }

    .line-item-meta {
        margin-top: 2px;
        color: #6f7f95;
        font-size: 11px;
        font-weight: 500;
    }

    .remove-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 78px;
        height: 30px;
        border: 1px solid #f1c0c8;
        background: #fff3f5;
        color: #b42318;
        border-radius: 9px;
        font-size: 11px;
        font-weight: 700;
        padding: 0 10px;
        cursor: pointer;
    }

    .checkout-totals {
        margin-top: 14px;
        border-top: 1px solid #dfe5ef;
        border-bottom: 1px solid #dfe5ef;
        padding: 12px 0;
        display: grid;
        gap: 12px;
    }

    .total-line {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        color: #2a384d;
        font-weight: 600;
        font-size: 15px;
    }

    .total-line strong {
        font-size: 20px;
        letter-spacing: 0.01em;
        font-weight: 700;
    }

    .vat-line {
        color: #3d4f67;
        font-weight: 700;
    }

    .vat-toggle {
        display: inline-flex;
        align-items: center;
        gap: 10px;
    }

    .switch {
        position: relative;
        display: inline-block;
        width: 50px;
        height: 30px;
    }

    .switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .switch-slider {
        position: absolute;
        inset: 0;
        border-radius: 999px;
        background: #c7d0dd;
        transition: 0.2s ease;
    }

    .switch-slider::before {
        content: '';
        position: absolute;
        width: 22px;
        height: 22px;
        left: 4px;
        top: 4px;
        border-radius: 50%;
        background: #fff;
        transition: 0.2s ease;
        box-shadow: 0 2px 6px rgba(15, 23, 42, 0.25);
    }

    .switch input:checked + .switch-slider {
        background: #119997;
    }

    .switch input:checked + .switch-slider::before {
        transform: translateX(20px);
    }

    .total-divider {
        height: 1px;
        background: #d8dfeb;
    }

    .total-line.total {
        color: #17263b;
        font-size: 29px;
        font-weight: 800;
    }

    .total-line.total strong {
        color: #138f8c;
        font-size: 42px;
        font-weight: 800;
        letter-spacing: -0.01em;
    }

    .checkout-label {
        margin-top: 14px;
        color: #5d6d82;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.09em;
        text-transform: uppercase;
    }

    .payment-grid {
        margin-top: 8px;
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 10px;
    }

    .payment-chip {
        position: relative;
    }

    .payment-chip input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .payment-chip span {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        min-height: 52px;
        border-radius: 14px;
        border: 2px solid #cfd6e2;
        background: #fff;
        color: #57677e;
        font-weight: 700;
        font-size: 15px;
        letter-spacing: -0.01em;
        cursor: pointer;
        transition: all 0.18s ease;
    }

    .payment-chip input:checked + span {
        border-color: #159997;
        color: #137470;
        background: #edf9f8;
        box-shadow: 0 6px 16px rgba(20,154,151,0.18);
    }

    .complete-btn {
        width: 100%;
        margin-top: 16px;
        height: 56px;
        border: none;
        border-radius: 14px;
        background: linear-gradient(135deg, #139b98 0%, #0f827f 100%);
        color: #fff;
        font-size: 19px;
        font-weight: 800;
        letter-spacing: -0.01em;
        cursor: pointer;
        box-shadow: 0 11px 22px rgba(16, 136, 133, 0.26);
        transition: transform 0.18s ease;
    }

    .complete-btn:hover {
        transform: translateY(-1px);
    }

    @media (max-width: 1180px) {
        .sale-layout {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 760px) {
        .field-grid.two-col {
            grid-template-columns: 1fr;
        }

        .payment-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .sale-card {
            padding: 14px;
        }

        .sale-card h2,
        .sale-card h3 {
            font-size: 24px;
        }

        .total-line.total {
            font-size: 24px;
        }

        .total-line.total strong {
            font-size: 34px;
        }

        .add-btn {
            font-size: 20px;
        }
    }
</style>
@endpush

@section('header')
    <div class="header-row">
        <h1>Make a Sale</h1>
        <a class="btn sale-summary-btn" href="{{ route('summary') }}">View Sales Summary</a>
    </div>
@endsection

@section('content')
    @php($oldMethod = old('method', 'mobile'))

    <div class="sale-shell">
        @if ($errors->any())
            <div class="sale-alert error">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        @if (session('status'))
            <div class="sale-alert success">{{ session('status') }}</div>
        @endif

        <form id="sale_form" method="POST" action="{{ route('sale.store') }}" class="sale-layout">
            @csrf

            <section class="sale-card quick-register">
                <h2>Quick Register</h2>
                <p class="sale-lead">
                    Select a product, enter quantity, and add to cart. Need to correct stock?
                    <a href="{{ route('stock') }}">Open stock list</a>
                </p>

                <div class="category-pills" id="category_pills">
                    <button type="button" class="category-pill is-active" data-category="all">All Products</button>
                    @foreach(($categories ?? collect()) as $category)
                        <button type="button" class="category-pill" data-category="{{ $category->id }}">{{ $category->name }}</button>
                    @endforeach
                </div>

                <div class="field-grid two-col">
                    <label class="field">
                        Search Product
                        <input id="search_product" class="control" type="text" placeholder="Search product name...">
                    </label>
                    <label class="field">
                        Scan Barcode
                        <input id="barcode_search" class="control" type="text" placeholder="Scan barcode...">
                    </label>
                </div>

                <label class="field" style="margin-top:12px;">
                    Select Product
                    <select id="product_id" class="control">
                        <option value="">-- Select product --</option>
                        @foreach ($products as $product)
                            <option
                                value="{{ $product->id }}"
                                data-name="{{ $product->name }}"
                                data-price="{{ $product->price }}"
                                data-stock="{{ (int) ($product->stock_on_hand ?? 0) }}"
                                data-barcode="{{ $product->barcode }}"
                                data-serial="{{ $product->serial_number }}"
                                data-category-id="{{ $product->category_id ?? '' }}"
                            >
                                {{ $product->name }} ({{ $product->sku }}) - {{ (int) ($product->stock_on_hand ?? 0) }} on hand
                            </option>
                        @endforeach
                    </select>
                </label>

                <div class="field-grid two-col">
                    <label class="field">
                        Quantity
                        <input id="quantity" class="control" type="number" min="1" value="1">
                    </label>
                    <label class="field">
                        Unit Price (KES)
                        <input id="unit_price" class="control" type="number" step="0.01" min="0" placeholder="Auto-filled or enter price...">
                    </label>
                </div>

                <button type="button" id="add_to_cart" class="add-btn">+ Add to Cart</button>
                <div id="stock_info" class="stock-info">Select a product to see availability.</div>
            </section>

            <section class="sale-card checkout">
                <div class="checkout-head">
                    <h3>Checkout</h3>
                    <span id="cart_count" class="cart-count">0 items</span>
                </div>

                <div class="cart-table-wrap">
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th class="align-right">Qty</th>
                                <th class="align-right">Price</th>
                                <th class="align-right">Sub</th>
                                <th class="action-col">Action</th>
                            </tr>
                        </thead>
                        <tbody id="cart_body"></tbody>
                    </table>
                </div>

                <div class="checkout-totals">
                    <div class="total-line">
                        <span>Subtotal</span>
                        <strong id="subtotal_text">KES 0.00</strong>
                    </div>

                    <div class="total-line vat-line">
                        <span class="vat-toggle">
                            <input type="hidden" name="apply_tax" value="0">
                            <label class="switch" for="apply_tax">
                                <input id="apply_tax" name="apply_tax" type="checkbox" value="1" @checked(old('apply_tax') == '1')>
                                <span class="switch-slider"></span>
                            </label>
                            <span>Apply 16% VAT</span>
                        </span>
                        <strong id="tax_text">KES 0.00</strong>
                    </div>

                    <div class="total-divider"></div>

                    <div class="total-line total">
                        <span>Total</span>
                        <strong id="total_text">KES 0.00</strong>
                    </div>
                </div>

                <div class="checkout-label">Payment Method</div>
                <div class="payment-grid">
                    <label class="payment-chip">
                        <input type="radio" name="method" value="mobile" @checked($oldMethod === 'mobile')>
                        <span>Mobile</span>
                    </label>
                    <label class="payment-chip">
                        <input type="radio" name="method" value="cash" @checked($oldMethod === 'cash')>
                        <span>Cash</span>
                    </label>
                    <label class="payment-chip">
                        <input type="radio" name="method" value="card" @checked($oldMethod === 'card')>
                        <span>Card</span>
                    </label>
                    <label class="payment-chip">
                        <input type="radio" name="method" value="bank" @checked($oldMethod === 'bank')>
                        <span>Bank</span>
                    </label>
                    <label class="payment-chip">
                        <input type="radio" name="method" value="other" @checked($oldMethod === 'other')>
                        <span>Other</span>
                    </label>
                </div>

                <div class="field-grid" style="margin-top:14px;">
                    <label class="field">
                        Customer Name
                        <input name="customer_name" class="control" type="text" value="{{ old('customer_name') }}" placeholder="Walk-in">
                    </label>
                    <label class="field">
                        Phone
                        <input name="customer_phone" class="control" type="text" value="{{ old('customer_phone') }}" placeholder="+254...">
                    </label>
                    <label class="field">
                        Location
                        <input name="customer_location" class="control" type="text" value="{{ old('customer_location') }}" placeholder="City/Area">
                    </label>
                </div>

                <button class="complete-btn" type="submit">Complete Sale</button>
            </section>
        </form>
    </div>
@endsection

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
    const categoryButtons = Array.from(document.querySelectorAll('.category-pill'));
    const cartCount = document.getElementById('cart_count');

    let idx = 0;
    let activeCategory = 'all';

    function formatKes(amount) {
        return 'KES ' + amount.toFixed(2);
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function updateTotalsPreview(syncPrice) {
        const option = productSelect.options[productSelect.selectedIndex];
        const hasProduct = option && option.value;

        if (!hasProduct) {
            stockInfo.textContent = 'Select a product to see availability.';
            if (syncPrice) {
                unitInput.value = '';
            }
            return;
        }

        const price = parseFloat(option.dataset.price || 0);
        const stock = parseInt(option.dataset.stock || 0, 10);
        const serial = (option.dataset.serial || '').trim();

        if (syncPrice) {
            unitInput.value = Number.isFinite(price) && price > 0 ? price.toFixed(2) : '';
        }

        stockInfo.textContent = stock + ' in stock' + (serial ? ' | Serial: ' + serial : '');
    }

    function refreshCartTotals() {
        let subtotal = 0;
        const rows = Array.from(cartBody.querySelectorAll('tr'));

        rows.forEach((row) => {
            subtotal += parseFloat(row.dataset.subtotal || 0);
        });

        const tax = applyTaxInput.checked ? (subtotal * 0.16) : 0;
        const total = subtotal + tax;

        subtotalText.textContent = formatKes(subtotal);
        taxText.textContent = formatKes(tax);
        totalText.textContent = formatKes(total);
        cartCount.textContent = rows.length + (rows.length === 1 ? ' item' : ' items');
    }

    function quantityAlreadyInCart(productId) {
        return Array.from(cartBody.querySelectorAll('tr')).reduce((sum, row) => {
            if (row.dataset.productId === String(productId)) {
                return sum + parseInt(row.dataset.qty || 0, 10);
            }
            return sum;
        }, 0);
    }

    function addToCart() {
        const option = productSelect.options[productSelect.selectedIndex];
        const productId = option?.value;

        if (!productId) {
            alert('Select a product first.');
            return;
        }

        const name = option.dataset.name || option.textContent || 'Product';
        const defaultPrice = parseFloat(option.dataset.price || 0);
        const price = parseFloat(unitInput.value || defaultPrice || 0);
        const stock = parseInt(option.dataset.stock || 0, 10);
        const serial = option.dataset.serial || 'N/A';
        const qty = parseInt(qtyInput.value || 0, 10);
        const inCartQty = quantityAlreadyInCart(productId);

        if (!Number.isInteger(qty) || qty < 1) {
            alert('Enter a valid quantity.');
            return;
        }

        if (!Number.isFinite(price) || price < 0) {
            alert('Enter a valid unit price.');
            return;
        }

        if (stock >= 0 && (qty + inCartQty) > stock) {
            alert('Quantity exceeds stock on hand.');
            return;
        }

        const lineSubtotal = price * qty;
        const tr = document.createElement('tr');
        tr.dataset.subtotal = String(lineSubtotal);
        tr.dataset.productId = String(productId);
        tr.dataset.qty = String(qty);

        tr.innerHTML = `
            <td>
                <div class="line-item-name">${escapeHtml(name)}</div>
                <div class="line-item-meta">Serial: ${escapeHtml(serial)}</div>
            </td>
            <td class="align-right">${qty}</td>
            <td class="align-right">${price.toFixed(2)}</td>
            <td class="align-right">${lineSubtotal.toFixed(2)}</td>
            <td class="action-col">
                <button type="button" class="remove-btn">Remove</button>
                <input type="hidden" name="items[${idx}][product_id]" value="${productId}">
                <input type="hidden" name="items[${idx}][quantity]" value="${qty}">
                <input type="hidden" name="items[${idx}][unit_price]" value="${price.toFixed(2)}">
            </td>
        `;

        idx += 1;

        tr.querySelector('.remove-btn').addEventListener('click', () => {
            tr.remove();
            refreshCartTotals();
        });

        cartBody.appendChild(tr);
        refreshCartTotals();
    }

    function applyProductFilters(syncPrice) {
        const term = searchInput.value.trim().toLowerCase();
        const currentValue = productSelect.value;
        let firstVisibleValue = '';

        for (const option of productSelect.options) {
            if (!option.value) {
                continue;
            }

            const text = ((option.dataset.name || '') + ' ' + (option.textContent || '')).toLowerCase();
            const categoryId = option.dataset.categoryId || '';
            const stock = parseInt(option.dataset.stock || '0', 10);
            const matchesCategory = activeCategory === 'all' || categoryId === activeCategory;
            const matchesTerm = term === '' || text.includes(term);
            const visible = matchesCategory && matchesTerm && stock > 0;

            option.hidden = !visible;
            if (visible && !firstVisibleValue) {
                firstVisibleValue = option.value;
            }
        }

        const currentOption = Array.from(productSelect.options).find((option) => option.value === currentValue);
        if (currentOption && !currentOption.hidden) {
            productSelect.value = currentValue;
        } else {
            productSelect.value = firstVisibleValue;
        }

        updateTotalsPreview(syncPrice);
    }

    function setActiveCategory(categoryId) {
        activeCategory = categoryId;
        categoryButtons.forEach((button) => {
            button.classList.toggle('is-active', button.dataset.category === categoryId);
        });
        applyProductFilters(true);
    }

    categoryButtons.forEach((button) => {
        button.addEventListener('click', () => {
            setActiveCategory(button.dataset.category);
        });
    });

    searchInput.addEventListener('input', () => {
        applyProductFilters(false);
    });

    barcodeInput.addEventListener('input', (event) => {
        const code = event.target.value.trim();
        if (!code) {
            return;
        }

        const match = Array.from(productSelect.options).find((option) => option.value && option.dataset.barcode === code);
        if (!match) {
            return;
        }

        const categoryId = match.dataset.categoryId || 'all';
        const hasCategoryButton = categoryButtons.some((button) => button.dataset.category === categoryId);

        searchInput.value = '';
        setActiveCategory(hasCategoryButton ? categoryId : 'all');
        productSelect.value = match.value;
        updateTotalsPreview(true);
    });

    productSelect.addEventListener('change', () => updateTotalsPreview(true));
    qtyInput.addEventListener('input', () => updateTotalsPreview(false));
    applyTaxInput.addEventListener('change', refreshCartTotals);

    unitInput.addEventListener('blur', () => {
        const value = parseFloat(unitInput.value);
        if (Number.isFinite(value) && value >= 0) {
            unitInput.value = value.toFixed(2);
        }
    });

    addBtn.addEventListener('click', addToCart);

    form.addEventListener('submit', (event) => {
        if (cartBody.querySelectorAll('tr').length === 0) {
            event.preventDefault();
            alert('Add at least one item to the cart.');
        }
    });

    applyProductFilters(true);
    refreshCartTotals();
</script>
@endpush
