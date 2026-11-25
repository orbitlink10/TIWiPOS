<?php

namespace Tests\Feature;

use App\Models\{Branch, Business, Payment, Product, ProductStock, Sale, Subscription, Supplier, SupplierInvoice, SupplierPayment, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use RefreshDatabase;

    private function tenant(): array
    {
        $business = Business::create(['name'=>'Report Store', 'slug'=>uniqid('report-'), 'status'=>'active', 'subscription_status'=>'active', 'current_period_end'=>now()->addMonth()]);
        $branch = Branch::create(['business_id'=>$business->id, 'name'=>'Main Branch', 'code'=>uniqid(), 'is_default'=>true]);
        Subscription::create(['business_id'=>$business->id, 'plan'=>'standard', 'interval'=>'monthly', 'status'=>'active', 'amount'=>0, 'currency'=>'KES', 'period_start'=>today(), 'period_end'=>now()->addMonth(), 'grace_until'=>now()->addMonth()]);
        $owner = User::factory()->create(['business_id'=>$business->id, 'branch_id'=>$branch->id, 'role'=>'owner']);
        $this->actingAs($owner);
        return [$business, $branch, $owner];
    }

    private function sell(User $user): Sale
    {
        $this->actingAs($user);
        $product = Product::create(['business_id'=>$user->business_id, 'name'=>'Report Router', 'sku'=>uniqid('SKU-'), 'price'=>100, 'cost'=>50, 'stock_alert'=>2, 'is_active'=>true]);
        ProductStock::create(['business_id'=>$user->business_id, 'branch_id'=>$user->branch_id, 'product_id'=>$product->id, 'location'=>'main', 'quantity'=>10]);
        $this->post(route('sale.store'), ['items'=>[['product_id'=>$product->id, 'quantity'=>2]], 'method'=>'cash', 'apply_tax'=>1, 'installation_amount'=>10, 'customer_name'=>'Visible Customer'])->assertSessionHasNoErrors();
        $sale = Sale::withoutGlobalScopes()->latest('id')->firstOrFail();
        $this->travel(1)->seconds();
        return $sale;
    }

    public function test_all_reports_render_and_calculate_sales_stock_and_purchase_totals(): void
    {
        [$business, $branch, $owner] = $this->tenant();
        $this->sell($owner);
        Product::create(['business_id'=>$business->id, 'name'=>'Empty Shelf', 'sku'=>'EMPTY', 'price'=>10, 'cost'=>5, 'stock_alert'=>2, 'is_active'=>true]);
        $supplier = Supplier::create(['business_id'=>$business->id, 'name'=>'Report Supplier']);
        $invoice = SupplierInvoice::create(['business_id'=>$business->id, 'branch_id'=>$branch->id, 'supplier_id'=>$supplier->id, 'reference'=>'INV-REPORT', 'supplied_at'=>today()->startOfMonth(), 'terms'=>'credit', 'total_cents'=>10050]);
        SupplierPayment::create(['supplier_invoice_id'=>$invoice->id, 'amount_cents'=>3000, 'paid_at'=>today(), 'method'=>'cash']);

        foreach (['sales','daily-sales','product-sales','payments','stock-valuation','low-stock','stock-movements','purchases'] as $report) {
            $response = $this->get(route('reports.index', ['report'=>$report]))->assertOk();
            if (in_array($report, ['sales','daily-sales'])) {
                $response->assertViewHas('totals', fn ($totals) => (float) $totals->total === 242.0 && (float) $totals->tax === 32.0);
            }
            if ($report === 'product-sales') $response->assertViewHas('totals', fn ($totals) => (float) $totals->gross_profit === 100.0);
            if ($report === 'payments') $response->assertViewHas('totals', fn ($totals) => (float) $totals->amount === 242.0);
            if ($report === 'stock-valuation') $response->assertViewHas('totals', fn ($totals) => (float) $totals->stock_value === 400.0);
            if ($report === 'low-stock') $response->assertSee('Empty Shelf')->assertDontSee('Report Router</td>', false);
            if ($report === 'purchases') $response->assertSee('INV-REPORT')->assertViewHas('totals', fn ($totals) => (float) $totals->balance === 70.5);
        }
    }

    public function test_reports_and_csv_respect_tenant_branch_dates_and_completed_status(): void
    {
        [$business, $branch, $owner] = $this->tenant();
        $sale = $this->sell($owner);
        $otherBranch = Branch::create(['business_id'=>$business->id, 'name'=>'Other', 'code'=>'OTHER']);
        foreach ([['sale_number'=>'OTHER-BRANCH','branch_id'=>$otherBranch->id], ['sale_number'=>'OLD-SALE','created_at'=>now()->subMonths(2)], ['sale_number'=>'CANCELLED','status'=>'cancelled']] as $changes) {
            $copy = $sale->replicate();
            $copy->forceFill($changes)->save();
        }
        [$foreignBusiness, $foreignBranch] = $this->tenant();
        $copy = $sale->replicate();
        $copy->forceFill(['sale_number'=>'FOREIGN-TENANT', 'business_id'=>$foreignBusiness->id, 'branch_id'=>$foreignBranch->id])->save();
        $this->actingAs($owner)->withSession(['branch_id'=>$branch->id]);
        $this->get(route('reports.index'))->assertOk()->assertViewHas('totals', fn ($totals) => $totals->row_count == 1 && $totals->total == 242);
        $csv = $this->get(route('reports.index', ['format'=>'csv']))->assertOk()->streamedContent();
        $this->assertStringContainsString($sale->sale_number, $csv);
        foreach (['OTHER-BRANCH','OLD-SALE','CANCELLED','FOREIGN-TENANT'] as $hidden) $this->assertStringNotContainsString($hidden, $csv);
        $this->get(route('reports.index', ['from'=>today()->toDateString(), 'to'=>today()->toDateString()]))->assertOk()->assertViewHas('totals', fn ($totals) => $totals->row_count == 1);
    }

    public function test_staff_only_report_their_sales_and_cannot_export_cost_or_purchase_reports(): void
    {
        [$business, $branch, $owner] = $this->tenant();
        $ownerSale = $this->sell($owner);
        $staff = User::factory()->create(['business_id'=>$business->id, 'branch_id'=>$branch->id, 'role'=>'staff']);
        $staffSale = $this->sell($staff);
        $this->get(route('reports.index'))->assertOk()->assertSee($staffSale->sale_number)->assertDontSee($ownerSale->sale_number)->assertSee('Your sales only');
        $response = $this->get(route('reports.index', ['report'=>'product-sales']))->assertOk();
        $response->assertDontSee('Gross product profit')->assertDontSee('Unit cost');
        $csv = $this->get(route('reports.index', ['report'=>'product-sales', 'format'=>'csv']))->streamedContent();
        $this->assertStringNotContainsString('profit', $csv);
        foreach (['stock-valuation','purchases'] as $report) $this->get(route('reports.index', ['report'=>$report, 'format'=>'csv']))->assertSessionHasErrors('report');
    }

    public function test_totals_and_csv_include_all_pages_and_csv_neutralizes_formulas(): void
    {
        [, , $owner] = $this->tenant();
        $sale = $this->sell($owner);
        $sale->update(['customer_name'=>'=SUM(1,2)']);
        for ($i=0; $i<26; $i++) {
            $copy = $sale->replicate();
            $copy->forceFill(['sale_number'=>'PAGE-'.$i])->save();
        }
        $this->get(route('reports.index'))->assertOk()->assertViewHas('rows', fn ($rows) => $rows->count() === 25)->assertViewHas('totals', fn ($totals) => $totals->row_count == 27 && $totals->total == 6534);
        $csv = $this->get(route('reports.index', ['format'=>'csv']))->streamedContent();
        $this->assertStringContainsString('PAGE-0', $csv);
        $this->assertStringContainsString('PAGE-25', $csv);
        $this->assertStringContainsString("'=SUM(1,2)", $csv);
    }

    public function test_report_filters_are_validated_and_login_is_required(): void
    {
        $this->get(route('reports.index'))->assertRedirect(route('login'));
        $this->tenant();
        $this->get(route('reports.index', ['from'=>'2026-11-20','to'=>'2026-11-01']))->assertSessionHasErrors('to');
        $this->get(route('reports.index', ['report'=>'unknown']))->assertSessionHasErrors('report');
    }
}
