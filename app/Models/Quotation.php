<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quotation extends Model
{
    protected $fillable = ['business_id', 'branch_id', 'number', 'customer_name', 'quotation_date', 'valid_until', 'document'];

    protected $casts = ['document' => 'array', 'quotation_date' => 'date', 'valid_until' => 'date'];
}
