<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\User;
use App\Support\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class SettingsController extends Controller
{
    private function ensureCanManageProfile(): void
    {
        if (!auth()->user()->canAccessAbility('manage_profile')) {
            abort(403, 'Your role does not allow this action.');
        }
    }

    private function canManageAdminSettings(): bool
    {
        return auth()->user()->canAccessAbility('manage_settings');
    }

    public function index()
    {
        $this->ensureCanManageProfile();

        $user = auth()->user();
        $canManageAdminSettings = $this->canManageAdminSettings();

        $staff = collect();
        $branches = collect();

        if ($canManageAdminSettings) {
            $businessId = Tenant::businessId();

            $staff = User::with('branch')
                ->where('business_id', $businessId)
                ->where('role', '!=', 'owner')
                ->orderBy('name')
                ->get();

            $branches = Branch::where('business_id', $businessId)->orderBy('name')->get();
        }

        return view('pages.settings', compact('user', 'canManageAdminSettings', 'staff', 'branches'));
    }

    public function updateProfile(Request $request)
    {
        $this->ensureCanManageProfile();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $user = $request->user();
        $updates = User::mergePhoneAttribute([
            'name' => trim($data['name']),
        ], $data['phone'] ?? null);

        if ($request->hasFile('profile_photo')) {
            $newPath = $request->file('profile_photo')->store('profile-photos', 'public');
            $oldPath = $user->profile_photo_path;
            $updates['profile_photo_path'] = $newPath;
            $user->forceFill($updates)->save();

            if ($oldPath && $oldPath !== $newPath) {
                Storage::disk('public')->delete($oldPath);
            }
        } else {
            $user->forceFill($updates)->save();
        }

        $phone = User::normalizePhone($data['phone'] ?? null);
        if ($user->isOwner()) {
            $user->business?->update(['phone' => $phone]);
            $user->branch?->update(['phone' => $phone]);
        }

        return back()->with('status', 'Profile updated successfully.');
    }

    public function updatePassword(Request $request)
    {
        $this->ensureCanManageProfile();

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
        ]);

        $user = $request->user();

        if (!Hash::check($data['current_password'], $user->password)) {
            return back()->withErrors([
                'current_password' => 'Current password is incorrect.',
            ]);
        }

        $user->forceFill([
            'password' => Hash::make($data['password']),
        ])->save();

        return back()->with('status', 'Password updated successfully.');
    }
}
