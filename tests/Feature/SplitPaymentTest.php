<?php

namespace Tests\Feature;

use App\Models\{Branch, Business, CashierShift, Payment, Product, ProductStock, Sale, StockMovement, Subscription, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SplitPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_split_payment_covers_tax_installation_receipt_history_and_shift_cash(): void
    {
        [$user, $product, $shift] = $this->fixture();
        $this->actingAs($user)->post(route('sale.store'), [
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
            'apply_tax' => 1, 'installation_amount' => 50, 'split_payment' => 1,
            'payments' => ['cash' => '100.00', 'mobile' => '182.00', 'card' => null],
        ])->assertSessionHasNoErrors()->assertRedirect();

        $sale = Sale::sole();
        $this->assertEquals(282, $sale->total);
        $this->assertSame(2, $sale->payments()->count());
        $this->assertEquals(100, $sale->payments()->where('method', 'cash')->value('amount'));
        $this->assertEquals(182, $sale->payments()->where('method', 'mobile')->value('amount'));
        $this->assertEquals(8, $product->stocks()->sum('quantity'));
        $this->get(route('sale.receipt', $sale))->assertOk()->assertSee('Split payment')
            ->assertSee('CASH')->assertSee('100.00')->assertSee('MOBILE')->assertSee('182.00');
        $this->get(route('sales.index'))->assertOk()->assertSee('100.00')->assertSee('182.00');
        $this->assertSame(['cash' => 10000, 'mobile' => 18200], $shift->summary()['payments']);
        $this->assertSame(15000, $shift->summary()['expected_cash_cents']);
    }

    public function test_fractional_amounts_are_compared_in_cents(): void
    {
        [$user, $product] = $this->fixture();
        $product->update(['price' => 0.30]);
        $this->actingAs($user)->post(route('sale.store'), [
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'payments' => ['cash' => '0.10', 'bank' => '0.20'],
        ])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame(30, (int) round(Sale::sole()->payments()->sum('amount') * 100));
    }

    public function test_invalid_splits_never_create_sales_payments_or_stock_movements(): void
    {
        [$user, $product] = $this->fixture();
        $this->actingAs($user);
        foreach ([
            ['cash' => 50, 'mobile' => 49], ['cash' => 50, 'mobile' => 51],
            ['cash' => 100], ['cash' => 0, 'mobile' => 0], [],
            ['cash' => -1, 'mobile' => 101], ['cash' => '0.001', 'mobile' => '99.999'],
            ['cash' => 50, 'unknown' => 50],
        ] as $payments) {
            $this->from(route('sale'))->post(route('sale.store'), [
                'items' => [['product_id' => $product->id, 'quantity' => 1]],
                'method' => 'cash', 'split_payment' => 1, 'payments' => $payments,
            ])->assertRedirect(route('sale'))->assertSessionHasErrors();
            $this->assertSame(0, Sale::count());
            $this->assertSame(0, Payment::count());
            $this->assertSame(0, StockMovement::count());
            $this->assertEquals(10, $product->stocks()->sum('quantity'));
        }
    }

    public function test_single_method_payments_remain_supported(): void
    {
        [$user, $product] = $this->fixture();
        $this->actingAs($user)->post(route('sale.store'), [
            'items' => [['product_id' => $product->id, 'quantity' => 1]], 'method' => 'card',
        ])->assertSessionHasNoErrors()->assertRedirect();
        $payment = Payment::sole();
        $this->assertSame('card', $payment->method);
        $this->assertEquals(100, $payment->amount);
    }

    public function test_split_sale_can_be_edited_without_duplicate_payments(): void
    {
        [$user, $product, $shift] = $this->fixture();
        $items = [['product_id' => $product->id, 'quantity' => 1]];
        $this->actingAs($user)->post(route('sale.store'), [
            'items' => $items, 'payments' => ['cash' => 40, 'mobile' => 60],
        ])->assertSessionHasNoErrors();
        $sale = Sale::sole();
        $this->get(route('sales.edit', $sale))->assertOk()->assertSee('Split across payment methods');
        $this->put(route('sales.update', $sale), [
            'items' => $items, 'payments' => ['cash' => 25, 'bank' => 75],
        ])->assertSessionHasNoErrors()->assertRedirect(route('sale.receipt', $sale));
        $this->assertSame(2, $sale->payments()->count());
        $this->assertFalse($sale->payments()->where('method', 'mobile')->exists());
        $this->assertSame(7500, $shift->summary()['expected_cash_cents']);

        $this->put(route('sales.update', $sale), ['items' => $items, 'method' => 'card'])
            ->assertSessionHasNoErrors()->assertRedirect(route('sale.receipt', $sale));
        $this->assertSame(1, $sale->payments()->count());
        $this->assertSame('card', $sale->payments()->sole()->method);
        $this->assertSame(5000, $shift->summary()['expected_cash_cents']);
        $this->assertEquals(9, $product->stocks()->sum('quantity'));
    }

    public function test_invalid_edit_rolls_back_inventory_and_payments(): void
    {
        [$user, $product] = $this->fixture();
        $items = [['product_id' => $product->id, 'quantity' => 1]];
        $this->actingAs($user)->post(route('sale.store'), [
            'items' => $items, 'payments' => ['cash' => 40, 'mobile' => 60],
        ])->assertSessionHasNoErrors();
        $sale = Sale::sole();
        $this->from(route('sales.edit', $sale))->put(route('sales.update', $sale), [
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
            'payments' => ['cash' => 40, 'mobile' => 60],
        ])->assertSessionHasErrors('payments');
        $this->assertEquals(100, $sale->fresh()->total);
        $this->assertEquals(100, $sale->payments()->sum('amount'));
        $this->assertEquals(9, $product->stocks()->sum('quantity'));
    }

    public function test_staff_can_take_split_payments_but_a_closed_shift_cannot(): void
    {
        [$user, $product, $shift] = $this->fixture();
        $user->update(['role' => 'staff']);
        $data = ['items' => [['product_id' => $product->id, 'quantity' => 1]], 'payments' => ['cash' => 30, 'mobile' => 70]];
        $this->actingAs($user)->post(route('sale.store'), $data)->assertSessionHasNoErrors()->assertRedirect();
        $this->get(route('sale.receipt', Sale::sole()))->assertOk()->assertSee('Split payment');
        $shift->update(['status' => 'closed', 'active_user_id' => null, 'closed_at' => now()]);
        $this->postJson(route('sale.store'), $data)->assertStatus(422);
        $this->assertSame(1, Sale::count());
        $this->assertSame(2, Payment::count());
        $this->assertEquals(9, $product->stocks()->sum('quantity'));
    }

    private function fixture(): array
    {
        $business = Business::create([
            'name' => 'Split Test', 'slug' => 'split-test', 'billing_email' => 'split@example.com',
            'status' => 'active', 'subscription_status' => 'active',
            'current_period_start' => now()->toDateString(), 'current_period_end' => now()->addMonth()->toDateString(),
        ]);
        $branch = Branch::create(['business_id' => $business->id, 'name' => 'Main Branch', 'code' => 'MAIN', 'is_default' => true]);
        Subscription::create([
            'business_id' => $business->id, 'plan' => 'standard', 'interval' => 'monthly', 'status' => 'active',
            'amount' => 0, 'currency' => 'KES', 'period_start' => now()->toDateString(),
            'period_end' => now()->addMonth()->toDateString(), 'grace_until' => now()->addDays(3), 'last_payment_at' => now(),
        ]);
        $user = User::factory()->create(['business_id' => $business->id, 'branch_id' => $branch->id, 'role' => 'owner']);
        $shift = CashierShift::create([
            'business_id' => $business->id, 'branch_id' => $branch->id, 'user_id' => $user->id,
            'active_user_id' => $user->id, 'opening_cash_cents' => 5000, 'opened_at' => now(), 'status' => 'open',
        ]);
        $product = Product::create(['business_id' => $business->id, 'name' => 'Router', 'sku' => 'SPLIT-1', 'price' => 100, 'cost' => 50, 'stock_alert' => 0, 'is_active' => true]);
        ProductStock::create(['business_id' => $business->id, 'branch_id' => $branch->id, 'product_id' => $product->id, 'location' => 'main', 'quantity' => 10]);
        return [$user, $product, $shift];
    }
}
