<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;

class Tenant
{
    public static function businessId(): ?int
    {
        return Auth::user()?->business_id;
    }

    public static function branchId(): ?int
    {
        return session('branch_id') ?? Auth::user()?->branch_id;
    }
}
