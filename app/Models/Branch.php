<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory, BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'name',
        'code',
        'location',
        'phone',
        'timezone',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];
}
