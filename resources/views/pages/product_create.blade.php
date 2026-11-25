@extends('layouts.app')
@section('title', 'Add Product')

@push('styles')
@include('partials.product-editor-styles')
@endpush

@section('header')
<div class="header-row"><h1>Add Product</h1><a class="product-button" href="{{ route('products') }}">&larr; Back to Products</a></div>
@endsection

@section('content')
@if(session('status'))<div class="product-message success" role="status">{{ session('status') }}</div>@endif
@if($errors->any())
<div class="product-message error" role="alert"><strong>Check the highlighted fields.</strong>@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
@endif
<form class="product-editor" method="POST" action="{{ route('products.store') }}">
    @csrf
    <header class="product-editor-head">
        <div><p class="product-eyebrow">Product catalogue</p><h2>Create a product</h2><p class="product-help">Your product code (SKU) is generated automatically when saved. Extra details are under More options.</p></div>
        <span class="product-chip">Quick add</span>
    </header>
    <section class="product-section" aria-labelledby="product-details-title">
        <div class="product-section-head"><div><p class="product-eyebrow">Required</p><h3 id="product-details-title">Product details</h3></div><p class="product-help">Fields marked <span class="product-required">*</span> are required.</p></div>
        <div class="product-grid">
            <label class="product-field wide">
                <span>Product name <span class="product-required">*</span></span>
                <input name="name" value="{{ old('name') }}" list="product-name-suggestions" maxlength="255" placeholder="e.g. Wireless router" required autofocus aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}">
                <datalist id="product-name-suggestions">@foreach($productNames as $pname)<option value="{{ $pname }}"></option>@endforeach</datalist>
                @error('name')<small class="field-error">{{ $message }}</small>@enderror
            </label>
            <label class="product-field">
                <span>Serial number (optional)</span>
                <input name="serial_number" value="{{ old('serial_number') }}" maxlength="255" placeholder="Unique item serial, if available" aria-invalid="{{ $errors->has('serial_number') ? 'true' : 'false' }}">
                <small>Leave blank for products without a serial number.</small>
                @error('serial_number')<small class="field-error">{{ $message }}</small>@enderror
            </label>
            <label class="product-field">
                <span>Category <span class="product-required">*</span></span>
                <select name="category_id" required aria-invalid="{{ $errors->has('category_id') ? 'true' : 'false' }}"><option value="">Select category</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected((string) old('category_id') === (string) $category->id)>{{ $category->name }}</option>@endforeach</select>
                @if($categories->isEmpty())<small>No categories yet. <a href="{{ route('categories.create', ['redirect_to'=>'products.create']) }}">Add a category</a> before saving.</small>@endif
                @error('category_id')<small class="field-error">{{ $message }}</small>@enderror
            </label>
            <label class="product-field">
                <span>Cost price (KES) <span class="product-required">*</span></span>
                <input name="cost" type="number" step="0.01" min="0.01" value="{{ old('cost') }}" placeholder="0.00" required aria-invalid="{{ $errors->has('cost') ? 'true' : 'false' }}">
                @error('cost')<small class="field-error">{{ $message }}</small>@enderror
            </label>
            <label class="product-field">
                <span>Selling price (KES) <span class="product-required">*</span></span>
                <input name="price" type="number" step="0.01" min="0" value="{{ old('price') }}" placeholder="0.00" required aria-invalid="{{ $errors->has('price') ? 'true' : 'false' }}">
                <small>Set a price above zero to make this item available at POS.</small>
                @error('price')<small class="field-error">{{ $message }}</small>@enderror
            </label>
        </div>
    </section>
    <section class="product-section product-stock" aria-labelledby="product-stock-title">
        <div class="product-section-head"><div><p class="product-eyebrow">Opening inventory</p><h3 id="product-stock-title">Opening stock</h3></div><p class="product-help">For {{ $currentBranch->name ?? 'the current branch' }}. Enter 0 if stock will be received later.</p></div>
        <div class="product-grid">
            <label class="product-field"><span>Opening quantity</span><input name="stock" type="number" min="0" step="1" value="{{ old('stock', 1) }}" aria-invalid="{{ $errors->has('stock') ? 'true' : 'false' }}">@error('stock')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="product-field"><span>Date recorded</span><input name="recorded_at" type="date" value="{{ old('recorded_at', now()->toDateString()) }}" aria-invalid="{{ $errors->has('recorded_at') ? 'true' : 'false' }}">@error('recorded_at')<small class="field-error">{{ $message }}</small>@enderror</label>
        </div>
    </section>
    <details class="product-optional" @if(old('barcode') || old('supplier_id') || old('description') || old('stock_alert') || $errors->hasAny(['barcode','supplier_id','description','stock_alert','is_active'])) open @endif>
        <summary>More options <span>Barcode, supplier, reorder level and notes</span></summary>
        <div class="product-section">
            <div class="product-grid">
                <label class="product-field"><span>Barcode</span><input name="barcode" value="{{ old('barcode') }}" maxlength="255" placeholder="Scan or type barcode" aria-invalid="{{ $errors->has('barcode') ? 'true' : 'false' }}">@error('barcode')<small class="field-error">{{ $message }}</small>@enderror</label>
                <label class="product-field"><span>Supplier</span><select name="supplier_id" aria-invalid="{{ $errors->has('supplier_id') ? 'true' : 'false' }}"><option value="">No supplier</option>@foreach($suppliers as $supplier)<option value="{{ $supplier->id }}" @selected((string) old('supplier_id') === (string) $supplier->id)>{{ $supplier->name }}</option>@endforeach</select>@error('supplier_id')<small class="field-error">{{ $message }}</small>@enderror</label>
                <label class="product-field"><span>Reorder level</span><input name="stock_alert" type="number" min="0" step="1" value="{{ old('stock_alert', 0) }}" aria-invalid="{{ $errors->has('stock_alert') ? 'true' : 'false' }}"><small>Flag low stock when the quantity reaches this level.</small>@error('stock_alert')<small class="field-error">{{ $message }}</small>@enderror</label>
                <label class="product-field full"><span>Description / notes</span><textarea name="description" rows="3" placeholder="Optional product information" aria-invalid="{{ $errors->has('description') ? 'true' : 'false' }}">{{ old('description') }}</textarea>@error('description')<small class="field-error">{{ $message }}</small>@enderror</label>
            </div>
            <label class="product-active"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', 1))><span>Active product<small>Available for sale when it has stock and a selling price above zero.</small></span></label>
        </div>
    </details>
    <footer class="product-editor-footer"><p class="product-help">Opening quantity defaults to 1. Reorder level defaults to 0.</p><div class="product-actions"><a class="product-button" href="{{ route('products') }}">Cancel</a><button class="product-button primary" type="submit">Create product</button></div></footer>
</form>
@endsection
