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
            'login',
            'logout',
            'stock-control.index',
            'stock-control.adjust',
            'stock-control.adjust.store',
            'stock.adjust',
            'stock.adjust.store',
            'stock.edit',
            'stock.update',
            'sale',
            'shifts.index',
            'shifts.open',
            'shifts.show',
            'shifts.close',
            'sale.store',
            'billing.show',
            'billing.pay',
            'payments.store',
            'products',
            'products.show',
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
            'service-workers.store',
            'service-workers.edit',
            'service-workers.update',
            'service-workers.status',
            'service-visits.store',
            'service-visits.edit',
            'service-visits.update',
            'service-visits.status',
            'categories.create',
            'categories.store',
            'sub-categories.create',
            'sub-categories.store',
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
