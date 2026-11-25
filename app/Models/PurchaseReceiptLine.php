<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseReceiptLine extends Model
{
    public $timestamps = false;
    protected $guarded = ['id'];

    public function serials()
    {
        return $this->hasMany(PurchaseReceiptSerial::class);
    }

    public function item()
    {
        return $this->belongsTo(SupplierInvoiceItem::class, 'supplier_invoice_item_id');
    }
}
