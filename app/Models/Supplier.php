<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToBusiness;

class Supplier extends Model
{
    use HasFactory, BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'name',
        'contact_name',
        'phone',
        'email',
        'address',
        'city',
        'country',
        'is_active',
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_supplier')->withPivot('business_id')->withTimestamps();
    }
}
