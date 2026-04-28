@extends('layouts.app')

@section('title', 'Services')

@section('header')
    <div class="header-row">
        <h1>Services</h1>
        @if(auth()->user()->canAccessAbility('manage_catalog'))
            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <a class="btn" href="{{ route('services.create') }}">Add Service</a>
                <a class="btn" href="{{ route('service-categories.create') }}" style="background:#0ea5e9;">Manage Categories</a>
            </div>
        @endif
    </div>
@endsection

@section('content')
    @php($canManageCatalog = auth()->user()->canAccessAbility('manage_catalog'))
    @php($canViewFinancials = auth()->user()->canViewFinancials())
    @php($columnCount = 6 + ($canViewFinancials ? 1 : 0) + ($canManageCatalog ? 1 : 0))

    <div style="display:grid; gap:16px;">
        <div class="panel">
            <h2>Service snapshot</h2>
            <p style="color: var(--muted); margin-top:6px;">Keep service categories and pricing ready for salon, spa, and other service-based checkout flows.</p>

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

            @if(!empty($schemaMissing))
                <div style="margin-top:10px; padding:10px 12px; border-radius:10px; border:1px solid rgba(239,68,68,0.3); background:rgba(239,68,68,0.08); color:#b91c1c;">
                    Service catalog tables are missing in this environment. Run the latest migrations to enable categories and services.
                </div>
            @endif

            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(190px,1fr)); gap:12px; margin-top:16px;">
                <div class="kpi-card blue">
                    {{ $stats['active_services'] }}
                    <span>Active services</span>
                </div>
                <div class="kpi-card amber">
                    {{ $stats['inactive_services'] }}
                    <span>Inactive services</span>
                </div>
                <div class="kpi-card">
                    {{ $stats['categories_count'] }}
                    <span>Service categories</span>
                </div>
                <div class="kpi-card green">
                    {{ $stats['average_duration'] > 0 ? $stats['average_duration'].' min' : '0 min' }}
                    <span>Average duration</span>
                </div>
            </div>

            <div style="margin-top:14px; color:var(--muted); font-size:14px;">
                Average price:
                <strong style="color:var(--text);">KES {{ number_format($stats['average_price'], 2) }}</strong>
            </div>
        </div>

        <div class="panel">
            <div class="header-row" style="margin-bottom:12px;">
                <div>
                    <h2 style="margin-bottom:6px;">Service categories</h2>
                    <p style="color: var(--muted); margin:0;">Register and organize service groups before assigning services to them.</p>
                </div>
                @if($canManageCatalog)
                    <a class="btn" href="{{ route('service-categories.create') }}">Add Category</a>
                @endif
            </div>

            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:14px;">
                @forelse($serviceCategories as $category)
                    <article style="border:1px solid #e5e7eb; border-radius:16px; padding:16px; background:#fbfdff;">
                        <div style="display:flex; justify-content:space-between; gap:10px; align-items:flex-start;">
                            <div>
                                <h3 style="margin:0; font-size:20px;">{{ $category->name }}</h3>
                                <div style="margin-top:6px; color:var(--muted); font-size:14px;">
                                    {{ optional($category->parent)->name ? 'Parent: '.optional($category->parent)->name : 'No parent category' }}
                                </div>
                            </div>
                            <span style="display:inline-flex; align-items:center; justify-content:center; min-width:38px; height:38px; border-radius:12px; background:#e8f5ff; color:#0b6fa4; font-weight:800;">
                                {{ $category->services_count }}
                            </span>
                        </div>
                        <p style="margin:14px 0 0; color:var(--muted); line-height:1.6;">
                            {{ $category->description ?: 'No description added yet.' }}
                        </p>
                        <div style="margin-top:14px; font-weight:700; color:{{ $category->is_active ? '#0f9d6f' : '#b45309' }};">
                            {{ $category->is_active ? 'Active' : 'Inactive' }}
                        </div>
                    </article>
                @empty
                    <div style="padding:16px; border:1px dashed #cbd5e1; border-radius:16px; color:var(--muted);">
                        No service categories yet.
                    </div>
                @endforelse
            </div>
        </div>

        <div class="panel">
            <div class="header-row" style="margin-bottom:12px;">
                <div>
                    <h2 style="margin-bottom:6px;">Service catalog</h2>
                    <p style="color: var(--muted); margin:0;">Manage the services that should be available for service-based sales.</p>
                </div>
                @if($canManageCatalog)
                    <a class="btn" href="{{ route('services.create') }}">Add Service</a>
                @endif
            </div>

            <div style="overflow:auto;">
                <table style="width:100%; border-collapse:collapse; border-spacing:0; font-size:14px; min-width:760px;">
                    <thead>
                        <tr style="background:#f7f7fb;">
                            <th style="text-align:left; padding:10px;">Name</th>
                            <th style="text-align:left; padding:10px;">Category</th>
                            <th style="text-align:left; padding:10px;">Description</th>
                            <th style="text-align:right; padding:10px;">Duration</th>
                            @if($canViewFinancials)
                                <th style="text-align:right; padding:10px;">Cost</th>
                            @endif
                            <th style="text-align:right; padding:10px;">Price</th>
                            <th style="text-align:center; padding:10px;">Status</th>
                            @if($canManageCatalog)
                                <th style="text-align:center; padding:10px; width:220px;">Action</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($services as $service)
                            <tr style="border-top:1px solid #e5e7eb;">
                                <td style="padding:10px; font-weight:700;">{{ $service->name }}</td>
                                <td style="padding:10px;">{{ $service->category->name ?? 'Unassigned' }}</td>
                                <td style="padding:10px; color:var(--muted);">{{ $service->description ?: '-' }}</td>
                                <td style="padding:10px; text-align:right;">{{ $service->duration_minutes }} min</td>
                                @if($canViewFinancials)
                                    <td style="padding:10px; text-align:right;">KES {{ number_format($service->cost, 2) }}</td>
                                @endif
                                <td style="padding:10px; text-align:right;">KES {{ number_format($service->price, 2) }}</td>
                                <td style="padding:10px; text-align:center;">
                                    <span style="display:inline-flex; align-items:center; justify-content:center; padding:6px 12px; border-radius:999px; font-weight:700; background:{{ $service->is_active ? '#e7f8f2' : '#fff4e5' }}; color:{{ $service->is_active ? '#0f7a55' : '#b45309' }};">
                                        {{ $service->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                @if($canManageCatalog)
                                    <td style="padding:10px; text-align:center;">
                                        <div style="display:flex; gap:8px; justify-content:center; flex-wrap:wrap;">
                                            <a href="{{ route('services.edit', $service) }}" style="border:1px solid #bfdbfe; background:#eff6ff; color:#1d4ed8; border-radius:8px; padding:6px 12px; font-weight:700; text-decoration:none;">
                                                Edit
                                            </a>
                                            <form method="POST" action="{{ route('services.destroy', $service) }}" onsubmit="return confirm('Delete this service? This action cannot be undone.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" style="border:1px solid #fecaca; background:#fff1f2; color:#b91c1c; border-radius:8px; padding:6px 12px; font-weight:700; cursor:pointer;">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $columnCount }}" style="padding:12px; text-align:center; color:var(--muted);">No services yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
