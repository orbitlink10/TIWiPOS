<?php

namespace Tests\Feature;

use App\Http\Middleware\FeatureGate;
use App\Models\Branch;
use App\Models\Business;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuotationTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        $business = Business::create(['name' => 'Test Shop', 'slug' => uniqid('shop-'), 'status' => 'active']);

        return User::factory()->create(['business_id' => $business->id, 'role' => 'owner']);
    }

    private function payload(): array
    {
        return [
            'number' => 'QT-100', 'customer_name' => 'Customer Limited',
            'quotation_date' => '2026-10-29', 'valid_until' => '2026-11-12',
            'issuer_name' => 'Test Shop', 'currency' => 'USD', 'discount' => '10.00', 'vat_rate' => '16',
            'items' => [
                ['description' => 'Standard Kit', 'quantity' => '2', 'unit' => 'PCS', 'unit_price' => '395.00'],
                ['description' => 'Installation', 'quantity' => '1.5', 'unit' => 'HR', 'unit_price' => '20.00'],
            ],
        ];
    }

    public function test_create_edit_and_print_with_server_calculated_totals(): void
    {
        $this->withoutMiddleware(FeatureGate::class);
        $user = $this->owner();
        $this->actingAs($user)->get(route('quotations.create'))->assertOk()->assertSee('Prepare Quotation');
        $this->post(route('quotations.store'), $this->payload() + ['business_id' => 999, 'total_cents' => 1])
            ->assertSessionHasNoErrors()->assertRedirect();
        $quote = Quotation::sole();
        $this->assertEquals($user->business_id, $quote->business_id);
        $this->assertSame(82000, $quote->document['subtotal_cents']);
        $this->assertSame(12960, $quote->document['vat_cents']);
        $this->assertSame(93960, $quote->document['total_cents']);
        $this->get(route('quotations.show', $quote))->assertOk()->assertSee('USD 939.60')->assertSee('CUSTOMER APPROVAL');
        $this->get(route('quotations.edit', $quote))->assertOk()->assertSee('Standard Kit');
        $data = $this->payload();
        $data['vat_rate'] = 0;
        $data['customer_name'] = '<script>alert(1)</script>';
        $this->put(route('quotations.update', $quote), $data)->assertSessionHasNoErrors()->assertRedirect();
        $this->get(route('quotations.show', $quote))->assertOk()->assertSee('USD 810.00')
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
        $this->get(route('quotations.index'))->assertOk()->assertSeeInOrder(['To Do List', 'Quotation']);
    }

    public function test_quotations_are_isolated_by_business_and_branch(): void
    {
        $this->withoutMiddleware(FeatureGate::class);
        $owner = $this->owner();
        $branch = Branch::create(['business_id' => $owner->business_id, 'name' => 'Main', 'code' => 'MAIN', 'is_default' => true]);
        $this->actingAs($owner)->post(route('quotations.store'), $this->payload())->assertRedirect();
        $quote = Quotation::sole();
        $this->assertEquals($branch->id, $quote->branch_id);
        $otherBranch = Branch::create(['business_id' => $owner->business_id, 'name' => 'Other', 'code' => 'OTHER']);
        $this->withSession(['branch_id' => $otherBranch->id])->get(route('quotations.show', $quote))->assertNotFound();
        $this->get(route('quotations.edit', $quote))->assertNotFound();
        $this->put(route('quotations.update', $quote), $this->payload())->assertNotFound();
        $this->get(route('quotations.index'))->assertOk()->assertDontSee('Customer Limited');
        $this->actingAs($this->owner())->get(route('quotations.show', $quote))->assertNotFound();
        $this->put(route('quotations.update', $quote), $this->payload())->assertNotFound();
    }

    public function test_invalid_amounts_dates_and_duplicate_numbers_are_rejected(): void
    {
        $this->withoutMiddleware(FeatureGate::class);
        $this->actingAs($this->owner());
        $data = $this->payload();
        $data['discount'] = 9999;
        $this->post(route('quotations.store'), $data)->assertSessionHasErrors('discount');
        $data = $this->payload();
        $data['items'][0]['quantity'] = 1000000;
        $data['items'][0]['unit_price'] = 999999999;
        $this->post(route('quotations.store'), $data)->assertSessionHasErrors('items');
        $data = $this->payload();
        $data['valid_until'] = '2026-10-01';
        $data['items'][0]['quantity'] = -1;
        $this->post(route('quotations.store'), $data)->assertSessionHasErrors(['valid_until', 'items.0.quantity']);
        $this->assertDatabaseCount('quotations', 0);
        $this->post(route('quotations.store'), $this->payload())->assertRedirect();
        $this->post(route('quotations.store'), $this->payload())->assertSessionHasErrors('number');
    }

    public function test_guests_cannot_access_quotations(): void
    {
        $this->get(route('quotations.index'))->assertRedirect(route('login'));
        $this->post(route('quotations.store'), $this->payload())->assertRedirect(route('login'));
    }
}
