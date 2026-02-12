<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\User;
use App\Support\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class StaffController extends Controller
{
    protected function ensureOwner(): void
    {
        if (auth()->user()->role !== 'owner') {
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

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'branch_id' => 'nullable|exists:branches,id',
            'role' => 'nullable|in:staff,manager',
        ]);

        $businessId = Tenant::businessId();
        $branchId = $data['branch_id'] ?? Tenant::branchId();

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
        if ((int) $user->business_id !== (int) $businessId || $user->role === 'owner') {
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
}
