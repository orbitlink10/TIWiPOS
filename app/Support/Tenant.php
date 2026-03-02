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
        $user = Auth::user();
        if (!$user) {
            return null;
        }

        $businessId = (int) ($user->business_id ?? 0);
        $sessionBranchId = (int) session('branch_id', 0);

        if ($sessionBranchId > 0) {
            if ($user->is_super_admin || $businessId === 0) {
                return $sessionBranchId;
            }

            $sessionBranchIsValid = Branch::query()
                ->where('business_id', $businessId)
                ->whereKey($sessionBranchId)
                ->exists();

            if ($sessionBranchIsValid) {
                return $sessionBranchId;
            }

            // Drop stale branch context from a previous tenant/user login.
            session()->forget('branch_id');
        }

        if ($user->branch_id) {
            $userBranchId = (int) $user->branch_id;
            if ($user->is_super_admin || $businessId === 0) {
                session(['branch_id' => $userBranchId]);
                return $userBranchId;
            }

            $userBranchIsValid = Branch::query()
                ->where('business_id', $businessId)
                ->whereKey($userBranchId)
                ->exists();

            if ($userBranchIsValid) {
                session(['branch_id' => $userBranchId]);
                return $userBranchId;
            }
        }

        if ($user->is_super_admin || $businessId === 0) {
            return null;
        }

        $branchId = Branch::where('business_id', $businessId)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->value('id');

        if ($branchId) {
            session(['branch_id' => $branchId]);
            if ((int) $user->branch_id !== (int) $branchId) {
                $user->forceFill(['branch_id' => $branchId])->save();
            }

            return (int) $branchId;
        }

        session()->forget('branch_id');

        return null;
    }
}
