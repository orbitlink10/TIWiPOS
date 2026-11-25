@extends('layouts.app')

@section('title', 'Point of Sale')

@push('styles')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap');

    .sale-shell {
        --sale-accent: #16a34a;
        --sale-accent-strong: #15803d;
        --sale-accent-soft: #ebf8ef;
        display: grid;
        gap: 10px;
        font-family: 'Plus Jakarta Sans', 'Manrope', 'Segoe UI', sans-serif;
        line-height: 1.35;
        font-size: 13px;
    }

    .sale-layout {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 360px;
        gap: 14px;
        align-items: start;
    }

    .sale-main {
        display: grid;
        gap: 12px;
    }

    .sale-card {
        background: #f7f9fc;
        border: 1px solid #d7deea;
        border-radius: 16px;
        padding: 16px;
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.7);
    }

    .sale-summary-btn {
        background: linear-gradient(135deg, var(--sale-accent) 0%, var(--sale-accent-strong) 100%);
        box-shadow: 0 10px 22px rgba(21, 128, 61, 0.26);
    }

    .sale-card h2,
    .sale-card h3 {
        margin: 0;
        font-size: 22px;
        font-weight: 800;
        letter-spacing: -0.02em;
        line-height: 1.15;
    }

    .quick-register h2,
    .checkout h3,
    .installation h2 {
        font-size: 22px;
    }

    .sale-lead {
        margin: 5px 0 0;
        color: #607086;
        font-size: 13px;
        font-weight: 500;
        line-height: 1.5;
    }

    .sale-lead a {
        color: var(--sale-accent-strong);
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
        margin-top: 12px;
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .category-pill {
        border: 1px solid #cfd6e2;
        background: #fff;
        color: #6a778b;
        border-radius: 999px;
        padding: 7px 12px;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: -0.01em;
        cursor: pointer;
        transition: all 0.18s ease;
    }

    .category-pill:hover {
        border-color: var(--sale-accent);
        color: #166534;
    }

    .category-pill.is-active {
        background: var(--sale-accent);
        border-color: var(--sale-accent);
        color: #fff;
        box-shadow: 0 9px 18px rgba(22,163,74,0.24);
    }

    .field-grid {
        margin-top: 10px;
        display: grid;
        gap: 10px;
    }

    .field-grid.two-col {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .field {
        display: flex;
        flex-direction: column;
        gap: 5px;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: #5e6d82;
    }

    .field .control {
        height: 44px;
        border: 2px solid #d2d9e4;
        border-radius: 10px;
        background: #fff;
        color: #122033;
        font-size: 14px;
        font-weight: 600;
        letter-spacing: 0;
        text-transform: none;
        font-family: 'Plus Jakarta Sans', 'Manrope', 'Segoe UI', sans-serif;
        padding: 0 11px;
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
        margin-top: 12px;
        display: inline-flex;
        justify-content: center;
        align-items: center;
        height: 48px;
        border-radius: 11px;
        border: 2px solid #14532d;
        background: linear-gradient(180deg, #22c55e 0%, #16a34a 100%);
        color: #fff;
        font-size: 17px;
        font-weight: 800;
        letter-spacing: -0.02em;
        cursor: pointer;
        transition: transform 0.18s ease, box-shadow 0.18s ease;
    }

    .add-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 10px 22px rgba(22,163,74,0.26);
    }

    .stock-info {
        margin-top: 8px;
        color: #5d6d83;
        font-size: 12px;
        font-weight: 500;
    }

    .checkout-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 8px;
    }

    .cart-count {
        border-radius: 999px;
        background: var(--sale-accent);
        color: #fff;
        padding: 5px 11px;
        font-size: 12px;
        font-weight: 700;
    }

    .cart-table-wrap {
        border: 1px solid #d8dfeb;
        border-radius: 12px;
        overflow: auto;
        background: #fff;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.7);
    }

    .cart-table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
        font-size: 12px;
    }

    .cart-table th {
        text-align: left;
        padding: 8px 9px;
        background: #edf2f8;
        color: #64748b;
        font-size: 10px;
        letter-spacing: 0.07em;
        text-transform: uppercase;
        font-weight: 700;
        line-height: 1.15;
    }

    .cart-table td {
        padding: 8px 9px;
        border-top: 1px solid #edf2f8;
        font-size: 12px;
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
        font-size: 12px;
    }

    .line-item-meta {
        margin-top: 2px;
        color: #6f7f95;
        font-size: 10px;
        font-weight: 500;
    }

    .line-edit {
        width: 100%;
        border: 1px solid #d2d9e4;
        border-radius: 8px;
        height: 30px;
        padding: 0 8px;
        font-size: 12px;
        text-align: right;
        background: #fff;
        color: #0f172a;
    }

    .remove-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 68px;
        height: 28px;
        border: 1px solid #f1c0c8;
        background: #fff3f5;
        color: #b42318;
        border-radius: 9px;
        font-size: 10px;
        font-weight: 700;
        padding: 0 9px;
        cursor: pointer;
    }

    .checkout-totals {
        margin-top: 12px;
        border-top: 1px solid #dfe5ef;
        border-bottom: 1px solid #dfe5ef;
        padding: 8px 0;
        display: grid;
        gap: 8px;
    }

    .total-line {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        color: #2a384d;
        font-weight: 600;
        font-size: 12px;
    }

    .total-line strong {
        font-size: 13px;
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
        background: var(--sale-accent);
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
        font-size: 17px;
        font-weight: 800;
    }

    .total-line.total strong {
        color: var(--sale-accent-strong);
        font-size: 22px;
        font-weight: 800;
        letter-spacing: 0;
        white-space: nowrap;
    }

    .checkout-label {
        margin-top: 12px;
        color: #5d6d82;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.09em;
        text-transform: uppercase;
    }

    .payment-grid {
        margin-top: 8px;
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 8px;
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
        min-height: 40px;
        border-radius: 10px;
        border: 2px solid #cfd6e2;
        background: #fff;
        color: #57677e;
        font-weight: 700;
        font-size: 12px;
        letter-spacing: -0.01em;
        cursor: pointer;
        transition: all 0.18s ease;
    }

    .payment-chip input:checked + span {
        border-color: var(--sale-accent);
        color: #166534;
        background: var(--sale-accent-soft);
        box-shadow: 0 6px 16px rgba(22,163,74,0.18);
    }

    .complete-btn {
        width: 100%;
        margin-top: 12px;
        height: 44px;
        border: none;
        border-radius: 12px;
        background: linear-gradient(135deg, var(--sale-accent) 0%, var(--sale-accent-strong) 100%);
        color: #fff;
        font-size: 14px;
        font-weight: 800;
        letter-spacing: -0.01em;
        cursor: pointer;
        box-shadow: 0 11px 22px rgba(21, 128, 61, 0.26);
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
            font-size: 22px;
        }

        .total-line.total {
            font-size: 15px;
        }

        .total-line.total strong {
            font-size: 24px;
        }

        .add-btn {
            font-size: 18px;
        }
    }
