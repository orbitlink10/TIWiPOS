<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPhoneAndContentAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_update_profile_phone_number(): void
    {
        [$business, $branch, $owner] = $this->createActiveTenant();

        $response = $this->actingAs($owner)->patch(route('settings.profile.update'), [
            'name' => 'Owner Updated',
            'phone' => '+254733123456',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $owner->id,
            'name' => 'Owner Updated',
            'phone' => '+254733123456',
            'business_id' => $business->id,
            'branch_id' => $branch->id,
        ]);
    }

    public function test_non_super_admin_cannot_access_pages_cms(): void
    {
        [, , $owner] = $this->createActiveTenant();

        $response = $this->actingAs($owner)->get(route('content.index'));

        $response->assertForbidden();
    }

    public function test_super_admin_can_access_pages_cms(): void
    {
        $superAdmin = User::query()->where('email', 'reisenseo@gmail.com')->firstOrFail();

        $response = $this->actingAs($superAdmin)->get(route('content.index'));

        $response->assertOk();
        $response->assertSee('Pages');
    }

    private function createActiveTenant(): array
    {
        $business = Business::create([
            'name' => 'Test Business',
            'slug' => 'test-business-' . uniqid(),
            'billing_email' => 'owner@example.com',
            'status' => 'active',
            'subscription_status' => 'active',
            'current_period_start' => now()->toDateString(),
            'current_period_end' => now()->addMonth()->toDateString(),
        ]);

        $branch = Branch::create([
            'business_id' => $business->id,
            'name' => 'Main Branch',
            'code' => 'MAIN',
            'is_default' => true,
        ]);

        Subscription::create([
            'business_id' => $business->id,
            'plan' => 'standard',
            'interval' => 'monthly',
            'status' => 'active',
            'amount' => 0,
            'currency' => 'KES',
            'period_start' => now()->toDateString(),
            'period_end' => now()->addMonth()->toDateString(),
            'grace_until' => now()->addDays(3),
            'last_payment_at' => now(),
        ]);

        $owner = User::factory()->create([
            'name' => 'Owner',
            'email' => 'owner@example.com',
            'business_id' => $business->id,
            'branch_id' => $branch->id,
            'role' => User::ROLE_OWNER,
        ]);

        return [$business, $branch, $owner];
    }
}
