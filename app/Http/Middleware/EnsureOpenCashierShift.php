<?php

namespace App\Http\Middleware;

use App\Models\CashierShift;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOpenCashierShift
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!CashierShift::currentQuery()->exists()) {
            if ($request->expectsJson()) return response()->json(['message' => 'Open your cashier shift before making a sale.', 'url' => route('shifts.index')], 422);
            return redirect()->route('shifts.index')->with('status', 'Open your cashier shift before making a sale.');
        }
        return $next($request);
    }
}
