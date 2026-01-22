@extends('layouts.app')

@section('title', 'Add Product')

@section('header')
    <div class="header-row">
        <h1>Add Product</h1>
        <a class="btn" href="{{ route('products') }}">⬅ Back to Products</a>
    </div>
@endsection

@section('content')
    <div class="panel">
        <h2>Product details</h2>
        <p style="color: var(--muted); margin-top:6px;">Enter item information to add it to your catalog.</p>

        @if (session('status'))
            <div style="margin-top:10px; padding:10px 12px; border-radius:10px; border:1px solid rgba(16,185,129,0.3); background:rgba(16,185,129,0.1); color:#065f46;">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div style="margin-top:10px; padding:10px 12px; border-radius:10px; border:1px solid rgba(239,68,68,0.3); background:rgba(239,68,68,0.08); color:#b91c1c;">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('products.store') }}" style="margin-top:14px; display:grid; gap:14px;">
            @csrf
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:14px;">
                <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                    Name
                    <input name="name" type="text" list="product-name-suggestions" placeholder="Product name" required style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                    <datalist id="product-name-suggestions">
                        @foreach($productNames as $pname)
                            <option value="{{ $pname }}"></option>
                        @endforeach
                    </datalist>
                </label>
                <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                    SKU
                    <input name="sku" type="text" placeholder="SKU-001" required style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                </label>
                <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                    Serial number
                    <input name="serial_number" type="text" placeholder="Unique serial" required style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                </label>
                <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                    Barcode
                    <input name="barcode" type="text" placeholder="Scan or type" style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                </label>
                <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                    Category
                    <select name="category_id" style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                        <option value="">Select category</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                    Supplier
                    <select name="supplier_id" style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                        <option value="">Select supplier</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                </label>
            </div>

            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:14px;">
                <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                    Cost (KES)
                    <input name="cost" type="number" step="0.01" value="0" style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                </label>
                <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                    Price (KES)
                    <input name="price" type="number" step="0.01" value="0" style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                </label>
                <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                    Stock on hand
                    <input name="stock" type="number" value="0" style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                </label>
                <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                    Stock location
                    <input name="stock_location" type="text" value="main" style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                </label>
                <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                    Reorder at
                    <input name="stock_alert" type="number" value="0" style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                </label>
            </div>

            <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                Description
                <textarea name="description" rows="4" placeholder="Optional notes" style="padding:12px;border:1px solid #e5e7eb;border-radius:10px; resize:vertical;"></textarea>
            </label>

            <label style="display:flex; align-items:center; gap:10px; font-weight:600;">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" checked style="width:18px;height:18px;"> Active
            </label>

            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <button class="btn" type="submit">💾 Save Product</button>
                <a class="btn" style="background:#e5e7eb; color:#0f172a;" href="{{ route('products') }}">Cancel</a>
            </div>
        </form>
    </div>
@endsection
