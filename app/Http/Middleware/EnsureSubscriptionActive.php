<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureSubscriptionActive
{
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check()) {
            $business = $request->user()->business;
            $isActive = $business &&
                $business->subscription_status === 'active' &&
                (! $business->current_period_end || $business->current_period_end->isFuture());

            if (! $isActive) {
                return redirect()->route('home')->withErrors([
                    'subscription' => 'Your subscription is inactive. Please renew to continue.',
                ]);
            }
        }

        return $next($request);
    }
}
