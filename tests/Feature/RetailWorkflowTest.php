<?php

namespace Tests\Feature;

use App\Models\{Branch, Business, CashierShift, Category, Product, ProductStock, PurchaseReceipt, Sale, StockMovement, StockPeriod, Subscription, Supplier, SupplierInvoice, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RetailWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function shop(): array
    {
        $business = Business::create(['name'=>'Workflow Shop','slug'=>'workflow-shop','status'=>'active','subscription_status'=>'active','current_period_start'=>today(),'current_period_end'=>today()->addMonth()]);
        $branch = Branch::create(['business_id'=>$business->id,'name'=>'Workflow Branch','code'=>'WF','is_default'=>true]);
        Subscription::create(['business_id'=>$business->id,'plan'=>'standard','interval'=>'monthly','status'=>'active','amount'=>0,'currency'=>'KES','period_start'=>today(),'period_end'=>today()->addMonth(),'grace_until'=>now()->addMonth()]);
        $owner = User::factory()->create(['business_id'=>$business->id,'branch_id'=>$branch->id,'role'=>'owner']);
        $staff = User::factory()->create(['business_id'=>$business->id,'branch_id'=>$branch->id,'role'=>'staff']);
        $this->actingAs($owner)->withSession(['branch_id'=>$branch->id]);
        $category = Category::create(['business_id'=>$business->id,'name'=>'Routers','slug'=>'routers','is_active'=>true]);
        return [$business,$branch,$owner,$staff,$category];
    }

    public function test_purchase_sale_verification_count_download_and_next_count_end_to_end(): void
    {
        [$business,$branch,$owner,$staff,$category] = $this->shop();
        $this->post(route('products.store'), ['name'=>'Workflow Router','category_id'=>$category->id,'price'=>100,'cost'=>50,'stock'=>10,'recorded_at'=>today()->toDateString(),'is_active'=>1])->assertSessionHasNoErrors();
        $product = Product::where('business_id',$business->id)->sole();
        $this->assertStringStartsWith('SKU-', $product->sku);
        $this->assertNull($product->serial_number);
        $supplier = Supplier::create(['business_id'=>$business->id,'name'=>'Workflow Supply','is_active'=>true]);
        $this->post(route('suppliers.products.store',$supplier), ['product_ids'=>[$product->id]])->assertSessionHasNoErrors();
        $this->post(route('purchases.store'), ['supplier_id'=>$supplier->id,'reference'=>'WF-1','supplied_at'=>today()->toDateString(),'terms'=>'credit','due_at'=>today()->addDay()->toDateString(),'amount'=>'0','method'=>'cash','items'=>[['product_id'=>$product->id,'quantity'=>5,'unit_cost'=>'50']]])->assertSessionHasNoErrors();
        $invoice = SupplierInvoice::where('business_id',$business->id)->sole();
        $item = $invoice->items()->sole();
        $receive = ['receipt_token'=>(string)Str::uuid(),'received_at'=>today()->toDateString(),'lines'=>[$item->id=>['quantity'=>3,'serial_numbers'=>'WF-SERIAL-1']]];
        $this->post(route('purchases.receive.store',$invoice),$receive)->assertSessionHasNoErrors();
        $this->post(route('purchases.receive.store',$invoice),$receive)->assertSessionHasNoErrors();
        $this->assertSame(1, PurchaseReceipt::count());
        $this->assertEquals(13,$product->stocks()->sum('quantity'));
        $receive['receipt_token'] = (string)Str::uuid();
        $receive['lines'][$item->id] = ['quantity'=>3];
        $this->post(route('purchases.receive.store',$invoice),$receive)->assertSessionHasErrors();
        $receive['lines'][$item->id] = ['quantity'=>2];
        $this->post(route('purchases.receive.store',$invoice),$receive)->assertSessionHasNoErrors();
        $this->assertEquals(15,$product->stocks()->sum('quantity'));
        $this->assertTrue((bool)$invoice->fresh()->stock_received);

        $this->actingAs($staff)->get(route('stock-control.periods.index'))->assertForbidden();
        $this->post(route('shifts.open'),['opening_cash'=>'10'])->assertSessionHasNoErrors();
        $shift = CashierShift::where('user_id',$staff->id)->sole();
        $this->post(route('sale.store'),['items'=>[['product_id'=>$product->id,'quantity'=>2]],'payments'=>['cash'=>100,'mobile'=>100]])->assertSessionHasNoErrors();
        $sale = Sale::where('business_id',$business->id)->sole();
        $this->get(route('sale.receipt',$sale))->assertOk()->assertSee('Split payment');
        $this->assertSame(11000,$shift->summary()['expected_cash_cents']);
        $this->post(route('shifts.close',$shift),['counted_cash'=>'110','confirmation'=>1,'expected_sales_count'=>1,'expected_cash_cents'=>11000])->assertSessionHasNoErrors();
        $this->get(route('shifts.show',$shift))->assertOk()->assertSee('layout pos-only',false)->assertSee('Pending verification');
        $this->actingAs($owner)->post(route('shifts.verify',$shift),['confirmation'=>1,'verified'=>['cash'=>'110','mobile'=>'100','card'=>'','bank'=>'','other'=>'']])->assertSessionHasNoErrors();
        $this->assertNotNull($shift->fresh()->verified_at);
        $this->get(route('shifts.show',$shift))->assertOk()->assertDontSee('Awaiting manager or admin verification.');
        $this->assertSame(0,$shift->fresh()->verified_payment_totals['card']);

        $start = ['creation_token'=>(string)Str::uuid(),'location'=>'main','starts_on'=>today()->toDateString(),'ends_on'=>today()->toDateString()];
        $this->post(route('stock-control.periods.store'),$start)->assertSessionHasNoErrors();
        $period = StockPeriod::where('business_id',$business->id)->sole();
        $this->assertNull($period->user_id);
        $line = $period->lines()->sole();
        $this->assertEquals(13,$line->expected_quantity);
        $this->post(route('stock-control.periods.post',$period),['version'=>1,'confirmation'=>1])->assertSessionHasErrors();
        $this->post(route('stock-control.periods.save',$period),['version'=>1,'page'=>1,'lines'=>[$line->id=>['quantity'=>12]]])->assertSessionHasNoErrors();
        // A later sale must be preserved, not overwritten by the physical snapshot.
        $this->actingAs($staff)->post(route('shifts.open'),['opening_cash'=>'0'])->assertSessionHasNoErrors();
        $this->post(route('sale.store'),['items'=>[['product_id'=>$product->id,'quantity'=>1]],'method'=>'cash'])->assertSessionHasNoErrors();
        $this->actingAs($owner)->post(route('stock-control.periods.post',$period),['version'=>2,'confirmation'=>1])->assertSessionHasNoErrors()->assertRedirect(route('stock-control.variance',['period'=>$period->id]));
        $this->assertEquals(11,$product->stocks()->sum('quantity'));
        $this->assertSame($period->id,$shift->fresh()->stock_period_id);
        $this->assertNotNull($shift->fresh()->stock_locked_at);
        $this->assertSame(1,$period->shifts()->count());
        $this->assertSame(1,CashierShift::where('user_id',$staff->id)->where('status','open')->whereNull('stock_period_id')->count());
        $this->post(route('stock-control.periods.post',$period),['version'=>2,'confirmation'=>1])->assertSessionHasNoErrors();
        $this->assertSame(1,StockMovement::where('reference_type','stock_period')->where('reference_id',$period->id)->count());
        $this->get(route('stock-control.variance',['period'=>$period->id]))->assertOk()->assertSee('Workflow Router')->assertSee('50.00');
        $csv = $this->get(route('stock-control.variance',['period'=>$period->id,'export'=>'csv']));
        $csv->assertOk()->assertDownload();
        $this->assertStringContainsString('Workflow Router',$csv->streamedContent());
        $this->assertStringContainsString('13,12,-1,50.00,-50.00',$csv->streamedContent());
        $balanced = $this->get(route('stock-control.variance',['period'=>$period->id,'direction'=>'balanced','export'=>'csv']));
        $balanced->assertOk()->assertDownload();
        $this->assertStringNotContainsString('Workflow Router',$balanced->streamedContent());
        $start['creation_token'] = (string)Str::uuid();
        $this->post(route('stock-control.periods.store'),$start)->assertSessionHasNoErrors();
        $next = StockPeriod::where('business_id',$business->id)->latest('id')->first();
        $this->get(route('stock-control.periods.show',$next))->assertOk()->assertViewHas('countShifts',fn($shifts)=>$shifts->isEmpty());
        $otherBranch = Branch::create(['business_id'=>$business->id,'name'=>'Other Branch','code'=>'OTHER']);
        $this->withSession(['branch_id'=>$otherBranch->id])->get(route('stock-control.variance',['period'=>$period->id,'export'=>'csv']))->assertNotFound();
    }

    public function test_two_sales_in_the_same_second_get_distinct_receipts(): void
    {
        [$business,$branch,$owner,$staff,$category] = $this->shop();
        $this->travelTo(now()->startOfSecond());
        $product = Product::create(['business_id'=>$business->id,'name'=>'Fast sale product','sku'=>'FAST','price'=>100,'cost'=>50,'category_id'=>$category->id,'is_active'=>true]);
        ProductStock::create(['business_id'=>$business->id,'branch_id'=>$branch->id,'product_id'=>$product->id,'location'=>'main','quantity'=>10]);
        $this->actingAs($staff)->post(route('shifts.open'),['opening_cash'=>'0'])->assertRedirect();
        $data = ['items'=>[['product_id'=>$product->id,'quantity'=>1]],'method'=>'cash'];
        $this->post(route('sale.store'),$data)->assertRedirect()->assertSessionHasNoErrors();
        $this->post(route('sale.store'),$data)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(2,Sale::where('business_id',$business->id)->count());
        $this->assertSame(2,Sale::where('business_id',$business->id)->distinct()->count('sale_number'));
        $this->assertEquals(8,$product->stocks()->sum('quantity'));
    }

    public function test_verification_requires_an_independent_reviewer_and_explanation_for_differences(): void
    {
        [$business,$branch,$owner,$staff] = $this->shop();
        $shift = CashierShift::create(['business_id'=>$business->id,'branch_id'=>$branch->id,'user_id'=>$owner->id,'closed_by'=>$owner->id,'status'=>'closed','opened_at'=>now()->subHour(),'closed_at'=>now(),'opening_cash_cents'=>0,'expected_cash_cents'=>10000,'counted_cash_cents'=>10000,'sales_total_cents'=>10000,'sales_count'=>1,'payment_totals'=>['cash'=>10000]]);
        $data = ['confirmation'=>1,'verified'=>['cash'=>'100','mobile'=>'','card'=>'','bank'=>'','other'=>'']];
        $this->post(route('shifts.verify',$shift),$data)->assertForbidden();
        $this->actingAs($staff)->post(route('shifts.verify',$shift),$data)->assertForbidden();
        $reviewer = User::factory()->create(['business_id'=>$business->id,'branch_id'=>$branch->id,'role'=>'manager']);
        $data['verified']['cash'] = '';
        $this->actingAs($reviewer)->post(route('shifts.verify',$shift),$data)->assertSessionHasErrors('verified.cash');
        $data['verified']['cash'] = '0';
        $this->post(route('shifts.verify',$shift),$data)->assertSessionHasErrors('verification_notes');
        $this->assertNull($shift->fresh()->verified_at);
        $data['verification_notes'] = 'No cash was handed over; shortage reviewed.';
        $this->post(route('shifts.verify',$shift),$data)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(0,$shift->fresh()->verified_payment_totals['cash']);
        $this->assertSame(10000,(int)$shift->fresh()->counted_cash_cents);
        $data['verified']['cash'] = '100';
        $this->post(route('shifts.verify',$shift),$data)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(0,$shift->fresh()->verified_payment_totals['cash']);
    }

    public function test_stale_and_cancelled_counts_do_not_adjust_stock_or_lock_shifts(): void
    {
        [$business,$branch,$owner,$staff,$category] = $this->shop();
        $product = Product::create(['business_id'=>$business->id,'name'=>'Count product','sku'=>'COUNT','price'=>100,'cost'=>50,'category_id'=>$category->id,'is_active'=>true]);
        ProductStock::create(['business_id'=>$business->id,'branch_id'=>$branch->id,'product_id'=>$product->id,'location'=>'main','quantity'=>10]);
        $shift = CashierShift::create(['business_id'=>$business->id,'branch_id'=>$branch->id,'user_id'=>$staff->id,'status'=>'closed','opening_cash_cents'=>0,'opened_at'=>now()->subHour(),'closed_at'=>now()]);
        $start = ['creation_token'=>(string)Str::uuid(),'location'=>'main','starts_on'=>today()->toDateString(),'ends_on'=>today()->toDateString()];
        $this->post(route('stock-control.periods.store'),$start)->assertRedirect()->assertSessionHasNoErrors();
        $period = StockPeriod::where('business_id',$business->id)->sole();
        $line = $period->lines()->sole();
        $save = ['version'=>1,'page'=>1,'lines'=>[$line->id=>['quantity'=>9]]];
        $this->post(route('stock-control.periods.save',$period),$save)->assertSessionHasNoErrors();
        $save['lines'][$line->id]['quantity']=0;
        $this->post(route('stock-control.periods.save',$period),$save)->assertSessionHasErrors('period');
        $this->assertEquals(9,$line->fresh()->counted_quantity);
        $this->post(route('stock-control.periods.cancel',$period),['version'=>2,'closing_notes'=>'Count abandoned'])->assertRedirect()->assertSessionHasNoErrors();
        $this->post(route('stock-control.periods.post',$period),['version'=>3,'confirmation'=>1])->assertStatus(422);
        $this->assertEquals(10,$product->stocks()->sum('quantity'));
        $this->assertNull($shift->fresh()->stock_locked_at);
        $this->assertNull($shift->fresh()->stock_period_id);
        $this->assertSame(0,StockMovement::where('reference_type','stock_period')->count());
    }
}
