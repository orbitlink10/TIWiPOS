<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_category_name_in_same_business_returns_validation_error(): void
    {
        $business = $this->createActiveBusiness('Biz One', 'biz-one');
        $user = User::factory()->create([
            'business_id' => $business->id,
            'role' => 'owner',
        ]);

        $payload = [
            'category_name' => 'Starlink KITS',
            'description' => 'Category for Starlink kits',
            'is_active' => 1,
        ];

        $first = $this->actingAs($user)
            ->from(route('categories.create'))
            ->post(route('categories.store'), $payload);

        $first->assertRedirect(route('categories.create'));

        $second = $this->actingAs($user)
            ->from(route('categories.create'))
            ->post(route('categories.store'), $payload);

        $second->assertRedirect(route('categories.create'));
        $second->assertSessionHasErrors('category_name');
        $this->assertSame(1, Category::count());
    }

    public function test_same_category_name_can_exist_in_different_businesses(): void
    {
        $businessOne = $this->createActiveBusiness('Biz One', 'biz-one');
        $businessTwo = $this->createActiveBusiness('Biz Two', 'biz-two');

        $userOne = User::factory()->create([
            'business_id' => $businessOne->id,
            'role' => 'owner',
        ]);

        $userTwo = User::factory()->create([
            'business_id' => $businessTwo->id,
            'role' => 'owner',
        ]);

        $payload = [
            'category_name' => 'Starlink KITS',
            'description' => 'Category for Starlink kits',
            'is_active' => 1,
        ];

        $responseOne = $this->actingAs($userOne)->post(route('categories.store'), $payload);
        $responseOne->assertRedirect(route('categories.create'));

        $responseTwo = $this->actingAs($userTwo)->post(route('categories.store'), $payload);
        $responseTwo->assertRedirect(route('categories.create'));

        $categories = Category::withoutGlobalScopes()
            ->whereIn('business_id', [$businessOne->id, $businessTwo->id])
            ->where('name', 'Starlink KITS')
            ->orderBy('business_id')
            ->get();

        $this->assertCount(2, $categories);
        $this->assertNotSame($categories[0]->slug, $categories[1]->slug);
    }

    public function test_sub_category_page_creates_sub_category_under_selected_category(): void
    {
        $business = $this->createActiveBusiness('Biz One', 'biz-one');
        $user = User::factory()->create([
            'business_id' => $business->id,
            'role' => 'owner',
        ]);
        $parent = Category::create([
            'business_id' => $business->id,
            'name' => 'Hardware',
            'slug' => 'hardware',
            'parent_id' => null,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)
            ->post(route('sub-categories.store'), [
                'parent_id' => $parent->id,
                'sub_category_name' => 'Routers',
                'description' => 'Router products',
                'is_active' => 1,
            ]);

        $response->assertRedirect(route('sub-categories.create'));

        $subCategory = Category::where('name', 'Routers')->first();

        $this->assertNotNull($subCategory);
        $this->assertSame($parent->id, $subCategory->parent_id);
    }

    public function test_category_page_only_contains_category_controls(): void
    {
        $business = $this->createActiveBusiness('Biz One', 'biz-one');
        $user = User::factory()->create([
            'business_id' => $business->id,
            'role' => 'owner',
        ]);
        $category = Category::create([
            'business_id' => $business->id,
            'name' => 'Hardware',
            'slug' => 'hardware',
            'parent_id' => null,
            'is_active' => true,
        ]);
        Category::create([
            'business_id' => $business->id,
            'name' => 'Routers',
            'slug' => 'routers',
            'parent_id' => $category->id,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('categories.create'))
            ->assertOk()
            ->assertSee('Category')
            ->assertSee('Hardware')
            ->assertDontSee('Routers')
            ->assertSee('name="category_name"', false)
            ->assertDontSee('name="sub_category_name"', false)
            ->assertDontSee('<select name="parent_id"', false);
    }

    public function test_sub_category_page_uses_category_dropdown(): void
    {
        $business = $this->createActiveBusiness('Biz One', 'biz-one');
        $user = User::factory()->create([
            'business_id' => $business->id,
            'role' => 'owner',
        ]);
        $category = Category::create([
            'business_id' => $business->id,
            'name' => 'Hardware',
            'slug' => 'hardware',
            'parent_id' => null,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('sub-categories.create'))
            ->assertOk()
            ->assertSee('<select name="parent_id"', false)
            ->assertSee('name="sub_category_name"', false)
            ->assertSee('value="' . $category->id . '"', false)
            ->assertSee('Hardware')
            ->assertSee('Sub-Category');
    }

    public function test_sub_category_page_shows_total_quantity_per_sub_category(): void
    {
        $business = $this->createActiveBusiness('Biz One', 'biz-one');
        $user = User::factory()->create([
            'business_id' => $business->id,
            'role' => 'owner',
        ]);
        $category = Category::create([
            'business_id' => $business->id,
            'name' => 'Hardware',
            'slug' => 'hardware',
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
        $product = Product::create([
            'business_id' => $business->id,
            'name' => 'Router',
            'sku' => 'RTR-001',
            'serial_number' => 'SN-RTR-001',
            'category_id' => $subCategory->id,
            'cost' => 1000,
            'price' => 1500,
            'stock_alert' => 0,
            'recorded_at' => now()->toDateString(),
            'is_active' => true,
        ]);
        ProductStock::create([
            'business_id' => $business->id,
            'product_id' => $product->id,
            'location' => 'main',
            'quantity' => 5,
        ]);

        $this->actingAs($user)
            ->get(route('sub-categories.create'))
            ->assertOk()
            ->assertSee('Total Quantity')
            ->assertSee('<td style="padding:10px; text-align:right;">5</td>', false);
    }

    public function test_category_created_from_product_form_can_return_to_product_create(): void
    {
        $business = $this->createActiveBusiness('Biz One', 'biz-one');
        $user = User::factory()->create([
            'business_id' => $business->id,
            'role' => 'owner',
        ]);

        $response = $this->actingAs($user)
            ->post(route('categories.store'), [
                'category_name' => 'Installations',
                'redirect_to' => 'products.create',
                'is_active' => 1,
            ]);

        $response->assertRedirect(route('products.create'));
        $this->assertDatabaseHas('categories', [
            'business_id' => $business->id,
            'name' => 'Installations',
            'parent_id' => null,
        ]);
    }

    private function createActiveBusiness(string $name, string $slug): Business
    {
        $business = Business::create([
            'name' => $name,
            'slug' => $slug,
            'billing_email' => $slug.'@example.com',
            'status' => 'active',
            'subscription_status' => 'active',
            'current_period_start' => now()->toDateString(),
            'current_period_end' => now()->addMonth()->toDateString(),
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

        return $business;
    }
}
