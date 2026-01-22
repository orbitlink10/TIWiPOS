@extends('layouts.app')

@section('title', 'Add Supplier')

@section('header')
    <div class="header-row">
        <h1>Add Supplier</h1>
        <a class="btn" href="{{ route('products.create') }}">⬅ Back to Products</a>
    </div>
@endsection

@section('content')
    <div class="panel">
        <h2>Supplier details</h2>
        <p style="color: var(--muted); margin-top:6px;">Add supplier contact information.</p>

        @if ($errors->any())
            <div style="margin-top:10px; padding:10px 12px; border-radius:10px; border:1px solid rgba(239,68,68,0.3); background:rgba(239,68,68,0.08); color:#b91c1c;">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('suppliers.store') }}" style="margin-top:14px; display:grid; gap:14px;">
            @csrf
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:14px;">
                <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                    Supplier name
                    <input name="name" type="text" placeholder="Supplier" required style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                </label>
                <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                    Contact person
                    <input name="contact_name" type="text" placeholder="Contact" style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                </label>
                <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                    Phone
                    <input name="phone" type="text" placeholder="+254..." style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                </label>
                <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                    Email
                    <input name="email" type="email" placeholder="supplier@example.com" style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                </label>
            </div>

            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:14px;">
                <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                    Address
                    <input name="address" type="text" placeholder="Street" style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                </label>
                <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                    City
                    <input name="city" type="text" style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                </label>
                <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                    Country
                    <input name="country" type="text" style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                </label>
            </div>

            <label style="display:flex; align-items:center; gap:10px; font-weight:600;">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" checked style="width:18px;height:18px;"> Active
            </label>

            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <button class="btn" type="submit">💾 Save Supplier</button>
                <a class="btn" style="background:#e5e7eb; color:#0f172a;" href="{{ route('products.create') }}">Cancel</a>
            </div>
        </form>
    </div>
@endsection
