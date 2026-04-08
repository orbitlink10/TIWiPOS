<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCostVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_cannot_see_product_costs_on_products_page(): void
    {
        [$business, $branch] = $this->createActiveTenant();

        $staff = User::factory()->create([
            'business_id' => $business->id,
            'branch_id' => $branch->id,
            'role' => User::ROLE_STAFF,
        ]);

        $product = Product::create([
            'business_id' => $business->id,
            'name' => 'Hidden Laptop',
            'sku' => 'HC-001',
            'serial_number' => 'SER-HC-001',
            'barcode' => '100200300',
            'cost' => 1234.56,
            'price' => 7890.12,
            'stock_alert' => 2,
            'is_active' => true,
        ]);

        ProductStock::create([
            'business_id' => $business->id,
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'location' => 'main',
            'quantity' => 4,
        ]);

        $response = $this->actingAs($staff)->get(route('products'));

        $response->assertOk();
        $response->assertSee('Hidden Laptop');
        $response->assertSee('KES 7,890.12');
        $response->assertDontSee('>Cost<', false);
        $response->assertDontSee('KES 1,234.56');
    }

    public function test_manager_can_see_product_costs_on_products_page(): void
    {
        [$business, $branch] = $this->createActiveTenant();

        $manager = User::factory()->create([
            'business_id' => $business->id,
            'branch_id' => $branch->id,
            'role' => User::ROLE_MANAGER,
        ]);

        $product = Product::create([
            'business_id' => $business->id,
            'name' => 'Visible Cost Laptop',
            'sku' => 'VC-001',
            'serial_number' => 'SER-VC-001',
            'barcode' => '300200100',
            'cost' => 2345.67,
            'price' => 8901.23,
            'stock_alert' => 2,
            'is_active' => true,
        ]);

        ProductStock::create([
            'business_id' => $business->id,
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'location' => 'main',
            'quantity' => 6,
        ]);

        $response = $this->actingAs($manager)->get(route('products'));

        $response->assertOk();
        $response->assertSee('Visible Cost Laptop');
        $response->assertSee('>Cost<', false);
        $response->assertSee('KES 2,345.67');
        $response->assertSee('KES 8,901.23');
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

        $owner = User::factory()->create([
            'business_id' => $business->id,
            'branch_id' => $branch->id,
            'role' => User::ROLE_OWNER,
        ]);

        return [$business, $branch, $owner];
    }
}