</style>
<style>@include('partials.pos-terminal-styles')</style>
@endpush

@section('header')
    <div class="header-row">
        <div><h1>Point of Sale</h1><p class="sale-lead">{{ $currentBranch->name ?? 'Your store' }} · {{ now()->format('D, d M Y') }}</p></div>
        <div class="sale-header-actions">
            <a class="sale-dashboard-btn" href="{{ route('shifts.show', $cashierShift->id) }}">Shift #{{ $cashierShift->id }}</a>
            @if(auth()->user()->isPosOnly())
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="sale-dashboard-btn" type="submit">Sign Out</button>
                </form>
            @else
            <a class="sale-dashboard-btn" href="{{ route('home') }}">&larr; Back to Dashboard</a>
            @endif
        </div>
    </div>
@endsection

@section('content')
    @php($oldMethod = old('method', 'mobile'))

    <div class="sale-shell">
        <div id="sale_feedback" class="sale-alert error" role="alert" hidden></div>
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

            <nav class="terminal-actions" aria-label="Sale actions">
                <button type="button" data-panel="customer_details"><span aria-hidden="true">CU</span>Customer</button>
                <button type="button" data-panel="installation_details"><span aria-hidden="true">SV</span>Installation</button>
                <button type="button" data-panel="manual_entry"><span aria-hidden="true">QT</span>Quantity / price</button>
                @unless(auth()->user()->isPosOnly())
                    <a href="{{ route('stock-control.index') }}"><span aria-hidden="true">ST</span>Stock Control</a>
                    <a href="{{ route('sales.index') }}"><span aria-hidden="true">SH</span>Sales history</a>
                @endunless
            </nav>

            <div class="sale-main">
            <section class="sale-card quick-register">
                <div class="catalogue-heading"><h2>Product catalogue</h2><span class="terminal-caption">{{ $products->count() }} products</span></div>
                <p class="sale-lead">
                    Tap a product row to add it, or scan a barcode and press Enter.
                    @unless(auth()->user()->isPosOnly())
                        <a href="{{ route('stock-control.index') }}">Open Stock Control</a>
                    @endunless
                </p>

                <div class="category-pills" id="category_pills">
                    <button type="button" class="category-pill is-active" data-category="all" aria-pressed="true">All Products</button>
                    @foreach(($categories ?? collect()) as $category)
                        <button type="button" class="category-pill" data-category="{{ $category->id }}" aria-pressed="false">{{ $category->name }}</button>
                    @endforeach
                </div>

                <div class="field-grid two-col">
                    <label class="field">
                        Search Product
                        <input id="search_product" class="control" type="search" placeholder="Search name, SKU or barcode..." autocomplete="off">
                    </label>
                    <label class="field">
                        Scan Barcode
                        <input id="barcode_search" class="control" type="text" placeholder="Scan and press Enter..." autocomplete="off">
                    </label>
                </div>

                <div class="product-grid" id="product_grid" aria-label="Available products" hidden>
                    <div class="product-list-head" aria-hidden="true"><span>Product</span><span>Stock</span><span>Price (KES)</span><span>Qty</span><span></span></div>
                    @foreach ($products as $product)
                        <div class="product-tile" data-product-id="{{ $product->id }}">
                            <button type="button" class="product-row-name" aria-label="Add {{ $product->name }} to cart"><strong>{{ $product->name }}</strong><span class="product-tile-code">{{ $product->sku ?: 'Product' }}</span></button>
                            <span class="product-tile-stock" aria-label="Available stock">{{ (int) ($product->stock_on_hand ?? 0) }}</span>
                            <span class="product-tile-price">{{ number_format((float) $product->price, 2) }}</span>
                            <input class="product-row-qty" form="catalogue_controls" type="number" min="1" step="1" max="{{ (int) ($product->stock_on_hand ?? 0) }}" value="1" aria-label="Quantity to add for {{ $product->name }}">
                            <button type="button" class="product-row-add" aria-label="Add {{ $product->name }} to cart">+ Add</button>
                        </div>
                    @endforeach
                </div>
                <p id="catalogue_empty" class="catalogue-empty" role="status">Start typing a product name, SKU or barcode to find products.</p>

                <details class="manual-entry" id="manual_entry"><summary>Enter quantity or adjust unit price</summary>
                <label class="field" style="margin-top:12px;">
                    Select Product
                    <select id="product_id" class="control">
                        <option value="">-- Select product --</option>
                        @foreach ($products as $product)
                            <option
                                value="{{ $product->id }}"
                                data-name="{{ $product->name }}"
                                data-sku="{{ $product->sku }}"
                                data-price="{{ number_format((float) $product->price, 2, '.', '') }}"
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
                </details>
            </section>

            <details id="installation_details" class="sale-card installation" @if(old('installation_amount') || old('technician_name')) open @endif>
                <summary>Installation / service details <span>Optional</span></summary>

                <div class="field-grid">
                    <label class="field">
                        Technician Name
                        <input name="technician_name" class="control" type="text" value="{{ old('technician_name') }}" placeholder="Technician name">
                    </label>
                    <label class="field">
                        Phone Number
                        <input name="technician_phone" class="control" type="text" value="{{ old('technician_phone') }}" placeholder="+254...">
                    </label>
                    <label class="field">
                        Location for the installation
                        <input name="installation_location" class="control" type="text" value="{{ old('installation_location') }}" placeholder="City/Area">
                    </label>
                    <label class="field">
                        Installation Amount
                        <input id="installation_amount" name="installation_amount" class="control" type="number" step="0.01" min="0" value="{{ old('installation_amount') }}" placeholder="KES 0.00">
                    </label>
                </div>

                <button id="add_installation" class="complete-btn" type="button">Add Installation</button>
            </details>
            </div>

            <section class="sale-card checkout">
                <div class="checkout-head">
                    <h3>Current sale</h3>
                    <span id="cart_count" class="cart-count">0 items</span>
                    <button type="button" id="clear_cart" class="clear-cart" disabled>Clear</button>
                </div>

                <p id="cart_empty" class="catalogue-empty">Your cart is empty. Add a product to start a sale.</p>

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
                                <input id="apply_tax" name="apply_tax" type="checkbox" value="1" aria-label="Apply 16% VAT" @checked(old('apply_tax') == '1')>
                                <span class="switch-slider"></span>
                            </label>
                            <span>Apply 16% VAT</span>
                        </span>
                        <strong id="tax_text">KES 0.00</strong>
                    </div>

                    <div class="total-line">
                        <span>Installation</span>
                        <strong id="installation_text">KES 0.00</strong>
                    </div>

                    <div class="total-divider"></div>

                    <div class="total-line total">
                        <span>Total</span>
                        <strong id="total_text">KES 0.00</strong>
                    </div>
                </div>

                <details id="customer_details" class="customer-details" @if(old('customer_name') || old('customer_phone') || old('customer_location')) open @endif>
                    <summary>Customer details <span>Walk-in / optional</span></summary>
                    <div class="field-grid">
                        <label class="field">Customer Name<input name="customer_name" class="control" type="text" value="{{ old('customer_name') }}" placeholder="Walk-in"></label>
                        <label class="field">Customer Phone<input name="customer_phone" class="control" type="text" value="{{ old('customer_phone') }}" placeholder="+254..."></label>
                        <label class="field">Customer Location<input name="customer_location" class="control" type="text" value="{{ old('customer_location') }}" placeholder="City/Area"></label>
                    </div>
                </details>

                <button class="complete-btn" id="open_payment" type="button" disabled>Proceed to payment <span id="pay_total">KES 0.00</span></button>
                <p class="terminal-hint">Review quantities and prices before taking payment.</p>

                <dialog id="payment_dialog" class="payment-dialog" aria-labelledby="payment_title">
                <div class="payment-dialog-head"><div><p class="terminal-caption">CHECKOUT</p><h3 id="payment_title">Complete payment</h3></div><button type="button" id="close_payment" class="payment-close" aria-label="Close payment">&times;</button></div>
                <div class="payment-amount"><span>Amount due</span><strong id="payment_total">KES 0.00</strong></div>
                <p id="payment_summary" class="sale-lead"></p>
                <div data-single-payment>
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

                </div>
                @include('partials.split-payments')
                <button class="complete-btn" type="submit">Complete Sale</button>
                </dialog>
            </section>
        </form>
        <form id="catalogue_controls" hidden></form>
    </div>
