<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('cashier_shift_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('opened_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->uuid('creation_token')->unique();
            $table->string('active_slot', 64)->nullable()->unique();
            $table->string('location', 100)->default('main');
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('status', 16)->default('open');
            $table->unsignedInteger('version')->default(1);
            $table->text('notes')->nullable();
            $table->text('closing_notes')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->index(['business_id', 'branch_id', 'status']);
        });
        Schema::create('stock_period_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('product_name');
            $table->string('sku');
            $table->integer('opening_quantity');
            $table->integer('expected_quantity');
            $table->unsignedInteger('counted_quantity')->nullable();
            $table->integer('variance')->nullable();
            $table->decimal('unit_cost', 12, 2)->default(0);
            $table->timestamps();
            $table->unique(['stock_period_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_period_lines');
        Schema::dropIfExists('stock_periods');
    }
};
