<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function openTestShift(\App\Models\User $user): void
    {
        $branch = \App\Models\Branch::withoutGlobalScopes()->where('business_id', $user->business_id)->find($user->branch_id)
            ?? \App\Models\Branch::withoutGlobalScopes()->firstOrCreate(
            ['business_id' => $user->business_id, 'code' => 'TEST'],
            ['name' => 'Test Branch', 'is_default' => true]
        );
        $user->forceFill(['branch_id' => $branch->id])->save();
        $this->actingAs($user)->withSession(['branch_id' => $branch->id]);
        \App\Models\CashierShift::firstOrCreate(['active_user_id' => $user->id], [
            'business_id' => $user->business_id, 'branch_id' => $branch->id, 'user_id' => $user->id,
            'status' => 'open', 'opening_cash_cents' => 0, 'opened_at' => now(),
        ]);
    }
}
