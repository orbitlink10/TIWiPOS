@extends('layouts.app')

@section('title', 'Platform Overview')


@push('styles')
<style>
.admin-eyebrow{margin:0 0 6px;color:#0f7fa7;font-size:11px;font-weight:800;letter-spacing:.12em;text-transform:uppercase}.admin-subtitle{margin:6px 0 0;color:var(--muted);font-size:13px;line-height:1.6}.admin-console{min-width:0}.admin-welcome{display:flex;align-items:center;gap:16px;padding:20px 24px;border:1px solid var(--border);border-radius:16px;background:linear-gradient(110deg,#fff,#eef9fc);margin-bottom:18px}.admin-avatar{display:grid;place-items:center;width:52px;height:52px;border-radius:15px;background:#dceefa;color:#1057b0;font-size:24px;font-weight:800;flex-shrink:0}.admin-identity{display:grid;gap:4px;min-width:0}.admin-identity strong{font-size:17px}.admin-identity span{font-size:12px;color:var(--muted);overflow-wrap:anywhere}.admin-identity .admin-role{color:#0b7691;font-weight:700}.admin-link{margin-left:auto;color:#0f7fa7;font-size:13px;font-weight:700;text-decoration:none}.admin-link:hover{text-decoration:underline}.admin-metrics{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:22px}.admin-metrics>div{display:grid;gap:8px;background:#fff;border:1px solid var(--border);border-radius:14px;padding:20px}.admin-metrics span{font-size:12px;color:var(--muted);font-weight:700}.admin-metrics strong{font-size:30px;line-height:1.2;color:#10385e}.admin-metrics small{font-size:11px;color:var(--muted);line-height:1.5}.admin-section-head{display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap}.admin-console h2{font-size:20px;margin:0}.admin-result-count{font-size:12px;color:var(--muted);white-space:nowrap}.admin-toolbar{display:flex;gap:14px;margin-top:20px;align-items:end}.admin-toolbar label{display:grid;gap:7px;flex:1;min-width:0}.admin-toolbar label:last-child{flex:0 1 210px}.admin-toolbar label>span{font-size:12px;font-weight:700;color:#526473}.admin-toolbar :is(input,select){width:100%;box-sizing:border-box;border:1px solid var(--border);border-radius:9px;padding:11px 12px;min-height:44px;font:inherit;font-size:13px;background:white;color:var(--text)}.admin-console :is(input,select,button,a):focus-visible{outline:3px solid #8cbaf2;outline-offset:2px}.admin-console table{font-size:12px!important}.admin-console th{font-size:10px;text-transform:uppercase;letter-spacing:.05em;color:#5f6f86;padding:12px!important}.admin-console td{vertical-align:middle;overflow-wrap:anywhere;padding:14px 12px!important}.admin-console td:last-child{display:table-cell!important}.admin-console td:last-child>div,.admin-console td:last-child{gap:6px!important}.admin-console td form{display:inline-block;margin:3px 3px 3px 0}.admin-console td button{font-family:inherit!important;box-shadow:none!important;border-radius:7px!important;min-height:36px;white-space:nowrap}.admin-contact{display:block;margin-top:5px;color:var(--muted);font-size:11px}.admin-badge{display:inline-block;border-radius:20px;padding:5px 9px;font-size:11px;font-weight:700;white-space:nowrap}.admin-badge.positive{background:#e3f7ee;color:#087b54}.admin-badge.warning{background:#fff2db;color:#9b5900}.admin-badge.muted{background:#eef1f6;color:#526473}.admin-empty{padding:24px;text-align:center;color:var(--muted);font-size:13px}@media(min-width:651px){#registered-users td:nth-child(2),#registered-users td:nth-child(3),#registered-users td:nth-child(n+6){white-space:nowrap}#registered-users td:first-child,#registered-users td:nth-child(4){min-width:130px}}.admin-console [hidden]{display:none!important}
@media(max-width:1100px){.admin-metrics{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:650px){.admin-welcome{padding:16px;flex-wrap:wrap}.admin-link{margin-left:68px}.admin-metrics{gap:10px}.admin-metrics>div{padding:14px}.admin-metrics strong{font-size:26px}.admin-toolbar{flex-direction:column;align-items:stretch}.admin-toolbar label:last-child{flex:auto}.admin-toolbar :is(input,select){font-size:16px}.admin-console .panel{padding:16px}.admin-console table{min-width:0!important}.admin-console thead{position:absolute;width:1px;height:1px;overflow:hidden;clip-path:inset(50%)}.admin-console tbody{display:block}.admin-console tr{display:block;border:1px solid var(--border)!important;border-radius:10px;margin-bottom:12px;padding:8px;background:#fff}.admin-console td,.admin-console td:last-child{display:grid!important;grid-template-columns:90px minmax(0,1fr);gap:8px!important;padding:8px!important;font-size:12px}.admin-console td:before{content:attr(data-label);color:var(--muted);font-size:11px;font-weight:700}.admin-console td:first-child{background:#f5f8fc;border-radius:6px}.admin-console td button{min-height:44px}.admin-console td form{margin:0 0 5px}.admin-console td .admin-badge{justify-self:start}.admin-console td:last-child>div{min-width:0}}
</style>
@endpush

@section('header')
    <div class="header-row">
        <div><p class="admin-eyebrow">Platform administration</p><h1>Admin Console</h1><p class="admin-subtitle">Manage businesses, subscriptions and account access.</p></div>
        <p class="admin-empty" data-directory-empty hidden>No users match your search.</p>
    </section>
    </div>
<script>
(() => {
    document.querySelectorAll('[data-directory]').forEach(directory => {
        const rows = Array.from(directory.querySelectorAll('[data-directory-row]'));
        const headings = Array.from(directory.querySelectorAll('thead th')).map(th => th.textContent.trim());
        rows.forEach(row => Array.from(row.cells).forEach((cell, index) => cell.dataset.label = headings[index]));
        const search = directory.querySelector('[data-directory-search]');
        const filter = directory.querySelector('[data-directory-filter]');
        const update = () => {
            const term = search.value.trim().toLocaleLowerCase();
            rows.forEach(row => {
                const matchesState = !filter.value || (filter.value === 'super-admin' ? row.dataset.superAdmin === 'true' : row.dataset.state === filter.value);
                row.hidden = !matchesState || !(row.dataset.searchText || row.textContent).toLocaleLowerCase().includes(term);
            });
            const count = rows.filter(row => !row.hidden).length;
            directory.querySelector('[data-result-count]').textContent = count + ' of ' + rows.length + (directory.dataset.directory === 'users' ? ' accounts' : ' businesses');
            directory.querySelector('[data-directory-empty]').hidden = count !== 0;
        };
        search.addEventListener('input', update);
        filter.addEventListener('change', update);
        update();
    });
})();
</script>
@endsection

@section('content')
    @php
        $expiredCount = $tenants->filter(fn($tenant) => $tenant->current_period_end && $tenant->current_period_end->isPast())->count();
        $activeCount = $tenants->filter(fn($tenant) => $tenant->status === 'active' && $tenant->subscription_status === 'active' && (!$tenant->current_period_end || $tenant->current_period_end->isFuture()))->count();
    @endphp
    <div class="admin-console">
    <section class="admin-welcome" aria-label="Administrator profile">
        <div class="admin-avatar" aria-hidden="true">{{ mb_substr(auth()->user()->name, 0, 1) }}</div>
        <div class="admin-identity"><strong>{{ auth()->user()->name }}</strong><span>{{ auth()->user()->email }}</span><span class="admin-role">Super administrator</span></div>
        <a href="#registered-users" class="admin-link">Manage registered users &rarr;</a>
    </section>
    <section class="admin-metrics" aria-label="Platform summary">
        <div><span>Total businesses</span><strong>{{ $tenants->count() }}</strong><small>Across the platform</small></div>
        <div><span>Active subscriptions</span><strong>{{ $activeCount }}</strong><small>Active businesses with current access</small></div>
        <div><span>Expired periods</span><strong>{{ $expiredCount }}</strong><small>Subscription end date has passed</small></div>
        <div><span>Registered users</span><strong>{{ $users->count() }}</strong><small>{{ $users->where('is_active', true)->count() }} active accounts</small></div>
    </section>
    @php($hasAdminUserStatusRoute = Route::has('admin.users.status'))
    @php($hasAdminUserDeleteRoute = Route::has('admin.users.destroy'))

    <section class="panel admin-directory" data-directory="businesses" aria-labelledby="businesses-title">
        <div class="admin-section-head"><div><h2 id="businesses-title">All businesses</h2><p class="admin-subtitle">Review business access and subscription periods.</p></div><span class="admin-result-count" data-result-count role="status"></span></div>
        <div class="admin-toolbar"><label><span>Search businesses</span><input type="search" data-directory-search placeholder="Business name or contact details..."></label><label><span>Subscription</span><select data-directory-filter><option value="">All subscriptions</option><option value="current">Current</option><option value="expired">Expired</option><option value="inactive">Inactive</option></select></label></div>
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
        <div role="region" aria-label="Scrollable directory" tabindex="0" style="overflow:auto; margin-top:12px;">
            <table style="width:100%; min-width:780px; border-collapse:collapse; font-size:14px;">
                <thead>
                    <tr style="background:#f7f7fb;">
                        <th scope="col" style="padding:10px; text-align:left;">Name</th>
                        <th scope="col" style="padding:10px; text-align:left;">Status</th>
                        <th scope="col" style="padding:10px; text-align:left;">Subscription</th>
                        <th scope="col" style="padding:10px; text-align:left;">Period end</th>
                        <th scope="col" style="padding:10px; text-align:left;">Users</th>
                        <th scope="col" style="padding:10px; text-align:left;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tenants as $tenant)
                        @php($expired = $tenant->current_period_end && $tenant->current_period_end->isPast())
                        <tr data-directory-row data-search-text="{{ $tenant->name.' '.$tenant->billing_email.' '.$tenant->phone }}" data-state="{{ $expired ? 'expired' : (($tenant->status === 'active' && $tenant->subscription_status === 'active') ? 'current' : 'inactive') }}" style="border-top:1px solid #e5e7eb;">
                            <td style="padding:10px;"><div><strong>{{ $tenant->name }}</strong><small class="admin-contact">{{ $tenant->billing_email ?: $tenant->phone }}</small></div></td>
                            <td style="padding:10px;"><span class="admin-badge {{ $tenant->status === 'active' ? 'positive' : 'muted' }}">{{ ucfirst($tenant->status) }}</span></td>
                            <td style="padding:10px;"><span class="admin-badge {{ $expired ? 'warning' : ($tenant->subscription_status === 'active' ? 'positive' : 'muted') }}">{{ $expired ? 'Expired' : ucfirst($tenant->subscription_status) }}</span></td>
                            <td style="padding:10px;">{{ $tenant->current_period_end?->format('d M Y') ?? 'No end date' }}</td>
                            <td style="padding:10px;">{{ $tenant->users_count }}</td>
                            <td style="padding:10px;"><div class="admin-business-actions">
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
                            </div></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="admin-empty" data-directory-empty hidden>No businesses match your search.</p>
    </section>

    <section class="panel admin-directory" id="registered-users" data-directory="users" aria-labelledby="users-title" style="margin-top:18px;">
        <div class="header-row" style="margin-bottom:0;">
            <div>
                <h2 id="users-title">Registered users</h2>
                <p style="margin:6px 0 0; color:var(--muted);">System-wide user directory visible to super admin.</p>
            </div>
            <span class="admin-result-count" data-result-count role="status"></span>
        </div>
        <div class="admin-toolbar"><label><span>Search registered users</span><input type="search" data-directory-search placeholder="Name, email, phone or business..."></label><label><span>Account status</span><select data-directory-filter><option value="">All accounts</option><option value="active">Active</option><option value="inactive">Inactive</option><option value="super-admin">Super admins</option></select></label></div>
        <div role="region" aria-label="Scrollable directory" tabindex="0" style="overflow:auto; margin-top:12px;">
            <table style="width:100%; min-width:1180px; border-collapse:collapse; font-size:14px;">
                <thead>
                    <tr style="background:#f7f7fb;">
                        <th scope="col" style="padding:10px; text-align:left;">Name</th>
                        <th scope="col" style="padding:10px; text-align:left;">Email</th>
                        <th scope="col" style="padding:10px; text-align:left;">Phone</th>
                        <th scope="col" style="padding:10px; text-align:left;">Business</th>
                        <th scope="col" style="padding:10px; text-align:left;">Branch</th>
                        <th scope="col" style="padding:10px; text-align:left;">Role</th>
                        <th scope="col" style="padding:10px; text-align:left;">Status</th>
                        <th scope="col" style="padding:10px; text-align:left;">Access</th>
                        <th scope="col" style="padding:10px; text-align:left;">Registered</th>
                        <th scope="col" style="padding:10px; text-align:left;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $member)
                        @php($directoryPhone = $member->directory_phone)
                        @php($isProtectedAccount = $member->is_super_admin || (int) $member->id === (int) auth()->id())
                        <tr data-directory-row data-state="{{ $member->is_active ? 'active' : 'inactive' }}" data-super-admin="{{ $member->is_super_admin ? 'true' : 'false' }}" style="border-top:1px solid #e5e7eb; {{ $member->is_super_admin ? 'background:rgba(15,127,167,0.05);' : '' }}">
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
        <p class="admin-empty" data-directory-empty hidden>No users match your search.</p>
    </section>
    </div>
<script>
(() => {
    document.querySelectorAll('[data-directory]').forEach(directory => {
        const rows = Array.from(directory.querySelectorAll('[data-directory-row]'));
        const headings = Array.from(directory.querySelectorAll('thead th')).map(th => th.textContent.trim());
        rows.forEach(row => Array.from(row.cells).forEach((cell, index) => cell.dataset.label = headings[index]));
        const search = directory.querySelector('[data-directory-search]');
        const filter = directory.querySelector('[data-directory-filter]');
        const update = () => {
            const term = search.value.trim().toLocaleLowerCase();
            rows.forEach(row => {
                const matchesState = !filter.value || (filter.value === 'super-admin' ? row.dataset.superAdmin === 'true' : row.dataset.state === filter.value);
                row.hidden = !matchesState || !(row.dataset.searchText || row.textContent).toLocaleLowerCase().includes(term);
            });
            const count = rows.filter(row => !row.hidden).length;
            directory.querySelector('[data-result-count]').textContent = count + ' of ' + rows.length + (directory.dataset.directory === 'users' ? ' accounts' : ' businesses');
            directory.querySelector('[data-directory-empty]').hidden = count !== 0;
        };
        search.addEventListener('input', update);
        filter.addEventListener('change', update);
        update();
    });
})();
</script>
@endsection
