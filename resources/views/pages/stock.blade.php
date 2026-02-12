@extends('layouts.app')

@section('title', 'Stock')

@push('styles')
<style>
    .stock-panel {
        display: grid;
        gap: 18px;
    }

    .stock-lead {
        margin: 6px 0 0;
        color: var(--muted);
        font-weight: 600;
    }

    .stock-metrics {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
        gap: 12px;
    }

    .metric-card {
        border-radius: 14px;
        padding: 14px;
        border: 1px solid var(--border);
        background: linear-gradient(180deg, #ffffff 0%, #f9fbff 100%);
    }

    .metric-label {
        color: var(--muted);
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        font-weight: 700;
    }

    .metric-value {
        margin-top: 6px;
        font-size: 28px;
        font-weight: 800;
        color: var(--text);
        letter-spacing: -0.02em;
    }

    .metric-value.out { color: #b42318; }
    .metric-value.low { color: #b54708; }
    .metric-value.total { color: #175cd3; }

    .stock-status {
        padding: 11px 12px;
        border-radius: 12px;
        border: 1px solid rgba(15,157,111,0.3);
        background: rgba(15,157,111,0.09);
        color: #0b6b4e;
        font-weight: 700;
    }

    .stock-table-wrap {
        border: 1px solid var(--border);
        border-radius: 14px;
        overflow: auto;
    }

    .stock-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 860px;
        font-size: 14px;
    }

    .stock-table th {
        background: #eef3fb;
        color: #2d3f5f;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        font-size: 11px;
        font-weight: 800;
        text-align: left;
        padding: 12px 14px;
        border-bottom: 1px solid var(--border);
    }

    .stock-table th.align-right,
    .stock-table td.align-right {
        text-align: right;
    }

    .stock-table td {
        padding: 12px 14px;
        border-bottom: 1px solid #ecf1f7;
        vertical-align: middle;
    }

    .stock-table tr:last-child td { border-bottom: none; }

    .stock-table tbody tr:hover {
        background: #f8fbff;
    }

    .serial-empty { color: #99a4b5; }

    .qty-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 44px;
        padding: 4px 10px;
        border-radius: 999px;
        font-weight: 800;
        font-size: 12px;
    }

    .qty-pill--ok {
        background: rgba(15,157,111,0.12);
        color: #067647;
    }

    .qty-pill--low {
        background: rgba(245,158,11,0.17);
        color: #9a4a00;
    }

    .qty-pill--out {
        background: rgba(220,53,69,0.14);
        color: #b42318;
    }

    .stock-empty {
        padding: 18px;
        text-align: center;
        color: var(--muted);
        font-weight: 600;
    }

    .btn-inline {
        padding: 8px 12px;
        font-size: 12px;
        border-radius: 9px;
    }
</style>
@endpush

@section('header')
    <div class="header-row">
        <div>
            <h1>Stock</h1>
            <p class="stock-lead">Monitor levels and correct quantities before checkout.</p>
        </div>
        <a class="btn" href="{{ route('products') }}">Manage Products</a>
    </div>
@endsection

@section('content')
    <div class="panel stock-panel">
        <div>
            <h2>Inventory at a glance</h2>
            <p class="stock-lead">Category listing with current stock quantity per category.</p>
        </div>

        @if (session('status'))
            <div class="stock-status">{{ session('status') }}</div>
        @endif

        <div class="stock-metrics">
            <div class="metric-card">
                <div class="metric-label">Out of stock categories</div>
                <div class="metric-value out">{{ $outOfStock }}</div>
            </div>
            <div class="metric-card">
                <div class="metric-label">Low stock categories</div>
                <div class="metric-value low">{{ $lowStock }}</div>
            </div>
            <div class="metric-card">
                <div class="metric-label">Total units on hand</div>
                <div class="metric-value total">{{ $totalItems }}</div>
            </div>
        </div>

        <div class="stock-table-wrap">
            <table class="stock-table">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th class="align-right">Quantity</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $category)
                        <tr>
                            <td>{{ $category['category_name'] }}</td>
                            <td class="align-right">{{ $category['on_hand'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="stock-empty">No categories recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
