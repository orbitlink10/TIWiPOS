@extends('layouts.app')

@section('title', 'Staff')

@section('header')
    <div class="header-row">
        <h1>Staff Profiles</h1>
        <a class="btn" href="{{ route('home') }}">Back to Dashboard</a>
    </div>
@endsection

@section('content')
    <div class="panel">
        <h2>Team members</h2>
        <p style="color: var(--muted); margin-top:6px;">Create logins for cashiers and managers. Staff cannot access admin-only actions.</p>

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

        <div style="display:grid; grid-template-columns:2fr 1fr; gap:16px; margin-top:12px;">
            <div style="overflow:auto;">
                <table style="width:100%; border-collapse:collapse; min-width:580px; font-size:14px;">
                    <thead>
                        <tr style="background:#f7f7fb;">
                            <th style="text-align:left; padding:10px;">Name</th>
                            <th style="text-align:left; padding:10px;">Email</th>
                            <th style="text-align:left; padding:10px;">Branch</th>
                            <th style="text-align:left; padding:10px;">Role</th>
                            <th style="text-align:left; padding:10px;">Status</th>
                            <th style="text-align:left; padding:10px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($staff as $member)
                            <tr style="border-top:1px solid #e5e7eb;">
                                <td style="padding:10px;">{{ $member->name }}</td>
                                <td style="padding:10px;">{{ $member->email }}</td>
                                <td style="padding:10px;">
                                    <form method="POST" action="{{ route('staff.branch', $member) }}" style="display:inline-flex;">
                                        @csrf
                                        @method('PATCH')
                                        <select name="branch_id" onchange="this.form.submit()" style="padding:8px;border:1px solid #e5e7eb;border-radius:8px;">
                                            @foreach($branches as $branch)
                                                <option value="{{ $branch->id }}" @selected((int) $member->branch_id === (int) $branch->id)>{{ $branch->name }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                </td>
                                <td style="padding:10px;">
                                    <form method="POST" action="{{ route('staff.role', $member) }}" style="display:inline-flex;">
                                        @csrf
                                        @method('PATCH')
                                        <select name="role" onchange="this.form.submit()" style="padding:8px;border:1px solid #e5e7eb;border-radius:8px;">
                                            @foreach(\App\Models\User::assignableRoles() as $roleKey => $roleLabel)
                                                <option value="{{ $roleKey }}" @selected($member->role === $roleKey)>{{ $roleLabel }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                </td>
                                <td style="padding:10px;">
                                    <span style="display:inline-flex; border-radius:999px; padding:4px 10px; font-size:12px; font-weight:800; {{ $member->is_active ? 'color:#065f46;background:#d1fae5;' : 'color:#991b1b;background:#fee2e2;' }}">
                                        {{ $member->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td style="padding:10px;">
                                    <form method="POST" action="{{ route('staff.status', $member) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="is_active" value="{{ $member->is_active ? 0 : 1 }}">
                                        <button type="submit" style="border:1px solid {{ $member->is_active ? '#fecaca' : '#d1d5db' }}; background:{{ $member->is_active ? '#fff1f2' : '#f8fafc' }}; color:{{ $member->is_active ? '#b91c1c' : '#334155' }}; border-radius:8px; padding:6px 10px; font-weight:700; cursor:pointer;">
                                            {{ $member->is_active ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="padding:12px; text-align:center; color:var(--muted);">No staff yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="panel" style="padding:14px;">
                <h3 style="margin:0 0 8px;">Create staff profile</h3>
                <form method="POST" action="{{ route('staff.store') }}" style="display:grid; gap:10px; margin-top:8px;">
                    @csrf
                    <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                        Full name
                        <input name="name" type="text" value="{{ old('name') }}" required style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                    </label>
                    <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                        Email
                        <input name="email" type="email" value="{{ old('email') }}" required style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                    </label>
                    <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                        Branch
                        <select name="branch_id" style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                            <option value="">Use default branch</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" @selected(old('branch_id') == $branch->id)>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                        Role
                        <select name="role" style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                            <option value="staff" @selected(old('role') === 'staff')>Staff</option>
                            <option value="manager" @selected(old('role') === 'manager')>Manager</option>
                        </select>
                    </label>
                    <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                        Password
                        <input name="password" type="password" required style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                    </label>
                    <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                        Confirm password
                        <input name="password_confirmation" type="password" required style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                    </label>
                    <button class="btn" type="submit" style="justify-content:center;">Create profile</button>
                </form>
                <div style="color:var(--muted); font-size:12px; margin-top:8px;">
                    Role permissions: Manager and Staff have the same access. Only Owner can manage staff, settings, and branches.
                </div>
            </div>
        </div>
    </div>
@endsection
