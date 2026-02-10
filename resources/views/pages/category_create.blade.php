@extends('layouts.app')

@section('title', 'Add Category')

@section('header')
    <div class="header-row">
        <h1>Add Category</h1>
        <a class="btn" href="{{ route('products.create') }}">⬅ Back to Products</a>
    </div>
@endsection

@section('content')
    <div class="panel">
        <h2>Category details</h2>
        <p style="color: var(--muted); margin-top:6px;">Create a category to group your products.</p>

        @if ($errors->any())
            <div style="margin-top:10px; padding:10px 12px; border-radius:10px; border:1px solid rgba(239,68,68,0.3); background:rgba(239,68,68,0.08); color:#b91c1c;">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('categories.store') }}" style="margin-top:14px; display:grid; gap:14px;">
            @csrf
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(240px,1fr)); gap:14px;">
                <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                    Name
                    <input name="name" type="text" placeholder="Category name" value="{{ old('name') }}" required style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                </label>
                <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                    Parent
                    <select name="parent_id" style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                        <option value="">None</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" @selected((string) old('parent_id') === (string) $cat->id)>{{ $cat->name }}</option>
                        @endforeach
                    </select>
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
                <button class="btn" type="submit">💾 Save Category</button>
                <a class="btn" style="background:#e5e7eb; color:#0f172a;" href="{{ route('products.create') }}">Cancel</a>
            </div>
        </form>
    </div>
@endsection
