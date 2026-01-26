@extends('layouts.app')

@section('title', 'Branches')

@section('header')
<div class="header-row">
    <div>
        <h1>Branches</h1>
        <p style="margin:6px 0 0;color:#6b7280;">Manage locations for your business and switch the active branch.</p>
    </div>
</div>
@endsection

@section('content')
<div class="panel" style="margin-bottom:16px;">
    @if(session('status'))
        <div style="padding:10px 12px; background:#ecfdf3; border:1px solid #bbf7d0; color:#166534; border-radius:10px; margin-bottom:12px;">
            {{ session('status') }}
        </div>
    @endif
    <div style="display:grid; grid-template-columns: 1fr 320px; gap:18px; align-items:start;">
        <div>
            <h2 style="margin-top:0;">Existing branches</h2>
            <table style="width:100%; border-collapse:collapse; background:#fff; border:1px solid #e5edf5; border-radius:12px; overflow:hidden;">
                <thead style="background:#f7fbff;">
                    <tr>
                        <th style="text-align:left; padding:12px;">Name</th>
                        <th style="text-align:left; padding:12px;">Code</th>
                        <th style="text-align:left; padding:12px;">Location</th>
                        <th style="text-align:left; padding:12px;">Active</th>
                        <th style="padding:12px;">Switch</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($branches as $branch)
                        <tr style="border-top:1px solid #e5edf5;">
                            <td style="padding:12px;">{{ $branch->name }}</td>
                            <td style="padding:12px;">{{ $branch->code ?? '—' }}</td>
                            <td style="padding:12px;">{{ $branch->location ?? '—' }}</td>
                            <td style="padding:12px;">{{ $branch->id === $current ? 'Yes' : 'No' }}</td>
                            <td style="padding:12px; text-align:center;">
                                @if($branch->id !== $current)
                                    <form method="POST" action="{{ route('branches.switch') }}">
                                        @csrf
                                        <input type="hidden" name="branch_id" value="{{ $branch->id }}">
                                        <button class="btn" style="padding:8px 12px; font-size:14px;">Switch</button>
                                    </form>
                                @else
                                    <span style="color:#16a34a; font-weight:700;">Current</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" style="padding:12px;">No branches yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="background:#f8fafc; border:1px solid #e5edf5; border-radius:12px; padding:16px;">
            <h3 style="margin-top:0;">Add a branch</h3>
            <form method="POST" action="{{ route('branches.store') }}">
                @csrf
                <div class="field">
                    <label for="name" style="display:block; font-weight:600; margin-bottom:6px;">Name</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" required style="width:100%; padding:12px; border-radius:10px; border:1px solid #e5edf5;">
                    @error('name') <small style="color:#d14343;">{{ $message }}</small> @enderror
                </div>
                <div class="field">
                    <label for="code" style="display:block; font-weight:600; margin-bottom:6px;">Code (optional)</label>
                    <input id="code" name="code" type="text" value="{{ old('code') }}" style="width:100%; padding:12px; border-radius:10px; border:1px solid #e5edf5;">
                    @error('code') <small style="color:#d14343;">{{ $message }}</small> @enderror
                </div>
                <div class="field">
                    <label for="location" style="display:block; font-weight:600; margin-bottom:6px;">Location</label>
                    <input id="location" name="location" type="text" value="{{ old('location') }}" style="width:100%; padding:12px; border-radius:10px; border:1px solid #e5edf5;">
                </div>
                <div class="field">
                    <label for="phone" style="display:block; font-weight:600; margin-bottom:6px;">Phone</label>
                    <input id="phone" name="phone" type="text" value="{{ old('phone') }}" style="width:100%; padding:12px; border-radius:10px; border:1px solid #e5edf5;">
                </div>
                <div class="field">
                    <label for="timezone" style="display:block; font-weight:600; margin-bottom:6px;">Timezone</label>
                    <input id="timezone" name="timezone" type="text" value="{{ old('timezone') }}" placeholder="Africa/Nairobi" style="width:100%; padding:12px; border-radius:10px; border:1px solid #e5edf5;">
                </div>
                <button type="submit" class="btn" style="width:100%; justify-content:center;">Create branch</button>
            </form>
        </div>
    </div>
</div>
@endsection
