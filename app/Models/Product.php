<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'sku',
        'serial_number',
        'barcode',
        'category_id',
        'supplier_id',
        'cost',
        'price',
        'stock_alert',
        'is_active',
        'description',
    ];

    public function stocks()
    {
        return $this->hasMany(ProductStock::class);
    }

    public function stockOnHand(string $location = 'main'): int
    {
        return (int) $this->stocks()->where('location', $location)->sum('quantity');
    }
}
