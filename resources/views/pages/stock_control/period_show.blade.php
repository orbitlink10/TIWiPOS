@extends('layouts.app')
@section('title', $period->reference.' - Stock Count')
@push('styles')@include('pages.stock_control.style')@include('pages.stock_control.period_style')@endpush
@section('header')
<div class="header-row"><div><h1>{{ $period->reference }}</h1><p class="sc-intro">{{ $currentBranch->name ?? 'Current branch' }} &middot; {{ $period->location }} &middot; {{ $period->starts_on->format('d M Y') }} – {{ $period->ends_on->format('d M Y') }}</p></div><div class="sc-actions"><a class="sc-button" href="{{ route('stock-control.periods.index') }}">&larr; Stock periods</a>@if($period->status==='open' && auth()->user()->canAccessAbility('adjust_stock'))<a class="sc-button primary" href="#count-sheet">Count stock</a>@endif</div></div>
@endsection
@section('content')
@php($canCount = $period->status === 'open' && auth()->user()->canAccessAbility('adjust_stock'))
<div class="sc-page">
@if(session('status'))<div class="sc-message" role="status">{{ session('status') }}</div>@endif
@if($errors->any())<div class="sc-message error" role="alert">{{ $errors->first() }}</div>@endif
<section class="sc-card"><div class="sp-head"><div><h2>Physical stock count</h2><p class="sc-muted">{{ $period->category->name ?? 'All products' }} @if($period->user_id)&middot; Account: {{ $period->account->name ?? 'Former user' }}@endif &middot; Opened by {{ $period->opener->name ?? 'Staff' }} &middot; {{ $summary->counted_lines }} / {{ $summary->total_lines }} products counted</p></div><span class="sp-status {{ $period->status }}">{{ ucfirst($period->status) }}</span></div>@if($period->cashier_shift_id)<p class="sc-muted">Related shift: <a href="{{ route('shifts.show',$period->cashier_shift_id) }}">#{{ $period->cashier_shift_id }}</a>. Stock comparison is the balance captured when this period opened or was refreshed.</p>@endif @if($period->notes)<p class="sp-notes">{{ $period->notes }}</p>@endif @if($period->closed_at)<p class="sc-muted">{{ ucfirst($period->status) }} {{ $period->closed_at->format('d M Y H:i') }} by {{ $period->closer->name ?? 'Staff' }}</p>@endif @if($period->closing_notes)<p class="sp-notes">{{ $period->closing_notes }}</p>@endif</section>
<div class="sc-kpis"><div class="sc-kpi"><span>Comparison units</span><strong>{{ number_format($summary->expected_units) }}</strong></div><div class="sc-kpi"><span>Saved physical units</span><strong>{{ number_format($summary->counted_units) }}</strong></div><div class="sc-kpi"><span>Saved count variance</span><strong>{{ $summary->variance>0 ? '+' : '' }}{{ number_format($summary->variance) }}</strong></div>@if(auth()->user()->canViewFinancials())<div class="sc-kpi"><span>Saved variance value at cost</span><strong>KES {{ number_format($summary->variance_value,2) }}</strong></div>@endif</div>
<section class="sc-card sp-count-sheet" id="count-sheet" aria-labelledby="count-sheet-title">
    <div class="sp-head"><div><h2 id="count-sheet-title">{{ $canCount ? 'Enter stock count' : 'Count sheet' }}</h2><p class="sc-muted">@if($canCount)Enter the physical quantity for each product. Enter 0 for empty stock; leave blank for items still to count. Save before reviewing and posting.@else Saved physical quantities and differences for this period.@endif</p></div><span class="sc-muted">{{ $summary->counted_lines }} / {{ $summary->total_lines }} saved</span></div>
    <div class="sp-count-tools"><label class="sc-field" for="count-item-search">Find product on this page<input class="sp-count-search" type="search" id="count-item-search" placeholder="Product name or SKU"></label><span class="sc-muted">Page {{ $lines->currentPage() }} of {{ $lines->lastPage() }} · {{ $lines->total() }} products</span></div>
    @if($canCount)<form id="stock-count-form" method="POST" action="{{ route('stock-control.periods.save',$period->id) }}">@csrf<input type="hidden" name="version" value="{{ $period->version }}"><input type="hidden" name="page" value="{{ $lines->currentPage() }}">@endif
    <div class="sc-scroll"><table class="sc-table"><thead><tr><th>Product / SKU</th><th class="number">Expected stock</th><th class="number">Actual physical count</th><th class="number">Variance</th>@if(auth()->user()->canViewFinancials())<th class="number">Variance value (KES)</th>@endif</tr></thead><tbody>
    @foreach($lines as $line)
        @php($difference = $line->counted_quantity === null ? null : $line->counted_quantity - $line->expected_quantity)
        <tr data-count-search="{{ $line->product_name.' '.$line->sku }}" data-expected="{{ $line->expected_quantity }}" data-cost="{{ $line->unit_cost }}" data-saved="{{ $line->counted_quantity }}">
            <td><a href="{{ route('products.show',$line->product_id) }}">{{ $line->product_name }}</a><small>{{ $line->sku }}</small></td>
            <td class="number">{{ number_format($line->expected_quantity) }}</td>
            <td class="number">@if($canCount)<input class="sp-count-input" name="lines[{{ $line->id }}][quantity]" type="number" inputmode="numeric" min="0" max="1000000" step="1" value="{{ old('lines.'.$line->id.'.quantity',$line->counted_quantity) }}" placeholder="Not counted" aria-label="Physical quantity for {{ $line->product_name }}">@error('lines.'.$line->id.'.quantity')<small class="sp-variance negative">{{ $message }}</small>@enderror @else{{ $line->counted_quantity ?? 'Not counted' }}@endif</td>
            <td class="number sp-variance {{ $difference<0 ? 'negative' : ($difference>0 ? 'positive' : '') }}" data-row-variance>{{ $difference===null ? '—' : ($difference>0 ? '+' : '').number_format($difference) }}</td>
            @if(auth()->user()->canViewFinancials())<td class="number sp-variance {{ $difference<0 ? 'negative' : ($difference>0 ? 'positive' : '') }}" data-row-value>{{ $difference===null ? '—' : number_format($difference*$line->unit_cost,2) }}</td>@endif
        </tr>
    @endforeach
    </tbody>@if($canCount)<tfoot><tr><td><strong>This page · live totals</strong><small id="count-page-progress"></small></td><td class="number">{{ number_format($lines->sum('expected_quantity')) }}</td><td class="number" id="count-page-physical">—</td><td class="number sp-variance" id="count-page-variance">—</td>@if(auth()->user()->canViewFinancials())<td class="number sp-variance" id="count-page-value">—</td>@endif</tr></tfoot>@endif</table></div>
    <p class="sc-muted" id="count-no-matches" hidden>No matching products on this page. Use the page navigation to find more products.</p>
    @if($canCount)<div class="sp-save-bar"><p class="sc-muted" id="count-save-state" role="status">Saved counts are shown above. Stock changes only when the period is posted.</p><div class="sc-actions"><button class="sc-button primary" type="submit">Save counts</button>@if($lines->hasMorePages())<button class="sc-button" type="submit" name="next" value="1">Save & next page</button>@endif</div></div></form>@endif
    {{ $lines->fragment('count-sheet')->links('pagination.app') }}
