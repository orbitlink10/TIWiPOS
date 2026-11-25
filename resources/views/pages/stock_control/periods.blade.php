@extends('layouts.app')
@section('title', 'Stock Periods')
@push('styles')@include('pages.stock_control.style')@include('pages.stock_control.period_style')@endpush
@section('header')
<div class="header-row"><div><h1>Stock Control</h1><p class="sc-intro">Stock periods and physical counts &middot; {{ $currentBranch->name ?? 'Current branch' }}</p></div><div class="sc-actions"><a class="sc-button" href="{{ route('stock-control.variance') }}">Variance report</a>@if(auth()->user()->canAccessAbility('adjust_stock'))<button class="sc-button primary" type="button" onclick="document.getElementById('start-stock-period').showModal()">+ Start count</button>@endif</div></div>
@endsection
@section('content')
<div class="sc-page">
@if(session('status'))<div class="sc-message" role="status">{{ session('status') }}</div>@endif
@if($errors->any())<div class="sc-message error" role="alert">{{ $errors->first() }}</div>@endif
<section class="sc-card"><h2>Stock periods</h2><p class="sc-muted">Start a count, save quantities and review differences before closing the period.</p>
    <form method="GET" action="{{ route('stock-control.periods.index') }}" class="sc-filters"><label class="sc-field">Search<input type="search" name="q" value="{{ $search }}" placeholder="Period reference or notes" maxlength="100"></label><label class="sc-field">Status<select name="status"><option value="">All periods</option>@foreach(['open'=>'Open','closed'=>'Closed / posted','cancelled'=>'Cancelled'] as $key=>$label)<option value="{{ $key }}" @selected(request('status')===$key)>{{ $label }}</option>@endforeach</select></label><button class="sc-button primary" type="submit">Apply</button><a class="sc-button" href="{{ route('stock-control.periods.index') }}">Reset</a></form>
    <div class="sc-scroll"><table class="sc-table"><thead><tr><th>Period</th><th>Dates / location</th><th>Branch</th><th>Count progress</th><th>Status</th><th>Opened by</th><th>Action</th></tr></thead><tbody>
    @forelse($periods as $period)<tr><td><a href="{{ route('stock-control.periods.show',$period->id) }}">{{ $period->reference }}</a>@if($period->notes)<small>{{ $period->notes }}</small>@endif</td><td>{{ $period->starts_on->format('d M Y') }} – {{ $period->ends_on->format('d M Y') }}<small>{{ $period->location }}</small></td><td>{{ $period->branch->name ?? 'Current branch' }}<small>{{ $period->category->name ?? 'All products' }}</small></td><td>{{ $period->counted_lines_count }} / {{ $period->lines_count }} products</td><td><span class="sp-status {{ $period->status }}">{{ ucfirst($period->status) }}</span></td><td>{{ $period->opener->name ?? 'Staff' }}<small>{{ $period->created_at->format('d M Y H:i') }}</small></td><td><a href="{{ route('stock-control.periods.show',$period->id) }}">{{ $period->status==='open' ? 'Continue count' : 'View' }}</a></td></tr>@empty<tr><td colspan="7" class="sc-empty">No stock periods found. Use Start count to open your first period.</td></tr>@endforelse
    </tbody></table></div>{{ $periods->links('pagination.app') }}
</section>
</div>
@if(auth()->user()->canAccessAbility('adjust_stock'))
<dialog class="sp-dialog small" id="start-stock-period" aria-labelledby="start-period-title"><div class="sp-head"><h2 id="start-period-title">Start stock count</h2><button class="sc-button" type="button" onclick="this.closest('dialog').close()">Close</button></div><p class="sc-muted">Count stock for {{ $currentBranch->name ?? 'the current branch' }} and review shortages and surpluses in the shop.</p>
    <form method="POST" action="{{ route('stock-control.periods.store') }}" class="sp-form">@csrf<input type="hidden" name="creation_token" value="{{ old('creation_token',(string)\Illuminate\Support\Str::uuid()) }}">
        <label class="sc-field">Branch<input type="text" value="{{ $currentBranch->name ?? 'Current branch' }}" readonly></label>
        <div class="sp-row"><label class="sc-field">Period from<input type="date" name="starts_on" value="{{ old('starts_on',today()->toDateString()) }}" max="{{ today()->toDateString() }}" required></label><label class="sc-field">Period to<input type="date" name="ends_on" value="{{ old('ends_on',today()->toDateString()) }}" max="{{ today()->toDateString() }}" required></label></div>
        @if($locations->count()>1)<label class="sc-field">Stock location<select name="location">@foreach($locations as $location)<option value="{{ $location }}" @selected(old('location','main')===$location)>{{ $location }}</option>@endforeach</select></label>@else<input type="hidden" name="location" value="main">@endif
        <label class="sc-field">Notes (optional)<textarea name="notes" rows="2" maxlength="1000" placeholder="e.g. Evening count">{{ old('notes') }}</textarea></label>
        <p class="sc-muted">The count captures current stock for this branch and location. Dates label the count period. Posting locks the branch’s uncounted closed shifts opened within these dates. Previously counted shifts are excluded. One count can be open per location.</p>
        <div class="sc-footer"><button class="sc-button" type="button" onclick="this.closest('dialog').close()">Cancel</button><button class="sc-button primary" type="submit">Open period & count stock</button></div>
    </form>
</dialog>
@if(request()->boolean('start') || ($errors->any() && old('creation_token')))<script>document.getElementById('start-stock-period').showModal();</script>@endif
@endif
@endsection
