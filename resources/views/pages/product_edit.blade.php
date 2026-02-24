@extends('layouts.app')

@section('title', 'Edit Product')

@section('header')
    <div class="header-row">
        <h1>Edit Product</h1>
        <a class="btn" href="{{ route('products') }}">Back to Products</a>
    </div>
@endsection

@section('content')
    <div class="panel">
        <h2>Product details</h2>
        <p style="color: var(--muted); margin-top:6px;">Update product information and stock for this item.</p>

        @if ($errors->any())
            <div style="margin-top:10px; padding:10px 12px; border-radius:10px; border:1px solid rgba(239,68,68,0.3); background:rgba(239,68,68,0.08); color:#b91c1c;">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('products.update', $product) }}" style="margin-top:14px; display:grid; gap:14px;">
            @csrf
            @method('PUT')

            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:14px;">
                <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                    Name
                    <input name="name" type="text" value="{{ old('name', $product->name) }}" placeholder="Product name" required style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                </label>
                <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                    SKU
                    <input name="sku" type="text" value="{{ old('sku', $product->sku) }}" placeholder="SKU-001" required style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                </label>
                <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                    Serial number
                    <input name="serial_number" type="text" value="{{ old('serial_number', $product->serial_number) }}" placeholder="Unique serial" required style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                </label>
                <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                    Barcode
                    <input name="barcode" type="text" value="{{ old('barcode', $product->barcode) }}" placeholder="Scan or type" style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                </label>
                <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                    Category
                    <select name="category_id" required style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                        <option value="" disabled @selected(!old('category_id', $product->category_id))>Select category</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) old('category_id', $product->category_id) === (string) $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                    Supplier
                    <select name="supplier_id" style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                        <option value="">Select supplier</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" @selected((string) old('supplier_id', $product->supplier_id) === (string) $supplier->id)>{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                </label>
            </div>

            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:14px;">
                <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                    Cost (KES)
                    <input name="cost" type="number" step="0.01" min="0.01" value="{{ old('cost', $product->cost) }}" required style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                </label>
                <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                    Price (KES)
                    <input name="price" type="number" step="0.01" value="{{ old('price', $product->price) }}" required style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                </label>
                <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                    Stock on hand
                    <input name="stock" type="number" value="{{ old('stock', $stockQuantity) }}" min="0" style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                </label>
                <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                    Stock location
                    <input name="stock_location" type="text" value="{{ old('stock_location', $stockLocation) }}" style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                </label>
                <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                    Reorder at
                    <input name="stock_alert" type="number" value="{{ old('stock_alert', $product->stock_alert) }}" min="0" style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                </label>
            </div>

            <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                Description
                <textarea name="description" rows="4" placeholder="Optional notes" style="padding:12px;border:1px solid #e5e7eb;border-radius:10px; resize:vertical;">{{ old('description', $product->description) }}</textarea>
            </label>

            <label style="display:flex; align-items:center; gap:10px; font-weight:600;">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', (int) $product->is_active)) style="width:18px;height:18px;"> Active
            </label>

            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <button class="btn" type="submit">Save Changes</button>
                <a class="btn" style="background:#e5e7eb; color:#0f172a;" href="{{ route('products') }}">Cancel</a>
            </div>
        </form>
    </div>
@endsection
