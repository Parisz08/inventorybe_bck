<?php

namespace App\Http\Controllers;

use App\Spb;
use App\SpbItem;
use App\SpbCondition;
use App\SpbItemCondition;
use App\SpbItemRequestedVendor;
use App\SpbPurchaseOrder;
use App\StockBarang;
use App\Vendor;
use App\Http\Library\Responses;
use App\Http\Traits\LoggedUser;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SpbController extends Controller
{
    use LoggedUser;

    public function index(Request $request)
    {
        $query = Spb::with('items', 'purchaseOrders')->orderBy('created_at', 'desc');

        $status = $request->input('status');
        if (!empty($status)) {
            $query->where('status', $status);
        }

        $search = $request->input('search');
        if (!empty($search)) {
            $query->where('no_spb', 'like', '%' . $search . '%');
        }

        $data = $query->paginate(10);

        return Responses::sendResponse($data, 'SPB Retrieved Successfully');
    }

    public function show($id)
    {
        $data = Spb::with('items.conditions.vendor', 'items.requestedVendors.vendor', 'items.purchaseOrder', 'purchaseOrders.items')->find($id);

        if (!$data) {
            return Responses::sendError([], 'SPB Not Found');
        }

        foreach ($data->items as $item) {
            $stock = StockBarang::where('material_code', $item->material_code)->first();
            $item->actual_stock = $stock ? $stock->stock_barang : null;
            $item->min_stock    = $stock ? $stock->min_stock : null;
        }

        return Responses::sendResponse($data, 'SPB Detail Retrieved Successfully');
    }

    public function store(Request $request)
    {
        $items = $request->input('items', []);

        $validator = app('validator')->make($request->all(), [
    'items'            => 'required|array|min:1',
    'items.*.material_name' => 'required',
    'items.*.qty'      => 'required|integer|min:1',
    'needed_date'    => 'required|date',
    'sign_diajukan'  => 'required',
    'sign_ditinjau'  => 'required',
    'sign_disetujui' => 'required',
], []);
        if ($validator->fails()) {
            return Responses::sendError($validator->errors(), 'Validasi Gagal');
        }

        $userData = $this->get();
        $user     = $userData['user'];

        $noSpb = 'SPPB-' . date('Ymd') . '-' . str_pad(Spb::whereDate('created_at', date('Y-m-d'))->count() + 1, 4, '0', STR_PAD_LEFT);

        $spb = Spb::create([
    'no_spb'        => $noSpb,
    'divisi'        => $request->input('divisi'),
    'keperluan'     => $request->input('keperluan'),
    'needed_date'   => $request->input('needed_date'),
    'sign_diajukan'  => $request->input('sign_diajukan'),
    'sign_ditinjau'  => $request->input('sign_ditinjau'),
    'sign_disetujui' => $request->input('sign_disetujui'),
    'request_date'  => date('Y-m-d'),
    'status'        => 'Menunggu Approval',
    'created_by'    => $user->full_name,
    'updated_by'    => $user->full_name,
]);

        foreach ($items as $item) {
            SpbItem::create([
                'spb_id'        => $spb->id,
                'material_code' => $item['material_code'] ?? null,
                'kategori'      => $item['kategori'] ?? null,
                'material_name' => $item['material_name'],
                'merek'         => $item['merek'] ?? null,
                'specification' => $item['specification'] ?? null,
                'qty'           => $item['qty'],
                'unit'          => $item['unit'] ?? null,
                'note'          => $item['note'] ?? null,
            ]);
        }

        return Responses::sendResponse($spb->load('items'), 'SPB Created Successfully');
    }

    /**
     * Approve/Tolak SPB. Hanya Admin. Bisa dipakai ulang untuk SPB yang statusnya
     * "Menunggu Approval" ATAU "Ditolak" (supaya Admin bisa approve ulang SPB yang tadinya ditolak).
     */
    public function approve(Request $request, $id)
    {
        $validator = app('validator')->make($request->all(), ['approve' => 'required|boolean']);
        if ($validator->fails()) {
            return Responses::sendError($validator->errors(), 'Validasi Gagal');
        }

        $spb = Spb::find($id);
        if (!$spb) {
            return Responses::sendError([], 'SPB Not Found');
        }
        if ($spb->status != 'Menunggu Approval' && $spb->status != 'Ditolak') {
            return Responses::sendError([], 'SPB ini sudah masuk tahap pengadaan, tidak bisa diubah lagi dari sini');
        }

        $userData = $this->get();
        $user     = $userData['user'];

        if ($user->role != 'Admin') {
            return Responses::sendError([], 'Hanya Admin yang bisa melakukan approval SPB');
        }

        $approve = $request->input('approve');

        $spb->approved_by   = $user->full_name;
        $spb->approved_at   = Carbon::now();
        $spb->approval_note = $request->input('approval_note');
        $spb->status        = $approve ? 'Permintaan Vendor' : 'Ditolak';
        $spb->updated_by    = $user->full_name;
        $spb->save();

        $message = $approve ? 'SPB Approved, lanjut ke Permintaan Vendor' : 'SPB Ditolak';
        return Responses::sendResponse($spb, $message);
    }

    /**
     * Tandai vendor yang diminta memberi penawaran untuk 1 BARANG tertentu (belum ada harga).
     * Hanya Purchasing. Hanya boleh selama SPB berstatus "Permintaan Vendor".
     */
    public function requestVendor(Request $request, $itemId)
    {
        $userData = $this->get();
        $user     = $userData['user'];

        if ($user->role != 'Purchasing') {
            return Responses::sendError([], 'Hanya Purchasing yang bisa meminta penawaran vendor');
        }

        $validator = app('validator')->make($request->all(), ['vendor_id' => 'required|integer']);
        if ($validator->fails()) {
            return Responses::sendError($validator->errors(), 'Validasi Gagal');
        }

        $item = SpbItem::find($itemId);
        if (!$item) {
            return Responses::sendError([], 'Item SPB Not Found');
        }

        $spb = Spb::find($item->spb_id);
        if (!$spb || $spb->status != 'Permintaan Vendor') {
            return Responses::sendError([], 'SPB harus berstatus Permintaan Vendor untuk meminta penawaran');
        }

        $vendor = Vendor::find($request->input('vendor_id'));
        if (!$vendor) {
            return Responses::sendError([], 'Vendor Not Found');
        }

        $exists = SpbItemRequestedVendor::where('spb_item_id', $item->id)
                    ->where('vendor_id', $vendor->id)->first();
        if ($exists) {
            return Responses::sendResponse($exists->load('vendor'), 'Vendor Sudah Diminta Sebelumnya');
        }

        $requested = SpbItemRequestedVendor::create([
            'spb_item_id'  => $item->id,
            'vendor_id'    => $vendor->id,
            'requested_by' => $user->full_name,
        ]);

        return Responses::sendResponse($requested->load('vendor'), 'Vendor Berhasil Diminta Untuk Memberi Penawaran');
    }

    /**
     * Batalkan permintaan penawaran ke 1 vendor untuk 1 barang. Hanya Purchasing,
     * hanya selama SPB masih berstatus "Permintaan Vendor".
     */
    public function unrequestVendor($requestedVendorId)
    {
        $userData = $this->get();
        $user     = $userData['user'];

        if ($user->role != 'Purchasing') {
            return Responses::sendError([], 'Hanya Purchasing yang bisa mengubah permintaan vendor');
        }

        $requested = SpbItemRequestedVendor::find($requestedVendorId);
        if (!$requested) {
            return Responses::sendError([], 'Data Permintaan Vendor Not Found');
        }

        $item = SpbItem::find($requested->spb_item_id);
        $spb  = $item ? Spb::find($item->spb_id) : null;
        if (!$spb || $spb->status != 'Permintaan Vendor') {
            return Responses::sendError([], 'SPB harus berstatus Permintaan Vendor');
        }

        $requested->delete();

        return Responses::sendResponse([], 'Permintaan Vendor Berhasil Dibatalkan');
    }

    /**
     * Lanjut dari tahap "Permintaan Vendor" ke tahap "Permintaan Pengadaan" (isi harga).
     * Hanya Purchasing. Setiap barang wajib sudah punya minimal 1 vendor yang diminta.
     */
    public function lanjutPenawaran($id)
    {
        $userData = $this->get();
        $user     = $userData['user'];

        if ($user->role != 'Purchasing') {
            return Responses::sendError([], 'Hanya Purchasing yang bisa melanjutkan ke tahap penawaran');
        }

        $spb = Spb::with('items.requestedVendors')->find($id);
        if (!$spb) {
            return Responses::sendError([], 'SPB Not Found');
        }
        if ($spb->status != 'Permintaan Vendor') {
            return Responses::sendError([], 'SPB harus berstatus Permintaan Vendor');
        }

        foreach ($spb->items as $item) {
            if ($item->requestedVendors->count() < 1) {
                return Responses::sendError([], 'Barang "' . $item->material_name . '" belum punya vendor yang diminta penawaran');
            }
        }

        $spb->status     = 'Permintaan Pengadaan';
        $spb->updated_by = $user->full_name;
        $spb->save();

        return Responses::sendResponse($spb, 'Lanjut ke Tahap Penawaran Harga');
    }

    /**
     * Tambah penawaran vendor untuk 1 BARANG tertentu (bukan seluruh SPB). Hanya Purchasing.
     */
    public function addItemCondition(Request $request, $itemId)
    {
        $userData = $this->get();
        $user     = $userData['user'];

        if ($user->role != 'Purchasing') {
            return Responses::sendError([], 'Hanya Purchasing yang bisa menambahkan penawaran vendor');
        }

        $item = SpbItem::find($itemId);
        if (!$item) {
            return Responses::sendError([], 'Item SPB Not Found');
        }

        $spb = Spb::find($item->spb_id);
        if (!$spb || $spb->status != 'Permintaan Pengadaan') {
            return Responses::sendError([], 'SPB harus berstatus Permintaan Pengadaan untuk menambahkan kondisi');
        }

        $vendorId = $request->input('vendor_id');
        if (!$vendorId) {
            return Responses::sendError([], 'Vendor wajib dipilih dari daftar yang sudah diminta penawaran');
        }

        $requested = SpbItemRequestedVendor::where('spb_item_id', $item->id)
                        ->where('vendor_id', $vendorId)->first();
        if (!$requested) {
            return Responses::sendError([], 'Vendor ini belum diminta penawaran untuk barang ini. Tambahkan dulu di tahap Permintaan Vendor.');
        }

        $vendor = Vendor::find($vendorId);

        $round = $item->conditions()->count() + 1;

        $condition = SpbItemCondition::create([
            'spb_item_id'    => $item->id,
            'vendor_id'      => $vendorId,
            'round'          => $round,
            'supplier'       => $vendor ? $vendor->name : $request->input('supplier'),
            'price'          => $request->input('price'),
            'condition_note' => $request->input('condition_note'),
            'selected'       => false,
            'created_by'     => $user->full_name,
        ]);

        return Responses::sendResponse($condition->load('vendor'), 'Penawaran Vendor Berhasil Ditambahkan');
    }

    /**
     * Checklist vendor pemenang UNTUK 1 BARANG. Hanya Purchasing. Hanya boleh 1 vendor terpilih per barang.
     */
    public function selectItemCondition(Request $request, $conditionId)
    {
        $userData = $this->get();
        $user     = $userData['user'];

        if ($user->role != 'Purchasing') {
            return Responses::sendError([], 'Hanya Purchasing yang bisa memilih vendor');
        }

        $condition = SpbItemCondition::find($conditionId);
        if (!$condition) {
            return Responses::sendError([], 'Penawaran Vendor Not Found');
        }

        $item = SpbItem::find($condition->spb_item_id);
        $spb  = $item ? Spb::find($item->spb_id) : null;
        if (!$spb || $spb->status != 'Permintaan Pengadaan') {
            return Responses::sendError([], 'SPB harus berstatus Permintaan Pengadaan');
        }

        SpbItemCondition::where('spb_item_id', $condition->spb_item_id)->update(['selected' => false]);
        $condition->selected = true;
        $condition->save();

        return Responses::sendResponse($condition->load('vendor'), 'Vendor Berhasil Dipilih Untuk Barang Ini');
    }

    /**
     * Finalisasi pilihan vendor per-barang & otomatis terbitkan PO. Hanya Purchasing.
     * Setiap barang WAJIB sudah punya vendor terpilih. Barang-barang dikelompokkan
     * berdasarkan vendor pemenangnya masing-masing, lalu sistem otomatis membuat
     * 1 Purchase Order terpisah untuk setiap kelompok vendor (No. PO & Total otomatis).
     * Kalau "Belum Ada yang Sesuai" (disposisi=false), balik ke Permintaan Pengadaan untuk nego ulang.
     */
    public function disposisi(Request $request, $id)
    {
        $userData = $this->get();
        $user     = $userData['user'];

        if ($user->role != 'Purchasing') {
            return Responses::sendError([], 'Hanya Purchasing yang bisa melakukan disposisi');
        }

        $validator = app('validator')->make($request->all(), ['disposisi' => 'required|boolean']);
        if ($validator->fails()) {
            return Responses::sendError($validator->errors(), 'Validasi Gagal');
        }

        $disposisi = $request->input('disposisi');

        if ($disposisi) {
            $signValidator = app('validator')->make($request->all(), [
                'diajukan_oleh' => 'required',
            ], [
                'diajukan_oleh.required' => 'Nama Diajukan Oleh (Manager Dept.) wajib diisi sebelum PO bisa diterbitkan',
            ]);
            if ($signValidator->fails()) {
                return Responses::sendError($signValidator->errors(), 'Validasi Gagal');
            }
        }

        $spb = Spb::with('items.conditions')->find($id);
        if (!$spb) {
            return Responses::sendError([], 'SPB Not Found');
        }
        if ($spb->status != 'Permintaan Pengadaan') {
            return Responses::sendError([], 'SPB harus berstatus Permintaan Pengadaan untuk disposisi');
        }

        $groups    = [];

        if ($disposisi) {
            // Pastikan SEMUA barang sudah punya vendor terpilih
            foreach ($spb->items as $item) {
                $hasSelected = $item->conditions->contains(function ($c) {
                    return $c->selected;
                });
                if (!$hasSelected) {
                    return Responses::sendError([], 'Barang "' . $item->material_name . '" belum punya vendor terpilih. Pilih vendor untuk semua barang terlebih dahulu.');
                }
            }

            // Kelompokkan barang berdasarkan vendor pemenang masing-masing
            foreach ($spb->items as $item) {
                $selected = $item->conditions->firstWhere('selected', true);
                $vendorId = $selected->vendor_id ?: 0;
                if (!isset($groups[$vendorId])) {
                    $groups[$vendorId] = [
                        'vendor_id' => $selected->vendor_id,
                        'supplier'  => $selected->supplier,
                        'items'     => [],
                        'total'     => 0,
                    ];
                }
                $groups[$vendorId]['items'][]  = $item;
                $groups[$vendorId]['total']   += ($selected->price * $item->qty);
            }

            $baseNumber = str_replace('SPPB-', 'PO-', $spb->no_spb);
            $multiple   = count($groups) > 1;
            $index      = 1;

            foreach ($groups as $group) {
                $poNumber = $multiple ? ($baseNumber . '-' . $index) : $baseNumber;

                $po = SpbPurchaseOrder::create([
                    'spb_id'        => $spb->id,
                    'vendor_id'     => $group['vendor_id'],
                    'supplier'      => $group['supplier'],
                    'diajukan_oleh' => $request->input('diajukan_oleh'),
                    'po_number'     => $poNumber,
                    'po_date'       => date('Y-m-d'),
                    'po_total'      => $group['total'],
                    'status'        => 'PO Diterbitkan',
                    'updated_by'    => $user->full_name,
                ]);

                foreach ($group['items'] as $item) {
                    $item->spb_purchase_order_id = $po->id;
                    $item->save();
                }

                $index++;
            }

            $spb->status = 'PO Diterbitkan';
        } else {
            $spb->status = 'Permintaan Pengadaan';
        }

        $spb->disposisi_by   = $user->full_name;
        $spb->disposisi_at   = Carbon::now();
        $spb->disposisi_note = $request->input('disposisi_note');
        $spb->updated_by     = $user->full_name;
        $spb->save();

        $message = $disposisi
            ? 'Vendor final terpilih untuk semua barang. ' . count($groups) . ' Purchase Order berhasil diterbitkan otomatis.'
            : 'Kembali ke Permintaan Pengadaan';
        return Responses::sendResponse($spb->load('items.conditions.vendor', 'purchaseOrders.items'), $message);
    }

    /**
     * Receive Material untuk 1 Purchase Order. Hanya Purchasing.
     */
    public function resolusiPo(Request $request, $poId)
    {
        $po = SpbPurchaseOrder::find($poId);
        if (!$po) {
            return Responses::sendError([], 'Purchase Order Not Found');
        }
        if ($po->status != 'PO Diterbitkan') {
            return Responses::sendError([], 'PO harus berstatus PO Diterbitkan untuk resolusi');
        }

        $userData = $this->get();
        $user     = $userData['user'];

        if ($user->role != 'Purchasing') {
            return Responses::sendError([], 'Hanya akun Purchasing yang bisa mencatat Receive Material');
        }

        $po->resolusi_note = $request->input('resolusi_note');
        $po->resolusi_at   = Carbon::now();
        $po->status        = 'Resolusi';
        $po->updated_by    = $user->full_name;
        $po->save();

        return Responses::sendResponse($po, 'Resolusi Berhasil Dicatat');
    }
    /**
     * Simpan nama tanda tangan SPPB (Diajukan / Ditinjau / Disetujui Oleh).
     * Dipakai sebelum SPPB bisa di-print.
     */
    public function saveSignature(Request $request, $id)
    {
        $validator = app('validator')->make($request->all(), [
            'sign_diajukan'  => 'required',
            'sign_ditinjau'  => 'required',
            'sign_disetujui' => 'required',
        ], [
            'required' => 'Nama wajib diisi',
        ]);
        if ($validator->fails()) {
            return Responses::sendError($validator->errors(), 'Validasi Gagal');
        }

        $spb = Spb::find($id);
        if (!$spb) {
            return Responses::sendError([], 'SPB Not Found');
        }

        $spb->sign_diajukan  = $request->input('sign_diajukan');
        $spb->sign_ditinjau  = $request->input('sign_ditinjau');
        $spb->sign_disetujui = $request->input('sign_disetujui');
        $spb->save();

        return Responses::sendResponse($spb, 'Nama Tanda Tangan SPPB Berhasil Disimpan');
    }

    /**
     * Simpan nama tanda tangan PO (Dibuat / Disetujui Oleh).
     * Dipakai sebelum PO bisa di-print.
     */
    public function savePoSignature(Request $request, $poId)
    {
        $validator = app('validator')->make($request->all(), [
            'sign_dibuat'    => 'required',
            'sign_disetujui' => 'required',
        ], [
            'required' => 'Nama wajib diisi',
        ]);
        if ($validator->fails()) {
            return Responses::sendError($validator->errors(), 'Validasi Gagal');
        }

        $po = SpbPurchaseOrder::find($poId);
        if (!$po) {
            return Responses::sendError([], 'Purchase Order Not Found');
        }

        $po->sign_dibuat    = $request->input('sign_dibuat');
        $po->sign_disetujui = $request->input('sign_disetujui');
        $po->save();

        return Responses::sendResponse($po, 'Nama Tanda Tangan PO Berhasil Disimpan');
    }

    /**
     * Catat Invoice untuk 1 Purchase Order. Hanya Purchasing.
     */
    public function invoicePo(Request $request, $poId)
    {
        $validator = app('validator')->make($request->all(), [
            'invoice_number' => 'required',
            'invoice_date'   => 'required',
            'invoice_amount' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            return Responses::sendError($validator->errors(), 'Validasi Gagal');
        }

        $po = SpbPurchaseOrder::find($poId);
        if (!$po) {
            return Responses::sendError([], 'Purchase Order Not Found');
        }
        if ($po->status != 'Resolusi') {
            return Responses::sendError([], 'PO harus berstatus Resolusi untuk mencatat invoice');
        }

        $userData = $this->get();
        $user     = $userData['user'];

        if ($user->role != 'Purchasing') {
            return Responses::sendError([], 'Hanya akun Purchasing yang bisa mencatat Invoice');
        }

        $po->invoice_number = $request->input('invoice_number');
        $po->invoice_date   = $request->input('invoice_date');
        $po->invoice_amount = $request->input('invoice_amount');
        $po->status         = 'Invoice';
        $po->updated_by     = $user->full_name;
        $po->save();

        return Responses::sendResponse($po, 'Invoice Berhasil Dicatat');
    }

    /**
     * Catat Payment untuk 1 Purchase Order -> PO Selesai. Hanya Purchasing.
     * Kalau SEMUA PO milik SPB ini sudah Selesai, SPB keseluruhan otomatis ikut jadi Selesai.
     */
    public function paymentPo(Request $request, $poId)
    {
        $validator = app('validator')->make($request->all(), [
            'payment_date'   => 'required',
            'payment_amount' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            return Responses::sendError($validator->errors(), 'Validasi Gagal');
        }

        $po = SpbPurchaseOrder::find($poId);
        if (!$po) {
            return Responses::sendError([], 'Purchase Order Not Found');
        }
        if ($po->status != 'Invoice') {
            return Responses::sendError([], 'PO harus berstatus Invoice untuk mencatat pembayaran');
        }

        $userData = $this->get();
        $user     = $userData['user'];

        if ($user->role != 'Purchasing') {
            return Responses::sendError([], 'Hanya akun Purchasing yang bisa mencatat Payment');
        }

        $po->payment_date   = $request->input('payment_date');
        $po->payment_amount = $request->input('payment_amount');
        $po->payment_method = $request->input('payment_method');
        $po->status         = 'Selesai';
        $po->updated_by     = $user->full_name;
        $po->save();

        $message = 'Pembayaran Berhasil Dicatat, PO Selesai';

        // Kalau semua PO di SPB ini sudah Selesai, SPB keseluruhan ikut ditandai Selesai
        $spb = Spb::find($po->spb_id);
        if ($spb) {
            $belumSelesai = $spb->purchaseOrders()->where('status', '!=', 'Selesai')->count();
            if ($belumSelesai == 0) {
                $spb->status     = 'Selesai';
                $spb->updated_by = $user->full_name;
                $spb->save();
                $message = 'Pembayaran Berhasil Dicatat. Semua PO sudah Selesai, SPB ditandai Selesai.';
            }
        }

        return Responses::sendResponse($po, $message);
    }

    /**
     * Hapus SPB. Hanya Admin.
     */
    public function destroy($id)
    {
        $userData = $this->get();
        $user     = $userData['user'];

        if ($user->role != 'Admin') {
            return Responses::sendError([], 'Hanya Admin yang bisa menghapus SPB');
        }

        $spb = Spb::find($id);
        if (!$spb) {
            return Responses::sendError([], 'SPB Not Found');
        }

        $spb->delete();

        return Responses::sendResponse([], 'SPB Berhasil Dihapus');
    }
}