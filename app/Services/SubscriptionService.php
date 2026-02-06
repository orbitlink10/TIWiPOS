<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionEvent;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{
    public function checkAndUpdate(Business $business): bool
    {
        $subscription = $business->subscriptions()
            ->orderByDesc('period_end')
            ->first();

        if (!$subscription) {
            $this->recordEvent($business, null, 'missing_subscription', $business->subscription_status, 'inactive', 'No subscription found');
            $business->forceFill(['subscription_status' => 'inactive'])->save();
            return false;
        }

        $now = Carbon::now();
        $graceUntil = $subscription->grace_until ? Carbon::parse($subscription->grace_until) : null;
        $activeWindow = $subscription->period_end && Carbon::parse($subscription->period_end)->isFuture();
        $inGrace = $graceUntil && $graceUntil->isFuture();

        $isActive = $subscription->status === 'active' && ($activeWindow || $inGrace);

        $business->forceFill([
            'subscription_status' => $isActive ? 'active' : 'inactive',
            'current_period_start' => $subscription->period_start,
            'current_period_end' => $subscription->period_end,
            'last_payment_at' => $subscription->last_payment_at,
            'plan' => $subscription->plan ?? $business->plan,
        ])->save();

        if (!$isActive && $business->subscription_status !== 'inactive') {
            $this->recordEvent($business, $subscription, 'auto_expire', 'active', 'inactive', 'Subscription period ended');
        }

        return $isActive;
    }

    public function recordPaymentAndActivate(Business $business, array $payload): Payment
    {
        return DB::transaction(function () use ($business, $payload) {
            $subscription = $business->subscriptions()->orderByDesc('period_end')->first();
            if (!$subscription) {
                $planId = $payload['plan_id'] ?? Plan::query()->value('id');
                $subscription = Subscription::create([
                    'business_id' => $business->id,
                    'plan_id' => $planId,
                    'plan' => $payload['plan_name'] ?? 'standard',
                    'interval' => 'monthly',
                    'status' => 'active',
                    'amount' => $payload['amount'] ?? 0,
                    'currency' => $payload['currency'] ?? 'KES',
                    'period_start' => now(),
                    'period_end' => now()->addMonth(),
                    'grace_until' => now()->addDays(3),
                    'last_payment_at' => now(),
                ]);
            } else {
                $subscription->forceFill([
                    'status' => 'active',
                    'period_end' => $this->extendPeriod($subscription->period_end),
                    'grace_until' => now()->addDays(3),
                    'last_payment_at' => now(),
                ])->save();
            }

            $payment = Payment::create([
                'business_id' => $business->id,
                'branch_id' => null,
                'subscription_id' => $subscription->id,
                'method' => $payload['method'] ?? 'other',
                'amount' => $payload['amount'] ?? 0,
                'currency' => $payload['currency'] ?? 'KES',
                'provider' => $payload['provider'] ?? null,
                'provider_ref' => $payload['provider_ref'] ?? null,
                'status' => $payload['status'] ?? 'success',
                'raw_payload' => $payload['raw_payload'] ?? null,
                'paid_at' => now(),
            ]);

            $this->recordEvent($business, $subscription, 'payment', $business->subscription_status, 'active', 'Payment recorded');

            $business->forceFill([
                'subscription_status' => 'active',
                'current_period_start' => $subscription->period_start,
                'current_period_end' => $subscription->period_end,
                'last_payment_at' => now(),
            ])->save();

            return $payment;
        });
    }

    public function extendPeriod($currentPeriodEnd): string
    {
        $end = $currentPeriodEnd ? Carbon::parse($currentPeriodEnd) : Carbon::now();
        if ($end->isPast()) {
            $end = Carbon::now();
        }
        return $end->addMonth()->toDateString();
    }

    protected function recordEvent(Business $business, ?Subscription $subscription, string $eventType, ?string $old, ?string $new, ?string $notes = null): void
    {
        SubscriptionEvent::create([
            'business_id' => $business->id,
            'subscription_id' => $subscription?->id,
            'event_type' => $eventType,
            'old_status' => $old,
            'new_status' => $new,
            'notes' => $notes,
        ]);
    }
}
