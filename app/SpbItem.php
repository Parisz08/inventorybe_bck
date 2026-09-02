<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SpbItem extends Model
{
    protected $table = 'spb_items';

    protected $fillable = [
        'spb_id', 'spb_purchase_order_id', 'material_code', 'kategori', 'material_name', 'merek', 'specification', 'qty', 'unit', 'note',
    ];

    public function spb()
    {
        return $this->belongsTo(Spb::class, 'spb_id');
    }

    public function conditions()
    {
        return $this->hasMany(SpbItemCondition::class, 'spb_item_id')->orderBy('round', 'asc');
    }

    public function requestedVendors()
    {
        return $this->hasMany(SpbItemRequestedVendor::class, 'spb_item_id');
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(SpbPurchaseOrder::class, 'spb_purchase_order_id');
    }
}