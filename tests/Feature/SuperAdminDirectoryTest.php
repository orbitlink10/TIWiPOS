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

    public function test_super_admin_can_view_registered_users_from_admin_console(): void
    {
        [$firstBusiness, $firstBranch] = $this->createActiveTenant('Alpha Stores', 'alpha@example.com');
        [$secondBusiness, $secondBranch] = $this->createActiveTenant('Beta Mart', 'beta@example.com');

        $firstOwner = User::factory()->create([
            'name' => 'Alpha Owner',
            'email' => 'owner1@example.com',
            'business_id' => $firstBusiness->id,
            'branch_id' => $firstBranch->id,
            'role' => User::ROLE_OWNER,
        ]);

        $secondStaff = User::factory()->create([
            'name' => 'Beta Cashier',
            'email' => 'cashier2@example.com',
            'business_id' => $secondBusiness->id,
            'branch_id' => $secondBranch->id,
            'role' => User::ROLE_STAFF,
        ]);

        $superAdmin = User::query()->where('email', 'reisenseo@gmail.com')->firstOrFail();

        $response = $this->actingAs($superAdmin)->get(route('admin.tenants.index'));

        $response->assertOk();
        $response->assertSee('Registered users');
        $response->assertSee($firstOwner->email);
        $response->assertSee($secondStaff->email);
        $response->assertSee($firstBusiness->name);
        $response->assertSee($secondBusiness->name);
        $response->assertSee('reisenseo@gmail.com');
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

    public function test_database_seeder_promotes_reisenseo_account_without_resetting_password(): void
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
        $this->assertTrue($superAdmin->is_super_admin);
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
}
