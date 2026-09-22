<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $table = 'notifications';

    protected $fillable = [
        'user_id', 'type', 'title', 'message', 'spb_id', 'po_id', 'is_read', 'read_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function spb()
    {
        return $this->belongsTo(Spb::class, 'spb_id');
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(SpbPurchaseOrder::class, 'po_id');
    }
}
