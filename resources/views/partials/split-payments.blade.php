@php
    $savedAmounts = isset($sale) ? $sale->payments->groupBy('method')->map(fn ($rows) => $rows->sum('amount'))->all() : [];
    $splitAmounts = old('payments', $savedAmounts);
    $splitEnabled = (bool) old('split_payment', count($savedAmounts) > 1);
@endphp
<div class="split-payments">
    <label class="split-toggle"><input id="split_payment" name="split_payment" type="checkbox" value="1" @checked($splitEnabled)> Split across payment methods</label>
    <fieldset id="split_amounts" @disabled(!$splitEnabled) @if(!$splitEnabled) hidden @endif>
        <legend>Payment amounts (KES)</legend>
        <p class="split-hint">Enter amounts for two or more methods. They must add up to the amount due.</p>
        @foreach(['mobile' => 'Mobile', 'cash' => 'Cash', 'card' => 'Card', 'bank' => 'Bank', 'other' => 'Other'] as $method => $label)
            <label class="split-amount-row"><span>{{ $label }}</span><input name="payments[{{ $method }}]" data-payment-method="{{ $method }}" type="number" inputmode="decimal" min="0" max="999999999.99" step="0.01" placeholder="0.00" value="{{ $splitAmounts[$method] ?? '' }}" aria-label="{{ $label }} payment amount in KES"></label>
        @endforeach
        <div id="split_balance" class="split-balance" role="status" aria-live="polite"></div>
    </fieldset>
</div>
@push('styles')
<style>
    .split-payments { margin-top:18px; }
    .split-toggle { display:flex; align-items:center; gap:9px; font-size:13px; font-weight:700; cursor:pointer; }
    .split-toggle input { width:18px; height:18px; accent-color:#1769d1; }
    #split_amounts { min-width:0; margin:14px 0 0; padding:12px; border:1px solid #d6e1eb; border-radius:8px; }
    #split_amounts[hidden] { display:none; }
    #split_amounts legend { padding:0 5px; color:#334155; font-size:12px; font-weight:700; }
    .split-hint { margin:0 0 12px; color:#64748b; font-size:12px; line-height:1.5; }
    .split-amount-row { display:flex; align-items:center; gap:12px; margin-top:8px; font-size:13px; font-weight:600; }
    .split-amount-row span { flex:1; }
    .split-amount-row input { width:60%; min-width:0; min-height:44px; padding:9px 10px; border:1px solid #cdd8e3; border-radius:6px; color:#16242b; font:inherit; text-align:right; }
    .split-balance { margin-top:12px; padding:10px; border-radius:6px; background:#f1f5f9; color:#334155; font-size:13px; font-weight:700; }
    .split-balance.balanced { background:#ecfdf5; color:#065f46; }
    @media(max-width:600px) { .split-amount-row input { font-size:16px; } }
</style>
@endpush
@push('scripts')
<script>
    (() => {
        const toggle = document.getElementById('split_payment');
        const amounts = document.getElementById('split_amounts');
        const inputs = Array.from(amounts.querySelectorAll('input'));
        const balance = document.getElementById('split_balance');
        const form = toggle.form;
        let totalCents = 0;
        function refresh() {
            amounts.disabled = !toggle.checked;
            amounts.hidden = !toggle.checked;
            form.querySelector('[data-single-payment]').hidden = toggle.checked;
            const paid = inputs.reduce((sum, input) => sum + Math.round((Number(input.value) || 0) * 100), 0);
            const count = inputs.filter(input => Number(input.value) > 0).length;
            const remaining = totalCents - paid;
            const valid = remaining === 0 && count >= 2;
            const message = remaining === 0
                ? (valid ? 'Fully allocated' : 'Use at least two payment methods.')
                : (remaining > 0 ? 'Remaining: KES ' : 'Overallocated: KES ') + (Math.abs(remaining) / 100).toFixed(2);
            balance.textContent = message;
            balance.classList.toggle('balanced', valid);
            inputs[0].setCustomValidity(toggle.checked && !valid ? message : '');
            return !toggle.checked || valid;
        }
        window.updateSplitPaymentTotal = total => { totalCents = Math.round(total * 100); refresh(); };
        toggle.addEventListener('change', refresh);
        inputs.forEach(input => input.addEventListener('input', refresh));
        form.addEventListener('submit', event => {
            if (!refresh()) {
                event.preventDefault();
                event.stopImmediatePropagation();
                inputs[0].reportValidity();
            }
        }, true);
        refresh();
    })();
</script>
@endpush
