<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffRoleManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_update_staff_role(): void
    {
        [$business, $branch] = $this->createActiveTenant();

        $staff = User::factory()->create([
            'business_id' => $business->id,
            'branch_id' => $branch->id,
            'role' => User::ROLE_STAFF,
        ]);

        $response = $this->actingAs($owner)->patch(route('staff.role', $staff), [
            'role' => User::ROLE_MANAGER,
            'redirect_to' => 'settings.index',
        ]);

        $response->assertRedirect(route('settings.index'));
        $this->assertDatabaseHas('users', [
            'id' => $staff->id,
            'role' => User::ROLE_MANAGER,
        ]);
    }

    public function test_cannot_change_owner_role_via_staff_endpoint(): void
    {
        [, , $owner] = $this->createActiveTenant();

        $response = $this->actingAs($owner)->patch(route('staff.role', $owner), [
            'role' => User::ROLE_MANAGER,
        ]);

        $response->assertNotFound();
    }

    public function test_staff_cannot_update_other_user_role(): void
    {
        [$business, $branch, $owner] = $this->createActiveTenant();

        $staff = User::factory()->create([
            'business_id' => $business->id,
            'branch_id' => $branch->id,
            'role' => User::ROLE_STAFF,
        ]);

        $other = User::factory()->create([
            'business_id' => $business->id,
            'branch_id' => $branch->id,
            'role' => User::ROLE_STAFF,
        ]);

        $response = $this->actingAs($staff)->patch(route('staff.role', $other), [
            'role' => User::ROLE_MANAGER,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('users', [
            'id' => $other->id,
            'role' => User::ROLE_STAFF,
        ]);
    }

    public function test_staff_can_access_catalog_management_routes(): void
    {
        [$business, $branch] = $this->createActiveTenant();

        $staff = User::factory()->create([
            'business_id' => $business->id,
            'branch_id' => $branch->id,
            'role' => User::ROLE_STAFF,
        ]);

        $response = $this->actingAs($staff)->get(route('products.create'));

        $response->assertOk();
    }

    public function test_manager_can_access_catalog_management_routes(): void
    {
        [$business, $branch] = $this->createActiveTenant();

        $manager = User::factory()->create([
            'business_id' => $business->id,
            'branch_id' => $branch->id,
            'role' => User::ROLE_MANAGER,
        ]);

        $response = $this->actingAs($manager)->get(route('products.create'));

        $response->assertOk();
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
            'business_id' => $business->id,
            'branch_id' => $branch->id,
            'role' => User::ROLE_OWNER,
        ]);

        return [$business, $branch, $owner];
    }
}
