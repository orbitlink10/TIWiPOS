@extends('layouts.app')

@section('title', 'Staff Profile')

@push('styles')
<style>
    .staff-profile { max-width:960px; }
    .staff-profile-head { display:flex; align-items:center; gap:16px; padding-bottom:22px; border-bottom:1px solid var(--border); }
    .staff-avatar { display:grid; place-items:center; flex-shrink:0; width:64px; height:64px; border-radius:16px; background:#eaf3fc; color:#1769d1; font-size:26px; font-weight:800; }
    .staff-profile-head h2 { margin:0 0 8px; overflow-wrap:anywhere; }
    .staff-status { display:inline-flex; padding:4px 10px; border-radius:999px; font-size:12px; font-weight:800; }
    .staff-status.active { color:#065f46; background:#d1fae5; }
    .staff-status.inactive { color:#991b1b; background:#fee2e2; }
    .staff-details { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:24px; margin:24px 0 0; }
    .staff-details dt { color:var(--muted); font-size:12px; font-weight:700; margin-bottom:6px; }
    .staff-details dd { margin:0; font-size:15px; overflow-wrap:anywhere; }
    .staff-details a { color:var(--accent); }
    @media(max-width:600px) { .staff-details { grid-template-columns:minmax(0,1fr); gap:18px; } }
</style>
@endpush

@section('header')
    <div class="header-row">
        <h1>Staff Profile</h1>
        <div style="display:flex; flex-wrap:wrap; gap:10px;">
            <a class="btn" href="{{ route('staff.edit', $member) }}">Edit Profile</a>
            <a class="btn" href="{{ route('staff.index') }}">Back to Staff</a>
        </div>
    </div>
@endsection

@section('content')
    <section class="panel staff-profile" aria-label="Staff profile details">
        @if(session('status'))
            <p role="status" style="padding:12px; margin:0 0 20px; border-radius:8px; background:#d1fae5; color:#065f46;">{{ session('status') }}</p>
        @endif
        <div class="staff-profile-head">
            <span class="staff-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($member->name, 0, 1)) }}</span>
            <div>
                <h2>{{ $member->name }}</h2>
                <span class="staff-status {{ $member->is_active ? 'active' : 'inactive' }}">{{ $member->is_active ? 'Active' : 'Inactive' }}</span>
            </div>
        </div>
        <dl class="staff-details">
            <div><dt>Full name</dt><dd>{{ $member->name }}</dd></div>
            <div><dt>Role</dt><dd>{{ \App\Models\User::assignableRoles()[$member->role] ?? ucfirst($member->role) }}</dd></div>
            <div><dt>Email</dt><dd><a href="mailto:{{ $member->email }}">{{ $member->email }}</a></dd></div>
            <div><dt>Phone number</dt><dd>{{ $member->phone ?: 'Not provided' }}</dd></div>
            <div><dt>Branch</dt><dd>{{ $member->branch?->name ?? 'Not assigned' }}</dd></div>
            <div><dt>Created on</dt><dd>{{ $member->created_at?->format('d M Y, H:i') ?? 'Not available' }}</dd></div>
        </dl>
    </section>
@endsection
