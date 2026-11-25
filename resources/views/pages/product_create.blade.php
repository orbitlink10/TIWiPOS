@extends('layouts.app')
@section('title', 'Add Product')

@push('styles')
<style>
main{min-width:0}
.product-editor{max-width:1080px;background:#fff;border:1px solid #e2e8ee;border-radius:12px;overflow:hidden;box-shadow:0 1px 2px rgba(16,36,53,.04)}
.product-editor-head{padding:22px 24px;display:flex;justify-content:space-between;align-items:start;gap:16px;border-bottom:1px solid #edf0f4}
.product-editor h2{margin:4px 0 6px;font-size:21px;letter-spacing:-.4px}.product-editor h3{margin:3px 0 0;font-size:16px}
.product-eyebrow{margin:0;font-size:10px;font-weight:800;letter-spacing:.12em;color:#718087;text-transform:uppercase}
.product-help{margin:5px 0 0;color:#667885;font-size:12px;line-height:1.6}.product-chip{padding:6px 10px;border-radius:6px;background:#eef4fb;color:#1057b0;font-size:11px;font-weight:700;white-space:nowrap}
.product-section{padding:22px 24px}.product-section-head{display:flex;gap:12px;justify-content:space-between;align-items:center;margin-bottom:18px}
.product-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px}
.product-field{display:flex;flex-direction:column;gap:6px;min-width:0;color:#334155;font-size:12px;font-weight:700}.product-field.wide{grid-column:span 2}.product-field.full{grid-column:1/-1}
.product-field input,.product-field select,.product-field textarea{width:100%;min-width:0;padding:11px 12px;border:1px solid #cdd8e3;border-radius:7px;background:#fff;color:#16242b;font:inherit;font-size:14px;font-weight:500}.product-field textarea{resize:vertical}
.product-field input:focus,.product-field select:focus,.product-field textarea:focus{outline:2px solid #a7c9f4;outline-offset:1px;border-color:#1769d1}.product-field [aria-invalid="true"]{border-color:#c53929}
.product-field small{color:#718087;font-size:11px;font-weight:500;line-height:1.5}.product-field .field-error{color:#b42318}.product-field a{color:#1057b0}.product-required{color:#b42318}
.product-optional{border-top:1px solid #edf0f4}.product-optional summary{padding:18px 24px;cursor:pointer;color:#334155;font-size:13px;font-weight:700}.product-optional summary span{color:#718087;font-size:12px;font-weight:500;margin-left:8px}.product-optional .product-section{padding-top:4px}
.product-stock{border-top:1px solid #edf0f4;background:#fafbfd}.product-active{display:flex;gap:10px;align-items:start;margin-top:18px;font-size:13px;font-weight:700;color:#334155}.product-active input{width:18px;height:18px;margin:1px 0 0;accent-color:#1769d1}.product-active small{display:block;margin-top:3px;color:#718087;font-size:11px;font-weight:500}
.product-editor-footer{display:flex;justify-content:space-between;align-items:center;gap:16px;padding:18px 24px;border-top:1px solid #e2e8ee}.product-actions{display:flex;gap:8px;flex-shrink:0}
.product-button{display:inline-block;padding:11px 16px;border:1px solid #ccd8e5;border-radius:7px;background:#fff;color:#334155;text-decoration:none;font:inherit;font-size:13px;font-weight:700;cursor:pointer}.product-button.primary{border-color:#1769d1;background:#1769d1;color:#fff}
.product-message{max-width:1080px;padding:12px 16px;border-radius:8px;margin-bottom:14px;font-size:13px;line-height:1.6}.product-message.error{color:#b42318;background:#fff0ee;border:1px solid #f1ccc6}.product-message.success{color:#166534;background:#edf8ef;border:1px solid #c7e7ce}
@media(max-width:900px){.product-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.product-section-head{display:block}}
@media(max-width:600px){.product-grid{grid-template-columns:minmax(0,1fr);gap:14px}.product-field.wide{grid-column:auto}.product-editor-head,.product-section,.product-editor-footer{padding:18px 16px}.product-optional summary{padding:18px 16px}.product-optional summary span{display:block;margin:6px 0 0}.product-editor-footer{align-items:stretch;flex-direction:column}.product-actions{justify-content:flex-end}.product-field input,.product-field select,.product-field textarea{font-size:16px}.product-chip{display:none}}
</style>
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
        <div><p class="product-eyebrow">Product catalogue</p><h2>Create a product</h2><p class="product-help">Start with the details needed to sell. Add extra information under More options.</p></div>
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
                <span>SKU / product code <span class="product-required">*</span></span>
                <input name="sku" value="{{ old('sku') }}" maxlength="255" placeholder="e.g. RTR-001" required aria-invalid="{{ $errors->has('sku') ? 'true' : 'false' }}">
                @error('sku')<small class="field-error">{{ $message }}</small>@enderror
            </label>
            <label class="product-field">
                <span>Serial number <span class="product-required">*</span></span>
                <input name="serial_number" value="{{ old('serial_number') }}" maxlength="255" placeholder="Unique item serial" required aria-invalid="{{ $errors->has('serial_number') ? 'true' : 'false' }}">
                <small>Use the item's unique serial number.</small>
                @error('serial_number')<small class="field-error">{{ $message }}</small>@enderror
            </label>
            <label class="product-field wide">
                <span>Sub-category <span class="product-required">*</span></span>
                <select name="category_id" required aria-invalid="{{ $errors->has('category_id') ? 'true' : 'false' }}"><option value="">Select sub-category</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected((string) old('category_id') === (string) $category->id)>{{ $category->parent ? $category->parent->name.' / ' : '' }}{{ $category->name }}</option>@endforeach</select>
                @if($categories->isEmpty())<small>No sub-categories yet. <a href="{{ route('sub-categories.create', ['redirect_to'=>'products.create']) }}">Add a sub-category</a> before saving.</small>@endif
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
        <div class="product-section-head"><div><p class="product-eyebrow">Opening inventory</p><h3 id="product-stock-title">Stock & location</h3></div><p class="product-help">For {{ $currentBranch->name ?? 'the current branch' }}. Enter 0 if stock will be received later.</p></div>
        <div class="product-grid">
            <label class="product-field"><span>Opening quantity</span><input name="stock" type="number" min="0" step="1" value="{{ old('stock', 1) }}" aria-invalid="{{ $errors->has('stock') ? 'true' : 'false' }}">@error('stock')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="product-field"><span>Stock location</span><input name="stock_location" maxlength="100" value="{{ old('stock_location', 'main') }}" placeholder="e.g. main" aria-invalid="{{ $errors->has('stock_location') ? 'true' : 'false' }}">@error('stock_location')<small class="field-error">{{ $message }}</small>@enderror</label>
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
