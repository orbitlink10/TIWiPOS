<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockSubCategoryListingTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_page_lists_only_sub_categories_and_their_quantities(): void
    {
        [$business, $branch, $user] = $this->createActiveTenant();

        $category = Category::create([
            'business_id' => $business->id,
            'name' => 'Hardware',
            'slug' => 'hardware',
            'parent_id' => null,
            'is_active' => true,
        ]);

        $topLevelOnlyCategory = Category::create([
            'business_id' => $business->id,
            'name' => 'Standalone Stock',
            'slug' => 'standalone-stock',
            'parent_id' => null,
            'is_active' => true,
        ]);

        $subCategory = Category::create([
            'business_id' => $business->id,
            'name' => 'Routers',
            'slug' => 'routers',
            'parent_id' => $category->id,
            'is_active' => true,
        ]);

        Category::create([
            'business_id' => $business->id,
            'name' => 'Switches',
            'slug' => 'switches',
            'parent_id' => $category->id,
            'is_active' => true,
        ]);

        $subCategoryProduct = $this->createProduct($business, $subCategory, 'RTR-001', 'SN-RTR-001');
        ProductStock::create([
            'business_id' => $business->id,
            'branch_id' => $branch->id,
            'product_id' => $subCategoryProduct->id,
            'location' => 'main',
            'quantity' => 3,
        ]);

        $topLevelProduct = $this->createProduct($business, $topLevelOnlyCategory, 'TOP-001', 'SN-TOP-001');
        ProductStock::create([
            'business_id' => $business->id,
            'branch_id' => $branch->id,
            'product_id' => $topLevelProduct->id,
            'location' => 'main',
            'quantity' => 7,
        ]);

        $this->actingAs($user)
            ->get(route('stock'))
            ->assertOk()
            ->assertSee('Sub-category listing with current stock quantity per sub-category.')
            ->assertSee('Out of stock sub-categories')
            ->assertSee('<div class="metric-value out">1</div>', false)
            ->assertSee('<div class="metric-value total">3</div>', false)
            ->assertSee('<td>Routers</td>', false)
            ->assertSee('<td class="align-right">3</td>', false)
            ->assertSee('<td>Switches</td>', false)
            ->assertDontSee('Standalone Stock')
            ->assertDontSee('<td class="align-right">7</td>', false);
    }

    private function createProduct(Business $business, Category $category, string $sku, string $serialNumber): Product
    {
        return Product::create([
            'business_id' => $business->id,
            'name' => $category->name . ' Product',
            'sku' => $sku,
            'serial_number' => $serialNumber,
            'category_id' => $category->id,
            'cost' => 1000,
            'price' => 1500,
            'stock_alert' => 1,
            'recorded_at' => now()->toDateString(),
            'is_active' => true,
        ]);
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

        $user = User::factory()->create([
            'business_id' => $business->id,
            'branch_id' => $branch->id,
            'role' => User::ROLE_OWNER,
        ]);

        return [$business, $branch, $user];
    }
}
