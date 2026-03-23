@extends('layouts.app')

@section('title', 'Admin Console')

@section('header')
    <div class="header-row">
        <h1>Admin Console</h1>
        <a class="btn" href="{{ route('home') }}">Back</a>
    </div>
@endsection

@section('content')
    <div class="panel">
        <h2>All businesses</h2>
        @if (session('status'))
            <div style="margin-top:10px; padding:10px 12px; border-radius:10px; border:1px solid rgba(16,185,129,0.3); background:rgba(16,185,129,0.1); color:#065f46;">
                {{ session('status') }}
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
            <table style="width:100%; min-width:960px; border-collapse:collapse; font-size:14px;">
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
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $member)
                        <tr style="border-top:1px solid #e5e7eb; {{ $member->is_super_admin ? 'background:rgba(15,127,167,0.05);' : '' }}">
                            <td style="padding:10px; font-weight:700;">{{ $member->name }}</td>
                            <td style="padding:10px;">{{ $member->email }}</td>
                            <td style="padding:10px;">{{ $member->phone ?: '-' }}</td>
                            <td style="padding:10px;">{{ $member->business?->name ?? 'No business assigned' }}</td>
                            <td style="padding:10px;">{{ $member->branch?->name ?? 'No branch assigned' }}</td>
                            <td style="padding:10px;">{{ ucfirst($member->role) }}</td>
                            <td style="padding:10px; color:{{ $member->is_active ? '#0f9d6f' : '#dc3545' }};">
                                {{ $member->is_active ? 'Active' : 'Inactive' }}
                            </td>
                            <td style="padding:10px;">{{ $member->is_super_admin ? 'Super admin' : 'Standard user' }}</td>
                            <td style="padding:10px;">{{ optional($member->created_at)->toDateString() }}</td>
                        </tr>
                    @empty
                        <tr style="border-top:1px solid #e5e7eb;">
                            <td colspan="9" style="padding:14px; color:var(--muted);">No registered users found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
