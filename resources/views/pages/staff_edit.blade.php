@extends('layouts.app')

@section('title', 'Edit Staff Profile')

@push('styles')
<style>
    .staff-edit { max-width:900px; }
    .staff-edit h2 { margin:0; overflow-wrap:anywhere; }
    .staff-edit-form { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:18px; margin-top:24px; }
    .staff-edit-form label { display:flex; flex-direction:column; gap:7px; font-size:14px; font-weight:600; }
    .staff-edit-form input,.staff-edit-form select { width:100%; min-width:0; min-height:44px; padding:11px 12px; border:1px solid var(--border); border-radius:8px; background:#fff; color:var(--text); font:inherit; }
    .staff-edit-form :is(input,select):focus-visible { outline:3px solid #8cbaf2; outline-offset:2px; }
    .staff-edit-password,.staff-edit-actions { grid-column:1/-1; }
    .staff-edit-password { border-top:1px solid var(--border); padding-top:18px; }
    .staff-edit-password h3 { margin:0 0 6px; font-size:16px; }
    .staff-edit-password p { margin:0; color:var(--muted); font-size:13px; }
    .staff-edit-actions { display:flex; flex-wrap:wrap; align-items:center; gap:16px; }
    .staff-edit-actions a { color:var(--accent); font-weight:700; }
    @media(max-width:600px) { .staff-edit-form { grid-template-columns:minmax(0,1fr); } .staff-edit-form input,.staff-edit-form select { font-size:16px; } }
</style>
@endpush

@section('header')
    <div class="header-row">
        <h1>Edit Staff Profile</h1>
        <a class="btn" href="{{ route('staff.show', $member) }}">Back to Profile</a>
    </div>
@endsection

@section('content')
    <section class="panel staff-edit">
        <h2>{{ $member->name }}</h2>
        @if($errors->any())
            <div role="alert" style="margin-top:16px; padding:12px; border-radius:8px; background:#fee2e2; color:#991b1b;">
                @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
            </div>
        @endif
        <form class="staff-edit-form" method="POST" action="{{ route('staff.update', $member) }}">
            @csrf
            @method('PATCH')
            <label>Full name<input name="name" type="text" value="{{ old('name', $member->name) }}" maxlength="255" autocomplete="name" required></label>
            <label>Email<input name="email" type="email" value="{{ old('email', $member->email) }}" maxlength="255" autocomplete="off" required></label>
            <label>Phone number<input name="phone" type="tel" value="{{ old('phone', $member->phone) }}" maxlength="30" placeholder="+254..."></label>
            <label>Branch
                <select name="branch_id">
                    <option value="">Not assigned</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" @selected(old('branch_id', $member->branch_id) == $branch->id)>{{ $branch->name }}</option>
                    @endforeach
                </select>
            </label>
            <label>Role
                <select name="role" required>
                    @foreach(\App\Models\User::assignableRoles() as $roleKey => $roleLabel)
                        <option value="{{ $roleKey }}" @selected(old('role', $member->role) === $roleKey)>{{ $roleLabel }}</option>
                    @endforeach
                </select>
            </label>
            <label>Status
                <select name="is_active" required>
                    <option value="1" @selected((string) old('is_active', (int) $member->is_active) === '1')>Active</option>
                    <option value="0" @selected((string) old('is_active', (int) $member->is_active) === '0')>Inactive</option>
                </select>
            </label>
            <div class="staff-edit-password"><h3>Change password</h3><p id="password_hint">Leave both fields blank to keep the current password.</p></div>
            <label>New password<input name="password" type="password" minlength="8" autocomplete="new-password" aria-describedby="password_hint"></label>
            <label>Confirm new password<input name="password_confirmation" type="password" minlength="8" autocomplete="new-password"></label>
            <div class="staff-edit-actions">
                <button class="btn" type="submit">Save Changes</button>
                <a href="{{ route('staff.show', $member) }}">Cancel</a>
            </div>
        </form>
    </section>
@endsection
