<?php

namespace App\Http\Middleware;

use App\Services\SubscriptionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FeatureGate
{
    public function __construct(private SubscriptionService $subscriptionService)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user) {
            return $next($request);
        }

        if ($user->is_super_admin) {
            return $next($request);
        }

        $business = $user->business;
        if (!$business) {
            return redirect()->route('login')->withErrors(['tenant' => 'No business assigned.']);
        }

        $isActive = $this->subscriptionService->checkAndUpdate($business);

        if ($isActive) {
            return $next($request);
        }

        $allowedNames = [
            'home',
            'summary',
            'login',
            'logout',
            'stock',
            'stock.adjust',
            'stock.adjust.store',
            'stock.edit',
            'stock.update',
            'sale',
            'sale.store',
            'billing.show',
            'billing.pay',
            'payments.store',
            'products',
            'products.create',
            'products.store',
            'products.edit',
            'products.update',
            'services',
            'services.create',
            'services.store',
            'services.edit',
            'services.update',
            'service-categories.create',
            'service-categories.store',
            'categories.create',
            'categories.store',
            'suppliers.create',
            'suppliers.store',
            'sales.index',
            'sale.receipt',
        ];

        $routeName = $request->route()?->getName();
        if ($routeName && in_array($routeName, $allowedNames, true)) {
            return $next($request);
        }

        return redirect()->route('billing.show')
            ->withErrors(['subscription' => 'Subscription expired—pay to restore full access.']);
    }
}
