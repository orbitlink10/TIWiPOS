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

    public function test_staff_only_see_their_own_sales_history_summary_and_sale_pages(): void
    {
        [$owner, $staffOne, $staffTwo] = $this->createTenantUsers();
        $product = $this->createProductWithStock($owner->business_id, $owner->branch_id);

        $staffOneSale = $this->createSaleForUser($staffOne, $product->id, 'First Customer');
        $staffTwoSale = $this->createSaleForUser($staffTwo, $product->id, 'Second Customer');

        $history = $this->actingAs($staffOne)->get(route('sales.index'));
        $history->assertOk();
        $history->assertSee('Showing your sales only.');
        $history->assertSee($staffOneSale->sale_number);
        $history->assertDontSee($staffTwoSale->sale_number);

        $summary = $this->actingAs($staffOne)->get(route('summary'));
        $summary->assertOk();
        $summary->assertSee('Showing your sales only.');
        $summary->assertSee($staffOneSale->sale_number);
        $summary->assertDontSee($staffTwoSale->sale_number);
        $summary->assertViewHas('todaySalesTotal', fn ($total) => (float) $total === 100.0);
        $summary->assertViewHas('todayOrders', 1);
        $summary->assertViewHas('recentSales', function ($sales) use ($staffOneSale) {
            return $sales->count() === 1 && (int) $sales->first()->id === (int) $staffOneSale->id;
        });

        $this->actingAs($staffOne)
            ->get(route('sale.receipt', $staffTwoSale))
            ->assertNotFound();

        $this->actingAs($staffOne)
            ->get(route('sales.edit', $staffTwoSale))
            ->assertNotFound();
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
