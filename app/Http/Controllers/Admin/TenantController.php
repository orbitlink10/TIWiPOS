<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\SubscriptionEvent;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    public function __construct(private SubscriptionService $subscriptionService)
    {
    }

    public function index()
    {
        $tenants = Business::with('subscriptions')->orderBy('name')->get();
        return view('pages.admin.tenants', compact('tenants'));
    }

    public function activate(Business $business)
    {
        $business->update(['subscription_status' => 'active', 'status' => 'active']);
        SubscriptionEvent::create([
            'business_id' => $business->id,
            'subscription_id' => null,
            'event_type' => 'admin_activate',
            'old_status' => 'inactive',
            'new_status' => 'active',
            'notes' => 'Activated by super admin',
        ]);

        return back()->with('status', 'Tenant activated.');
    }

    public function deactivate(Business $business)
    {
        $business->update(['subscription_status' => 'inactive', 'status' => 'inactive']);
        SubscriptionEvent::create([
            'business_id' => $business->id,
            'subscription_id' => null,
            'event_type' => 'admin_deactivate',
            'old_status' => 'active',
            'new_status' => 'inactive',
            'notes' => 'Deactivated by super admin',
        ]);

        return back()->with('status', 'Tenant deactivated.');
    }

    public function impersonate(Request $request, Business $business)
    {
        $user = $business->users()->first();
        if (!$user) {
            return back()->withErrors(['tenant' => 'No user found to impersonate.']);
        }

        auth()->login($user);
        return redirect()->route('home')->with('status', 'Impersonating ' . $business->name);
    }
}
