<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseReceipt extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['received_at' => 'date'];

    public function lines()
    {
        return $this->hasMany(PurchaseReceiptLine::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
