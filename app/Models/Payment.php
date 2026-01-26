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
        'method',
        'amount',
        'reference',
        'paid_at',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
}
