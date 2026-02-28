<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\User;
use App\Support\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class StaffController extends Controller
{
    protected function ensureOwner(): void
    {
        if (!auth()->user()->canAccessAbility('manage_staff')) {
            abort(403, 'Only admins can manage staff.');
        }
    }

    public function index()
    {
        $this->ensureOwner();
        $businessId = Tenant::businessId();

        $staff = User::with('branch')
            ->where('business_id', $businessId)
            ->where('role', '!=', 'owner')
            ->orderBy('name')
            ->get();

        $branches = Branch::where('business_id', $businessId)->orderBy('name')->get();

        return view('pages.staff', compact('staff', 'branches'));
    }

    public function store(Request $request)
    {
        $this->ensureOwner();

        $businessId = Tenant::businessId();

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'branch_id' => [
                'nullable',
                Rule::exists('branches', 'id')->where(fn ($query) => $query->where('business_id', $businessId)),
            ],
            'role' => ['nullable', Rule::in(array_keys(User::assignableRoles()))],
        ]);

        $branchId = $data['branch_id'] ?? Branch::query()
            ->where('business_id', $businessId)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->value('id')
            ?? Tenant::branchId();

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'business_id' => $businessId,
            'branch_id' => $branchId,
            'role' => $data['role'] ?? 'staff',
            'is_active' => true,
        ]);

        $redirectTo = $request->input('redirect_to') === 'settings.index' ? 'settings.index' : 'staff.index';

        return redirect()->route($redirectTo)->with('status', 'Staff profile created.');
    }

    public function status(Request $request, User $user)
    {
        $this->ensureOwner();

        $businessId = Tenant::businessId();
        if ((int) $user->business_id !== (int) $businessId || $user->role === User::ROLE_OWNER) {
            abort(404);
        }

        $data = $request->validate([
            'is_active' => 'required|boolean',
        ]);

        if ((int) $user->id === (int) auth()->id() && !$data['is_active']) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        $user->is_active = (bool) $data['is_active'];
        $user->save();

        $redirectTo = $request->input('redirect_to') === 'settings.index' ? 'settings.index' : 'staff.index';
        $statusText = $user->is_active ? 'activated' : 'deactivated';

        return redirect()->route($redirectTo)->with('status', "Staff account {$statusText}.");
    }

    public function role(Request $request, User $user)
    {
        $this->ensureOwner();

        $businessId = Tenant::businessId();
        if ((int) $user->business_id !== (int) $businessId || $user->role === User::ROLE_OWNER) {
            abort(404);
        }

        $data = $request->validate([
            'role' => ['required', Rule::in(array_keys(User::assignableRoles()))],
        ]);

        $user->role = $data['role'];
        $user->save();

        $redirectTo = $request->input('redirect_to') === 'settings.index' ? 'settings.index' : 'staff.index';
        $roleText = User::assignableRoles()[$user->role] ?? ucfirst($user->role);

        return redirect()->route($redirectTo)->with('status', "Staff role updated to {$roleText}.");
    }

    public function branch(Request $request, User $user)
    {
        $this->ensureOwner();

        $businessId = Tenant::businessId();
        if ((int) $user->business_id !== (int) $businessId || $user->role === User::ROLE_OWNER) {
            abort(404);
        }

        $data = $request->validate([
            'branch_id' => [
                'required',
                Rule::exists('branches', 'id')->where(fn ($query) => $query->where('business_id', $businessId)),
            ],
        ]);

        $user->branch_id = (int) $data['branch_id'];
        $user->save();

        $redirectTo = $request->input('redirect_to') === 'settings.index' ? 'settings.index' : 'staff.index';

        return redirect()->route($redirectTo)->with('status', 'Staff branch updated.');
    }
}
