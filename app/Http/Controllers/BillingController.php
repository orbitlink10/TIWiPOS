<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function __construct(private SubscriptionService $subscriptionService)
    {
    }

    public function show(Request $request)
    {
        $business = $request->user()->business;
        $plans = Plan::all();
        $isActive = $this->subscriptionService->checkAndUpdate($business);

        return view('pages.billing', compact('business', 'plans', 'isActive'));
    }

    public function pay(Request $request)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:0',
            'currency' => 'nullable|string|max:8',
            'provider' => 'nullable|string|max:100',
            'provider_ref' => 'nullable|string|max:255',
            'plan_id' => 'nullable|exists:plans,id',
        ]);

        $business = $request->user()->business;

        $payment = $this->subscriptionService->recordPaymentAndActivate($business, [
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'KES',
            'provider' => $data['provider'] ?? 'manual',
            'provider_ref' => $data['provider_ref'] ?? null,
            'plan_id' => $data['plan_id'] ?? null,
            'status' => 'success',
            'method' => 'manual',
            'raw_payload' => $data,
        ]);

        return redirect()->route('billing.show')->with('status', 'Payment recorded. Subscription is active.');
    }
}
