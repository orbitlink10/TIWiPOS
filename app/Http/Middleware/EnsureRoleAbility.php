<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRoleAbility
{
    public function handle(Request $request, Closure $next, string $ability): Response
    {
        $user = $request->user();
        if (!$user) {
            abort(401);
        }

        if (!$user->canAccessAbility($ability)) {
            abort(403, 'Your role does not allow this action.');
        }

        return $next($request);
    }
}
