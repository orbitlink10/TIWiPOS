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

class ProductSubCategorySelectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_create_form_lists_top_level_categories_only(): void
    {
        [$business, , $user] = $this->createActiveTenant();

        $parent = $this->createCategory($business, 'Hardware', null);
        $topLevelOnly = $this->createCategory($business, 'Top Level Only', null);
        $subCategory = $this->createCategory($business, 'Routers', $parent->id);

        $this->actingAs($user)
            ->get(route('products.create'))
            ->assertOk()
            ->assertSee('Select category')
            ->assertViewHas('categories', fn ($categories) => $categories->modelKeys() === [$parent->id, $topLevelOnly->id])
            ->assertSee('<option value="' . $parent->id . '"', false)
            ->assertSee('<option value="' . $topLevelOnly->id . '"', false)
            ->assertDontSee('<option value="' . $subCategory->id . '"', false);
    }

    public function test_product_store_accepts_top_level_category_and_rejects_sub_category(): void
    {
        [$business, , $user] = $this->createActiveTenant();

        $parent = $this->createCategory($business, 'Hardware', null);

        $this->actingAs($user)
            ->from(route('products.create'))
            ->post(route('products.store'), [
                'name' => 'Router',
                'sku' => 'RTR-001',
                'serial_number' => 'SN-RTR-001',
                'category_id' => $parent->id,
                'cost' => 1000,
                'price' => 1500,
                'stock' => 1,
                'stock_location' => 'main',
                'is_active' => 1,
            ])
            ->assertRedirect(route('products'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('products', [
            'name' => 'Router',
            'business_id' => $business->id,
            'category_id' => $parent->id,
            'serial_number' => 'SN-RTR-001',
        ]);

        $subCategory = $this->createCategory($business, 'Routers', $parent->id);
        $this->from(route('products.create'))->post(route('products.store'), [
            'name' => 'Invalid child category product',
            'serial_number' => 'SN-RTR-002',
            'category_id' => $subCategory->id,
            'cost' => 1000,
            'price' => 1500,
            'stock' => 1,
            'is_active' => 1,
        ])->assertRedirect(route('products.create'))->assertSessionHasErrors('category_id');

        $this->assertDatabaseMissing('products', ['serial_number' => 'SN-RTR-002']);
    }

    public function test_stock_adjust_adds_serials_and_returns_to_stock_control(): void
    {
        [$business, , $user] = $this->createActiveTenant();

        $parent = $this->createCategory($business, 'Hardware', null);
        $topLevelOnly = $this->createCategory($business, 'Top Level Only', null);
        $subCategory = $this->createCategory($business, 'Routers', $parent->id);

        $this->actingAs($user)
            ->get(route('stock.adjust'))
            ->assertOk()
            ->assertSee('Sub-Category')
            ->assertSee('Hardware - Routers')
            ->assertSee('<option value="' . $subCategory->id . '"', false)
            ->assertDontSee('<option value="' . $parent->id . '"', false)
            ->assertDontSee('<option value="' . $topLevelOnly->id . '"', false);

        $this->actingAs($user)
            ->post(route('stock.adjust.store'), [
                'category_id' => $subCategory->id,
                'serial_numbers' => "SN-RTR-101\nSN-RTR-102",
                'location' => 'main',
                'stock_date' => now()->toDateString(),
            ])
            ->assertRedirect(route('stock-control.index'));

        $this->assertSame(2, Product::where('category_id', $subCategory->id)->count());

        $this->actingAs($user)
            ->get(route('stock-control.index'))
            ->assertOk()
            ->assertViewHas('rows', fn ($rows) => (int) $rows->sum('on_hand') === 2);
    }

    private function createCategory(Business $business, string $name, ?int $parentId): Category
    {
        return Category::create([
            'business_id' => $business->id,
            'name' => $name,
            'slug' => str($name)->slug() . '-' . uniqid(),
            'parent_id' => $parentId,
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
