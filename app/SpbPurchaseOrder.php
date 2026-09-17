<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SpbPurchaseOrder extends Model
{
    protected $table = 'spb_purchase_orders';

    protected $fillable = [
        'spb_id', 'vendor_id', 'supplier', 'diajukan_oleh', 'po_number', 'po_date', 'po_total', 'status',
        'discount_percent', 'ppn_percent', 'pph_percent', 'up_name', 'no_sppb_manual', 'tax_updated_by', 'tax_updated_at',
        'resolusi_note', 'resolusi_at',
        'invoice_number', 'invoice_date', 'invoice_amount',
        'payment_date', 'payment_amount', 'payment_method',
        'updated_by',
    ];

    public function spb()
    {
        return $this->belongsTo(Spb::class, 'spb_id');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function items()
    {
        return $this->hasMany(SpbItem::class, 'spb_purchase_order_id');
    }
}