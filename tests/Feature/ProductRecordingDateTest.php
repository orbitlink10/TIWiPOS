<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Category;
use App\Models\Product;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductRecordingDateTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_create_form_uses_date_picker_for_recorded_date(): void
    {
        [$user] = $this->createActiveTenant();

        $response = $this->actingAs($user)->get(route('products.create'));

        $response->assertOk();
        $response->assertSee('Date recorded');
        $response->assertSee('name="recorded_at" type="date"', false);
    }

    public function test_product_store_saves_recorded_date(): void
    {
        [$user, $category] = $this->createActiveTenant();

        $response = $this->actingAs($user)->post(route('products.store'), [
            'name' => 'Install Router',
            'sku' => 'ROUTER-001',
            'serial_number' => 'SER-ROUTER-001',
            'category_id' => $category->id,
            'cost' => 1000,
            'price' => 1500,
            'stock' => 1,
            'stock_location' => 'main',
            'recorded_at' => '2026-06-19',
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('products'));

        $product = Product::where('sku', 'ROUTER-001')->firstOrFail();
        $this->assertSame('2026-06-19', $product->recorded_at->toDateString());
    }

    private function createActiveTenant(): array
    {
        $business = Business::create([
            'name' => 'Product Date Business',
            'slug' => 'product-date-business-' . uniqid(),
            'billing_email' => 'product-date@example.com',
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

        $user = User::factory()->create([
            'business_id' => $business->id,
            'role' => User::ROLE_OWNER,
        ]);

        $category = Category::create([
            'business_id' => $business->id,
            'name' => 'Installations',
            'slug' => 'installations-' . uniqid(),
            'parent_id' => null,
            'is_active' => true,
        ]);

        $subCategory = Category::create([
            'business_id' => $business->id,
            'name' => 'Router Installations',
            'slug' => 'router-installations-' . uniqid(),
            'parent_id' => $category->id,
            'is_active' => true,
        ]);

        return [$user, $subCategory];
    }
}
