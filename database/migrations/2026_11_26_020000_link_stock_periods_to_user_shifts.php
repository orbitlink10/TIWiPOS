<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_periods', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->constrained('users')->restrictOnDelete();
        });
        Schema::table('cashier_shifts', function (Blueprint $table) {
            $table->foreignId('stock_period_id')->nullable()->constrained('stock_periods')->restrictOnDelete();
            $table->timestamp('stock_locked_at')->nullable();
            $table->index(['business_id', 'branch_id', 'user_id', 'opened_at'], 'shift_account_period_index');
        });
    }

    public function down(): void
    {
        Schema::table('cashier_shifts', function (Blueprint $table) {
            $table->dropIndex('shift_account_period_index');
            $table->dropConstrainedForeignId('stock_period_id');
            $table->dropColumn('stock_locked_at');
        });
        Schema::table('stock_periods', fn (Blueprint $table) => $table->dropConstrainedForeignId('user_id'));
    }
};