</section>
@if($period->user_id)
<details class="sc-card sp-shifts"><summary>Account shifts <span class="sc-muted">({{ $periodShifts->count() }}) · {{ $period->account->name ?? 'Former user' }}</span></summary><p class="sc-muted">Shifts opened within the selected dates for {{ $period->account->name ?? 'this account' }} at this branch. Posting the full count locks pending shifts. Stock quantities are the shared location balance captured when this count opened or was refreshed.</p><div class="sc-scroll"><table class="sc-table"><thead><tr><th>Shift</th><th>Opened</th><th>Cash closed</th><th>Status</th></tr></thead><tbody>@forelse($periodShifts as $accountShift)<tr><td><a href="{{ route('shifts.show',$accountShift->id) }}">#{{ $accountShift->id }}</a></td><td>{{ $accountShift->opened_at->format('d M Y H:i') }}</td><td>{{ $accountShift->closed_at?->format('d M Y H:i') ?? 'Still open' }}</td><td>{{ $accountShift->status_label }}</td></tr>@empty<tr><td colspan="4" class="sc-empty">No pending shifts in this period.</td></tr>@endforelse</tbody></table></div></details>
@endif
@if($period->status==='open' && auth()->user()->canAccessAbility('adjust_stock'))
<section class="sc-card"><h2>Review & close period</h2><p class="sc-muted">Count every product, review differences, then close the period. Only the differences are posted to stock. Posting also locks the account’s pending shifts within these dates. Closed periods cannot be posted twice.</p>
    @if((int)$summary->counted_lines===(int)$summary->total_lines)
    <form method="POST" action="{{ route('stock-control.periods.post',$period->id) }}" class="sp-review">@csrf<input type="hidden" name="version" value="{{ $period->version }}"><label class="sc-field">Closing notes (optional)<textarea name="closing_notes" rows="2" maxlength="1000">{{ old('closing_notes') }}</textarea></label><label class="sp-check"><input type="checkbox" name="confirmation" value="1" required><span>I reviewed the physical counts and authorize posting the stock differences and locking the account’s shifts for this period.</span></label><div><button class="sc-button primary" type="submit">Post count & lock shifts</button></div></form>
    @else<p class="sc-muted"><strong>{{ $summary->total_lines-$summary->counted_lines }} products still need a count.</strong> Enter 0 for products with no physical stock.</p>@endif
    <details style="margin-top:20px"><summary class="sc-muted" style="cursor:pointer">Refresh changed stock or cancel this period</summary><p class="sc-muted">If sales or receipts changed stock after opening the count, refresh and recount the affected products. Saved counts for unchanged products are kept.</p><form method="POST" action="{{ route('stock-control.periods.refresh',$period->id) }}">@csrf<input type="hidden" name="version" value="{{ $period->version }}"><button class="sc-button" type="submit">Refresh changed stock</button></form><form method="POST" action="{{ route('stock-control.periods.cancel',$period->id) }}" class="sp-review">@csrf<input type="hidden" name="version" value="{{ $period->version }}"><label class="sc-field">Reason for cancellation<input name="closing_notes" minlength="3" maxlength="1000" required placeholder="Why is this count being cancelled?"></label><div><button class="sc-button" type="submit">Cancel period without changing stock</button></div></form></details>
