@extends('layouts.app')

@section('title', 'Dashboard')

@section('header')
    <div class="header-row">
        <h1>Monthly Sales Overview</h1>
        <a class="btn" href="{{ route('summary') }}">🗂️ View Today’s Sales Summary</a>
    </div>
@endsection

@section('content')
    <div class="content">
        <div class="panel">
            <h2>{{ $stats['month_name'] }} Sales</h2>
            <div class="kpi">
                KES {{ number_format($stats['month_sales'], 2) }}
                <small>{{ $stats['month_name'] }} Sales</small>
            </div>
        </div>
        <div class="cards">
            <div class="kpi-card purple">
                <div style="font-size:26px;">{{ $stats['out_of_stock'] }}</div>
                <span>Out Of Stock</span>
            </div>
            <div class="kpi-card green">
                <div style="font-size:26px;">KES {{ number_format($stats['this_month'], 2) }}</div>
                <span>This Month</span>
            </div>
            <div class="kpi-card amber">
                <div style="font-size:26px;">KES {{ number_format($stats['this_week'], 2) }}</div>
                <span>This Week</span>
            </div>
            <div class="kpi-card blue">
                <div style="font-size:26px;">KES {{ number_format($stats['today'], 2) }}</div>
                <span>Today</span>
            </div>
            <div class="kpi-card amber" style="background:#f97316;">
                <div style="font-size:26px;">{{ $stats['low_stock'] }}</div>
                <span>Low Stock (≤ alert)</span>
            </div>
            <div class="kpi-card" style="background:#0ea5e9;">
                <div style="font-size:26px;">KES {{ number_format($stats['today_profit'], 2) }}</div>
                <span>Today’s Profit</span>
            </div>
        </div>
    </div>
@endsection