@endsection

@push('scripts')
<script>
    const productSelect = document.getElementById('product_id');
    const qtyInput = document.getElementById('quantity');
    const unitInput = document.getElementById('unit_price');
    const subtotalText = document.getElementById('subtotal_text');
    const taxText = document.getElementById('tax_text');
    const installationText = document.getElementById('installation_text');
    const totalText = document.getElementById('total_text');
    const applyTaxInput = document.getElementById('apply_tax');
    const installationInput = document.getElementById('installation_amount');
    const stockInfo = document.getElementById('stock_info');
    const cartBody = document.getElementById('cart_body');
    const addBtn = document.getElementById('add_to_cart');
    const addInstallationBtn = document.getElementById('add_installation');
    const form = document.getElementById('sale_form');
    const searchInput = document.getElementById('search_product');
    const barcodeInput = document.getElementById('barcode_search');
    const categoryButtons = Array.from(document.querySelectorAll('.category-pill'));
    const cartCount = document.getElementById('cart_count');
    const productTiles = Array.from(document.querySelectorAll('.product-tile'));
    const feedback = document.getElementById('sale_feedback');
    const clearCart = document.getElementById('clear_cart');
    const completeButton = form.querySelector('button[type="submit"]');
    const openPayment = document.getElementById('open_payment');
    const paymentDialog = document.getElementById('payment_dialog');
    const previousItems = @json(old('items', []));
    let submitting = false;

    function showFeedback(message) {
        feedback.textContent = message;
        feedback.hidden = !message;
    }

    let idx = 0;
    let activeCategory = 'all';

    function formatKes(amount) {
        return 'KES ' + amount.toFixed(2);
    }

    function parseMoney(value) {
        const normalized = String(value ?? '').replace(/[^0-9.-]/g, '');
        const parsed = Number.parseFloat(normalized);
        return Number.isFinite(parsed) ? parsed : 0;
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

        const price = parseMoney(option.dataset.price);
        const stock = parseInt(option.dataset.stock || 0, 10);
        const serial = (option.dataset.serial || '').trim();

        if (syncPrice) {
            unitInput.value = Number.isFinite(price) ? price.toFixed(2) : '';
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
        const installationAmount = Math.max(0, parseMoney(installationInput?.value || 0));
        const total = subtotal + tax + installationAmount;

        subtotalText.textContent = formatKes(subtotal);
        taxText.textContent = formatKes(tax);
        installationText.textContent = formatKes(installationAmount);
        totalText.textContent = formatKes(total);
        window.updateSplitPaymentTotal(total);
        document.getElementById('pay_total').textContent = formatKes(total);
        document.getElementById('payment_total').textContent = formatKes(total);
        const quantity = rows.reduce((sum, row) => sum + Number(row.dataset.qty), 0);
        cartCount.textContent = quantity + (quantity === 1 ? ' item' : ' items');
        document.getElementById('cart_empty').hidden = rows.length > 0;
        clearCart.disabled = rows.length === 0;
        completeButton.disabled = rows.length === 0 || submitting;
        openPayment.disabled = rows.length === 0 || submitting;
        document.getElementById('payment_summary').textContent = cartCount.textContent + ' · ' + (form.elements.customer_name.value.trim() || 'Walk-in customer');
        productTiles.forEach((tile) => {
            const option = Array.from(productSelect.options).find((item) => item.value === tile.dataset.productId);
            const available = Math.max(0, Number(option.dataset.stock) - quantityAlreadyInCart(option.value));
            tile.querySelector('.product-tile-stock').textContent = available;
            tile.querySelector('.product-row-add').disabled = available === 0;
            tile.querySelector('.product-row-name').disabled = available === 0;
            tile.querySelector('.product-row-qty').disabled = available === 0;
            tile.querySelector('.product-row-qty').max = available;
            tile.classList.toggle('in-cart', available < Number(option.dataset.stock));
            tile.classList.toggle('is-out', available === 0);
        });
    }

    function quantityAlreadyInCart(productId, excludeRow = null) {
        return Array.from(cartBody.querySelectorAll('tr')).reduce((sum, row) => {
            if (excludeRow && row === excludeRow) {
                return sum;
            }
            if (row.dataset.productId === String(productId)) {
                return sum + parseInt(row.dataset.qty || 0, 10);
            }
            return sum;
        }, 0);
    }

    function updateCartRow(tr) {
        const qtyInput = tr.querySelector('.line-qty-input');
        const priceInput = tr.querySelector('.line-price-input');
        const subtotalCell = tr.querySelector('.line-subtotal');
        const quantityHidden = tr.querySelector('.line-qty-hidden');
        const unitPriceHidden = tr.querySelector('.line-price-hidden');

        const currentQty = parseInt(tr.dataset.qty || '1', 10);
        const currentPrice = parseMoney(unitPriceHidden?.value || tr.dataset.unitPrice || 0);
        let nextQty = parseInt(qtyInput?.value || currentQty, 10);
        let nextPrice = parseMoney(priceInput?.value || currentPrice);

        if (!Number.isInteger(nextQty) || nextQty < 1) {
            nextQty = currentQty;
        }
        if (!Number.isFinite(nextPrice) || nextPrice <= 0) {
            showFeedback('Enter a unit price greater than zero.');
            nextPrice = currentPrice;
        }

        const rowStock = parseInt(tr.dataset.stock || '0', 10);
        const inCartQtyWithoutThisRow = quantityAlreadyInCart(tr.dataset.productId, tr);
        if (rowStock >= 0 && (nextQty + inCartQtyWithoutThisRow) > rowStock) {
            showFeedback('Quantity exceeds stock on hand.');
            nextQty = currentQty;
        }

        const lineSubtotal = nextQty * nextPrice;

        tr.dataset.qty = String(nextQty);
        tr.dataset.subtotal = String(lineSubtotal);
        tr.dataset.unitPrice = String(nextPrice.toFixed(2));

        if (qtyInput) {
            qtyInput.value = String(nextQty);
        }
        if (priceInput) {
            priceInput.value = nextPrice.toFixed(2);
        }
        if (quantityHidden) {
            quantityHidden.value = String(nextQty);
        }
        if (unitPriceHidden) {
            unitPriceHidden.value = nextPrice.toFixed(2);
        }
        if (subtotalCell) {
            subtotalCell.textContent = lineSubtotal.toFixed(2);
        }

        refreshCartTotals();
    }

    function addToCart() {
        const option = productSelect.options[productSelect.selectedIndex];
        const productId = option?.value;

        if (!productId) {
            showFeedback('Select a product first.');
            return false;
        }

        const name = option.dataset.name || option.textContent || 'Product';
        const defaultPrice = parseMoney(option.dataset.price);
        const hasManualPrice = unitInput.value !== null && unitInput.value.trim() !== '';
        const price = hasManualPrice ? parseMoney(unitInput.value) : defaultPrice;
        const stock = parseInt(option.dataset.stock || 0, 10);
        const serial = option.dataset.serial || 'N/A';
        const qty = parseInt(qtyInput.value || 0, 10);
        const inCartQty = quantityAlreadyInCart(productId);

        if (!Number.isInteger(qty) || qty < 1) {
            showFeedback('Enter a valid quantity.');
            return false;
        }

        if (!Number.isFinite(price) || price <= 0) {
            showFeedback('Enter a valid unit price.');
            return false;
        }

        if (stock >= 0 && (qty + inCartQty) > stock) {
            showFeedback('Not enough stock for ' + name + '. ' + Math.max(0, stock - inCartQty) + ' available.');
            return false;
        }

        showFeedback('');
        const existingRow = Array.from(cartBody.querySelectorAll('tr')).find((row) =>
            row.dataset.productId === String(productId) && Number(row.dataset.unitPrice) === price);
        if (existingRow) {
            existingRow.querySelector('.line-qty-input').value = Number(existingRow.dataset.qty) + qty;
            updateCartRow(existingRow);
            return true;
        }

        const lineSubtotal = price * qty;
        const tr = document.createElement('tr');
        tr.dataset.subtotal = String(lineSubtotal);
        tr.dataset.productId = String(productId);
        tr.dataset.qty = String(qty);
        tr.dataset.stock = String(stock);
        tr.dataset.unitPrice = String(price.toFixed(2));

        tr.innerHTML = `
            <td>
                <div class="line-item-name">${escapeHtml(name)}</div>
                <div class="line-item-meta">Serial: ${escapeHtml(serial)}</div>
            </td>
            <td class="align-right">
                <input type="number" min="1" aria-label="Quantity for ${escapeHtml(name)}" class="line-edit line-qty-input" value="${qty}">
            </td>
            <td class="align-right">
                <input type="number" min="0.01" step="0.01" aria-label="Unit price for ${escapeHtml(name)}" class="line-edit line-price-input" value="${price.toFixed(2)}">
            </td>
            <td class="align-right line-subtotal">${lineSubtotal.toFixed(2)}</td>
            <td class="action-col">
                <button type="button" class="remove-btn">Remove</button>
                <input type="hidden" name="items[${idx}][product_id]" value="${productId}">
                <input type="hidden" name="items[${idx}][quantity]" value="${qty}" class="line-qty-hidden">
                <input type="hidden" name="items[${idx}][unit_price]" value="${price.toFixed(2)}" class="line-price-hidden">
            </td>
        `;

        idx += 1;

        tr.querySelector('.remove-btn').addEventListener('click', () => {
            tr.remove();
            refreshCartTotals();
        });
        tr.querySelector('.line-qty-input').addEventListener('change', () => updateCartRow(tr));
        tr.querySelector('.line-price-input').addEventListener('change', () => updateCartRow(tr));
        tr.querySelector('.line-price-input').addEventListener('blur', () => updateCartRow(tr));

        cartBody.appendChild(tr);
        refreshCartTotals();
        return true;
    }

    function applyProductFilters(syncPrice) {
        const term = searchInput.value.trim().toLowerCase();
        const currentValue = productSelect.value;
        let firstVisibleValue = '';

        for (const option of productSelect.options) {
            if (!option.value) {
                continue;
            }

            const text = [option.dataset.name, option.dataset.sku, option.dataset.barcode, option.dataset.serial].join(' ').toLowerCase();
            const categoryId = option.dataset.categoryId || '';
            const stock = parseInt(option.dataset.stock || '0', 10);
            const price = parseMoney(option.dataset.price);
            const matchesCategory = activeCategory === 'all' || categoryId === activeCategory;
            const matchesTerm = term === '' || text.includes(term);
            const visible = matchesCategory && matchesTerm && stock > 0 && price > 0;

            option.hidden = !visible;
            const tile = productTiles.find((item) => item.dataset.productId === option.value);
            if (tile) tile.hidden = !visible;
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
        const hasResults = term !== '' && Boolean(firstVisibleValue);
        document.getElementById('product_grid').hidden = !hasResults;
        const catalogueEmpty = document.getElementById('catalogue_empty');
        catalogueEmpty.hidden = hasResults;
        catalogueEmpty.textContent = term === ''
            ? 'Start typing a product name, SKU or barcode to find products.'
            : 'No products found. Check your search or add stock to this branch.';
    }

    function setActiveCategory(categoryId) {
        activeCategory = categoryId;
        categoryButtons.forEach((button) => {
            button.classList.toggle('is-active', button.dataset.category === categoryId);
            button.setAttribute('aria-pressed', String(button.dataset.category === categoryId));
        });
        applyProductFilters(true);
    }

    categoryButtons.forEach((button) => {
        button.addEventListener('click', () => {
            setActiveCategory(button.dataset.category);
        });
    });

    searchInput.addEventListener('input', () => {
        applyProductFilters(true);
    });

    barcodeInput.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter') return;
        event.preventDefault();
        const code = event.target.value.trim();
        if (!code) {
            return;
        }

        const match = Array.from(productSelect.options).find((option) => option.value && option.dataset.barcode === code);
        if (!match) {
            showFeedback('No product found for barcode ' + code + '.');
            barcodeInput.select();
            return;
        }

        const categoryId = match.dataset.categoryId || 'all';
        const hasCategoryButton = categoryButtons.some((button) => button.dataset.category === categoryId);

        searchInput.value = '';
        setActiveCategory(hasCategoryButton ? categoryId : 'all');
        productSelect.value = match.value;
        updateTotalsPreview(true);
        qtyInput.value = '1';
        if (addAndResetSearch()) barcodeInput.value = '';
    });

    productSelect.addEventListener('change', () => updateTotalsPreview(true));
    qtyInput.addEventListener('input', () => updateTotalsPreview(false));
    applyTaxInput.addEventListener('change', refreshCartTotals);
    installationInput.addEventListener('input', refreshCartTotals);

    unitInput.addEventListener('blur', () => {
        const value = parseFloat(unitInput.value);
        if (Number.isFinite(value) && value >= 0) {
            unitInput.value = value.toFixed(2);
        }
    });

    addBtn.addEventListener('click', addAndResetSearch);
    addInstallationBtn.addEventListener('click', refreshCartTotals);

    function addAndResetSearch() {
        if (!addToCart()) return false;
        searchInput.value = '';
        qtyInput.value = '1';
        setActiveCategory('all');
        searchInput.focus();
        return true;
    }

    function addProductRow(tile) {
        const rowQuantity = tile.querySelector('.product-row-qty');
        if (!rowQuantity.reportValidity()) return;
        productSelect.value = tile.dataset.productId;
        qtyInput.value = rowQuantity.value;
        updateTotalsPreview(true);
        if (addAndResetSearch()) {
            rowQuantity.value = '1';
        }
    }

    productTiles.forEach((tile) => {
        tile.addEventListener('click', (event) => {
            if (event.target.closest('input, button') || tile.querySelector('.product-row-add').disabled) return;
            addProductRow(tile);
        });
        tile.querySelector('.product-row-name').addEventListener('click', () => addProductRow(tile));
        tile.querySelector('.product-row-add').addEventListener('click', () => addProductRow(tile));
        tile.querySelector('.product-row-qty').addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                addProductRow(tile);
            }
        });
    });

    document.querySelectorAll('[data-panel]').forEach((button) => button.addEventListener('click', () => {
        const panel = document.getElementById(button.dataset.panel);
        panel.open = true;
        panel.scrollIntoView({ behavior: 'smooth', block: 'center' });
        panel.querySelector('input, select')?.focus({ preventScroll: true });
    }));

    openPayment.addEventListener('click', () => {
        refreshCartTotals();
        if (!openPayment.disabled) paymentDialog.showModal();
    });
    document.getElementById('close_payment').addEventListener('click', () => paymentDialog.close());

    clearCart.addEventListener('click', () => {
        if (!window.confirm('Remove all items from this cart?')) return;
        cartBody.replaceChildren();
        showFeedback('');
        refreshCartTotals();
    });

    form.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && event.target.tagName === 'INPUT') {
            event.preventDefault();
            if ([qtyInput, unitInput].includes(event.target)) addAndResetSearch();
        }
    });

    form.addEventListener('submit', (event) => {
        if (submitting || cartBody.querySelectorAll('tr').length === 0) {
            event.preventDefault();
            if (!submitting) showFeedback('Add at least one item to the cart.');
            return;
        }
        submitting = true;
        completeButton.disabled = true;
        completeButton.textContent = 'Completing sale…';
    });

    window.addEventListener('pageshow', () => {
        submitting = false;
        if (paymentDialog.open) paymentDialog.close();
        completeButton.textContent = 'Complete Sale';
        refreshCartTotals();
    });

    Object.values(previousItems).forEach((item) => {
        productSelect.value = String(item.product_id);
        if (!productSelect.value) {
            showFeedback('An item from your previous cart is no longer available. Check stock before completing the sale.');
            return;
        }
        updateTotalsPreview(true);
        qtyInput.value = item.quantity;
        if (item.unit_price !== null && item.unit_price !== undefined) unitInput.value = item.unit_price;
        addToCart();
    });
    qtyInput.value = '1';

    applyProductFilters(true);
    refreshCartTotals();
</script>
@endpush
