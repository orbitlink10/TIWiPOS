<?php

namespace Tests\Feature;

use App\Models\{Branch, Business, Payment, Product, ProductStock, Sale, Subscription, Supplier, SupplierInvoice, SupplierPayment, User};
use App\Models\{CashierShift, PurchaseReceipt, PurchaseReceiptLine, StockPeriod, StockPeriodLine, SupplierInvoiceItem};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_expanded_catalogue_exports_and_calculates_recorded_operational_data(): void
    {
        [$business, $branch, $owner] = $this->tenant();
        $sale = $this->sell($owner);
        $product = $sale->items()->sole()->product;
        $supplier = Supplier::create(['business_id'=>$business->id, 'name'=>'Catalogue Supplier']);
        $product->update(['supplier_id'=>$supplier->id]);
        // A split must not duplicate sales or cost in aggregate reports.
        $sale->payments()->first()->update(['amount'=>100]);
        Payment::create(['business_id'=>$business->id, 'branch_id'=>$branch->id, 'sale_id'=>$sale->id, 'method'=>'mobile', 'amount'=>142, 'paid_at'=>now()]);
        $invoice = SupplierInvoice::create(['business_id'=>$business->id, 'branch_id'=>$branch->id, 'supplier_id'=>$supplier->id, 'reference'=>'EXPANDED-INVOICE', 'supplied_at'=>today(), 'terms'=>'credit', 'total_cents'=>10050]);
        $invoiceItem = SupplierInvoiceItem::create(['supplier_invoice_id'=>$invoice->id, 'product_id'=>$product->id, 'product_name'=>'Report Router', 'quantity'=>10, 'received_quantity'=>3, 'unit_cost_cents'=>1000]);
        SupplierPayment::create(['supplier_invoice_id'=>$invoice->id, 'amount_cents'=>3000, 'paid_at'=>today(), 'method'=>'bank']);
        $receipt = PurchaseReceipt::create(['supplier_invoice_id'=>$invoice->id, 'business_id'=>$business->id, 'branch_id'=>$branch->id, 'user_id'=>$owner->id, 'receipt_token'=>(string) \Illuminate\Support\Str::uuid(), 'received_at'=>today()]);
        PurchaseReceiptLine::create(['purchase_receipt_id'=>$receipt->id, 'supplier_invoice_item_id'=>$invoiceItem->id, 'product_id'=>$product->id, 'quantity'=>3]);
        $period = StockPeriod::create(['business_id'=>$business->id, 'branch_id'=>$branch->id, 'creation_token'=>(string) \Illuminate\Support\Str::uuid(), 'starts_on'=>today()->startOfMonth(), 'ends_on'=>today(), 'status'=>'closed', 'closed_at'=>now()]);
        StockPeriodLine::create(['stock_period_id'=>$period->id, 'product_id'=>$product->id, 'product_name'=>'Report Router', 'sku'=>$product->sku, 'opening_quantity'=>10, 'expected_quantity'=>8, 'counted_quantity'=>7, 'variance'=>-1, 'unit_cost'=>50]);
        $shift = CashierShift::where('user_id',$owner->id)->sole();
        $shift->update(['status'=>'closed', 'active_user_id'=>null, 'closed_at'=>now(), 'sales_count'=>1, 'sales_total_cents'=>24200, 'expected_cash_cents'=>10000, 'counted_cash_cents'=>9800, 'cash_variance_cents'=>-200]);

        $expected = [
            'summary'=>['receipts'=>1,'total'=>242,'gross_profit'=>110],
            'profit-loss'=>['total'=>242,'gross_profit'=>110,'cost'=>100],
            'sales-outlet'=>['receipts'=>1,'total'=>242],
            'daily-sales-stores'=>['receipts'=>1,'total'=>242],
            'monthly-store-performance'=>['receipts'=>1,'total'=>242],
            'sales-customer'=>['total'=>242],
            'sales-user-store'=>['total'=>242],
            'staff-accountability'=>['total'=>242,'cash'=>100,'mobile'=>142],
            'sales-user-store-item'=>['quantity'=>2,'revenue'=>200,'gross_profit'=>100],
            'store-item-sales'=>['quantity'=>2,'revenue'=>200],
            'sales-supplier'=>['quantity'=>2,'revenue'=>200],
            'vat-analysis'=>['total'=>242,'vat'=>32],
            'price-list'=>['quantity'=>8,'stock_value'=>400],
            'stock-vendor'=>['quantity'=>8,'stock_value'=>400],
            'stock-supplier-summary'=>['quantity'=>8,'stock_value'=>400],
            'stock-period-counts'=>['variance'=>-1,'variance_value'=>-50],
            'stock-period-variance'=>['variance'=>-1,'variance_value'=>-50],
            'day-close'=>['receipts'=>1,'total'=>242,'expected_cash'=>100,'variance'=>-2],
            'shift-reconciliation'=>['receipts'=>1,'total'=>242,'variance'=>-2],
            'purchases-item'=>['ordered'=>10,'received'=>3,'pending'=>7,'ordered_cost'=>100],
            'supplier-ordered-received'=>['ordered'=>10,'received'=>3,'pending'=>7],
            'supplier-performance'=>['ordered'=>10,'received'=>3,'pending'=>7],
            'grn-register'=>['quantity'=>3,'cost'=>30],
            'supplier-outstanding'=>['balance'=>70.5],
            'supplier-statements'=>['debit'=>100.5,'credit'=>30,'net'=>70.5],
            'supplier-payments'=>['amount'=>30],
        ];
        $catalog = $this->get(route('reports.index'))->assertOk()->viewData('reports');
        $this->assertCount(34, $catalog);
        foreach (array_keys($catalog) as $report) {
            $response = $this->get(route('reports.index', ['report'=>$report]))->assertOk();
            foreach ($expected[$report] ?? [] as $metric=>$value) {
                $this->assertEquals($value, $response->viewData('totals')->{$metric}, $report.': '.$metric);
            }
            $csv = $this->get(route('reports.index', ['report'=>$report,'format'=>'csv']))
                ->assertOk()->assertHeader('Content-Type','text/csv; charset=UTF-8')->streamedContent();
            $this->assertNotEmpty($csv, $report);
        }
    }

    public function test_branch_filter_is_shared_by_existing_and_extended_reports_and_exports(): void
    {
        [$business, $branch, $owner] = $this->tenant();
        $sale = $this->sell($owner);
        $otherBranch = Branch::create(['business_id'=>$business->id,'name'=>'Second Branch','code'=>'SECOND']);
        $copy = $sale->replicate();
        $copy->forceFill(['sale_number'=>'SECOND-BRANCH','branch_id'=>$otherBranch->id])->save();
        $item = $sale->items()->sole()->replicate();
        $item->forceFill(['sale_id'=>$copy->id,'branch_id'=>$otherBranch->id])->save();
        $payment = $sale->payments()->sole()->replicate();
        $payment->forceFill(['sale_id'=>$copy->id,'branch_id'=>$otherBranch->id])->save();
        $cancelled = $sale->replicate();
        $cancelled->forceFill(['sale_number'=>'CANCELLED-COPY','status'=>'cancelled'])->save();
        [$foreignBusiness, $foreignBranch] = $this->tenant();
        $foreign = $sale->replicate();
        $foreign->forceFill(['sale_number'=>'FOREIGN-REPORT','business_id'=>$foreignBusiness->id,'branch_id'=>$foreignBranch->id])->save();
        $this->actingAs($owner)->withSession(['branch_id'=>$branch->id]);
        foreach (['sales','daily-sales','summary','sales-outlet','daily-sales-stores','monthly-store-performance','sales-customer','sales-user-store','staff-accountability','vat-analysis','profit-loss'] as $report) {
            $this->get(route('reports.index',['report'=>$report]))->assertOk()
                ->assertViewHas('totals', fn ($totals) => (float) $totals->total === 242.0);
            $this->get(route('reports.index',['report'=>$report,'branch_id'=>'all']))->assertOk()
                ->assertViewHas('totals', fn ($totals) => (float) $totals->total === 484.0);
            $this->get(route('reports.index',['report'=>$report,'branch_id'=>$otherBranch->id]))->assertOk()
                ->assertViewHas('totals', fn ($totals) => (float) $totals->total === 242.0);
        }
        $this->get(route('reports.index',['report'=>'payments','branch_id'=>'all']))->assertOk()
            ->assertViewHas('totals', fn ($totals) => (float) $totals->amount === 484.0);
        $this->get(route('reports.index',['report'=>'product-sales','branch_id'=>'all']))->assertOk()
            ->assertViewHas('totals', fn ($totals) => (float) $totals->quantity === 4.0);
        $csv = $this->get(route('reports.index',['branch_id'=>'all','format'=>'csv']))->assertOk()->streamedContent();
        $this->assertStringContainsString('SECOND-BRANCH',$csv);
        $this->assertStringNotContainsString('FOREIGN-REPORT',$csv);
        $this->assertStringNotContainsString('CANCELLED-COPY',$csv);
        $this->getJson(route('reports.index',['branch_id'=>$foreignBranch->id]))->assertUnprocessable()->assertJsonValidationErrors('branch_id');
    }

    public function test_inventory_and_purchase_reports_restrict_all_branches_to_the_current_business(): void
    {
        [$business, $branch, $owner] = $this->tenant();
        $sale = $this->sell($owner);
        $product = $sale->items()->sole()->product;
        $other = Branch::create(['business_id'=>$business->id,'name'=>'Other Warehouse','code'=>'WH']);
        ProductStock::create(['business_id'=>$business->id,'branch_id'=>$other->id,'product_id'=>$product->id,'location'=>'warehouse','quantity'=>5]);
        $supplier = Supplier::create(['business_id'=>$business->id,'name'=>'Local Supplier']);
        foreach ([[$branch->id,10000,'MAIN'],[$other->id,20000,'OTHER']] as [$branchId,$amount,$reference]) {
            SupplierInvoice::create(['business_id'=>$business->id,'branch_id'=>$branchId,'supplier_id'=>$supplier->id,'reference'=>$reference,'supplied_at'=>today(),'terms'=>'credit','total_cents'=>$amount]);
        }
        [$foreignBusiness,$foreignBranch] = $this->tenant();
        $foreignSupplier = Supplier::create(['business_id'=>$foreignBusiness->id,'name'=>'Foreign Supplier']);
        SupplierInvoice::create(['business_id'=>$foreignBusiness->id,'branch_id'=>$foreignBranch->id,'supplier_id'=>$foreignSupplier->id,'reference'=>'FOREIGN','supplied_at'=>today(),'terms'=>'credit','total_cents'=>90000]);
        $foreignProduct = Product::create(['business_id'=>$foreignBusiness->id,'name'=>'Foreign Product','sku'=>'FOREIGN','price'=>100,'cost'=>50,'stock_alert'=>0,'is_active'=>true]);
        ProductStock::create(['business_id'=>$foreignBusiness->id,'branch_id'=>$foreignBranch->id,'product_id'=>$foreignProduct->id,'location'=>'main','quantity'=>500]);
        $this->actingAs($owner)->withSession(['branch_id'=>$branch->id]);
        foreach (['stock-valuation','price-list','stock-vendor','stock-supplier-summary'] as $report) {
            $this->get(route('reports.index',['report'=>$report]))->assertOk()->assertViewHas('totals',fn($t)=>(int)$t->quantity===8);
            $this->get(route('reports.index',['report'=>$report,'branch_id'=>'all']))->assertOk()
                ->assertViewHas('totals',fn($t)=>(int)$t->quantity===13)->assertDontSee('Foreign Product');
            $this->get(route('reports.index',['report'=>$report,'branch_id'=>$other->id]))->assertOk()->assertViewHas('totals',fn($t)=>(int)$t->quantity===5);
        }
        foreach (['purchases','supplier-outstanding'] as $report) {
            $this->get(route('reports.index',['report'=>$report]))->assertOk()->assertViewHas('totals',fn($t)=>(float)$t->balance===100.0);
            $this->get(route('reports.index',['report'=>$report,'branch_id'=>'all']))->assertOk()
                ->assertViewHas('totals',fn($t)=>(float)$t->balance===300.0)->assertDontSee('Foreign Supplier');
        }
        $this->get(route('reports.index',['report'=>'supplier-statements','branch_id'=>'all']))->assertOk()
            ->assertViewHas('totals',fn($t)=>(float)$t->debit===300.0);
    }

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
        CashierShift::firstOrCreate(['active_user_id'=>$user->id], ['business_id'=>$user->business_id, 'branch_id'=>$user->branch_id, 'user_id'=>$user->id, 'status'=>'open', 'opened_at'=>now()]);
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

    public function test_staff_cannot_access_reports_or_exports(): void
    {
        [$business, $branch] = $this->tenant();
        $staff = User::factory()->create(['business_id'=>$business->id, 'branch_id'=>$branch->id, 'role'=>'staff']);
        $this->actingAs($staff)->get(route('reports.index'))->assertForbidden();
        foreach (['sales', 'product-sales', 'stock-valuation', 'purchases'] as $report) {
            $this->get(route('reports.index', ['report'=>$report, 'format'=>'csv']))->assertForbidden();
        }
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
