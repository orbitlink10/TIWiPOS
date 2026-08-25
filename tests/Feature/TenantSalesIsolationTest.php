<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Sale;
use App\Models\Subscription;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TenantSalesIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_history_and_receipts_are_isolated_per_business(): void
    {
        [$firstBusiness, $firstBranch, $firstOwner] = $this->createActiveTenant('Alpha Retail', 'alpha@example.com');
        [$secondBusiness, $secondBranch, $secondOwner] = $this->createActiveTenant('Beta Stores', 'beta@example.com');

        $firstProduct = $this->createProductWithStock($firstBusiness->id, $firstBranch->id, 'alpha');
        $secondProduct = $this->createProductWithStock($secondBusiness->id, $secondBranch->id, 'beta');

        $this->actingAs($firstOwner)->post(route('sale.store'), [
            'items' => [
                ['product_id' => $firstProduct->id, 'quantity' => 1],
            ],
            'method' => 'cash',
            'customer_name' => 'Alpha Customer',
            'apply_tax' => 0,
        ])->assertRedirect();

        $this->travel(1)->seconds();

        $this->actingAs($secondOwner)->post(route('sale.store'), [
            'items' => [
                ['product_id' => $secondProduct->id, 'quantity' => 1],
            ],
            'method' => 'mobile',
            'customer_name' => 'Beta Customer',
            'apply_tax' => 0,
        ])->assertRedirect();

        $firstSale = Sale::withoutGlobalScopes()
            ->where('business_id', $firstBusiness->id)
            ->latest('id')
            ->firstOrFail();

        $secondSale = Sale::withoutGlobalScopes()
            ->where('business_id', $secondBusiness->id)
            ->latest('id')
            ->firstOrFail();

        $firstHistory = $this->actingAs($firstOwner)->get(route('sales.index'));
        $firstHistory->assertOk();
        $firstHistory->assertSee($firstSale->sale_number);
        $firstHistory->assertDontSee($secondSale->sale_number);

        $secondHistory = $this->actingAs($secondOwner)->get(route('sales.index'));
        $secondHistory->assertOk();
        $secondHistory->assertSee($secondSale->sale_number);
        $secondHistory->assertDontSee($firstSale->sale_number);

        $this->actingAs($firstOwner)
            ->get(route('sale.receipt', $secondSale))
            ->assertNotFound();
    }

    public function test_sale_creation_rejects_products_from_another_business(): void
    {
        [$firstBusiness, $firstBranch, $firstOwner] = $this->createActiveTenant('Gamma Retail', 'gamma@example.com');
        [$secondBusiness, $secondBranch] = $this->createActiveTenant('Delta Stores', 'delta@example.com');

        $foreignProduct = $this->createProductWithStock($secondBusiness->id, $secondBranch->id, 'foreign');

        $response = $this->actingAs($firstOwner)
            ->from(route('sale'))
            ->post(route('sale.store'), [
                'items' => [
                    ['product_id' => $foreignProduct->id, 'quantity' => 1],
                ],
                'method' => 'cash',
                'apply_tax' => 0,
            ]);

        $response->assertRedirect(route('sale'));
        $response->assertSessionHasErrors('items.0.product_id');

        $this->assertDatabaseMissing('sales', [
            'business_id' => $firstBusiness->id,
        ]);
    }

    public function test_product_creation_rejects_category_and_supplier_from_another_business(): void
    {
        [$firstBusiness, , $firstOwner] = $this->createActiveTenant('Catalog One', 'catalog-one@example.com');
        [$secondBusiness] = $this->createActiveTenant('Catalog Two', 'catalog-two@example.com');

        $foreignParentCategory = Category::create([
            'business_id' => $secondBusiness->id,
            'name' => 'Foreign Category',
            'slug' => 'foreign-category',
            'parent_id' => null,
            'is_active' => true,
        ]);

        $foreignCategory = Category::create([
            'business_id' => $secondBusiness->id,
            'name' => 'Foreign Sub-Category',
            'slug' => 'foreign-sub-category',
            'parent_id' => $foreignParentCategory->id,
            'is_active' => true,
        ]);

        $foreignSupplier = Supplier::create([
            'business_id' => $secondBusiness->id,
            'name' => 'Foreign Supplier',
            'is_active' => true,
        ]);

        $response = $this->actingAs($firstOwner)
            ->from(route('products.create'))
            ->post(route('products.store'), [
                'name' => 'Tenant Scoped Product',
                'sku' => 'TENANT-' . Str::upper(Str::random(6)),
                'serial_number' => 'SERIAL-' . Str::upper(Str::random(8)),
                'category_id' => $foreignCategory->id,
                'supplier_id' => $foreignSupplier->id,
                'cost' => 100,
                'price' => 150,
                'stock' => 1,
                'is_active' => 1,
            ]);

        $response->assertRedirect(route('products.create'));
        $response->assertSessionHasErrors(['category_id', 'supplier_id']);

        $this->assertDatabaseMissing('products', [
            'name' => 'Tenant Scoped Product',
            'business_id' => $firstBusiness->id,
        ]);
    }

    private function createActiveTenant(string $name, string $billingEmail): array
    {
        $business = Business::create([
            'name' => $name,
            'slug' => str($name)->slug() . '-' . Str::lower(Str::random(6)),
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

        $owner = User::factory()->create([
            'business_id' => $business->id,
            'branch_id' => $branch->id,
            'role' => User::ROLE_OWNER,
            'is_active' => true,
        ]);

        return [$business, $branch, $owner];
    }

    private function createProductWithStock(int $businessId, int $branchId, string $prefix): Product
    {
        $token = Str::upper(Str::random(8));

        $product = Product::create([
            'business_id' => $businessId,
            'name' => ucfirst($prefix) . ' Product ' . $token,
            'sku' => $prefix . '-SKU-' . $token,
            'serial_number' => $prefix . '-SER-' . $token,
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
            'quantity' => 10,
        ]);

        return $product;
    }
}
