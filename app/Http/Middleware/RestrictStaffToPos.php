<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictStaffToPos
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()?->isPosOnly()) {
            return $next($request);
        }

        if ($request->routeIs('home', 'login')) {
            return redirect()->route('sale');
        }

        if (!$request->routeIs('sale', 'sale.store', 'sale.receipt', 'shifts.*', 'logout')) {
            abort(403, 'Staff accounts can only access the point of sale.');
        }

        return $next($request);
    }
}
