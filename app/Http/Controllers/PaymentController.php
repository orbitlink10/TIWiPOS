<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Payment;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(private SubscriptionService $subscriptionService)
    {
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:0',
            'currency' => 'nullable|string|max:8',
            'provider' => 'nullable|string|max:100',
            'provider_ref' => 'nullable|string|max:255',
            'status' => 'nullable|string|max:50',
            'raw_payload' => 'nullable|array',
        ]);

        $business = $request->user()->business;
        $payment = $this->subscriptionService->recordPaymentAndActivate($business, array_merge($data, [
            'method' => 'manual',
        ]));

        return response()->json(['payment' => $payment], 201);
    }

    public function webhook(Request $request)
    {
        // Placeholder for provider callbacks (e.g., M-Pesa)
        $signature = $request->header('X-Signature');
        // TODO: validate $signature

        $businessId = $request->input('business_id');
        $business = Business::find($businessId);
        if (!$business) {
            return response()->json(['error' => 'Unknown tenant'], 404);
        }

        $payload = $request->all();
        $payload['status'] = $payload['status'] ?? 'success';
        $this->subscriptionService->recordPaymentAndActivate($business, $payload);

        return response()->json(['ok' => true]);
    }
}
