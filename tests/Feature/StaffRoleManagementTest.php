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

    public function test_owner_can_edit_staff_details_without_changing_password(): void
    {
        [$business, $branch, $owner] = $this->createActiveTenant();
        $staff = User::factory()->create([
            'business_id' => $business->id, 'branch_id' => $branch->id, 'role' => User::ROLE_STAFF,
        ]);
        $password = $staff->password;
        $newBranch = Branch::create([
            'business_id' => $business->id, 'name' => 'Second Branch', 'code' => 'SECOND',
        ]);

        $this->actingAs($owner)->get(route('staff.show', $staff))
            ->assertOk()->assertSee(route('staff.edit', $staff), false);
        $this->get(route('staff.edit', $staff))->assertOk()->assertSee($staff->email);
        $this->patch(route('staff.update', $staff), [
            'name' => 'Updated Staff', 'email' => $staff->email, 'phone' => '+254711222333',
            'branch_id' => $newBranch->id, 'role' => User::ROLE_MANAGER, 'is_active' => 0,
            'password' => '', 'password_confirmation' => '', 'is_super_admin' => true,
        ])->assertRedirect(route('staff.show', $staff))->assertSessionHas('status', 'Staff profile updated.');

        $staff->refresh();
        $this->assertSame('Updated Staff', $staff->name);
        $this->assertSame('+254711222333', $staff->phone);
        $this->assertSame($newBranch->id, $staff->branch_id);
        $this->assertSame(User::ROLE_MANAGER, $staff->role);
        $this->assertFalse($staff->is_active);
        $this->assertFalse($staff->is_super_admin);
        $this->assertSame($password, $staff->password);
        $this->assertSame($business->id, $staff->business_id);
    }

    public function test_owner_can_change_staff_email_and_reset_password(): void
    {
        [$business, $branch, $owner] = $this->createActiveTenant();
        $staff = User::factory()->create([
            'business_id' => $business->id, 'branch_id' => $branch->id, 'role' => User::ROLE_STAFF,
        ]);

        $this->actingAs($owner)->patch(route('staff.update', $staff), [
            'name' => $staff->name, 'email' => 'updated-staff@example.com', 'phone' => '',
            'branch_id' => $branch->id, 'role' => User::ROLE_STAFF, 'is_active' => 1,
            'password' => 'new-staff-password', 'password_confirmation' => 'new-staff-password',
        ])->assertRedirect(route('staff.show', $staff));

        $staff->refresh();
        $this->assertSame('updated-staff@example.com', $staff->email);
        $this->assertNull($staff->phone);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('new-staff-password', $staff->password));
    }

    public function test_staff_update_validates_email_branch_role_and_password(): void
    {
        [$business, $branch, $owner] = $this->createActiveTenant();
        [, $foreignBranch] = $this->createActiveTenant();
        $staff = User::factory()->create([
            'business_id' => $business->id, 'branch_id' => $branch->id, 'role' => User::ROLE_STAFF,
        ]);
        $original = $staff->refresh()->getAttributes();
        $data = [
            'name' => $staff->name, 'email' => $staff->email, 'branch_id' => $branch->id,
            'role' => User::ROLE_STAFF, 'is_active' => 1,
        ];
        foreach ([
            ['email' => $owner->email],
            ['branch_id' => $foreignBranch->id],
            ['role' => User::ROLE_OWNER],
            ['password' => 'long-enough-password', 'password_confirmation' => 'different-password'],
        ] as $invalid) {
            $this->actingAs($owner)->from(route('staff.edit', $staff))
                ->patch(route('staff.update', $staff), array_merge($data, $invalid))
                ->assertRedirect(route('staff.edit', $staff))
                ->assertSessionHasErrors(array_key_first($invalid));
            $this->assertSame($original, $staff->fresh()->getAttributes());
        }
    }

    public function test_staff_edit_and_update_reject_foreign_and_owner_records(): void
    {
        [, , $owner] = $this->createActiveTenant();
        [$business, $branch] = $this->createActiveTenant();
        $staff = User::factory()->create([
            'business_id' => $business->id, 'branch_id' => $branch->id, 'role' => User::ROLE_STAFF,
        ]);
        foreach ([$staff, $owner] as $target) {
            $this->actingAs($owner)->get(route('staff.edit', $target))->assertNotFound();
            $this->patch(route('staff.update', $target), ['name' => 'Changed'])->assertNotFound();
        }
    }

    public function test_staff_and_managers_cannot_edit_staff_details(): void
    {
        [$business, $branch] = $this->createActiveTenant();
        foreach ([User::ROLE_STAFF, User::ROLE_MANAGER] as $role) {
            $user = User::factory()->create([
                'business_id' => $business->id, 'branch_id' => $branch->id, 'role' => $role,
            ]);
            $this->actingAs($user)->get(route('staff.edit', $user))->assertForbidden();
            $this->patch(route('staff.update', $user), ['name' => 'Changed'])->assertForbidden();
        }
    }

    public function test_owner_can_view_staff_profile_from_team_list(): void
    {
        [$business, $branch, $owner] = $this->createActiveTenant();
        $staff = User::factory()->create([
            'business_id' => $business->id,
            'branch_id' => $branch->id,
            'role' => User::ROLE_STAFF,
            'phone' => '+254700123456',
            'is_active' => false,
        ]);

        $this->actingAs($owner)->get(route('staff.index'))
            ->assertOk()->assertSee(route('staff.show', $staff), false);

        $this->get(route('staff.show', $staff))->assertOk()
            ->assertSee($staff->name)->assertSee($staff->email)
            ->assertSee('+254700123456')->assertSee($branch->name)
            ->assertSee('Inactive')->assertDontSee($staff->password);
    }

    public function test_staff_profile_rejects_other_business_and_owner_records(): void
    {
        [, , $owner] = $this->createActiveTenant();
        [$otherBusiness, $otherBranch] = $this->createActiveTenant();
        $otherStaff = User::factory()->create([
            'business_id' => $otherBusiness->id,
            'branch_id' => $otherBranch->id,
            'role' => User::ROLE_STAFF,
        ]);

        $this->actingAs($owner)->get(route('staff.show', $otherStaff))->assertNotFound();
        $this->get(route('staff.show', $owner))->assertNotFound();
    }

    public function test_staff_and_managers_cannot_view_staff_profiles(): void
    {
        [$business, $branch] = $this->createActiveTenant();
        foreach ([User::ROLE_STAFF, User::ROLE_MANAGER] as $role) {
            $user = User::factory()->create([
                'business_id' => $business->id,
                'branch_id' => $branch->id,
                'role' => $role,
            ]);
            $this->actingAs($user)->get(route('staff.show', $user))->assertForbidden();
        }
    }

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

    public function test_staff_cannot_access_other_modules_or_modify_inventory(): void
    {
        [$business, $branch] = $this->createActiveTenant();
        $staff = User::factory()->create([
            'business_id' => $business->id, 'branch_id' => $branch->id, 'role' => User::ROLE_STAFF,
        ]);
        $this->actingAs($staff);
        foreach (['products', 'products.create', 'stock-control.index', 'stock.adjust', 'suppliers.index',
            'purchases.index', 'services', 'quotations.index', 'todos.index', 'reports.index',
            'staff.index', 'settings.index', 'billing.show', 'branches.index'] as $route) {
            $this->get(route($route))->assertForbidden();
        }
        foreach (['products.store', 'stock.adjust.store', 'suppliers.store', 'purchases.store',
            'quotations.store', 'todos.store', 'branches.switch', 'payments.store'] as $route) {
            $this->post(route($route), [])->assertForbidden();
        }
        $this->assertFalse($staff->canAccessAbility('manage_catalog'));
        $this->assertFalse($staff->canAccessAbility('view_stock'));
        $this->assertFalse($staff->canAccessAbility('add_stock'));
    }

    public function test_staff_login_always_redirects_to_pos_and_can_sign_out(): void
    {
        [$business, $branch] = $this->createActiveTenant();
        $staff = User::factory()->create([
            'business_id' => $business->id, 'branch_id' => $branch->id, 'role' => User::ROLE_STAFF,
            'password' => 'staff-login-password', 'is_active' => true,
        ]);
        $this->withSession(['url.intended' => route('products')])->post(route('login.store'), [
            'email' => $staff->email, 'password' => 'staff-login-password',
        ])->assertRedirect(route('sale'));
        $this->assertAuthenticatedAs($staff);
        $this->get(route('home'))->assertRedirect(route('sale'));
        $this->post(route('logout'))->assertRedirect();
        $this->assertGuest();
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
