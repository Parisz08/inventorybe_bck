<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SpbItemRequestedVendor extends Model
{
    protected $table = 'spb_item_requested_vendors';

    protected $fillable = [
        'spb_item_id', 'vendor_id', 'requested_by',
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function item()
    {
        return $this->belongsTo(SpbItem::class, 'spb_item_id');
    }
}