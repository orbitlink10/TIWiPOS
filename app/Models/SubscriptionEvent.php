<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'subscription_id',
        'event_type',
        'old_status',
        'new_status',
        'notes',
    ];

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
}
