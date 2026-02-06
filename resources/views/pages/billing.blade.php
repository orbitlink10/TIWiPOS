@extends('layouts.app')

@section('title', 'Billing')

@section('header')
    <div class="header-row">
        <h1>Billing & Subscription</h1>
        <a class="btn" href="{{ route('home') }}">Back to Dashboard</a>
    </div>
@endsection

@section('content')
    <div class="panel">
        <h2>Status: {{ $isActive ? 'Active' : 'Inactive' }}</h2>
        @if(!$isActive)
            <div style="margin-top:8px; padding:10px 12px; border-radius:10px; border:1px solid #fecaca; background:#fff1f2; color:#991b1b;">
                Subscription expired—pay to restore full access.
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

        <div style="margin-top:12px;">
            <div><strong>Current plan:</strong> {{ $business->plan ?? 'Standard' }}</div>
            <div><strong>Period ends:</strong> {{ optional($business->current_period_end)->toDateString() ?? 'N/A' }}</div>
            <div><strong>Last payment:</strong> {{ optional($business->last_payment_at)->toDateTimeString() ?? 'N/A' }}</div>
        </div>

        <form method="POST" action="{{ route('billing.pay') }}" style="margin-top:14px; display:grid; gap:12px; max-width:420px;">
            @csrf
            <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                Plan
                <select name="plan_id" style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
                    @foreach($plans as $plan)
                        <option value="{{ $plan->id }}">{{ $plan->name }} - {{ $plan->price }} {{ $plan->currency }}</option>
                    @endforeach
                </select>
            </label>
            <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                Amount
                <input name="amount" type="number" step="0.01" value="{{ $plans->first()->price ?? 0 }}" style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
            </label>
            <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                Currency
                <input name="currency" type="text" value="{{ $plans->first()->currency ?? 'KES' }}" style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
            </label>
            <label style="display:flex; flex-direction:column; gap:6px; font-weight:600;">
                Provider ref (optional)
                <input name="provider_ref" type="text" placeholder="Receipt / transaction code" style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;">
            </label>
            <button class="btn" type="submit" style="justify-content:center;">Pay & Activate</button>
        </form>
    </div>
@endsection
