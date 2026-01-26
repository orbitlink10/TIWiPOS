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
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
