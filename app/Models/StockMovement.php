<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
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
