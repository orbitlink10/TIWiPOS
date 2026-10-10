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

class DashboardTaxCardTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_sees_stock_value_for_current_branch_only(): void
    {
        $user = $this->createActiveOwner();
        $user->update(['role' => User::ROLE_MANAGER]);
        $product = $this->createProductWithStock($user->business_id, $user->branch_id);
        $this->createProductWithStock($user->business_id, $user->branch_id);
        $otherBranch = Branch::create([
            'business_id' => $user->business_id,
            'name' => 'Other Branch',
            'code' => 'OTHER',
        ]);
        ProductStock::create([
            'business_id' => $user->business_id,
            'branch_id' => $otherBranch->id,
            'product_id' => $product->id,
            'location' => 'other',
            'quantity' => 50,
        ]);

        $this->actingAs($user)->get(route('home'))
            ->assertOk()
            ->assertSee('Stock Value')
            ->assertSee('KES 4,000.00')
            ->assertViewHas('stats', fn (array $stats) => (float) $stats['stock_value'] === 4000.0);
    }

    public function test_staff_cannot_see_stock_value(): void
    {
        $user = $this->createActiveOwner();
        $user->update(['role' => User::ROLE_STAFF]);
        $this->createProductWithStock($user->business_id, $user->branch_id);

        $this->actingAs($user)->get(route('home'))
            ->assertOk()
            ->assertDontSee('Stock Value')
            ->assertViewHas('stats', fn (array $stats) => $stats['stock_value'] === null);
    }

    public function test_dashboard_shows_today_tax_total_for_taxed_sales(): void
    {
        $user = $this->createActiveOwner();
        $product = $this->createProductWithStock($user->business_id, $user->branch_id);

        $this->createSale($user, $product->id, 1);
        $this->createSale($user, $product->id, 0);

        $response = $this->actingAs($user)->get(route('home'));

        $response->assertOk();
        $response->assertSee('Tax');
        $response->assertViewHas('stats', function (array $stats) {
            return array_key_exists('today_tax', $stats)
                && (float) $stats['today_tax'] === 16.0;
        });
    }

    private function createActiveOwner(): User
    {
        $business = Business::create([
            'name' => 'Dashboard Tax Business',
            'slug' => 'dashboard-tax-' . Str::lower(Str::random(6)),
            'billing_email' => 'dashboard-tax@example.com',
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

        return User::factory()->create([
            'business_id' => $business->id,
            'branch_id' => $branch->id,
            'role' => User::ROLE_OWNER,
            'is_active' => true,
        ]);
    }

    private function createProductWithStock(int $businessId, int $branchId): Product
    {
        $token = Str::upper(Str::random(8));

        $product = Product::create([
            'business_id' => $businessId,
            'name' => 'Taxable Product ' . $token,
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

    private function createSale(User $user, int $productId, int $applyTax): Sale
    {
        $this->actingAs($user)
            ->post(route('sale.store'), [
                'items' => [
                    ['product_id' => $productId, 'quantity' => 1],
                ],
                'method' => 'cash',
                'apply_tax' => $applyTax,
            ])
            ->assertRedirect();

        return Sale::withoutGlobalScopes()
            ->where('business_id', $user->business_id)
            ->where('user_id', $user->id)
            ->latest('id')
            ->firstOrFail();
    }
}
