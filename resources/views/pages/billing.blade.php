@extends('layouts.app')

@section('title', 'Billing')

@push('styles')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap');

    .billing-panel {
        font-family: 'Plus Jakarta Sans', 'Manrope', 'Segoe UI', sans-serif;
    }

    .billing-panel h2 {
        font-size: 42px;
        font-weight: 800;
        letter-spacing: -0.03em;
        margin-bottom: 10px;
    }

    .billing-meta {
        margin-top: 14px;
        display: grid;
        gap: 6px;
        font-size: 16px;
        line-height: 1.45;
    }

    .billing-meta strong {
        font-weight: 800;
        letter-spacing: -0.01em;
    }

    .billing-form {
        margin-top: 16px;
        display: grid;
        gap: 12px;
        max-width: 420px;
    }

    .billing-field {
        display: flex;
        flex-direction: column;
        gap: 7px;
        font-weight: 700;
        font-size: 16px;
        letter-spacing: -0.01em;
        color: #1b2b44;
    }

    .billing-field input,
    .billing-field select {
        font-family: 'Plus Jakarta Sans', 'Manrope', sans-serif;
        font-size: 23px;
        font-weight: 600;
        letter-spacing: -0.02em;
        color: #0b2340;
        padding: 14px 16px;
        border: 1px solid #d4dce8;
        border-radius: 16px;
        background: #fbfdff;
    }

    .billing-field input::placeholder {
        font-size: 16px;
        color: #6b7280;
        font-weight: 500;
    }

    .billing-field select option {
        font-family: 'Plus Jakarta Sans', 'Manrope', sans-serif;
        font-size: 18px;
        font-weight: 600;
    }

    .billing-submit {
        justify-content: center;
        font-family: 'Plus Jakarta Sans', 'Manrope', sans-serif;
        font-weight: 800;
        letter-spacing: -0.01em;
    }

    @media (max-width: 640px) {
        .billing-panel h2 {
            font-size: 34px;
        }

        .billing-field input,
        .billing-field select {
            font-size: 20px;
            padding: 13px 14px;
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
    <div class="panel billing-panel">
        <h2>Status: {{ $isActive ? 'Active' : 'Inactive' }}</h2>
        @if(!$isActive)
            <div style="margin-top:8px; padding:10px 12px; border-radius:10px; border:1px solid #fecaca; background:#fff1f2; color:#991b1b;">
                Subscription expired - pay to restore full access.
            </div>
        @endif
        @if (session('status'))
            <div style="margin-top:10px; padding:10px 12px; border-radius:10px; border:1px solid rgba(16,185,129,0.3); background:rgba(16,185,129,0.1); color:#065f46;">
                {{ session('status') }}
            </div>
        @endif
        @if ($errors->any())
            <div style="margin-top:10px; padding:10px 12px; border-radius:10px; border:1px solid rgba(239,68,68,0.3); background:rgba(239,68,68,0.08); color:#b91c1c;">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <div class="billing-meta">
            <div><strong>Current plan:</strong> {{ $business->plan ?? 'Standard' }}</div>
            <div><strong>Period ends:</strong> {{ optional($business->current_period_end)->toDateString() ?? 'N/A' }}</div>
            <div><strong>Last payment:</strong> {{ optional($business->last_payment_at)->toDateTimeString() ?? 'N/A' }}</div>
        </div>

        <form method="POST" action="{{ route('billing.pay') }}" class="billing-form">
            @csrf
            <label class="billing-field">
                Plan
                <select name="plan_id">
                    @foreach($plans as $plan)
                        <option value="{{ $plan->id }}">{{ $plan->name }} - {{ $plan->price }} {{ $plan->currency }}</option>
                    @endforeach
                </select>
            </label>
            <label class="billing-field">
                Amount
                <input name="amount" type="number" step="0.01" value="{{ $plans->first()->price ?? 0 }}">
            </label>
            <label class="billing-field">
                Currency
                <input name="currency" type="text" value="{{ $plans->first()->currency ?? 'KES' }}">
            </label>
            <label class="billing-field">
                Provider ref (optional)
                <input name="provider_ref" type="text" placeholder="Receipt / transaction code">
            </label>
            <button class="btn billing-submit" type="submit">Pay & Activate</button>
        </form>
    </div>
@endsection
