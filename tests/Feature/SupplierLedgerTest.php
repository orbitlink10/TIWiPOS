<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Subscription;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierLedgerTest extends TestCase
{
    use RefreshDatabase;

    private function setupSupplier(): array
    {
        [$business, $branch, $user] = $this->createActiveTenant();
        $this->actingAs($user);
        $supplier = Supplier::create(['business_id' => $business->id, 'name' => 'Test Supplier', 'is_active' => true]);
        $product = Product::create(['business_id' => $business->id, 'name' => 'Router', 'sku' => uniqid(), 'price' => 300, 'cost' => 100, 'is_active' => true]);

        return [$business, $branch, $user, $supplier, $product];
    }

    private function supplyData(Product $product): array
    {
        return ['reference' => 'INV-001', 'supplied_at' => today()->subDays(3)->toDateString(), 'terms' => 'credit',
            'due_at' => today()->subDay()->toDateString(), 'amount' => '50.25', 'method' => 'mpesa',
            'items' => [['product_id' => $product->id, 'quantity' => 2, 'unit_cost' => '100.50']]];
    }

    public function test_credit_delivery_payments_balances_and_stock(): void
    {
        [$business, $branch, $user, $supplier, $product] = $this->setupSupplier();
        $data = $this->supplyData($product) + ['receive_stock' => 1];
        $this->post(route('suppliers.supply', $supplier->id), $data)->assertSessionHasNoErrors();
        $invoice = SupplierInvoice::firstOrFail();
        $this->assertEquals(20100, $invoice->total_cents);
        $this->assertEquals(15075, $invoice->balance_cents);
        $this->assertSame('Overdue', $invoice->status);
        $this->assertDatabaseHas('product_stocks', ['branch_id' => $branch->id, 'product_id' => $product->id, 'quantity' => 2]);
        $this->assertDatabaseHas('stock_movements', ['reference_type' => 'supplier_invoice', 'reference_id' => $invoice->id, 'quantity_change' => 2]);
        $this->get(route('suppliers.index'))->assertOk()->assertSee('Test Supplier')->assertSee('150.75');
        $this->get(route('suppliers.show', $supplier->id))->assertOk()->assertSee('INV-001')->assertSee('Router');
        $payment = ['amount' => '150.76', 'paid_at' => today()->toDateString(), 'method' => 'bank'];
        $url = route('suppliers.pay', [$supplier->id, $invoice->id]);
        $this->post($url, $payment)->assertSessionHasErrors('amount');
        $this->post($url, array_replace($payment, ['amount' => '150.75']))->assertSessionHasNoErrors();
        $this->assertSame('Paid', $invoice->fresh()->status);
        $this->post($url, array_replace($payment, ['amount' => '0.01']))->assertSessionHasErrors('amount');
        $this->post(route('suppliers.supply', $supplier->id), $data)->assertSessionHasErrors('reference');
        $this->assertEquals(2, ProductStock::first()->quantity);
    }

    public function test_ledger_only_and_cash_validation(): void
    {
        [, , , $supplier, $product] = $this->setupSupplier();
        $data = $this->supplyData($product);
        $this->post(route('suppliers.supply', $supplier->id), array_replace($data, ['terms' => 'cash']))->assertSessionHasErrors('amount');
        $this->assertDatabaseCount('supplier_invoices', 0);
        $this->post(route('suppliers.supply', $supplier->id), array_replace($data, ['terms' => 'cash', 'amount' => '201.00']))->assertSessionHasNoErrors();
        $this->assertSame('Paid', SupplierInvoice::first()->status);
        $this->assertDatabaseCount('product_stocks', 0);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_tenant_branch_and_role_access(): void
    {
        [$business, $branch, $user, $supplier, $product] = $this->setupSupplier();
        $this->post(route('suppliers.supply', $supplier->id), $this->supplyData($product))->assertSessionHasNoErrors();
        $invoice = SupplierInvoice::firstOrFail();
        $otherBranch = Branch::create(['business_id' => $business->id, 'name' => 'Other', 'code' => 'OTHER']);
        $this->withSession(['branch_id' => $otherBranch->id])->get(route('suppliers.show', $supplier->id))->assertOk()->assertDontSee('INV-001');
        $this->post(route('suppliers.pay', [$supplier->id, $invoice->id]), ['amount' => '1', 'paid_at' => today()->toDateString(), 'method' => 'cash'])->assertNotFound();
        $foreign = Business::create(['name' => 'Foreign', 'slug' => uniqid(), 'status' => 'active']);
        $foreignSupplier = Supplier::create(['business_id' => $foreign->id, 'name' => 'Foreign supplier']);
        $foreignProduct = Product::create(['business_id' => $foreign->id, 'name' => 'Secret', 'sku' => uniqid(), 'price' => 1, 'cost' => 1]);
        $this->get(route('suppliers.show', $foreignSupplier->id))->assertNotFound();
        $this->post(route('suppliers.supply', $supplier->id), array_replace($this->supplyData($foreignProduct), ['reference' => 'FOREIGN']))->assertSessionHasErrors('items.0.product_id');
        $user->update(['role' => User::ROLE_STAFF]);
        $this->get(route('suppliers.index'))->assertForbidden();
        $this->post(route('suppliers.supply', $supplier->id), $this->supplyData($product))->assertForbidden();
    }

    private function createActiveTenant(): array
    {
        $business = Business::create([
            'name' => 'Test Business',
            'slug' => 'test-business-'.uniqid(),
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
