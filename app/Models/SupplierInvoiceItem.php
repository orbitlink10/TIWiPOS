<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierInvoiceItem extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
