@extends('layouts.app')

@section('title', 'Billing')

@push('styles')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap');

    .billing-page {
        --billing-accent: #0f8f84;
        --billing-accent-strong: #0f766e;
        --billing-border: #d8e2ef;
        --billing-text: #13253d;
        --billing-muted: #64748b;
        font-family: 'Plus Jakarta Sans', 'Manrope', 'Segoe UI', sans-serif;
        display: grid;
        gap: 14px;
    }

    .billing-alert {
        padding: 11px 13px;
        border-radius: 12px;
        border: 1px solid;
        font-size: 13px;
        font-weight: 700;
        line-height: 1.4;
    }

    .billing-alert.success {
        border-color: rgba(16, 185, 129, 0.35);
        background: rgba(16, 185, 129, 0.1);
        color: #065f46;
    }

    .billing-alert.error {
        border-color: rgba(239, 68, 68, 0.35);
        background: rgba(239, 68, 68, 0.08);
        color: #b91c1c;
    }

    .billing-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 380px;
        gap: 18px;
        align-items: start;
    }

    .billing-card {
        border-radius: 18px;
        border: 1px solid var(--billing-border);
        background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
        box-shadow: 0 14px 34px rgba(13, 29, 56, 0.08);
    }

    .billing-status {
        padding: 20px;
    }

    .billing-title-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
    }

    .billing-title {
        margin: 0;
        color: var(--billing-text);
        font-size: 33px;
        font-weight: 800;
        letter-spacing: -0.03em;
        line-height: 1.1;
    }

    .status-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        min-width: 110px;
        padding: 8px 14px;
        font-size: 12px;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .status-pill.active {
        border: 1px solid #a7f3d0;
        background: #ecfdf5;
        color: #065f46;
    }

    .status-pill.inactive {
        border: 1px solid #fecaca;
        background: #fef2f2;
        color: #991b1b;
    }

    .billing-status-note {
        margin: 10px 0 0;
        color: var(--billing-muted);
        font-size: 14px;
        line-height: 1.45;
    }

    .billing-stats {
        margin-top: 14px;
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 10px;
    }

    .billing-stat {
        border: 1px solid #e4ebf5;
        border-radius: 12px;
        background: #fff;
        padding: 11px 12px;
        min-height: 84px;
    }

    .billing-stat-label {
        color: #6a7a91;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        font-weight: 700;
    }

    .billing-stat-value {
        margin-top: 7px;
        color: #10253f;
        font-size: 18px;
        font-weight: 800;
        letter-spacing: -0.02em;
        line-height: 1.2;
    }

    .billing-form-card {
        padding: 18px;
    }

    .billing-form-title {
        margin: 0;
        color: var(--billing-text);
        font-size: 22px;
        font-weight: 800;
        letter-spacing: -0.02em;
    }

    .billing-form-subtitle {
        margin: 6px 0 0;
        color: var(--billing-muted);
        font-size: 13px;
    }

    .billing-form {
        margin-top: 14px;
        display: grid;
        gap: 11px;
    }

    .billing-field {
        display: grid;
        gap: 6px;
    }

    .billing-field label {
        font-size: 11px;
        color: #607088;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        font-weight: 700;
    }

    .billing-field input,
    .billing-field select {
        width: 100%;
        border: 1px solid var(--billing-border);
        border-radius: 12px;
        background: #fff;
        color: #0f2642;
        font-size: 15px;
        font-weight: 600;
        padding: 11px 12px;
        font-family: inherit;
    }

    .billing-field input::placeholder {
        color: #94a3b8;
    }

    .billing-field input:focus,
    .billing-field select:focus {
        outline: 2px solid rgba(15, 143, 132, 0.2);
        border-color: rgba(15, 143, 132, 0.4);
    }

    .billing-submit {
        margin-top: 4px;
        width: 100%;
        justify-content: center;
        border: none;
        border-radius: 12px;
        padding: 12px 14px;
        background: linear-gradient(135deg, var(--billing-accent) 0%, var(--billing-accent-strong) 100%);
        color: #fff;
        font-size: 15px;
        font-weight: 800;
        letter-spacing: -0.01em;
        cursor: pointer;
        box-shadow: 0 10px 24px rgba(15, 143, 132, 0.25);
    }

    @media (max-width: 1080px) {
        .billing-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 760px) {
        .billing-title {
            font-size: 28px;
        }

        .billing-stats {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('header')
    <div class="header-row">
        <h1>Billing & Subscription</h1>
        <a class="btn" href="{{ route('home') }}">Back to Dashboard</a>
    </div>
@endsection

@section('content')
    @php
        $planName = $business->plan ?? 'Standard';
        $periodEnd = $business->current_period_end
            ? \Illuminate\Support\Carbon::parse($business->current_period_end)->format('M d, Y')
            : 'N/A';
        $lastPayment = $business->last_payment_at
            ? \Illuminate\Support\Carbon::parse($business->last_payment_at)->format('M d, Y H:i')
            : 'N/A';
    @endphp

    <div class="billing-page">
        @if(!$isActive)
            <div class="billing-alert error">
                Subscription expired. Record a payment below to restore full access.
            </div>
        @endif

        @if (session('status'))
            <div class="billing-alert success">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="billing-alert error">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <div class="billing-grid">
            <section class="billing-card billing-status">
                <div class="billing-title-row">
                    <h2 class="billing-title">Subscription Status</h2>
                    <span class="status-pill {{ $isActive ? 'active' : 'inactive' }}">
                        {{ $isActive ? 'Active' : 'Inactive' }}
                    </span>
                </div>
                <p class="billing-status-note">
                    Keep your subscription active to access all sales, inventory, and reporting features.
                </p>

                <div class="billing-stats">
                    <article class="billing-stat">
                        <div class="billing-stat-label">Current Plan</div>
                        <div class="billing-stat-value">{{ ucfirst((string) $planName) }}</div>
                    </article>
                    <article class="billing-stat">
                        <div class="billing-stat-label">Period Ends</div>
                        <div class="billing-stat-value">{{ $periodEnd }}</div>
                    </article>
                    <article class="billing-stat">
                        <div class="billing-stat-label">Last Payment</div>
                        <div class="billing-stat-value">{{ $lastPayment }}</div>
                    </article>
                </div>
            </section>

            <aside class="billing-card billing-form-card">
                <h3 class="billing-form-title">Record Payment</h3>
                <p class="billing-form-subtitle">Choose a plan and confirm payment details.</p>

                <form method="POST" action="{{ route('billing.pay') }}" class="billing-form">
                    @csrf

                    <div class="billing-field">
                        <label for="plan_id">Plan</label>
                        <select name="plan_id" id="plan_id">
                            @foreach($plans as $plan)
                                <option
                                    value="{{ $plan->id }}"
                                    data-price="{{ number_format((float) $plan->price, 2, '.', '') }}"
                                    data-currency="{{ $plan->currency }}"
                                >
                                    {{ $plan->name }} - {{ number_format((float) $plan->price, 2) }} {{ $plan->currency }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="billing-field">
                        <label for="amount">Amount</label>
                        <input id="amount" name="amount" type="number" step="0.01" value="{{ $plans->first()->price ?? 0 }}">
                    </div>

                    <div class="billing-field">
                        <label for="currency">Currency</label>
                        <input id="currency" name="currency" type="text" value="{{ $plans->first()->currency ?? 'KES' }}">
                    </div>

                    <div class="billing-field">
                        <label for="provider_ref">Provider Ref (Optional)</label>
                        <input id="provider_ref" name="provider_ref" type="text" placeholder="Receipt / transaction code">
                    </div>

                    <button class="billing-submit" type="submit">Pay & Activate</button>
                </form>
            </aside>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    (function () {
        const planSelect = document.getElementById('plan_id');
        const amountInput = document.getElementById('amount');
        const currencyInput = document.getElementById('currency');

        if (!planSelect || !amountInput || !currencyInput) {
            return;
        }

        const normalizeMoney = (value) => {
            const parsed = Number.parseFloat(String(value ?? '').replace(/[^0-9.-]/g, ''));
            return Number.isFinite(parsed) ? parsed : 0;
        };

        const syncPlanDetails = () => {
            const selected = planSelect.options[planSelect.selectedIndex];
            if (!selected) {
                return;
            }

            amountInput.value = normalizeMoney(selected.dataset.price).toFixed(2);
            currencyInput.value = (selected.dataset.currency || 'KES').toUpperCase();
        };

        planSelect.addEventListener('change', syncPlanDetails);

        amountInput.addEventListener('blur', () => {
            amountInput.value = normalizeMoney(amountInput.value).toFixed(2);
        });

        syncPlanDetails();
    })();
</script>
@endpush
