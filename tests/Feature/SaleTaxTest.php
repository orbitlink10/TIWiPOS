<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Sale;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SaleTaxTest extends TestCase
{
    use RefreshDatabase;

    public function test_sale_without_tax_keeps_total_equal_to_subtotal(): void
    {
        $user = $this->createActiveUser();
        $product = $this->createProductWithStock($user->business_id, 100.00, 10);

        $response = $this->actingAs($user)->post(route('sale.store'), [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ],
            'method' => 'cash',
            'apply_tax' => 0,
        ]);

        $response->assertStatus(302);

        $sale = Sale::firstOrFail();
        $payment = Payment::where('sale_id', $sale->id)->firstOrFail();

        $this->assertSame(200.00, (float) $sale->subtotal);
        $this->assertSame(0.00, (float) $sale->tax);
        $this->assertSame(200.00, (float) $sale->total);
        $this->assertSame(200.00, (float) $payment->amount);
    }

    public function test_sale_with_tax_applies_sixteen_percent_to_total(): void
    {
        $user = $this->createActiveUser();
        $product = $this->createProductWithStock($user->business_id, 100.00, 10);

        $response = $this->actingAs($user)->post(route('sale.store'), [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ],
            'method' => 'cash',
            'apply_tax' => 1,
        ]);

        $response->assertStatus(302);

        $sale = Sale::firstOrFail();
        $payment = Payment::where('sale_id', $sale->id)->firstOrFail();

        $this->assertSame(200.00, (float) $sale->subtotal);
        $this->assertSame(32.00, (float) $sale->tax);
        $this->assertSame(232.00, (float) $sale->total);
        $this->assertSame(232.00, (float) $payment->amount);
    }

    private function createActiveUser(): User
    {
        $slug = 'biz-'.Str::lower(Str::random(8));

        $business = Business::create([
            'name' => 'Tax Test Business',
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

        return User::factory()->create([
            'business_id' => $business->id,
            'role' => 'owner',
        ]);
    }

    private function createProductWithStock(int $businessId, float $price, int $stock): Product
    {
        $token = Str::lower(Str::random(8));

        $product = Product::create([
            'business_id' => $businessId,
            'name' => 'Tax Product '.$token,
            'sku' => 'SKU-'.$token,
            'serial_number' => 'SER-'.$token,
            'price' => $price,
            'cost' => 0,
            'stock_alert' => 0,
            'is_active' => true,
        ]);

        ProductStock::create([
            'business_id' => $businessId,
            'branch_id' => null,
            'product_id' => $product->id,
            'location' => 'main',
            'quantity' => $stock,
        ]);

        return $product;
    }
}

