<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_inactive_subscription_blocks_restricted_route(): void
    {
        $business = Business::create([
            'name' => 'Test Biz',
            'slug' => 'test-biz',
            'billing_email' => 'biz@example.com',
            'subscription_status' => 'inactive',
            'status' => 'inactive',
            'current_period_start' => now()->toDateString(),
            'current_period_end' => now()->toDateString(),
        ]);

        $user = User::factory()->create([
            'business_id' => $business->id,
            'role' => 'owner',
        ]);

        $response = $this->actingAs($user)->get('/sale');
        $response->assertRedirect(route('billing.show'));
    }
}
