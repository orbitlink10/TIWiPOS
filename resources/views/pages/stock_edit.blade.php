@extends('layouts.app')

@section('title', 'Edit Stock')

@section('header')
    <div class="header-row">
        <h1>Edit Stock</h1>
        <a class="btn" href="{{ route('stock') }}">Back to Stock</a>
    </div>
@endsection

@section('content')
    <div class="panel">
        <h2>{{ $product->name }}</h2>
        <p style="color: var(--muted); margin-top:6px;">Correct quantities before selling. Current on hand: {{ $quantity }}.</p>

        @if ($errors->any())
            <div style="margin-top:10px; padding:10px 12px; border-radius:10px; border:1px solid rgba(239,68,68,0.3); background:rgba(239,68,68,0.08); color:#b91c1c;">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('stock.update', $product) }}" style="margin-top:14px; display:grid; gap:14px;">
            @csrf
            @method('PUT')
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:12px;">
                <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                    Location
                    <input name="location" type="text" value="main" style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                </label>
                <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                    Quantity on hand
                    <input name="quantity" type="number" value="{{ $quantity }}" min="0" required style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                </label>
                <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                    Serial number (optional)
                    <input name="serial_number" type="text" value="{{ old('serial_number', $product->serial_number) }}" placeholder="Serial number" style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                </label>
            </div>
            <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                Note
                <input name="note" type="text" placeholder="Reason for change" style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
            </label>
            <div style="color:var(--muted); font-size:13px;">Saving will set the quantity exactly to the value above and log a correction movement.</div>
            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <button class="btn" type="submit">Save stock</button>
                <a class="btn" style="background:#e5e7eb; color:#0f172a;" href="{{ route('stock') }}">Cancel</a>
            </div>
        </form>
    </div>
@endsection
