@extends('layouts.app')

@section('title', 'Dashboard')

@push('styles')
<style>
    .dash-hero {
        background: linear-gradient(135deg, #00a5ff 0%, #0b7c73 100%);
        color: #fff;
        padding: 20px 22px;
        border-radius: 16px;
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 16px;
        align-items: center;
        box-shadow: 0 18px 50px rgba(0,0,0,0.14);
    }
    .dash-hero h2 { margin: 0 0 8px; font-size: 26px; }
    .dash-hero p { margin: 0; color: rgba(255,255,255,0.9); }
    .dash-chip { display:inline-flex; align-items:center; gap:8px; padding:8px 12px; border-radius:999px; background:rgba(255,255,255,0.18); font-weight:700; }
    .quick-actions { display:flex; gap:10px; flex-wrap:wrap; }
    .quick-actions .btn { background:#fff; color:#0f172a; border:1px solid var(--border); }
    .stats-grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(200px,1fr)); gap:12px; margin-top:16px; }
    .stat-card {
        background:#fff;
        border:1px solid var(--border);
        border-radius:14px;
        padding:14px;
        box-shadow:0 12px 30px rgba(0,0,0,0.05);
    }
    .stat-label { color: var(--muted); font-weight:600; margin-bottom:6px; }
    .stat-value { font-size:22px; font-weight:800; color:#0f172a; }
    .badge-soft { display:inline-block; padding:6px 10px; border-radius:10px; background:#e8f5ff; color:#0b6fa4; font-weight:700; font-size:13px; }
</style>
@endpush

@section('header')
    <div class="dash-hero">
        <div>
            <div class="dash-chip">Live store snapshot</div>
            <h2>Welcome back</h2>
            <p>Track sales performance, stock health, and today’s profit at a glance.</p>
            <div class="quick-actions" style="margin-top:10px;">
                <a class="btn" href="{{ route('sale') }}">Open POS</a>
                <a class="btn" href="{{ route('stock') }}">Stock</a>
                <a class="btn" href="{{ route('content.create') }}">New Page/Post</a>
                <a class="btn" href="{{ route('summary') }}">Today’s Summary</a>
            </div>
        </div>
        <div style="justify-self:end; text-align:right;">
            <div class="badge-soft">Today</div>
            <div style="font-size:28px; font-weight:800; margin-top:6px;">KES {{ number_format($stats['today'], 2) }}</div>
            <div style="color:rgba(255,255,255,0.9);">Sales closed</div>
        </div>
    </div>
@endsection

@section('content')
    <div class="content" style="grid-template-columns: 1fr;">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">{{ $stats['month_name'] }} Sales</div>
                <div class="stat-value">KES {{ number_format($stats['month_sales'], 2) }}</div>
                <div class="badge-soft">Month to date</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">This Week</div>
                <div class="stat-value">KES {{ number_format($stats['this_week'], 2) }}</div>
                <div class="badge-soft" style="background:#e4f7ec;color:#117a39;">Week to date</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Today’s Sales</div>
                <div class="stat-value">KES {{ number_format($stats['today'], 2) }}</div>
                <div class="badge-soft">Daily run-rate</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Today’s Profit</div>
                <div class="stat-value">KES {{ number_format($stats['today_profit'], 2) }}</div>
                <div class="badge-soft" style="background:#fff4e5;color:#b45b00;">After cost</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Low Stock</div>
                <div class="stat-value">{{ $stats['low_stock'] }}</div>
                <div class="badge-soft" style="background:#ffe4e6;color:#7f1d1d;">Needs attention</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Out of Stock</div>
                <div class="stat-value">{{ $stats['out_of_stock'] }}</div>
                <div class="badge-soft" style="background:#fef3c7;color:#92400e;">Unavailable</div>
            </div>
        </div>
    </div>
@endsection
