<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockPeriod extends Model
{
    public function account() { return $this->belongsTo(User::class, 'user_id'); }
    public function shifts() { return $this->hasMany(CashierShift::class); }
    protected $guarded = ['id'];
    protected $casts = ['starts_on' => 'date', 'ends_on' => 'date', 'closed_at' => 'datetime'];

    public function lines() { return $this->hasMany(StockPeriodLine::class); }
    public function opener() { return $this->belongsTo(User::class, 'opened_by'); }
    public function closer() { return $this->belongsTo(User::class, 'closed_by'); }
    public function shift() { return $this->belongsTo(CashierShift::class, 'cashier_shift_id'); }
    public function category() { return $this->belongsTo(Category::class); }
    public function getReferenceAttribute(): string { return 'SP-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT); }
}
