@extends('layouts.app')

@section('title', 'Admin Console')

@section('header')
    <div class="header-row">
        <h1>Admin Console</h1>
        <a class="btn" href="{{ route('home') }}">Back</a>
    </div>
@endsection

@section('content')
    @php($hasAdminUserStatusRoute = Route::has('admin.users.status'))
    @php($hasAdminUserDeleteRoute = Route::has('admin.users.destroy'))

    <div class="panel">
        <h2>All businesses</h2>
        @if (session('status'))
            <div style="margin-top:10px; padding:10px 12px; border-radius:10px; border:1px solid rgba(16,185,129,0.3); background:rgba(16,185,129,0.1); color:#065f46;">
                {{ session('status') }}
            </div>
        @endif
        @if (session('error'))
            <div style="margin-top:10px; padding:10px 12px; border-radius:10px; border:1px solid rgba(220,53,69,0.2); background:rgba(220,53,69,0.08); color:#991b1b;">
                {{ session('error') }}
            </div>
        @endif
        @if ($errors->any())
            <div style="margin-top:10px; padding:10px 12px; border-radius:10px; border:1px solid rgba(220,53,69,0.2); background:rgba(220,53,69,0.08); color:#991b1b;">
                {{ $errors->first() }}
            </div>
        @endif
        <div style="overflow:auto; margin-top:12px;">
            <table style="width:100%; min-width:780px; border-collapse:collapse; font-size:14px;">
                <thead>
                    <tr style="background:#f7f7fb;">
                        <th style="padding:10px; text-align:left;">Name</th>
                        <th style="padding:10px; text-align:left;">Status</th>
                        <th style="padding:10px; text-align:left;">Sub status</th>
                        <th style="padding:10px; text-align:left;">Period end</th>
                        <th style="padding:10px; text-align:left;">Users</th>
                        <th style="padding:10px; text-align:left;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tenants as $tenant)
                        <tr style="border-top:1px solid #e5e7eb;">
                            <td style="padding:10px;">{{ $tenant->name }}</td>
                            <td style="padding:10px;">{{ $tenant->status }}</td>
                            <td style="padding:10px;">{{ $tenant->subscription_status }}</td>
                            <td style="padding:10px;">{{ optional($tenant->current_period_end)->toDateString() }}</td>
                            <td style="padding:10px;">{{ $tenant->users_count }}</td>
                            <td style="padding:10px; display:flex; gap:8px; flex-wrap:wrap;">
                                <form method="POST" action="{{ route('admin.tenants.activate', $tenant) }}">
                                    @csrf
                                    <button class="btn" style="padding:8px 12px; font-size:12px; background:#22c55e;">Activate</button>
                                </form>
                                <form method="POST" action="{{ route('admin.tenants.deactivate', $tenant) }}">
                                    @csrf
                                    <button class="btn" style="padding:8px 12px; font-size:12px; background:#ef4444;">Deactivate</button>
                                </form>
                                <form method="POST" action="{{ route('admin.tenants.impersonate', $tenant) }}">
                                    @csrf
                                    <button class="btn" style="padding:8px 12px; font-size:12px; background:#0ea5e9;">Impersonate</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="panel" style="margin-top:18px;">
        <div class="header-row" style="margin-bottom:0;">
            <div>
                <h2>Registered users</h2>
                <p style="margin:6px 0 0; color:var(--muted);">System-wide user directory visible to super admin.</p>
            </div>
            <div style="font-weight:700; color:var(--muted);">{{ $users->count() }} accounts</div>
        </div>
        <div style="overflow:auto; margin-top:12px;">
            <table style="width:100%; min-width:1180px; border-collapse:collapse; font-size:14px;">
                <thead>
                    <tr style="background:#f7f7fb;">
                        <th style="padding:10px; text-align:left;">Name</th>
                        <th style="padding:10px; text-align:left;">Email</th>
                        <th style="padding:10px; text-align:left;">Phone</th>
                        <th style="padding:10px; text-align:left;">Business</th>
                        <th style="padding:10px; text-align:left;">Branch</th>
                        <th style="padding:10px; text-align:left;">Role</th>
                        <th style="padding:10px; text-align:left;">Status</th>
                        <th style="padding:10px; text-align:left;">Access</th>
                        <th style="padding:10px; text-align:left;">Registered</th>
                        <th style="padding:10px; text-align:left;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $member)
                        @php($directoryPhone = $member->directory_phone)
                        @php($isProtectedAccount = $member->is_super_admin || (int) $member->id === (int) auth()->id())
                        <tr style="border-top:1px solid #e5e7eb; {{ $member->is_super_admin ? 'background:rgba(15,127,167,0.05);' : '' }}">
                            <td style="padding:10px; font-weight:700;">{{ $member->name }}</td>
                            <td style="padding:10px;">{{ $member->email }}</td>
                            <td style="padding:10px;">
                                <div style="font-weight:600; color:{{ $directoryPhone ? '#0f172a' : 'var(--muted)' }};">{{ $directoryPhone ?: '-' }}</div>
                            </td>
                            <td style="padding:10px;">{{ $member->business?->name ?? 'No business assigned' }}</td>
                            <td style="padding:10px;">{{ $member->branch?->name ?? 'No branch assigned' }}</td>
                            <td style="padding:10px;">{{ ucfirst($member->role) }}</td>
                            <td style="padding:10px; color:{{ $member->is_active ? '#0f9d6f' : '#dc3545' }};">
                                {{ $member->is_active ? 'Active' : 'Inactive' }}
                            </td>
                            <td style="padding:10px;">{{ $member->is_super_admin ? 'Super admin' : 'Standard user' }}</td>
                            <td style="padding:10px;">{{ optional($member->created_at)->toDateString() }}</td>
                            <td style="padding:10px;">
                                <div style="display:flex; gap:8px; flex-wrap:wrap;">
                                    @if($hasAdminUserStatusRoute && !$member->is_super_admin)
                                        <form method="POST" action="{{ route('admin.users.status', $member) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="is_active" value="{{ $member->is_active ? 0 : 1 }}">
                                            <button type="submit" style="border:1px solid {{ $member->is_active ? '#fecaca' : '#d1d5db' }}; background:{{ $member->is_active ? '#fff1f2' : '#f8fafc' }}; color:{{ $member->is_active ? '#b91c1c' : '#334155' }}; border-radius:8px; padding:6px 10px; font-size:12px; font-weight:700; cursor:pointer;">
                                                {{ $member->is_active ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </form>
                                    @endif

                                    @if($hasAdminUserDeleteRoute && !$isProtectedAccount)
                                        <form method="POST" action="{{ route('admin.users.destroy', $member) }}" onsubmit="return confirm('Delete this registered user? This action cannot be undone.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" style="border:1px solid #fecaca; background:#fff1f2; color:#b91c1c; border-radius:8px; padding:6px 10px; font-size:12px; font-weight:700; cursor:pointer;">
                                                Delete
                                            </button>
                                        </form>
                                    @elseif($isProtectedAccount)
                                        <span style="display:inline-flex; align-items:center; border-radius:999px; padding:6px 10px; font-size:12px; font-weight:700; background:#e2e8f0; color:#334155;">
                                            Protected
                                        </span>
                                    @elseif(!$hasAdminUserStatusRoute || !$hasAdminUserDeleteRoute)
                                        <span style="display:inline-flex; align-items:center; border-radius:999px; padding:6px 10px; font-size:12px; font-weight:700; background:#f8fafc; color:#64748b;">
                                            Update routes
                                        </span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr style="border-top:1px solid #e5e7eb;">
                            <td colspan="10" style="padding:14px; color:var(--muted);">No registered users found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
