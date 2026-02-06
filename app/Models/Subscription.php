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
        'plan_id',
        'plan',
        'interval',
        'status',
        'amount',
        'currency',
        'period_start',
        'period_end',
        'grace_until',
        'last_payment_at',
        'canceled_at',
        'meta',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'grace_until' => 'datetime',
        'last_payment_at' => 'datetime',
        'canceled_at' => 'datetime',
        'meta' => 'array',
    ];

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }
}
