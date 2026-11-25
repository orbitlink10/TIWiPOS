<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToBusiness;
use App\Models\Concerns\BelongsToBranch;

class StockMovement extends Model
{
    use HasFactory, BelongsToBusiness, BelongsToBranch;

    protected $fillable = [
        'business_id',
        'branch_id',
        'product_id',
        'user_id',
        'location',
        'type',
        'quantity_change',
        'quantity_before',
        'quantity_after',
        'reference_type',
        'reference_id',
        'note',
        'effective_at',
    ];

    protected $casts = ['effective_at' => 'datetime'];

    protected static function booted(): void
    {
        static::creating(function (StockMovement $movement) {
            if ($movement->effective_at) return;
            $source = match ($movement->reference_type) {
                'sale' => ['sales', 'created_at'],
                'purchase_receipt' => ['purchase_receipts', 'received_at'],
                'stock_adjustment' => ['stock_adjustments', 'adjusted_at'],
                default => null,
            };
            $effectiveAt = $source && $movement->reference_id
                ? \Illuminate\Support\Facades\DB::table($source[0])->where('business_id', $movement->business_id ?? auth()->user()?->business_id)
                    ->where('id', $movement->reference_id)->value($source[1])
                : null;
            $movement->effective_at = $effectiveAt ?? $movement->created_at ?? now();
        });
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
