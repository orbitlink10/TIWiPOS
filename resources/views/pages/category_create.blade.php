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

    <div class="panel" style="margin-top:16px;">
        <h2>Manage Categories</h2>
        <p style="color: var(--muted); margin-top:6px;">Delete categories you no longer need. Products under the category are removed too.</p>

        <div style="margin-top:14px; overflow:auto;">
            <table style="width:100%; border-collapse:collapse; border-spacing:0; font-size:14px; min-width:520px;">
                <thead>
                    <tr style="background:#f7f7fb;">
                        <th style="text-align:left; padding:10px;">Category</th>
                        <th style="text-align:left; padding:10px;">Parent</th>
                        <th style="text-align:right; padding:10px;">Products</th>
                        <th style="text-align:center; padding:10px; width:120px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $cat)
                        <tr style="border-top:1px solid #e5e7eb;">
                            <td style="padding:10px;">{{ $cat->name }}</td>
                            <td style="padding:10px;">{{ optional($cat->parent)->name ?? 'None' }}</td>
                            <td style="padding:10px; text-align:right;">{{ $cat->products_count }}</td>
                            <td style="padding:10px; text-align:center;">
                                <form method="POST" action="{{ route('categories.destroy', $cat) }}" onsubmit="return confirm('Delete this category and all products under it?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" style="border:1px solid #fecaca; background:#fff1f2; color:#b91c1c; border-radius:8px; padding:6px 12px; font-weight:700; cursor:pointer;">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="padding:12px; text-align:center; color:var(--muted);">No categories yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
