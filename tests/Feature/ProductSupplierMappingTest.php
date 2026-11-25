<?php

namespace Tests\Feature;

use App\Models\{Branch, Business, CashierShift, Category, Product, ProductStock, Subscription, Supplier, SupplierInvoice, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductSupplierMappingTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_can_be_linked_to_two_suppliers_and_filtered_by_either(): void
    {
        [$owner,$a,$b,$product]=$this->fixture();
        $this->get(route('suppliers.show',$b))->assertOk()->assertViewHas('availableProducts',fn($p)=>$p->contains('id',$product->id));
        $this->post(route('suppliers.products.store',$b),['product_ids'=>[$product->id]])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame($a->id,$product->fresh()->supplier_id);
        $this->assertEqualsCanonicalizing([$a->id,$b->id],$product->suppliers()->pluck('suppliers.id')->all());
        foreach ([$a,$b] as $supplier) {
            $this->get(route('suppliers.show',$supplier))->assertOk()
                ->assertViewHas('mappedProducts',fn($rows)=>$rows->contains('id',$product->id))
                ->assertViewHas('availableProducts',fn($rows)=>!$rows->contains('id',$product->id));
            $this->get(route('products',['supplier_id'=>$supplier->id]))->assertOk()
                ->assertViewHas('products',fn($rows)=>$rows->count()===1&&$rows->first()->id===$product->id);
            $this->get(route('purchases.create',['supplier_id'=>$supplier->id]))->assertOk()
                ->assertViewHas('products',fn($rows)=>$rows->first()->suppliers->contains('id',$supplier->id));
        }
        $this->get(route('products.show',$product))->assertOk()->assertSee('Supplier Alpha')->assertSee('Supplier Beta');
        $this->get(route('products',['supplier_id'=>'unassigned']))->assertOk()->assertViewHas('products',fn($p)=>$p->isEmpty());
        $this->post(route('suppliers.products.store',$b),['product_ids'=>[$product->id]])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('product_supplier',2);
    }

    public function test_unlinking_one_supplier_keeps_other_links_stock_and_purchase_history(): void
    {
        [$owner,$a,$b,$product]=$this->fixture();
        $invoice=SupplierInvoice::create(['business_id'=>$owner->business_id,'branch_id'=>$owner->branch_id,'supplier_id'=>$a->id,'reference'=>'MAPPING-HISTORY','supplied_at'=>today(),'terms'=>'cash','total_cents'=>5000]);
        $this->post(route('suppliers.products.store',$b),['product_ids'=>[$product->id]])->assertSessionHasNoErrors();
        $this->delete(route('suppliers.products.destroy',[$a,$product]))->assertRedirect();
        $this->assertSame($b->id,$product->fresh()->supplier_id);
        $this->assertEquals([$b->id],$product->suppliers()->pluck('suppliers.id')->all());
        $this->assertDatabaseHas('supplier_invoices',['id'=>$invoice->id,'supplier_id'=>$a->id]);
        $this->assertEquals(5,$product->stocks()->sum('quantity'));
        $this->delete(route('suppliers.products.destroy',[$b,$product]))->assertRedirect();
        $this->assertNull($product->fresh()->supplier_id);
        $this->assertSame(0,$product->suppliers()->count());
        $this->assertEquals(5,$product->stocks()->sum('quantity'));
        $this->get(route('products',['supplier_id'=>'unassigned']))->assertOk()->assertViewHas('products',fn($p)=>$p->count()===1);
    }

    public function test_preferred_supplier_changes_keep_additional_mappings(): void
    {
        [, $a,$b,$product,$category]=$this->fixture();
        $this->put(route('products.update',$product),['name'=>$product->name,'sku'=>$product->sku,'category_id'=>$category->id,'supplier_id'=>$b->id,'cost'=>50,'price'=>100,'is_active'=>1])->assertSessionHasNoErrors();
        $this->assertSame($b->id,$product->fresh()->supplier_id);
        $this->assertEqualsCanonicalizing([$a->id,$b->id],$product->suppliers()->pluck('suppliers.id')->all());
    }

    public function test_mapping_rejects_foreign_products_and_suppliers_inactive_products_and_staff(): void
    {
        [$owner,$a,$b,$product]=$this->fixture();
        [$foreignOwner,$foreignSupplier,,$foreignProduct]=$this->fixture();
        $this->actingAs($owner)->withSession(['branch_id'=>$owner->branch_id]);
        $this->post(route('suppliers.products.store',$a),['product_ids'=>[$foreignProduct->id]])->assertSessionHasErrors('product_ids.0');
        $this->post(route('suppliers.products.store',$foreignSupplier),['product_ids'=>[$product->id]])->assertNotFound();
        $this->delete(route('suppliers.products.destroy',[$a,$foreignProduct]))->assertNotFound();
        $product->update(['is_active'=>false]);
        $this->post(route('suppliers.products.store',$b),['product_ids'=>[$product->id]])->assertSessionHasErrors('product_ids');
        $this->assertSame(1,$product->suppliers()->count());
        $staff=User::factory()->create(['business_id'=>$owner->business_id,'branch_id'=>$owner->branch_id,'role'=>'staff']);
        $this->actingAs($staff)->post(route('suppliers.products.store',$b),['product_ids'=>[$product->id]])->assertForbidden();
    }

    public function test_supplier_reports_do_not_duplicate_stock_or_sales_for_multiple_mappings(): void
    {
        [$owner,$a,$b,$product]=$this->fixture();
        $this->post(route('suppliers.products.store',$b),['product_ids'=>[$product->id]])->assertSessionHasNoErrors();
        $this->get(route('reports.index',['report'=>'stock-supplier-summary']))->assertOk()
            ->assertViewHas('totals',fn($t)=>(int)$t->quantity===5&&(float)$t->stock_value===250.0);
        CashierShift::create(['business_id'=>$owner->business_id,'branch_id'=>$owner->branch_id,'user_id'=>$owner->id,'active_user_id'=>$owner->id,'status'=>'open','opened_at'=>now()]);
        $this->post(route('sale.store'),['items'=>[['product_id'=>$product->id,'quantity'=>1]],'method'=>'cash'])->assertSessionHasNoErrors();
        $this->get(route('reports.index',['report'=>'sales-supplier']))->assertOk()
            ->assertViewHas('totals',fn($t)=>(int)$t->quantity===1&&(float)$t->revenue===100.0);
    }

    public function test_migration_preserves_legacy_preferred_supplier_links(): void
    {
        [, $a,,$product]=$this->fixture();
        $migration=require database_path('migrations/2026_11_26_050000_create_product_supplier_table.php');
        $migration->down();$migration->up();
        $this->assertDatabaseHas('product_supplier',['business_id'=>$product->business_id,'product_id'=>$product->id,'supplier_id'=>$a->id]);
        $this->assertSame($a->id,$product->fresh()->supplier_id);
    }

    private function fixture(): array
    {
        $business=Business::create(['name'=>'Supplier Mapping','slug'=>uniqid('map-'),'status'=>'active','subscription_status'=>'active','current_period_end'=>now()->addMonth()]);
        $branch=Branch::create(['business_id'=>$business->id,'name'=>'Main Branch','code'=>uniqid(),'is_default'=>true]);
        Subscription::create(['business_id'=>$business->id,'plan'=>'standard','interval'=>'monthly','status'=>'active','amount'=>0,'currency'=>'KES','period_start'=>today(),'period_end'=>now()->addMonth(),'grace_until'=>now()->addMonth()]);
        $owner=User::factory()->create(['business_id'=>$business->id,'branch_id'=>$branch->id,'role'=>'owner']);$this->actingAs($owner);
        $a=Supplier::create(['business_id'=>$business->id,'name'=>'Supplier Alpha','is_active'=>true]);
        $b=Supplier::create(['business_id'=>$business->id,'name'=>'Supplier Beta','is_active'=>true]);
        $category=Category::create(['business_id'=>$business->id,'name'=>'Routers','slug'=>uniqid('cat-'),'is_active'=>true]);
        $product=Product::create(['business_id'=>$business->id,'supplier_id'=>$a->id,'category_id'=>$category->id,'name'=>'Shared Router','sku'=>uniqid('RTR-'),'cost'=>50,'price'=>100,'stock_alert'=>0,'is_active'=>true]);
        ProductStock::create(['business_id'=>$business->id,'branch_id'=>$branch->id,'product_id'=>$product->id,'location'=>'main','quantity'=>5]);
        return [$owner,$a,$b,$product,$category];
    }
}
