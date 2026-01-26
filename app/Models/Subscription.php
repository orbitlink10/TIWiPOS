<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasFactory, BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'plan',
        'interval',
        'status',
        'amount',
        'currency',
        'period_start',
        'period_end',
        'canceled_at',
        'meta',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'canceled_at' => 'datetime',
        'meta' => 'array',
    ];
}
