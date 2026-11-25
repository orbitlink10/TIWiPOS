@extends('layouts.app')
@section('title', 'Shifts')
@push('styles')@include('pages.shifts.style')@endpush
@section('header')
<div class="header-row"><div><h1>Shifts</h1><p class="shift-intro">Cashier shifts &middot; {{ $currentBranch->name ?? 'Current branch' }}</p></div><div class="shift-actions">@if($currentShift)<a class="shift-button primary" href="{{ route('sale') }}">Open POS</a>@endif @if(auth()->user()->isPosOnly())<form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="shift-button">Sign out</button></form>@endif</div></div>
@endsection
@section('content')
<div class="shift-page">
    @if(session('status'))<div class="shift-message" role="status">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="shift-message error" role="alert">{{ $errors->first() }}</div>@endif
    @if($currentShift)
    <section class="shift-card"><div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap"><div><h2>Your shift #{{ $currentShift->id }} is open</h2><p class="shift-muted">Opened {{ $currentShift->opened_at->format('d M Y H:i') }} &middot; {{ auth()->user()->name }}</p></div><a class="shift-button" href="{{ route('shifts.show',$currentShift->id) }}">View / close shift</a></div>
        <div class="shift-grid"><div class="shift-metric"><small>Sales</small><strong>KES {{ number_format($currentSummary['sales_total_cents']/100,2) }}</strong></div><div class="shift-metric"><small>Receipts</small><strong>{{ $currentSummary['sales_count'] }}</strong></div><div class="shift-metric"><small>Expected cash</small><strong>KES {{ number_format($currentSummary['expected_cash_cents']/100,2) }}</strong></div></div>
    </section>
    @elseif($otherShift)
    <section class="shift-card"><h2>You have an open shift</h2><p class="shift-muted">Your shift is open in {{ $otherShift->branch->name ?? 'another branch' }}. Return to that branch and close it before opening a new shift.</p></section>
    @else
    <section class="shift-card"><h2>Open your shift</h2><p class="shift-muted">Enter the cash float already in your till. Sales will be recorded against your shift until you close it.</p><form method="POST" action="{{ route('shifts.open') }}" class="shift-form">@csrf<label class="shift-field">Opening cash (KES)<input name="opening_cash" type="number" min="0" max="9999999999.99" step="0.01" value="{{ old('opening_cash','0.00') }}" required></label><label class="shift-field">Notes (optional)<input name="notes" maxlength="1000" value="{{ old('notes') }}" placeholder="Opening notes"></label><button type="submit" class="shift-button primary">Open shift & start selling</button></form></section>
    @endif
    <section class="shift-card"><h2>{{ auth()->user()->canViewAllSales() ? 'Branch shift history' : 'Your shift history' }}</h2>
        <form method="GET" action="{{ route('shifts.index') }}" class="shift-form"><label class="shift-field" style="max-width:220px">Status<select name="status"><option value="">All shifts</option><option value="open" @selected(request('status')==='open')>Open</option><option value="pending_stock_count" @selected(request('status')==='pending_stock_count')>Pending stock count</option><option value="locked" @selected(request('status')==='locked')>Locked</option></select></label><button type="submit" class="shift-button">Apply</button><a class="shift-button" href="{{ route('shifts.index') }}">Reset</a></form>
        <div class="shift-scroll"><table class="shift-table"><thead><tr><th>Shift / cashier</th><th>Opened</th><th>Closed</th><th class="number">Opening cash</th><th class="number">Cash difference</th><th>Status</th><th>Action</th></tr></thead><tbody>@forelse($shifts as $shift)<tr><td><strong>#{{ $shift->id }}</strong><small>{{ $shift->user->name ?? 'Former user' }}</small></td><td>{{ $shift->opened_at->format('d M Y H:i') }}</td><td>{{ $shift->closed_at?->format('d M Y H:i') ?? '—' }}</td><td class="number">{{ number_format($shift->opening_cash_cents/100,2) }}</td><td class="number">{{ $shift->status==='closed' ? number_format($shift->cash_variance_cents/100,2) : '—' }}</td><td><span class="shift-badge {{ $shift->status==='closed' ? 'closed' : '' }}">{{ $shift->status_label }}</span></td><td><a href="{{ route('shifts.show',$shift->id) }}">View</a></td></tr>@empty<tr><td colspan="7" class="shift-empty">No shifts found.</td></tr>@endforelse</tbody></table></div>{{ $shifts->links('pagination.app') }}
    </section>
</div>
@endsection
