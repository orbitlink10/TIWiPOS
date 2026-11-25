<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('tracks_serials')->default(false);
        });
        DB::table('products')->whereNotNull('serial_number')->where('serial_number', '!=', '')->update(['tracks_serials' => true]);
        Schema::table('supplier_invoice_items', function (Blueprint $table) {
            $table->unsignedInteger('received_quantity')->default(0);
        });
        DB::table('supplier_invoice_items')->whereIn('supplier_invoice_id', DB::table('supplier_invoices')->where('stock_received', true)->select('id'))
            ->update(['received_quantity' => DB::raw('quantity')]);

        Schema::create('purchase_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_invoice_id')->constrained()->restrictOnDelete();
            $table->foreignId('business_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('receipt_token')->unique();
            $table->date('received_at');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
        Schema::create('purchase_receipt_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_receipt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_invoice_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity');
        });
        Schema::create('purchase_receipt_serials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_receipt_line_id')->constrained()->cascadeOnDelete();
            $table->string('serial_number')->unique();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_receipt_serials');
        Schema::dropIfExists('purchase_receipt_lines');
        Schema::dropIfExists('purchase_receipts');
        Schema::table('supplier_invoice_items', fn (Blueprint $table) => $table->dropColumn('received_quantity'));
        Schema::table('products', fn (Blueprint $table) => $table->dropColumn('tracks_serials'));
    }
};
