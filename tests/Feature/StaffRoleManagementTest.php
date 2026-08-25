<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Category;
use App\Models\Product;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffRoleManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_update_staff_role(): void
    {
        [$business, $branch, $owner] = $this->createActiveTenant();

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

    public function test_staff_can_edit_product_but_cannot_delete_product(): void
    {
        [$business, $branch] = $this->createActiveTenant();

        $staff = User::factory()->create([
            'business_id' => $business->id,
            'branch_id' => $branch->id,
            'role' => User::ROLE_STAFF,
        ]);

        $category = $this->createSubCategory($business);

        $product = Product::create([
            'business_id' => $business->id,
            'name' => 'Router',
            'sku' => 'RTR-001',
            'serial_number' => 'SN-RTR-001',
            'category_id' => $category->id,
            'cost' => 1000,
            'price' => 1500,
            'stock_alert' => 0,
            'recorded_at' => now()->toDateString(),
            'is_active' => true,
        ]);

        $this->actingAs($staff)
            ->get(route('products.edit', $product))
            ->assertOk();

        $this->actingAs($staff)
            ->put(route('products.update', $product), [
                'name' => 'Updated Router',
                'sku' => 'RTR-001',
                'serial_number' => 'SN-RTR-001',
                'category_id' => $category->id,
                'cost' => 1000,
                'price' => 1600,
                'stock_alert' => 0,
                'stock' => 0,
                'stock_location' => 'main',
                'recorded_at' => now()->toDateString(),
                'is_active' => 1,
            ])
            ->assertRedirect(route('products'));

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Router',
            'price' => 1600,
        ]);

        $this->actingAs($staff)
            ->delete(route('products.destroy', $product))
            ->assertForbidden();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
        ]);
    }

    public function test_staff_product_list_hides_delete_action_but_keeps_edit_action(): void
    {
        [$business, $branch] = $this->createActiveTenant();

        $staff = User::factory()->create([
            'business_id' => $business->id,
            'branch_id' => $branch->id,
            'role' => User::ROLE_STAFF,
        ]);

        $category = $this->createSubCategory($business);

        $product = Product::create([
            'business_id' => $business->id,
            'name' => 'Router',
            'sku' => 'RTR-002',
            'serial_number' => 'SN-RTR-002',
            'category_id' => $category->id,
            'cost' => 1000,
            'price' => 1500,
            'stock_alert' => 0,
            'recorded_at' => now()->toDateString(),
            'is_active' => true,
        ]);

        $this->actingAs($staff)
            ->get(route('products'))
            ->assertOk()
            ->assertSee(route('products.edit', $product), false)
            ->assertDontSee('action="' . route('products.destroy', $product) . '"', false)
            ->assertDontSee('Delete');
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

    private function createSubCategory(Business $business): Category
    {
        $parent = Category::create([
            'business_id' => $business->id,
            'name' => 'Hardware',
            'slug' => 'hardware-' . uniqid(),
            'parent_id' => null,
            'is_active' => true,
        ]);

        return Category::create([
            'business_id' => $business->id,
            'name' => 'Routers',
            'slug' => 'routers-' . uniqid(),
            'parent_id' => $parent->id,
            'is_active' => true,
        ]);
    }
}
