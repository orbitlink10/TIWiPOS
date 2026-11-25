<?php

namespace App\Models;

use App\Support\Tenant;
use Illuminate\Database\Eloquent\Model;

class CashierShift extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['opened_at' => 'datetime', 'closed_at' => 'datetime', 'payment_totals' => 'array'];

    public static function currentQuery()
    {
        return static::where('business_id', Tenant::businessId())->where('branch_id', Tenant::branchId())
            ->where('user_id', auth()->id())->where('status', 'open');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function salesQuery()
    {
        return Sale::withoutGlobalScopes()->where('business_id', $this->business_id)->where('branch_id', $this->branch_id)
            ->where('cashier_shift_id', $this->id)->where('user_id', $this->user_id)->where('status', 'completed');
    }

    public function summary(): array
    {
        if ($this->status === 'closed') {
            return ['sales_total_cents' => (int) $this->sales_total_cents, 'sales_count' => (int) $this->sales_count,
                'payments' => $this->payment_totals ?? [], 'expected_cash_cents' => (int) $this->expected_cash_cents];
        }
        $sales = $this->salesQuery();
        $totals = (clone $sales)->selectRaw('COUNT(*) as receipts, COALESCE(SUM(ROUND(total * 100)), 0) as cents')->first();
        $payments = Payment::withoutGlobalScopes()->where('business_id', $this->business_id)->where('branch_id', $this->branch_id)
            ->whereIn('sale_id', (clone $sales)->select('sales.id'))->groupBy('method')
            ->selectRaw('method, COALESCE(SUM(ROUND(amount * 100)), 0) as cents')->pluck('cents', 'method')->map(fn ($cents) => (int) $cents)->all();
        return ['sales_total_cents' => (int) $totals->cents, 'sales_count' => (int) $totals->receipts, 'payments' => $payments,
            'expected_cash_cents' => (int) $this->opening_cash_cents + ($payments['cash'] ?? 0)];
    }
}
