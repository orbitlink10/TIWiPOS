<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToBusiness;
use App\Models\Concerns\BelongsToBranch;

class Payment extends Model
{
    use HasFactory, BelongsToBusiness, BelongsToBranch;

    protected $fillable = [
        'business_id',
        'branch_id',
        'sale_id',
        'subscription_id',
        'method',
        'amount',
        'currency',
        'reference',
        'provider',
        'provider_ref',
        'status',
        'raw_payload',
        'paid_at',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'raw_payload' => 'array',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
}
