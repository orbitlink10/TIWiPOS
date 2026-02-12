<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToBusiness;

class Product extends Model
{
    use HasFactory, BelongsToBusiness;

    protected $fillable = [
        'business_id',
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

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function stockOnHand(?string $location = null, ?int $branchId = null): int
    {
        $query = $this->stocks();
        if (!is_null($location)) {
            $query->where('location', $location);
        }
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        return (int) $query->sum('quantity');
    }
}
