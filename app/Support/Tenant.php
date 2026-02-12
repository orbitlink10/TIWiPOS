<?php

namespace App\Support;

use App\Models\Branch;
use Illuminate\Support\Facades\Auth;

class Tenant
{
    public static function businessId(): ?int
    {
        return Auth::user()?->business_id;
    }

    public static function branchId(): ?int
    {
        $sessionBranchId = session('branch_id');
        if ($sessionBranchId) {
            return (int) $sessionBranchId;
        }

        $user = Auth::user();
        if (!$user) {
            return null;
        }

        if ($user->branch_id) {
            return (int) $user->branch_id;
        }

        if ($user->is_super_admin || !$user->business_id) {
            return null;
        }

        $branchId = Branch::where('business_id', $user->business_id)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->value('id');

        if ($branchId) {
            session(['branch_id' => $branchId]);
            $user->forceFill(['branch_id' => $branchId])->save();
            return (int) $branchId;
        }

        return null;
    }
}
