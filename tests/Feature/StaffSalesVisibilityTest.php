<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Sale;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class StaffSalesVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_only_use_pos_and_their_own_receipts(): void
    {
        [$owner, $staffOne, $staffTwo] = $this->createTenantUsers();
        $product = $this->createProductWithStock($owner->business_id, $owner->branch_id);

        $staffOneSale = $this->createSaleForUser($staffOne, $product->id, 'First Customer');
        $staffTwoSale = $this->createSaleForUser($staffTwo, $product->id, 'Second Customer');

        $history = $this->actingAs($staffOne)->get(route('sales.index'));
        $history->assertForbidden();

        $summary = $this->actingAs($staffOne)->get(route('summary'));
        $summary->assertForbidden();

        $this->get(route('sale'))->assertOk()->assertSee('Sign Out')
            ->assertDontSee('View Sales Summary')->assertDontSee('Back to Dashboard')
            ->assertDontSee('Open stock list')->assertDontSee('Sales history');
        $this->get(route('sale.receipt', $staffOneSale))->assertOk();

        $this->actingAs($staffOne)
            ->get(route('sale.receipt', $staffTwoSale))
            ->assertNotFound();

        $this->actingAs($staffOne)
            ->get(route('sales.edit', $staffTwoSale))
            ->assertForbidden();
    }

    public function test_only_admins_can_edit_completed_sales(): void
    {
        [$owner, $staff] = $this->createTenantUsers();
        $product = $this->createProductWithStock($owner->business_id, $owner->branch_id);
        $sale = $this->createSaleForUser($staff, $product->id, 'Original Customer');
        $payload = [
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
            'method' => 'cash',
            'customer_name' => 'Updated Customer',
            'apply_tax' => 0,
        ];

        foreach ([User::ROLE_STAFF, User::ROLE_MANAGER] as $role) {
            $staff->update(['role' => $role]);
            $this->actingAs($staff)->get(route('sales.index'))
                ->assertStatus($role === User::ROLE_STAFF ? 403 : 200)
                ->assertDontSee(route('sales.edit', $sale));
            $this->get(route('sales.edit', $sale))->assertForbidden();
            $this->put(route('sales.update', $sale), $payload)->assertForbidden();
            $this->assertSame('Original Customer', $sale->fresh()->customer_name);
            $this->assertEquals(19, $product->stocks()->sum('quantity'));
        }

        $this->actingAs($owner)->get(route('sales.index'))
            ->assertOk()->assertSee(route('sales.edit', $sale));
        $this->get(route('sales.edit', $sale))->assertOk();
        $this->put(route('sales.update', $sale), $payload)
            ->assertRedirect(route('sale.receipt', $sale));
        $this->assertSame('Updated Customer', $sale->fresh()->customer_name);
        $this->assertEquals(200, $sale->fresh()->total);
        $this->assertEquals(18, $product->stocks()->sum('quantity'));

        $staff->update(['is_super_admin' => true]);
        $this->actingAs($staff)->get(route('sales.edit', $sale))->assertOk();
        $this->put(route('sales.update', $sale), $payload)
            ->assertRedirect(route('sale.receipt', $sale));
    }

    public function test_owner_still_sees_team_sales(): void
    {
        [$owner, $staffOne, $staffTwo] = $this->createTenantUsers();
        $product = $this->createProductWithStock($owner->business_id, $owner->branch_id);

        $staffOneSale = $this->createSaleForUser($staffOne, $product->id, 'First Customer');
        $staffTwoSale = $this->createSaleForUser($staffTwo, $product->id, 'Second Customer');

        $history = $this->actingAs($owner)->get(route('sales.index'));
        $history->assertOk();
        $history->assertDontSee('Showing your sales only.');
        $history->assertSee($staffOneSale->sale_number);
        $history->assertSee($staffTwoSale->sale_number);

        $summary = $this->actingAs($owner)->get(route('summary'));
        $summary->assertOk();
        $summary->assertDontSee('Showing your sales only.');
        $summary->assertSee($staffOneSale->sale_number);
        $summary->assertSee($staffTwoSale->sale_number);
        $summary->assertViewHas('todaySalesTotal', fn ($total) => (float) $total === 200.0);
        $summary->assertViewHas('todayOrders', 2);

        $this->actingAs($owner)
            ->get(route('sale.receipt', $staffTwoSale))
            ->assertOk();
    }

    private function createTenantUsers(): array
    {
        $business = Business::create([
            'name' => 'Visibility Test Business',
            'slug' => 'visibility-test-' . Str::lower(Str::random(6)),
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
            'is_active' => true,
        ]);

        $staffOne = User::factory()->create([
            'business_id' => $business->id,
            'branch_id' => $branch->id,
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        $staffTwo = User::factory()->create([
            'business_id' => $business->id,
            'branch_id' => $branch->id,
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        return [$owner, $staffOne, $staffTwo];
    }

    private function createProductWithStock(int $businessId, int $branchId): Product
    {
        $token = Str::upper(Str::random(8));

        $product = Product::create([
            'business_id' => $businessId,
            'name' => 'Shared Product ' . $token,
            'sku' => 'SKU-' . $token,
            'serial_number' => 'SER-' . $token,
            'cost' => 50,
            'price' => 100,
            'stock_alert' => 0,
            'is_active' => true,
        ]);

        ProductStock::create([
            'business_id' => $businessId,
            'branch_id' => $branchId,
            'product_id' => $product->id,
            'location' => 'main',
            'quantity' => 20,
        ]);

        return $product;
    }

    private function createSaleForUser(User $user, int $productId, string $customerName): Sale
    {
        $this->actingAs($user)
            ->post(route('sale.store'), [
                'items' => [
                    ['product_id' => $productId, 'quantity' => 1],
                ],
                'method' => 'cash',
                'customer_name' => $customerName,
                'apply_tax' => 0,
            ])
            ->assertRedirect();

        $sale = Sale::withoutGlobalScopes()
            ->where('business_id', $user->business_id)
            ->where('user_id', $user->id)
            ->latest('id')
            ->firstOrFail();

        $this->travel(1)->seconds();

        return $sale;
    }
}
