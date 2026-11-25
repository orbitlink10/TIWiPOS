@extends('layouts.app')
@section('title', 'Shift #'.$shift->id)
@push('styles')@include('pages.shifts.style')
<style>.shift-verify-amount{width:150px;max-width:100%;padding:10px;border:1px solid #cdd8e3;border-radius:7px;font:inherit;text-align:right}.shift-verification-form{display:grid;gap:16px;margin-top:18px}.shift-verify-amount:focus-visible{outline:2px solid #a7c9f4;outline-offset:2px}@media(max-width:600px){.shift-verify-amount{font-size:16px}}</style>@endpush
@section('header')
<div class="header-row"><div><h1>Shift #{{ $shift->id }}</h1><p class="shift-intro">{{ $shift->user->name ?? 'Former user' }} &middot; {{ $currentBranch->name ?? 'Current branch' }} &middot; {{ $shift->status_label }}</p></div><div class="shift-actions"><a class="shift-button" href="{{ route('shifts.index') }}">&larr; Shifts</a>@if($shift->status==='open' && (int)$shift->user_id===(int)auth()->id())<a class="shift-button primary" href="{{ route('sale') }}">Open POS</a>@endif @if(auth()->user()->canAccessAbility('view_stock'))<a class="shift-button" href="{{ route('stock-control.index') }}">Stock Control</a>@endif</div></div>
@endsection
@section('content')
<div class="shift-page">
    @if(session('status'))<div class="shift-message" role="status">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="shift-message error" role="alert">{{ $errors->first() }}</div>@endif
    <div class="shift-grid"><div class="shift-card shift-metric"><small>Opening cash</small><strong>KES {{ number_format($shift->opening_cash_cents/100,2) }}</strong></div><div class="shift-card shift-metric"><small>Sales total</small><strong>KES {{ number_format($summary['sales_total_cents']/100,2) }}</strong><p class="shift-muted">{{ $summary['sales_count'] }} receipts</p></div><div class="shift-card shift-metric"><small>Cash sales</small><strong>KES {{ number_format(($summary['payments']['cash']??0)/100,2) }}</strong></div><div class="shift-card shift-metric"><small>Expected cash</small><strong>KES {{ number_format($summary['expected_cash_cents']/100,2) }}</strong></div></div>
    <div class="shift-columns">
        <section class="shift-card"><h2>Shift summary</h2><p class="shift-muted">Opened {{ $shift->opened_at->format('d M Y H:i') }}@if($shift->closed_at) &middot; Closed {{ $shift->closed_at->format('d M Y H:i') }}@endif</p>
            <div class="shift-scroll"><table class="shift-table" style="min-width:300px"><thead><tr><th>Payment method</th><th class="number">Amount (KES)</th></tr></thead><tbody>@foreach(['cash'=>'Cash','mobile'=>'Mobile money','card'=>'Card','bank'=>'Bank','other'=>'Other'] as $method=>$label)<tr><td>{{ $label }}</td><td class="number">{{ number_format(($summary['payments'][$method]??0)/100,2) }}</td></tr>@endforeach</tbody></table></div>
            <p class="shift-muted">Expected cash = opening cash + cash sales. Mobile money, cards and bank payments are shown separately.</p>
            @if($shift->opening_notes)<h3 style="font-size:13px">Opening notes</h3><p class="shift-note">{{ $shift->opening_notes }}</p>@endif
            @if($shift->closing_notes)<h3 style="font-size:13px">Closing notes</h3><p class="shift-note">{{ $shift->closing_notes }}</p>@endif
        </section>
        <section class="shift-card shift-close">
            @if($shift->stock_period_id && auth()->user()->canAccessAbility('view_stock'))<p><a class="shift-button" href="{{ route('stock-control.periods.show',$shift->stock_period_id) }}">View completed stock period</a></p>@endif
            @if($shift->status==='open')
            <h2>Close shift</h2><p class="shift-muted">Count the cash in your till. Closing saves cash totals and sends this shift to Pending stock count. Completing its stock period locks the shift.</p>
            <form method="POST" action="{{ route('shifts.close',$shift->id) }}" id="close-shift-form">@csrf
                <input type="hidden" name="expected_sales_count" value="{{ $summary['sales_count'] }}"><input type="hidden" name="expected_cash_cents" value="{{ $summary['expected_cash_cents'] }}">
                <label class="shift-field">Counted cash (KES)<input name="counted_cash" id="counted-cash" type="number" min="0" max="9999999999.99" step="0.01" value="{{ old('counted_cash') }}" required></label>
                <div><span class="shift-muted">Difference from expected cash</span><div class="shift-difference" id="cash-difference">—</div></div>
                <label class="shift-field">Closing notes (optional)<textarea name="closing_notes" rows="3" maxlength="1000" placeholder="Explain any shortage or overage">{{ old('closing_notes') }}</textarea></label>
                <label class="shift-check"><input name="confirmation" type="checkbox" value="1" required><span>I have counted the cash and am ready to close this shift.</span></label>
                <button class="shift-button danger" type="submit">Close shift</button>
            </form>
            @else
            <h2>{{ $shift->status_label }}</h2><p class="shift-muted">Counted cash</p><div class="shift-difference">KES {{ number_format($shift->counted_cash_cents/100,2) }}</div><p class="shift-muted">{{ $shift->cash_variance_cents < 0 ? 'Cash shortage' : ($shift->cash_variance_cents > 0 ? 'Cash overage' : 'Cash balanced') }}</p><div class="shift-difference {{ $shift->cash_variance_cents<0 ? 'short' : ($shift->cash_variance_cents>0 ? 'over' : '') }}">KES {{ number_format($shift->cash_variance_cents/100,2) }}</div><p class="shift-muted">@if($shift->stock_locked_at)Stock count completed. Locked {{ $shift->stock_locked_at->format('d M Y H:i') }}.@else Cash closing is complete. Awaiting stock count before the shift is locked.@endif</p>
            @endif
        </section>
    </div>
    @if($shift->status==='closed')
    <section class="shift-card" id="shift-verification">
        <h2>{{ $shift->verified_at ? 'Verification record' : 'Manager / admin verification' }}</h2>
        @if($shift->verified_at)
            <p class="shift-muted">Verified by {{ $shift->verifier->name ?? 'Former reviewer' }} on {{ $shift->verified_at->format('d M Y H:i') }}. Confirmed amounts are saved separately from the cashier’s cash count and sales totals.</p>
        @elseif(!$shift->stock_locked_at)
            <p class="shift-muted">Pending stock count. Complete and post the stock period before a manager or admin verifies the takings.</p>
        @elseif($shift->canBeVerifiedBy(auth()->user()))
            <p class="shift-muted">Stock count posted and shift locked. Review the receipts, confirm the money received for each payment method, and explain any differences. Expected cash includes opening cash.</p>
        @else
            <p class="shift-muted">Pending verification by another manager or admin. The cashier and the person who closed the shift cannot verify it.</p>
        @endif
        @if($shift->verified_at || $shift->canBeVerifiedBy(auth()->user()))
            @php($reviewExpected = array_merge($summary['payments'], ['cash' => $summary['expected_cash_cents']]))
            @if(!$shift->verified_at)<form method="POST" action="{{ route('shifts.verify',$shift->id) }}">@csrf @endif
            <div class="shift-scroll"><table class="shift-table"><thead><tr><th>Payment method</th><th class="number">Expected (KES)</th><th class="number">{{ $shift->verified_at ? 'Verified' : 'Confirm received' }} (KES)</th><th class="number">Difference (KES)</th></tr></thead><tbody>
            @foreach(['cash'=>'Cash including float','mobile'=>'Mobile money / M-Pesa','card'=>'Card','bank'=>'Bank','other'=>'Other'] as $method=>$label)
                @php($expectedCents = (int)($reviewExpected[$method] ?? 0))
                @php($confirmedCents = (int)($shift->verified_payment_totals[$method] ?? 0))
                <tr data-review-expected="{{ $expectedCents }}"><td>{{ $label }}@if($method==='cash')<small>Cashier counted KES {{ number_format($shift->counted_cash_cents/100,2) }}</small>@endif</td><td class="number">{{ number_format($expectedCents/100,2) }}</td><td class="number">
                @if($shift->verified_at){{ number_format($confirmedCents/100,2) }}@else
                    <input class="shift-verify-amount" name="verified[{{ $method }}]" type="number" min="0" max="9999999999.99" step="0.01" inputmode="decimal" value="{{ old('verified.'.$method) }}" placeholder="0.00" aria-label="Confirmed {{ $label }} in KES" required>
                @endif</td><td class="number" data-review-difference>{{ $shift->verified_at ? number_format(($confirmedCents-$expectedCents)/100,2) : '—' }}</td></tr>
            @endforeach
            </tbody></table></div>
            @if($shift->verified_at)
                @if($shift->verification_notes)<h3 style="font-size:13px">Reviewer notes</h3><p class="shift-note">{{ $shift->verification_notes }}</p>@endif
            @else
                <div class="shift-verification-form"><label class="shift-field">Review notes<textarea name="verification_notes" rows="3" maxlength="1000" placeholder="Explain shortages, overages or differences between received and expected amounts">{{ old('verification_notes') }}</textarea><small>Required when any confirmed amount differs from expected.</small></label>
                <label class="shift-check"><input type="checkbox" name="confirmation" value="1" required><span>I reviewed the stock count, receipts and payment totals and confirm these received amounts.</span></label>
                <div><button class="shift-button primary" type="submit">Verify shift</button></div></div>
                </form>
            @endif
        @endif
    </section>
    @endif
    <section class="shift-card"><h2>Shift receipts</h2><div class="shift-scroll"><table class="shift-table"><thead><tr><th>Receipt</th><th>Time</th><th>Customer</th><th class="number">Total (KES)</th></tr></thead><tbody>@forelse($sales as $sale)<tr><td><a href="{{ route('sale.receipt',$sale->id) }}">{{ $sale->sale_number }}</a></td><td>{{ $sale->created_at->format('d M Y H:i') }}</td><td>{{ $sale->customer_name ?: 'Walk-in' }}</td><td class="number">{{ number_format($sale->total,2) }}</td></tr>@empty<tr><td colspan="4" class="shift-empty">No completed sales in this shift.</td></tr>@endforelse</tbody></table></div>{{ $sales->links('pagination.app') }}</section>
</div>
@if($shift->canBeVerifiedBy(auth()->user()))
<script>
document.querySelectorAll('.shift-verify-amount').forEach(input => {
    const row = input.closest('tr'), cell = row.querySelector('[data-review-difference]');
    const update = () => {
        const difference = Math.round(Number(input.value || 0) * 100) - Number(row.dataset.reviewExpected);
        cell.textContent = input.value === '' ? '—' : (difference/100).toLocaleString('en-KE', {minimumFractionDigits:2, maximumFractionDigits:2});
        cell.style.color = input.value === '' ? '' : difference < 0 ? '#b42318' : difference > 0 ? '#166534' : '';
    };
    input.addEventListener('input', update); update();
});
</script>
@endif
@if($shift->status==='open')
<script>
(() => {
const expected = @json($summary['expected_cash_cents']);
const input = document.getElementById('counted-cash'), output = document.getElementById('cash-difference');
const update = () => { const difference=Math.round(Number(input.value||0)*100)-expected; output.textContent=input.value==='' ? '—' : 'KES '+(difference/100).toLocaleString('en-KE',{minimumFractionDigits:2,maximumFractionDigits:2}); output.className='shift-difference'+(input.value==='' ? '' : difference<0 ? ' short' : difference>0 ? ' over' : ''); };
input.addEventListener('input',update); update();
document.getElementById('close-shift-form').addEventListener('submit', event => { const button=event.target.querySelector('button[type=submit]');button.disabled=true; });
window.addEventListener('pageshow',()=>{document.querySelector('#close-shift-form button[type=submit]').disabled=false;});
})();
</script>
@endif
@endsection
