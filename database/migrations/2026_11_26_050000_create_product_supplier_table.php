<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_supplier', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['product_id','supplier_id']);
            $table->index(['business_id','supplier_id']);
        });
        DB::table('products as p')->join('suppliers as s','s.id','=','p.supplier_id')
            ->whereColumn('s.business_id','p.business_id')->select('p.id','p.business_id','p.supplier_id')
            ->orderBy('p.id')->chunkById(500, function ($products) {
                $now = now();
                DB::table('product_supplier')->insertOrIgnore($products->map(fn ($p) => [
                    'business_id'=>$p->business_id, 'product_id'=>$p->id, 'supplier_id'=>$p->supplier_id,
                    'created_at'=>$now, 'updated_at'=>$now,
                ])->all());
            }, 'p.id', 'id');
    }

    public function down(): void
    {
        Schema::dropIfExists('product_supplier');
    }
};
