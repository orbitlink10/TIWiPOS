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
    .monthly-panel { margin-top: 16px; padding: 16px; }
    .monthly-header { display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:12px; }
    .monthly-title { margin:0; font-size:19px; font-weight:800; color:#0f172a; }
    .monthly-subtitle { margin:2px 0 0; color:var(--muted); font-size:13px; font-weight:600; }
    .monthly-layout { display:grid; grid-template-columns: minmax(0, 1.6fr) minmax(0, 1fr); gap:14px; align-items:start; }
    .monthly-chart-wrap {
        border:1px solid var(--border);
        border-radius:12px;
        background:#f8fbff;
        padding:12px;
        min-height:280px;
    }
    .monthly-chart-wrap canvas { width:100%; height:250px; }
    .monthly-table-wrap {
        border:1px solid var(--border);
        border-radius:12px;
        background:#fff;
        overflow:auto;
    }
    .monthly-table { width:100%; border-collapse:collapse; font-size:13px; }
    .monthly-table th, .monthly-table td { padding:10px 12px; border-bottom:1px solid #edf2f9; text-align:right; white-space:nowrap; }
    .monthly-table th:first-child, .monthly-table td:first-child { text-align:left; }
    .monthly-table thead th { color:var(--muted); font-weight:700; background:#f8fbff; position:sticky; top:0; }
    .monthly-table tfoot td { font-weight:800; background:#f8fbff; }
    @media (max-width: 980px) {
        .monthly-layout { grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('header')
    <div class="dash-hero">
        <div>
            <div class="dash-chip">Live store snapshot</div>
            <h2>Welcome back</h2>
            <p>
                @if($canViewProfit ?? false)
                    Track sales performance, stock health, and today's profit at a glance.
                @else
                    Track sales performance and stock health at a glance.
                @endif
            </p>
            @isset($subscriptionActive)
                @if(!$subscriptionActive)
                    <div style="margin-top:8px; padding:8px 10px; border-radius:10px; background:#fff1f2; color:#991b1b; font-weight:700;">
                        Limited mode: subscription inactive. Use Billing to pay and restore access.
                    </div>
                @endif
            @endisset
            <div class="quick-actions" style="margin-top:10px;">
                <a class="btn" href="{{ route('sale') }}">Open POS</a>
                <a class="btn" href="{{ route('stock') }}">Stock</a>
                <a class="btn" href="{{ route('summary') }}">Today's Summary</a>
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
    @php
        $monthlyRows = collect($monthlyPerformance ?? []);
        $yearSalesTotal = $monthlyRows->sum('sales');
        $yearProfitTotal = $monthlyRows->sum('profit');
    @endphp
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
                <div class="stat-label">Today's Sales</div>
                <div class="stat-value">KES {{ number_format($stats['today'], 2) }}</div>
                <div class="badge-soft">Daily run-rate</div>
            </div>
            @if($canViewProfit ?? false)
                <div class="stat-card">
                    <div class="stat-label">Today's Profit</div>
                    <div class="stat-value">KES {{ number_format($stats['today_profit'], 2) }}</div>
                    <div class="badge-soft" style="background:#fff4e5;color:#b45b00;">After cost</div>
                </div>
            @endif
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

        <div class="stat-card monthly-panel">
            <div class="monthly-header">
                <div>
                    <h3 class="monthly-title">{{ now()->year }} Monthly Sales &amp; Profit</h3>
                    <p class="monthly-subtitle">Month-by-month totals and trend chart.</p>
                </div>
                <span class="badge-soft">Year to date view</span>
            </div>
            <div class="monthly-layout">
                <div class="monthly-chart-wrap">
                    <canvas id="monthlySalesProfitChart" aria-label="Monthly sales and profit chart"></canvas>
                </div>
                <div class="monthly-table-wrap">
                    <table class="monthly-table">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Sales</th>
                                @if($canViewProfit ?? false)
                                    <th>Profit</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($monthlyRows as $row)
                                <tr>
                                    <td>{{ $row['month'] }}</td>
                                    <td>KES {{ number_format($row['sales'], 2) }}</td>
                                    @if($canViewProfit ?? false)
                                        <td>KES {{ number_format($row['profit'], 2) }}</td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ ($canViewProfit ?? false) ? 3 : 2 }}">No monthly data yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr>
                                <td>Total</td>
                                <td>KES {{ number_format($yearSalesTotal, 2) }}</td>
                                @if($canViewProfit ?? false)
                                    <td>KES {{ number_format($yearProfitTotal, 2) }}</td>
                                @endif
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        (function () {
            var rows = @json($monthlyPerformance ?? []);
            var chartElement = document.getElementById('monthlySalesProfitChart');
            if (!chartElement || !rows.length || typeof Chart === 'undefined') return;

            var currency = new Intl.NumberFormat('en-KE', {
                style: 'currency',
                currency: 'KES',
                maximumFractionDigits: 0,
            });

            var labels = rows.map(function (item) { return item.month; });
            var salesSeries = rows.map(function (item) { return Number(item.sales || 0); });
            var datasets = [{
                label: 'Monthly Sales',
                data: salesSeries,
                borderColor: '#0f7fa7',
                backgroundColor: 'rgba(15,127,167,0.15)',
                pointBackgroundColor: '#0f7fa7',
                pointRadius: 3,
                borderWidth: 2,
                tension: 0.3
            }];

            @if($canViewProfit ?? false)
                datasets.push({
                    label: 'Monthly Profit',
                    data: rows.map(function (item) { return Number(item.profit || 0); }),
                    borderColor: '#16a34a',
                    backgroundColor: 'rgba(22,163,74,0.15)',
                    pointBackgroundColor: '#16a34a',
                    pointRadius: 3,
                    borderWidth: 2,
                    tension: 0.3
                });
            @endif

            new Chart(chartElement, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'top' },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    return context.dataset.label + ': ' + currency.format(context.parsed.y || 0);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function (value) {
                                    return currency.format(value);
                                }
                            },
                            grid: {
                                color: 'rgba(15,23,42,0.08)'
                            }
                        },
                        x: {
                            grid: { display: false }
                        }
                    }
                }
            });
        })();
    </script>
@endpush
