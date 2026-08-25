@extends('layouts.app')

@section('title', 'Add Sub-Category')

@section('header')
    @php($returnRoute = ($redirectTo ?? 'sub-categories.create') === 'products.create' ? 'products.create' : 'products')
    <div class="header-row">
        <h1>Add Sub-Category</h1>
        <a class="btn" href="{{ route($returnRoute) }}">Back to Products</a>
    </div>
@endsection

@section('content')
    @php($canDeleteProducts = auth()->user()->canAccessAbility('delete_products'))
    <div class="panel">
        <h2>Sub-category details</h2>
        <p style="color: var(--muted); margin-top:6px;">Create sub-categories under existing categories.</p>
        @if (session('status'))
            <div style="margin-top:10px; padding:10px 12px; border-radius:10px; border:1px solid rgba(16,185,129,0.3); background:rgba(16,185,129,0.1); color:#065f46;">
                {{ session('status') }}
            </div>
        @endif
        @if (session('error'))
            <div style="margin-top:10px; padding:10px 12px; border-radius:10px; border:1px solid rgba(239,68,68,0.3); background:rgba(239,68,68,0.08); color:#b91c1c;">
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div style="margin-top:10px; padding:10px 12px; border-radius:10px; border:1px solid rgba(239,68,68,0.3); background:rgba(239,68,68,0.08); color:#b91c1c;">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('sub-categories.store') }}" style="margin-top:14px; display:grid; gap:14px;">
            @csrf
            <input type="hidden" name="redirect_to" value="{{ $redirectTo ?? 'sub-categories.create' }}">

            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(240px,1fr)); gap:14px;">
                <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                    Category
                    <select name="parent_id" required style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                        <option value="" disabled @selected(!old('parent_id'))>Select category</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) old('parent_id') === (string) $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                    @if($categories->isEmpty())
                        <small style="color:#b45309; font-weight:500;">
                            No categories available yet.
                            <a href="{{ route('categories.create') }}" style="color:#0369a1; font-weight:600;">Create a category</a>
                        </small>
                    @endif
                </label>
                <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                    Sub-Category
                    <input name="sub_category_name" type="text" placeholder="Sub-category name" value="{{ old('sub_category_name') }}" autocomplete="off" required style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                </label>
            </div>

            <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                Description
                <textarea name="description" rows="3" style="padding:12px;border:1px solid #e5e7eb;border-radius:10px; resize:vertical;">{{ old('description') }}</textarea>
            </label>

            <label style="display:flex; align-items:center; gap:10px; font-weight:600;">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', '1') == '1') style="width:18px;height:18px;"> Active
            </label>

            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <button class="btn" type="submit" @if($categories->isEmpty()) disabled @endif>Save Sub-Category</button>
                <a class="btn" style="background:#e5e7eb; color:#0f172a;" href="{{ route($returnRoute) }}">Cancel</a>
            </div>
        </form>
    </div>

    <div class="panel" style="margin-top:16px;">
        <h2>Manage Sub-Categories</h2>
        <p style="color: var(--muted); margin-top:6px;">
            {{ $canDeleteProducts ? 'Delete sub-categories you no longer need.' : 'Review the sub-categories available for your products.' }}
        </p>

        <div style="margin-top:14px; overflow:auto;">
            <table style="width:100%; border-collapse:collapse; border-spacing:0; font-size:14px; min-width:520px;">
                <thead>
                    <tr style="background:#f7f7fb;">
                        <th style="text-align:left; padding:10px;">Category</th>
                        <th style="text-align:left; padding:10px;">Sub-Category</th>
                        <th style="text-align:right; padding:10px;">Products</th>
                        @if($canDeleteProducts)
                            <th style="text-align:center; padding:10px; width:120px;">Action</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($subCategories as $subCategory)
                        <tr style="border-top:1px solid #e5e7eb;">
                            <td style="padding:10px;">{{ optional($subCategory->parent)->name ?? 'None' }}</td>
                            <td style="padding:10px;">{{ $subCategory->name }}</td>
                            <td style="padding:10px; text-align:right;">{{ $subCategory->products_count }}</td>
                            @if($canDeleteProducts)
                                <td style="padding:10px; text-align:center;">
                                    <form method="POST" action="{{ route('categories.destroy', $subCategory) }}" onsubmit="return confirm('Delete this sub-category?');">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="redirect_to" value="sub-categories.create">
                                        <button type="submit" style="border:1px solid #fecaca; background:#fff1f2; color:#b91c1c; border-radius:8px; padding:6px 12px; font-weight:700; cursor:pointer;">
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canDeleteProducts ? 4 : 3 }}" style="padding:12px; text-align:center; color:var(--muted);">No sub-categories yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
