<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dateTime('effective_at')->nullable();
            $table->index(['business_id', 'branch_id', 'effective_at'], 'stock_movements_effective_index');
        });
        DB::table('stock_movements')->update(['effective_at' => DB::raw('created_at')]);
        foreach (['sale'=>['sales','created_at'], 'purchase_receipt'=>['purchase_receipts','received_at'], 'stock_adjustment'=>['stock_adjustments','adjusted_at']] as $type=>[$table,$column]) {
            DB::table('stock_movements')->where('reference_type',$type)->update([
                'effective_at'=>DB::raw("COALESCE((SELECT {$table}.{$column} FROM {$table} WHERE {$table}.id=stock_movements.reference_id AND {$table}.business_id=stock_movements.business_id), stock_movements.created_at)"),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropIndex('stock_movements_effective_index');
            $table->dropColumn('effective_at');
        });
    }
};
