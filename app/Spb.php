<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Spb extends Model
{
    protected $table = 'spb';

    // no_spb_short ikut dikirim di setiap data SPPB (JSON)
    protected $appends = ['no_spb_short'];

    protected $fillable = [
        'no_spb', 'divisi', 'keperluan', 'needed_date', 'sign_diajukan', 'sign_ditinjau', 'sign_disetujui', 'request_date', 'status',
        'approved_by', 'approved_at', 'approval_note',
        'cancelled_by', 'cancelled_at',
        'disposisi_by', 'disposisi_at', 'disposisi_note',
        'po_number', 'po_date', 'po_supplier', 'po_total',
        'resolusi_note', 'resolusi_at',
        'invoice_number', 'invoice_date', 'invoice_amount',
        'payment_date', 'payment_amount', 'payment_method',
        'created_by', 'created_by_user_id', 'updated_by',
    ];

    /**
     * Nomor SPPB singkat (mis. SPPB-0023) berdasarkan ID, naik terus dan tidak pernah direset.
     * Dipakai sebagai "nama" SPPB di tabel maupun di "Req. No." pada preview/print.
     * (no_spb panjang, mis. SPPB-20260908-0023, angka belakangnya direset tiap hari.)
     */
    public function getNoSpbShortAttribute()
    {
        return $this->id ? 'SPPB-' . str_pad($this->id, 4, '0', STR_PAD_LEFT) : null;
    }

    public function items()
    {
        return $this->hasMany(SpbItem::class, 'spb_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function conditions()
    {
        return $this->hasMany(SpbCondition::class, 'spb_id')->orderBy('round', 'asc');
    }

    public function purchaseOrders()
    {
        return $this->hasMany(SpbPurchaseOrder::class, 'spb_id');
    }
}