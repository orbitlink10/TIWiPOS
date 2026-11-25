<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierInvoice extends Model
{
    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::creating(function (SupplierInvoice $invoice) {
            if (blank($invoice->reference)) {
                $invoice->reference = 'PUR-'.\Illuminate\Support\Str::ulid();
            }
        });
    }

    protected function casts(): array
    {
        return ['supplied_at' => 'date', 'due_at' => 'date', 'stock_received' => 'boolean'];
    }

    public function items()
    {
        return $this->hasMany(SupplierInvoiceItem::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function payments()
    {
        return $this->hasMany(SupplierPayment::class);
    }

    public function receipts()
    {
        return $this->hasMany(PurchaseReceipt::class);
    }

    public function getReceivingStatusAttribute(): string
    {
        if ($this->stock_received) return 'Received';
        return $this->items->sum('received_quantity') > 0 ? 'Part received' : 'Awaiting receipt';
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function getPaidCentsAttribute(): int
    {
        return (int) $this->payments->sum('amount_cents');
    }

    public function getBalanceCentsAttribute(): int
    {
        return max(0, $this->total_cents - $this->paid_cents);
    }

    public function getStatusAttribute(): string
    {
        if ($this->balance_cents === 0) {
            return 'Paid';
        }
        if ($this->due_at && $this->due_at->lt(today())) {
            return 'Overdue';
        }

        return $this->paid_cents > 0 ? 'Part paid' : 'Unpaid';
    }
}
