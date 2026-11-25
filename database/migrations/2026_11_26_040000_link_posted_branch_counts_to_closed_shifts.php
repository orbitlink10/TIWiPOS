<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $periods = DB::table('stock_periods')->where('status', 'closed')->whereNull('user_id')->whereNotNull('closed_at')->orderBy('closed_at')->orderBy('id')->get();
            foreach ($periods as $period) {
                DB::table('branches')->where('id', $period->branch_id)->lockForUpdate()->first();
                DB::table('cashier_shifts')->where('business_id', $period->business_id)->where('branch_id', $period->branch_id)
                    ->where('status', 'closed')->whereNull('stock_period_id')->whereNull('stock_locked_at')
                    ->where('opened_at', '>=', Carbon::parse($period->starts_on)->startOfDay())
                    ->where('opened_at', '<', Carbon::parse($period->ends_on)->addDay()->startOfDay())
                    ->where('closed_at', '<=', $period->closed_at)
                    ->update(['stock_period_id' => $period->id, 'stock_locked_at' => $period->closed_at, 'updated_at' => now()]);
            }
        });
    }

    public function down(): void
    {
        // Posted stock-count locks are permanent audit records.
    }
};
