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
            if ($user->branch_id && !session()->has('branch_id')) {
                session(['branch_id' => $user->branch_id]);
            }

            view()->share('currentBusiness', $user->business);
            view()->share('currentBranch', $user->branch);
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
