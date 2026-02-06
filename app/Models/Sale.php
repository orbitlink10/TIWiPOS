<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToBusiness;
use App\Models\Concerns\BelongsToBranch;
use App\Models\User;

class Sale extends Model
{
    use HasFactory, BelongsToBusiness, BelongsToBranch;

    protected $fillable = [
        'business_id',
        'branch_id',
        'sale_number',
        'customer_id',
        'user_id',
        'subtotal',
        'discount',
        'tax',
        'total',
        'payment_status',
        'status',
        'paid_at',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
