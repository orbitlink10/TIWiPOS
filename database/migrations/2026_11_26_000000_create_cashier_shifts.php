<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cashier_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('active_user_id')->nullable()->unique();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 16)->default('open');
            $table->unsignedBigInteger('opening_cash_cents')->default(0);
            $table->unsignedBigInteger('expected_cash_cents')->nullable();
            $table->unsignedBigInteger('counted_cash_cents')->nullable();
            $table->bigInteger('cash_variance_cents')->nullable();
            $table->unsignedBigInteger('sales_total_cents')->nullable();
            $table->unsignedInteger('sales_count')->nullable();
            $table->json('payment_totals')->nullable();
            $table->text('opening_notes')->nullable();
            $table->text('closing_notes')->nullable();
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->index(['business_id', 'branch_id', 'status']);
        });
        Schema::table('sales', fn (Blueprint $table) => $table->foreignId('cashier_shift_id')->nullable()->constrained('cashier_shifts')->restrictOnDelete());
    }

    public function down(): void
    {
        Schema::table('sales', fn (Blueprint $table) => $table->dropConstrainedForeignId('cashier_shift_id'));
        Schema::dropIfExists('cashier_shifts');
    }
};
