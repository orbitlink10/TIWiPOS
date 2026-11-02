<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuotationEmail extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['content' => 'encrypted:array', 'sent_at' => 'datetime'];
}
