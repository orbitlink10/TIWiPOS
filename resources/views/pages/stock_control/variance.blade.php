@extends('layouts.app')
@section('title', 'Stock Variance Report')
@push('styles')@include('pages.stock_control.style')@include('pages.stock_control.period_style')@endpush
@section('header')
<div class="header-row"><div><h1>Stock variance report</h1><p class="sc-intro">{{ $currentBranch->name ?? 'Current branch' }} · Posted physical counts</p></div><div class="sc-actions"><a class="sc-button primary" href="{{ route('stock-control.variance', array_merge(request()->only(['from','to','q','direction','period']), ['export'=>'csv'])) }}">Download CSV</a><a class="sc-button" href="{{ route('stock-control.periods.index') }}">← Stock Control</a></div></div>
@endsection
@section('content')
<div class="sc-page">
@if(session('status'))<div class="sc-message" role="status">{{ session('status') }}</div>@endif
@if($errors->any())<div class="sc-message error" role="alert">{{ $errors->first() }}</div>@endif
<section class="sc-card"><h2>{{ $selectedPeriod ? $selectedPeriod->reference : 'Branch stock variances' }}</h2><p class="sc-muted">Final shortages and surpluses from posted counts for this branch. Quantities compare the saved physical count with its stock snapshot. Values use captured cost prices. Totals cover all matching rows.</p>
@if($selectedPeriod && $selectedPeriod->status !== 'closed')<p class="sc-muted">This count is {{ $selectedPeriod->status }}. Post the completed count to produce its final variance report.</p>@endif
<form method="GET" action="{{ route('stock-control.variance') }}" class="sc-filters">
@if($selectedPeriod)<input type="hidden" name="period" value="{{ $selectedPeriod->id }}">@endif
<label class="sc-field">Period ends from<input type="date" name="from" value="{{ request('from') }}"></label>
<label class="sc-field">Period ends to<input type="date" name="to" value="{{ request('to') }}"></label>
<label class="sc-field">Product / SKU<input type="search" name="q" value="{{ $search }}" maxlength="100" placeholder="Find product…"></label>
<label class="sc-field">Variance<select name="direction">@foreach(['changed'=>'With variance','shortage'=>'Shortages','surplus'=>'Surpluses','balanced'=>'Balanced','all'=>'All counted products'] as $key=>$label)<option value="{{ $key }}" @selected($direction===$key)>{{ $label }}</option>@endforeach</select></label>
<button class="sc-button primary" type="submit">Apply</button><a class="sc-button" href="{{ route('stock-control.variance', $selectedPeriod ? ['period'=>$selectedPeriod->id] : []) }}">Reset</a>
@if($selectedPeriod)<a class="sc-button" href="{{ route('stock-control.variance') }}">All periods</a>@endif
</form></section>
<div class="sc-kpis"><div class="sc-kpi"><span>Matching product counts</span><strong>{{ number_format($totals->items) }}</strong></div><div class="sc-kpi"><span>Shortage units</span><strong>{{ number_format($totals->shortage) }}</strong></div><div class="sc-kpi"><span>Surplus units</span><strong>{{ number_format($totals->surplus) }}</strong></div><div class="sc-kpi"><span>Net variance units</span><strong>{{ number_format($totals->net_units) }}</strong></div></div>
@if(auth()->user()->canViewFinancials())<div class="sc-kpis"><div class="sc-kpi"><span>Shortage value</span><strong>KES {{ number_format($totals->shortage_value,2) }}</strong></div><div class="sc-kpi"><span>Surplus value</span><strong>KES {{ number_format($totals->surplus_value,2) }}</strong></div><div class="sc-kpi"><span>Net variance value</span><strong>KES {{ number_format($totals->net_value,2) }}</strong></div></div>@endif
<section class="sc-card"><h2>Variance details</h2><div class="sc-scroll"><table class="sc-table"><thead><tr><th>Period / end date</th><th>Product / SKU</th><th>Location</th><th class="number">Expected</th><th class="number">Physical</th><th class="number">Variance</th>@if(auth()->user()->canViewFinancials())<th class="number">Value (KES)</th>@endif</tr></thead><tbody>
@forelse($rows as $row)<tr><td><a href="{{ route('stock-control.periods.show',$row->stock_period_id) }}">SP-{{ str_pad((string)$row->stock_period_id,6,'0',STR_PAD_LEFT) }}</a><small>{{ \Illuminate\Support\Carbon::parse($row->ends_on)->format('d M Y') }}</small></td><td><a href="{{ route('products.show',$row->product_id) }}">{{ $row->product_name }}</a><small>{{ $row->sku }}</small></td><td>{{ $row->location }}</td><td class="number">{{ number_format($row->expected_quantity) }}</td><td class="number">{{ number_format($row->counted_quantity) }}</td><td class="number sp-variance {{ $row->variance<0 ? 'negative' : ($row->variance>0 ? 'positive' : '') }}">{{ $row->variance>0 ? '+' : '' }}{{ number_format($row->variance) }}</td>@if(auth()->user()->canViewFinancials())<td class="number sp-variance {{ $row->variance<0 ? 'negative' : ($row->variance>0 ? 'positive' : '') }}">{{ number_format($row->variance*$row->unit_cost,2) }}</td>@endif</tr>
@empty<tr><td colspan="{{ auth()->user()->canViewFinancials() ? 7 : 6 }}" class="sc-empty">No posted stock counts match these filters.</td></tr>@endforelse
</tbody></table></div>{{ $rows->links('pagination.app') }}</section>
</div>
@endsection
