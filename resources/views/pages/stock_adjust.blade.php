@extends('layouts.app')

@section('title', 'Adjust Stock')

@section('header')
    <div class="header-row">
        <h1>Adjust Stock</h1>
        <a class="btn" href="{{ route('stock') }}">⬅ Back to Stock</a>
    </div>
@endsection

@section('content')
    <div class="panel">
        <h2>Manual adjustment</h2>
        <p style="color: var(--muted); margin-top:6px;">Increase or decrease on-hand quantities.</p>

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
                Product
                <select name="product_id" required style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                    <option value="">Select product</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->sku }})</option>
                    @endforeach
                </select>
            </label>
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:12px;">
                <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                    Location
                    <input name="location" type="text" value="main" style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                </label>
                <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                    Serial number (optional)
                    <input name="serial_number" type="text" placeholder="Capture serial while adding stock" style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                </label>
                <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                    Quantity change (use negative to reduce)
                    <input name="quantity" type="number" value="1" required style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                </label>
            </div>
            <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                Note
                <input name="note" type="text" placeholder="Reason (optional)" style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
            </label>
            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <button class="btn" type="submit">💾 Apply Adjustment</button>
                <a class="btn" style="background:#e5e7eb; color:#0f172a;" href="{{ route('stock') }}">Cancel</a>
            </div>
        </form>
    </div>
@endsection
