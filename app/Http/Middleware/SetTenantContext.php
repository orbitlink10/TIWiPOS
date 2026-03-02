<?php

namespace App\Http\Middleware;

use App\Models\Branch;
use Closure;
use Illuminate\Http\Request;

class SetTenantContext
{
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check()) {
            $user = $request->user();

            // Heal legacy/mismatched staff records where branch belongs to a different business.
            if ($user->branch_id) {
                $userBranch = Branch::withoutGlobalScope('business')
                    ->select('id', 'business_id')
                    ->find($user->branch_id);

                if ($userBranch && (int) $user->business_id !== (int) $userBranch->business_id) {
                    $user->forceFill(['business_id' => (int) $userBranch->business_id])->save();
                    $user->business_id = (int) $userBranch->business_id;
                    $user->unsetRelation('business');
                }
            }

            // Ensure active branch always belongs to the authenticated user's business.
            $sessionBranchId = (int) session('branch_id', 0);
            if ($sessionBranchId > 0) {
                $sessionBranchBusinessId = Branch::withoutGlobalScope('business')
                    ->whereKey($sessionBranchId)
                    ->value('business_id');

                if (!$sessionBranchBusinessId || (int) $sessionBranchBusinessId !== (int) $user->business_id) {
                    session()->forget('branch_id');
                    $sessionBranchId = 0;
                }
            }

            if ($sessionBranchId === 0) {
                $fallbackBranchId = null;
                if ($user->branch_id) {
                    $fallbackBranchId = Branch::query()
                        ->where('business_id', $user->business_id)
                        ->where('id', $user->branch_id)
                        ->value('id');
                }

                if (!$fallbackBranchId) {
                    $fallbackBranchId = Branch::query()
                        ->where('business_id', $user->business_id)
                        ->orderByDesc('is_default')
                        ->orderBy('id')
                        ->value('id');
                }

                if ($fallbackBranchId) {
                    session(['branch_id' => (int) $fallbackBranchId]);
                    if ((int) $user->branch_id !== (int) $fallbackBranchId) {
                        $user->forceFill(['branch_id' => (int) $fallbackBranchId])->save();
                        $user->branch_id = (int) $fallbackBranchId;
                    }
                }
            }

            $currentBranchId = (int) session('branch_id', (int) ($user->branch_id ?? 0));
            $currentBranch = $currentBranchId > 0
                ? Branch::query()->whereKey($currentBranchId)->first()
                : null;

            view()->share('currentBusiness', $user->business);
            view()->share('currentBranch', $currentBranch);
            view()->share(
                'availableBranches',
                Branch::query()
                    ->where('business_id', $user->business_id)
                    ->orderBy('name')
                    ->get(['id', 'name'])
            );
        }

        return $next($request);
    }
}
