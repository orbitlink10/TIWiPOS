<?php

namespace Tests\Feature;

use App\Models\{Branch, Business, CashierShift, Category, Product, ProductStock, Sale, StockMovement, Subscription, Supplier, SupplierInvoice, PurchaseReceipt, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class StockAsOfReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_as_of_day_ends_at_midnight_in_nairobi_not_utc(): void
    {
        $this->travelTo(now()->setDate(2026,11,5)->setTime(20,59));
        [$owner,$category]=$this->tenant();
        $product=$this->addProduct($category,5);
        $this->travel(1)->minutes(); // 00:00 on 6 November in Nairobi.
        $this->editProduct($product,$category,7);
        $this->assertSnapshot('2026-11-05',5);
        $this->assertSnapshot('2026-11-06',7);
        $this->get(route('reports.index',['report'=>'stock-as-at']))->assertOk()
            ->assertViewHas('asAt','2026-11-06')->assertSee('End of day (EAT)');
    }

    public function test_migration_recovers_original_dates_for_legacy_edited_sale_movements(): void
    {
        $this->travelTo(now()->setDate(2026,11,1)->setTime(10,0));
        [$owner,$category]=$this->tenant();
        $product=$this->addProduct($category,10);
        $this->travel(1)->days();
        CashierShift::create(['business_id'=>$owner->business_id,'branch_id'=>$owner->branch_id,'user_id'=>$owner->id,'active_user_id'=>$owner->id,'status'=>'open','opened_at'=>now()]);
        $this->post(route('sale.store'),['items'=>[['product_id'=>$product->id,'quantity'=>2]],'method'=>'cash'])->assertSessionHasNoErrors();
        $sale=Sale::sole();
        $this->travel(3)->days();
        $this->put(route('sales.update',$sale),['items'=>[['product_id'=>$product->id,'quantity'=>4]],'method'=>'cash'])->assertSessionHasNoErrors();
        $migration=require database_path('migrations/2026_11_26_040000_add_effective_at_to_stock_movements.php');
        $migration->down();
        $migration->up();
        $movement=StockMovement::where('reference_type','sale')->sole();
        $this->assertSame('2026-11-05',$movement->created_at->toDateString());
        $this->assertSame('2026-11-02',$movement->effective_at->toDateString());
        $this->assertSnapshot('2026-11-01',10);
        $this->assertSnapshot('2026-11-02',6);
    }

    public function test_historical_quantities_cover_opening_sales_backdated_receipts_and_edits(): void
    {
        $this->travelTo(now()->setDate(2026,11,1)->setTime(10,0));
        [$owner,$category] = $this->tenant();
        $product = $this->addProduct($category,10);
        $this->travel(1)->days();
        CashierShift::create(['business_id'=>$owner->business_id,'branch_id'=>$owner->branch_id,'user_id'=>$owner->id,'active_user_id'=>$owner->id,'status'=>'open','opened_at'=>now()]);
        $this->post(route('sale.store'),['items'=>[['product_id'=>$product->id,'quantity'=>2]],'method'=>'cash'])->assertSessionHasNoErrors();
        $sale = Sale::sole();
        $this->travel(1)->days();
        $this->editProduct($product,$category,13); // 8 + 5, recorded on 3 November.
        $supplier=Supplier::create(['business_id'=>$owner->business_id,'name'=>'Supplier']);
        $invoice=SupplierInvoice::create(['business_id'=>$owner->business_id,'branch_id'=>$owner->branch_id,'supplier_id'=>$supplier->id,'reference'=>'ASOF','supplied_at'=>'2026-11-04','terms'=>'cash','total_cents'=>15000]);
        $this->travel(3)->days(); // Receipt posted on 6 November, effective on 4 November.
        $receipt=PurchaseReceipt::create(['supplier_invoice_id'=>$invoice->id,'business_id'=>$owner->business_id,'branch_id'=>$owner->branch_id,'user_id'=>$owner->id,'receipt_token'=>(string)Str::uuid(),'received_at'=>'2026-11-04']);
        $stock=$product->stocks()->sole();$stock->increment('quantity',3);
        StockMovement::create(['business_id'=>$owner->business_id,'branch_id'=>$owner->branch_id,'product_id'=>$product->id,'location'=>'main','type'=>'purchase','quantity_change'=>3,'quantity_before'=>13,'quantity_after'=>16,'reference_type'=>'purchase_receipt','reference_id'=>$receipt->id]);
        $this->assertSnapshot('2026-10-31',0);
        $this->assertSnapshot('2026-11-01',10);
        $this->assertSnapshot('2026-11-02',8);
        $this->assertSnapshot('2026-11-03',13);
        $this->assertSnapshot('2026-11-04',16);
        $this->get(route('reports.index',['report'=>'stock-as-at','as_at'=>'2026-11-02']))->assertViewHas('totals',fn($t)=>(int)$t->inferred_positions===0);

        $this->travel(1)->days();
        $this->put(route('sales.update',$sale),['items'=>[['product_id'=>$product->id,'quantity'=>4]],'method'=>'cash'])->assertSessionHasNoErrors();
        $this->assertSnapshot('2026-11-01',10);
        $this->assertSnapshot('2026-11-02',6);
        $this->assertSnapshot('2026-11-03',11);
        $this->assertSnapshot('2026-11-04',14);
        $this->assertEquals(14,$product->stocks()->sum('quantity'));
    }

    public function test_backdated_opening_and_end_of_day_boundary_are_honoured(): void
    {
        $this->travelTo(now()->setDate(2026,11,5)->setTime(15,0));
        [$owner,$category]=$this->tenant();
        $product=$this->addProduct($category,5,'2026-11-01');
        $this->assertSnapshot('2026-10-31',0);
        $this->assertSnapshot('2026-11-01',5);
        $this->travelTo(now()->setDate(2026,11,6)->setTime(0,0));
        $this->editProduct($product,$category,7);
        $this->assertSnapshot('2026-11-05',5);
        $this->assertSnapshot('2026-11-06',7);
        $csv=$this->get(route('reports.index',['report'=>'stock-as-at','as_at'=>'2026-11-01','format'=>'csv']))->assertOk()->streamedContent();
        $this->assertStringContainsString('Units as of date',$csv);
        $this->assertStringContainsString('As-of Router',$csv);
        $this->getJson(route('reports.index',['report'=>'stock-as-at','as_at'=>'2026-11-07']))->assertUnprocessable()->assertJsonValidationErrors('as_at');
    }

    public function test_legacy_openings_are_flagged_and_products_created_after_cutoff_are_excluded(): void
    {
        $this->travelTo(now()->setDate(2026,11,1)->setTime(10,0));
        [$owner,$category]=$this->tenant();
        $legacy=Product::create(['business_id'=>$owner->business_id,'name'=>'Legacy Router','sku'=>'LEGACY','category_id'=>$category->id,'cost'=>50,'price'=>100,'stock_alert'=>0,'is_active'=>false]);
        ProductStock::create(['business_id'=>$owner->business_id,'branch_id'=>$owner->branch_id,'product_id'=>$legacy->id,'location'=>'main','quantity'=>8]);
        $this->travel(5)->days();
        $this->addProduct($category,20);
        $this->get(route('reports.index',['report'=>'stock-as-at','as_at'=>'2026-11-02']))->assertOk()
            ->assertSee('Legacy Router')->assertDontSee('As-of Router')
            ->assertSee('Inferred legacy opening')->assertSee('current catalogue cost')
            ->assertViewHas('totals',fn($t)=>(int)$t->quantity===8&&(int)$t->inferred_positions===1&&(float)$t->stock_value===400.0);
    }

    public function test_branch_and_business_boundaries_apply_to_historical_positions(): void
    {
        $this->travelTo(now()->setDate(2026,11,5)->setTime(10,0));
        [$owner,$category]=$this->tenant();
        $product=$this->addProduct($category,5);
        $other=Branch::create(['business_id'=>$owner->business_id,'name'=>'Other Branch','code'=>'OTHER']);
        ProductStock::create(['business_id'=>$owner->business_id,'branch_id'=>$other->id,'product_id'=>$product->id,'location'=>'other','quantity'=>3]);
        [$foreign]=$this->tenant();
        $foreignProduct=Product::create(['business_id'=>$foreign->business_id,'name'=>'Foreign Router','sku'=>'FOREIGN','cost'=>50,'price'=>100,'stock_alert'=>0]);
        ProductStock::create(['business_id'=>$foreign->business_id,'branch_id'=>$foreign->branch_id,'product_id'=>$foreignProduct->id,'location'=>'main','quantity'=>500]);
        $this->actingAs($owner)->withSession(['branch_id'=>$owner->branch_id]);
        $this->assertSnapshot('2026-11-05',5);
        $this->get(route('reports.index',['report'=>'stock-as-at','as_at'=>'2026-11-05','branch_id'=>'all']))->assertOk()
            ->assertDontSee('Foreign Router')->assertViewHas('totals',fn($t)=>(int)$t->quantity===8);
        $this->get(route('reports.index',['report'=>'stock-as-at','as_at'=>'2026-11-05','branch_id'=>$other->id]))->assertOk()
            ->assertViewHas('totals',fn($t)=>(int)$t->quantity===3);
    }

    private function assertSnapshot(string $day,int $quantity): void
    {
        $response=$this->get(route('reports.index',['report'=>'stock-as-at','as_at'=>$day]))->assertOk();
        $this->assertEquals($quantity,$response->viewData('totals')->quantity,'As of '.$day);
    }

    private function addProduct(Category $category,int $quantity,?string $recordedAt=null): Product
    {
        $this->post(route('products.store'),['name'=>'As-of Router','category_id'=>$category->id,'cost'=>50,'price'=>100,'stock'=>$quantity,'recorded_at'=>$recordedAt??today()->toDateString(),'is_active'=>1])->assertSessionHasNoErrors();
        return Product::latest('id')->firstOrFail();
    }

    private function editProduct(Product $product,Category $category,int $quantity): void
    {
        $this->put(route('products.update',$product),['name'=>$product->name,'sku'=>$product->sku,'category_id'=>$category->id,'cost'=>50,'price'=>100,'stock'=>$quantity,'is_active'=>1])->assertSessionHasNoErrors();
    }

    private function tenant(): array
    {
        $business=Business::create(['name'=>'As-of Test','slug'=>uniqid('asof-'),'status'=>'active','subscription_status'=>'active','current_period_end'=>now()->addMonth()]);
        $branch=Branch::create(['business_id'=>$business->id,'name'=>'Main Branch','code'=>uniqid(),'is_default'=>true]);
        Subscription::create(['business_id'=>$business->id,'plan'=>'standard','interval'=>'monthly','status'=>'active','amount'=>0,'currency'=>'KES','period_start'=>today(),'period_end'=>now()->addMonth(),'grace_until'=>now()->addMonth()]);
        $owner=User::factory()->create(['business_id'=>$business->id,'branch_id'=>$branch->id,'role'=>'owner']);
        $this->actingAs($owner);
        $category=Category::create(['business_id'=>$business->id,'name'=>'Routers','slug'=>uniqid('routers-'),'is_active'=>true]);
        return [$owner,$category];
    }
}
