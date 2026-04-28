@extends('layouts.app')

@section('title', 'Edit Stylist')

@section('header')
    <div class="header-row">
        <h1>Edit Stylist</h1>
        <a class="btn" href="{{ route('services') }}">Back to Services</a>
    </div>
@endsection

@section('content')
    <div class="panel">
        <h2>Stylist profile</h2>
        <p style="color: var(--muted); margin-top:6px;">Update stylist contact details, branch, and salon notes.</p>

        @if ($errors->any())
            <div style="margin-top:10px; padding:10px 12px; border-radius:10px; border:1px solid rgba(239,68,68,0.3); background:rgba(239,68,68,0.08); color:#b91c1c;">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('service-workers.update', $serviceWorker) }}" style="margin-top:14px; display:grid; gap:14px;">
            @csrf
            @method('PUT')

            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:14px;">
                <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                    Full name
                    <input name="name" type="text" value="{{ old('name', $serviceWorker->name) }}" required style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                </label>
                <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                    Title
                    <input name="title" type="text" value="{{ old('title', $serviceWorker->title) }}" required style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                </label>
                <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                    Phone
                    <input name="phone" type="text" value="{{ old('phone', $serviceWorker->phone) }}" inputmode="tel" style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                </label>
                <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                    Email
                    <input name="email" type="email" value="{{ old('email', $serviceWorker->email) }}" style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                </label>
                <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                    Branch
                    <select name="branch_id" style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                        <option value="">Use active branch</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" @selected((string) old('branch_id', $serviceWorker->branch_id) === (string) $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </label>
            </div>

            <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                Notes
                <textarea name="notes" rows="4" style="padding:12px;border:1px solid #e5e7eb;border-radius:10px; resize:vertical;">{{ old('notes', $serviceWorker->notes) }}</textarea>
            </label>

            <label style="display:flex; align-items:center; gap:10px; font-weight:600;">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', (int) $serviceWorker->is_active)) style="width:18px;height:18px;">
                Active stylist
            </label>

            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <button class="btn" type="submit">Save Changes</button>
                <a class="btn" style="background:#e5e7eb; color:#0f172a;" href="{{ route('services') }}">Cancel</a>
            </div>
        </form>
    </div>
@endsection