</section>
@endif
</div>
<script>
(() => {
    const rows = [...document.querySelectorAll('[data-count-search]')];
    const form = document.getElementById('stock-count-form');
    const format = (n, decimals = 0) => n.toLocaleString('en-KE', {minimumFractionDigits: decimals, maximumFractionDigits: decimals});
    let dirty = false, submitting = false;
    const varianceCell = (cell, value, money = false) => {
        if (!cell) return;
        cell.textContent = value === null ? '—' : (!money && value > 0 ? '+' : '') + format(value, money ? 2 : 0);
        cell.classList.toggle('negative', value !== null && value < 0);
        cell.classList.toggle('positive', value !== null && value > 0);
    };
    const update = () => {
        let physical = 0, variance = 0, amount = 0, counted = 0;
        dirty = false;
        rows.forEach(row => {
            const input = row.querySelector('.sp-count-input');
            if (!input) return;
            if (input.value !== row.dataset.saved) dirty = true;
            const quantity = input.value !== '' && input.validity.valid ? Number(input.value) : null;
            const difference = quantity === null ? null : quantity - Number(row.dataset.expected);
            varianceCell(row.querySelector('[data-row-variance]'), difference);
            varianceCell(row.querySelector('[data-row-value]'), difference === null ? null : difference * Number(row.dataset.cost), true);
            if (quantity !== null) { counted++; physical += quantity; variance += difference; amount += difference * Number(row.dataset.cost); }
        });
        document.getElementById('count-page-physical').textContent = format(physical);
        document.getElementById('count-page-progress').textContent = counted + ' / ' + rows.length + ' counted on this page';
        varianceCell(document.getElementById('count-page-variance'), counted ? variance : null);
        varianceCell(document.getElementById('count-page-value'), counted ? amount : null, true);
        document.getElementById('count-save-state').textContent = dirty ? 'Unsaved changes — save counts before reviewing or posting. Live totals include all items on this page.' : 'Counts saved. Review the differences below before posting. Blank items still need counting.';
        document.querySelectorAll('form[action$="/post"] button[type="submit"]').forEach(button => button.disabled = dirty);
    };
    rows.forEach(row => row.querySelector('.sp-count-input')?.addEventListener('input', update));
    document.getElementById('count-item-search').addEventListener('input', event => {
        const term = event.target.value.trim().toLowerCase();
        rows.forEach(row => row.hidden = !row.dataset.countSearch.toLowerCase().includes(term));
        document.getElementById('count-no-matches').hidden = rows.some(row => !row.hidden);
    });
    if (form) {
        update();
        form.addEventListener('keydown', event => {
            if (event.key !== 'Enter' || !event.target.matches('.sp-count-input')) return;
            event.preventDefault();
            const inputs = rows.filter(row => !row.hidden).map(row => row.querySelector('.sp-count-input'));
            const next = inputs[inputs.indexOf(event.target) + 1];
            if (next) { next.focus(); next.select(); }
            else form.querySelector('button[type="submit"]').focus();
        });
        document.querySelectorAll('form').forEach(other => other.addEventListener('submit', event => {
            if (other !== form && dirty) {
                event.preventDefault();
                document.getElementById('count-save-state').textContent = 'Save your count changes before continuing.';
                document.getElementById('count-sheet').scrollIntoView({behavior: 'smooth'});
                return;
            }
            submitting = true;
        }));
        window.addEventListener('beforeunload', event => {
            if (dirty && !submitting) { event.preventDefault(); event.returnValue = ''; }
        });
        window.addEventListener('pageshow', () => { submitting = false; });
    }
    @if(request()->boolean('count') || $errors->has('lines') || $errors->has('lines.*'))document.getElementById('count-sheet').scrollIntoView();@endif
})();
</script>
@endsection
