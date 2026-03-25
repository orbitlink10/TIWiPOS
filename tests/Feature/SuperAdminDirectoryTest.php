<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SuperAdminDirectoryTest extends TestCase
{
    use RefreshDatabase;

    private ?User $superAdminUser = null;

    public function test_super_admin_can_view_registered_users_from_admin_console(): void
    {
        [$firstBusiness, $firstBranch] = $this->createActiveTenant('Alpha Stores', 'alpha@example.com');
        [$secondBusiness, $secondBranch] = $this->createActiveTenant('Beta Mart', 'beta@example.com');

        $firstOwner = User::factory()->create([
            'name' => 'Alpha Owner',
            'email' => 'owner1@example.com',
            'phone' => '+254700111222',
            'business_id' => $firstBusiness->id,
            'branch_id' => $firstBranch->id,
            'role' => User::ROLE_OWNER,
        ]);

        $secondStaff = User::factory()->create([
            'name' => 'Beta Cashier',
            'email' => 'cashier2@example.com',
            'phone' => '+254711222333',
            'business_id' => $secondBusiness->id,
            'branch_id' => $secondBranch->id,
            'role' => User::ROLE_STAFF,
        ]);

        $superAdmin = $this->superAdmin();

        $response = $this->actingAs($superAdmin)->get(route('admin.tenants.index'));

        $response->assertOk();
        $response->assertSee('Registered users');
        $response->assertSee('Phone');
        $response->assertSee($firstOwner->email);
        $response->assertSee($firstOwner->phone);
        $response->assertSee($secondStaff->email);
        $response->assertSee($secondStaff->phone);
        $response->assertSee($firstBusiness->name);
        $response->assertSee($secondBusiness->name);
        $response->assertSee($superAdmin->email);
    }

    public function test_non_super_admin_cannot_view_admin_console(): void
    {
        [$business, $branch] = $this->createActiveTenant('Gamma Retail', 'gamma@example.com');

        $owner = User::factory()->create([
            'business_id' => $business->id,
            'branch_id' => $branch->id,
            'role' => User::ROLE_OWNER,
        ]);

        $response = $this->actingAs($owner)->get(route('admin.tenants.index'));

        $response->assertForbidden();
    }

    public function test_super_admin_directory_falls_back_to_business_phone_for_owner_accounts(): void
    {
        [$business, $branch] = $this->createActiveTenant('Phone Fallback Ltd', 'fallback@example.com');
        $business->update(['phone' => '+254733000111']);

        User::factory()->create([
            'name' => 'Fallback Owner',
            'email' => 'fallback-owner@example.com',
            'phone' => null,
            'business_id' => $business->id,
            'branch_id' => $branch->id,
            'role' => User::ROLE_OWNER,
        ]);

        $response = $this->actingAs($this->superAdmin())->get(route('admin.tenants.index'));

        $response->assertOk();
        $response->assertSee('+254733000111');
    }

    public function test_super_admin_directory_falls_back_to_branch_phone_for_owner_accounts(): void
    {
        [$business, $branch] = $this->createActiveTenant('Branch Phone Ltd', 'branch-fallback@example.com');
        $branch->update(['phone' => '+254722333444']);

        User::factory()->create([
            'name' => 'Branch Fallback Owner',
            'email' => 'branch-fallback-owner@example.com',
            'phone' => null,
            'business_id' => $business->id,
            'branch_id' => $branch->id,
            'role' => User::ROLE_OWNER,
        ]);

        $response = $this->actingAs($this->superAdmin())->get(route('admin.tenants.index'));

        $response->assertOk();
        $response->assertSee('+254722333444');
    }

    public function test_super_admin_can_update_registered_user_phone(): void
    {
        [$business, $branch] = $this->createActiveTenant('Phone Update Ltd', 'phone-update@example.com');

        $user = User::factory()->create([
            'name' => 'Phone Update Owner',
            'email' => 'phone-update-owner@example.com',
            'phone' => null,
            'business_id' => $business->id,
            'branch_id' => $branch->id,
            'role' => User::ROLE_OWNER,
        ]);

        $response = $this->actingAs($this->superAdmin())->patch(route('admin.users.phone', $user), [
            'phone' => '0714804532',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'User phone updated.');
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'phone' => '0714804532',
        ]);
        $this->assertDatabaseHas('businesses', [
            'id' => $business->id,
            'phone' => '0714804532',
        ]);
        $this->assertDatabaseHas('branches', [
            'id' => $branch->id,
            'phone' => '0714804532',
        ]);
    }

    public function test_super_admin_can_toggle_registered_user_status(): void
    {
        [$business, $branch] = $this->createActiveTenant('Status Toggle Ltd', 'status@example.com');

        $user = User::factory()->create([
            'name' => 'Status User',
            'email' => 'status-user@example.com',
            'business_id' => $business->id,
            'branch_id' => $branch->id,
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        DB::table('sessions')->insert([
            'id' => 'user-session-status',
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => base64_encode('test'),
            'last_activity' => now()->timestamp,
        ]);

        $deactivateResponse = $this->actingAs($this->superAdmin())->patch(route('admin.users.status', $user), [
            'is_active' => 0,
        ]);

        $deactivateResponse->assertRedirect();
        $deactivateResponse->assertSessionHas('status', 'User account deactivated.');
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_active' => false,
        ]);
        $this->assertDatabaseMissing('sessions', [
            'id' => 'user-session-status',
        ]);

        $activateResponse = $this->actingAs($this->superAdmin())->patch(route('admin.users.status', $user), [
            'is_active' => 1,
        ]);

        $activateResponse->assertRedirect();
        $activateResponse->assertSessionHas('status', 'User account activated.');
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_active' => true,
        ]);
    }

    public function test_super_admin_can_delete_registered_user(): void
    {
        [$business, $branch] = $this->createActiveTenant('Delete User Ltd', 'delete@example.com');

        $user = User::factory()->create([
            'name' => 'Delete Me',
            'email' => 'delete-me@example.com',
            'business_id' => $business->id,
            'branch_id' => $branch->id,
            'role' => User::ROLE_STAFF,
        ]);

        DB::table('sessions')->insert([
            'id' => 'user-session-delete',
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => base64_encode('test'),
            'last_activity' => now()->timestamp,
        ]);

        $response = $this->actingAs($this->superAdmin())->delete(route('admin.users.destroy', $user));

        $response->assertRedirect();
        $response->assertSessionHas('status', 'User deleted.');
        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);
        $this->assertDatabaseMissing('sessions', [
            'id' => 'user-session-delete',
        ]);
    }

    public function test_database_seeder_keeps_reisenseo_out_of_super_admin_without_resetting_password(): void
    {
        $superAdmin = User::query()->where('email', 'reisenseo@gmail.com')->firstOrFail();

        DB::table('users')
            ->where('id', $superAdmin->id)
            ->update([
                'name' => 'Francis',
                'password' => Hash::make('custom-secret'),
                'role' => User::ROLE_MANAGER,
                'is_active' => false,
                'is_super_admin' => false,
                'updated_at' => now(),
            ]);

        $this->seed(DatabaseSeeder::class);

        $superAdmin->refresh();

        $this->assertSame(User::ROLE_OWNER, $superAdmin->role);
        $this->assertTrue($superAdmin->is_active);
        $this->assertFalse($superAdmin->is_super_admin);
        $this->assertSame('Francis', $superAdmin->name);
        $this->assertTrue(Hash::check('custom-secret', $superAdmin->password));
    }

    private function createActiveTenant(string $name, string $billingEmail): array
    {
        $business = Business::create([
            'name' => $name,
            'slug' => str($name)->slug() . '-' . uniqid(),
            'billing_email' => $billingEmail,
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

        return [$business, $branch];
    }

    private function superAdmin(): User
    {
        return $this->superAdminUser ??= User::factory()->create([
            'name' => 'Test Super Admin',
            'email' => 'super-admin@example.com',
            'role' => User::ROLE_OWNER,
            'is_active' => true,
            'is_super_admin' => true,
        ]);
    }
}
