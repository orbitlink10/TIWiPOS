<?php

namespace App\Http\Middleware;

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
        }

        return $next($request);
    }
}
