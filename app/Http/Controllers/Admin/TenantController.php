<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\SubscriptionEvent;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TenantController extends Controller
{
    public function __construct(private SubscriptionService $subscriptionService)
    {
    }

    public function index()
    {
        $tenants = Business::with('subscriptions')
            ->withCount('users')
            ->orderBy('name')
            ->get();

        $users = User::with(['business', 'branch'])
            ->orderByDesc('is_super_admin')
            ->orderByDesc('created_at')
            ->orderBy('name')
            ->get();

        return view('pages.admin.tenants', compact('tenants', 'users'));
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

    public function updateUserPhone(Request $request, User $user)
    {
        $data = $request->validate([
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $phone = User::normalizePhone($data['phone'] ?? null);
        $updated = false;

        if (User::supportsPhoneColumn()) {
            $user->forceFill(['phone' => $phone])->save();
            $updated = true;
        }

        if ($user->isOwner() && $user->business) {
            $user->business->update(['phone' => $phone]);
            $updated = true;
        }

        if ($user->isOwner() && $user->branch) {
            $user->branch->update(['phone' => $phone]);
            $updated = true;
        }

        if (!$updated) {
            return back()->with('error', 'Phone numbers cannot be saved for this account until the phone migration is applied.');
        }

        return back()->with('status', 'User phone updated.');
    }

    public function status(Request $request, User $user)
    {
        $data = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        if ($user->is_super_admin) {
            return back()->with('error', 'Super admin accounts cannot be deactivated from this page.');
        }

        if ((int) $user->id === (int) $request->user()->id && !$request->boolean('is_active')) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        $user->is_active = (bool) $data['is_active'];
        $user->save();

        if (!$user->is_active && Schema::hasTable('sessions')) {
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }

        $statusText = $user->is_active ? 'activated' : 'deactivated';

        return back()->with('status', "User account {$statusText}.");
    }

    public function destroyUser(Request $request, User $user)
    {
        if ($user->is_super_admin) {
            return back()->with('error', 'Super admin accounts cannot be deleted.');
        }

        if ((int) $user->id === (int) $request->user()->id) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        if (Schema::hasTable('sessions')) {
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }

        $user->delete();

        return back()->with('status', 'User deleted.');
    }
}
