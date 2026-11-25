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

    public function test_category_filters_include_legacy_children_and_show_current_branch_stock(): void
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

        $soldProduct = $this->createProduct($business, $subCategory, 'SOLD-001', 'SN-SOLD-001');
        ProductStock::create([
            'business_id' => $business->id,
            'branch_id' => $branch->id,
            'product_id' => $soldProduct->id,
            'location' => 'main',
            'quantity' => 0,
        ]);
        $otherBranch = Branch::create([
            'business_id' => $business->id, 'name' => 'Other Branch', 'code' => 'OTHER',
        ]);
        ProductStock::create([
            'business_id' => $business->id,
            'branch_id' => $otherBranch->id,
            'product_id' => $soldProduct->id,
            'location' => 'other',
            'quantity' => 1,
        ]);

        $this->actingAs($user)->get(route('products', ['category_id' => $subCategory->id]))
            ->assertOk()
            ->assertViewHas('products', function ($products) use ($subCategoryProduct, $soldProduct) {
                $rows = $products->getCollection()->keyBy('id');
                return $rows->count() === 2
                    && (int) $rows[$subCategoryProduct->id]->stock_on_hand === 3
                    && (int) $rows[$soldProduct->id]->stock_on_hand === 0;
            })
            ->assertSee('SN-RTR-001')
            ->assertDontSee('SN-TOP-001')
            ->assertSee('SN-SOLD-001');

        $this->get(route('products', ['category_id' => $category->id]))
            ->assertOk()
            ->assertViewHas('products', fn ($products) => $products->getCollection()->pluck('id')->sort()->values()->all()
                === collect([$subCategoryProduct->id, $soldProduct->id])->sort()->values()->all());

        $this->get(route('products'))->assertOk()->assertSee('SN-SOLD-001');

        $subCategoryProduct->stocks()->where('branch_id', $branch->id)->update(['quantity' => 0]);
        $this->get(route('products', ['category_id' => $subCategory->id]))
            ->assertOk()
            ->assertViewHas('products', fn ($products) => $products->count() === 2
                && $products->every(fn ($product) => (int) $product->stock_on_hand === 0));

        $this->get(route('products', ['category_id' => $topLevelOnlyCategory->id]))
            ->assertOk()
            ->assertViewHas('products', fn ($products) => $products->count() === 1
                && (int) $products->first()->id === $topLevelProduct->id
                && (int) $products->first()->stock_on_hand === 7);
    }

    public function test_old_stock_page_is_removed_and_stock_control_remains_available(): void
    {
        [, , $user] = $this->createActiveTenant();
        $this->actingAs($user)->get('/stock')->assertNotFound();
        $this->get(route('stock-control.index'))->assertOk()->assertSee('Stock balances');
        $this->get(route('home'))->assertOk()->assertSee(route('stock-control.index'), false);
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
