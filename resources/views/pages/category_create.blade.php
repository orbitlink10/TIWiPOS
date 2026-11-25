@extends('layouts.app')
@section('title', 'Categories')
@push('styles')
@include('partials.product-editor-styles')
<style>
.categories-page{display:grid;grid-template-columns:minmax(0,1fr) 320px;gap:16px;align-items:start}.category-card{background:#fff;border:1px solid #e2e8ee;border-radius:10px;min-width:0;overflow:hidden}.category-head{display:flex;justify-content:space-between;align-items:center;gap:14px;flex-wrap:wrap;padding:18px 20px;border-bottom:1px solid #edf0f4}.category-head h2,.category-form h2{margin:0;font-size:16px;letter-spacing:-.2px}.category-count{display:inline-block;padding:3px 7px;margin-left:6px;background:#f0f4f7;border-radius:5px;font-size:11px;color:#718087}.category-search{width:240px;max-width:100%;padding:10px 12px;border:1px solid #cdd8e3;border-radius:7px;color:#16242b;font:inherit;font-size:12px}.category-search:focus{outline:2px solid #a7c9f4;outline-offset:1px}.category-scroll{overflow:auto}.category-table{width:100%;min-width:440px;border-collapse:collapse;font-size:13px}.category-table th{background:#f7f9fb;padding:12px 20px;text-align:left;color:#718087;font-size:10px;font-weight:800;letter-spacing:.05em;text-transform:uppercase}.category-table td{padding:15px 20px;border-top:1px solid #edf0f4;vertical-align:middle}.category-table tbody tr:hover{background:#fafcfe}.category-table .numeric{text-align:right;font-variant-numeric:tabular-nums}.category-name{font-weight:700;color:#16242b}.category-description{display:block;margin-top:4px;font-size:11px;font-weight:500;color:#718087;max-width:350px;line-height:1.6;overflow-wrap:anywhere}.category-status{display:inline-block;padding:4px 8px;border-radius:5px;background:#edf8ef;color:#166534;font-size:10px;font-weight:700}.category-status.inactive{background:#f0f3f6;color:#667885}.category-delete{padding:7px 10px;border:1px solid #f1ccc6;border-radius:6px;background:#fff5f3;color:#b42318;font:inherit;font-size:11px;font-weight:700;cursor:pointer}.category-form{padding:20px;position:sticky;top:16px}.category-form form{display:grid;gap:16px;margin-top:20px}.category-form .product-active{margin-top:0}.category-form .product-button{width:100%}.category-empty{text-align:center;padding:36px 20px;color:#718087;font-size:12px;line-height:1.8}.categories-page [hidden]{display:none!important}.category-table .category-action{text-align:right}.categories-page :is(a,button):focus-visible{outline:2px solid #a7c9f4;outline-offset:2px}
@media(max-width:1150px){.categories-page{grid-template-columns:minmax(0,1fr)}.category-form{position:static}.category-form form{grid-template-columns:repeat(2,minmax(0,1fr));align-items:start}.category-form .category-form-wide{grid-column:1/-1}}
@media(max-width:600px){.category-form form{grid-template-columns:minmax(0,1fr)}.category-head,.category-form{padding:16px}.category-search{width:100%;font-size:16px}.category-table th,.category-table td{padding:12px 15px}.header-row{flex-wrap:wrap;gap:12px}}
</style>
@endpush
@section('header')
@php($returnRoute = ($redirectTo ?? 'categories.create') === 'products.create' ? 'products.create' : 'products')
<div class="header-row"><div><h1>Categories</h1><p class="product-help">Organise your product catalogue.</p></div><a class="product-button" href="{{ route($returnRoute) }}">&larr; {{ $returnRoute === 'products.create' ? 'Back to Add Product' : 'Products' }}</a></div>
@endsection
@section('content')
@php($canDeleteProducts = auth()->user()->canAccessAbility('delete_products'))
@if(session('status'))<div class="product-message success" role="status">{{ session('status') }}</div>@endif
@if(session('error'))<div class="product-message error" role="alert">{{ session('error') }}</div>@endif
@if($errors->any())<div class="product-message error" role="alert">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
<div class="categories-page">
    <section class="category-card" aria-labelledby="category-list-title">
        <div class="category-head"><h2 id="category-list-title">Category directory <span class="category-count">{{ $categories->count() }}</span></h2><input type="search" id="category-search" class="category-search" placeholder="Search categories…" aria-label="Search categories"></div>
        @if($categories->isNotEmpty())
        <div class="category-scroll"><table class="category-table">
            <thead><tr><th scope="col">Category</th><th scope="col" class="numeric">Products</th><th scope="col">Status</th>@if($canDeleteProducts)<th scope="col" class="category-action">Action</th>@endif</tr></thead>
            <tbody>
                @foreach($categories as $category)
                <tr data-category-text="{{ $category->name.' '.$category->description }}">
                    <td><span class="category-name">{{ $category->name }}</span>@if($category->description)<span class="category-description">{{ $category->description }}</span>@endif</td>
                    <td class="numeric">{{ number_format($category->products_count) }}</td>
                    <td><span class="category-status {{ $category->is_active ? '' : 'inactive' }}">{{ $category->is_active ? 'Active' : 'Inactive' }}</span></td>
                    @if($canDeleteProducts)<td class="category-action"><form method="POST" action="{{ route('categories.destroy', $category) }}" onsubmit="return confirm('Delete this category and its related products? This cannot be undone.');">@csrf @method('DELETE')<input type="hidden" name="redirect_to" value="categories.create"><button type="submit" class="category-delete">Delete</button></form></td>@endif
                </tr>
                @endforeach
            </tbody>
        </table></div>
        <p class="category-empty" id="category-no-results" hidden>No categories match your search.</p>
        @else<div class="category-empty"><strong>No categories yet</strong><br>Create your first category to start organising products.</div>@endif
    </section>
    <section class="category-card category-form" aria-labelledby="category-form-title">
        <h2 id="category-form-title">New category</h2><p class="product-help">Give your product group a clear name.</p>
        <form method="POST" action="{{ route('categories.store') }}">
            @csrf
            <input type="hidden" name="redirect_to" value="{{ $redirectTo ?? 'categories.create' }}">
            <label class="product-field category-form-wide"><span>Category name <span class="product-required">*</span></span><input name="category_name" value="{{ old('category_name') }}" maxlength="255" autocomplete="off" placeholder="e.g. Routers & networking" required aria-invalid="{{ $errors->has('category_name') ? 'true' : 'false' }}">@error('category_name')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="product-field category-form-wide"><span>Description (optional)</span><textarea name="description" rows="3" placeholder="What products belong here?">{{ old('description') }}</textarea></label>
            <label class="product-active category-form-wide"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', '1') == '1')><span>Active category</span></label>
            <div class="category-form-wide"><button type="submit" class="product-button primary">Create category</button></div>
        </form>
    </section>
</div>
@endsection
@push('scripts')
<script>
document.getElementById('category-search').addEventListener('input', function () {
    const term = this.value.trim().toLowerCase();
    const rows = Array.from(document.querySelectorAll('[data-category-text]'));
    rows.forEach(row => { row.hidden = !row.dataset.categoryText.toLowerCase().includes(term); });
    const empty = document.getElementById('category-no-results');
    if (empty) empty.hidden = rows.some(row => !row.hidden);
});
</script>
@endpush
