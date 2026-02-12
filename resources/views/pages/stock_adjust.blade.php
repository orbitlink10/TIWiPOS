@extends('layouts.app')

@section('title', 'Adjust Stock')

@section('header')
    <div class="header-row">
        <h1>Adjust Stock</h1>
        <a class="btn" href="{{ route('stock') }}">Back to Stock</a>
    </div>
@endsection

@section('content')
    <div class="panel">
        <h2>Update stock by category + serial numbers</h2>
        <p style="color: var(--muted); margin-top:6px;">Select a category and enter serial numbers. Stock increases by the number of valid serial numbers entered.</p>

        @if ($errors->any())
            <div style="margin-top:10px; padding:10px 12px; border-radius:10px; border:1px solid rgba(239,68,68,0.3); background:rgba(239,68,68,0.08); color:#b91c1c;">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        @if (session('status'))
            <div style="margin-top:10px; padding:10px 12px; border-radius:10px; border:1px solid rgba(16,185,129,0.3); background:rgba(16,185,129,0.1); color:#065f46;">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('stock.adjust.store') }}" style="margin-top:14px; display:grid; gap:14px;">
            @csrf
            <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                Category
                <select name="category_id" required style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                    <option value="">Select category</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) old('category_id', $selectedCategoryId ?? '') === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </label>

            <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                Serial numbers
                <textarea name="serial_numbers" rows="6" required placeholder="Enter one serial number per line" style="padding:12px;border:1px solid #e5e7eb;border-radius:10px; resize:vertical;">{{ old('serial_numbers') }}</textarea>
                <span style="color:var(--muted); font-size:12px;">One serial per line. Comma-separated values are also accepted.</span>
            </label>

            <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                Location
                <input name="location" type="text" value="{{ old('location', 'main') }}" style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
            </label>

            <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                Note
                <input name="note" type="text" value="{{ old('note') }}" placeholder="Reason (optional)" style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
            </label>

            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <button class="btn" type="submit">Apply Update</button>
                <a class="btn" style="background:#e5e7eb; color:#0f172a;" href="{{ route('stock') }}">Cancel</a>
            </div>
        </form>
    </div>
@endsection