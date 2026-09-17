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

    /**
     * Ambil nomor urut berikutnya untuk sebuah key (misal 'spb-20260907') secara atomik,
     * aman dari race condition walau 2 request masuk bersamaan. Row di tabel
     * sequence_counters otomatis ter-lock oleh MySQL selama UPDATE berjalan.
     */
    private function nextSequenceNumber($key)
    {
        \Illuminate\Support\Facades\DB::statement(
            'INSERT INTO sequence_counters (seq_key, counter, created_at, updated_at) VALUES (?, 1, NOW(), NOW())
             ON DUPLICATE KEY UPDATE counter = LAST_INSERT_ID(counter + 1), updated_at = NOW()',
            [$key]
        );

        return (int) \Illuminate\Support\Facades\DB::getPdo()->lastInsertId();
    }

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

            // Riwayat harga terakhir per vendor untuk material yang sama (dari SPB manapun,
            // termasuk yang ini sendiri), supaya Purchasing bisa auto-isi harga kalau vendor
            // yang sama pernah kasih penawaran untuk barang yang sama sebelumnya.
            $lastPrices = [];
            if ($item->material_code) {
                $vendorIds = $item->requestedVendors->pluck('vendor_id')->filter()->unique()->values();
                if ($vendorIds->count()) {
                    $histories = SpbItemCondition::whereIn('vendor_id', $vendorIds)
                        ->whereHas('item', function ($q) use ($item) {
                            $q->where('material_code', $item->material_code);
                        })
                        ->orderBy('created_at', 'desc')
                        ->get(['vendor_id', 'price']);
                    foreach ($histories as $h) {
                        if (!array_key_exists($h->vendor_id, $lastPrices)) {
                            $lastPrices[$h->vendor_id] = $h->price;
                        }
                    }
                }
            }
            $item->last_prices = $lastPrices;
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

        // Nomor SPPB digenerate atomik lewat tabel sequence_counters, jadi aman
        // walau ada 2 request masuk bersamaan (tidak akan pernah ketabrak).
        $seqNumber = $this->nextSequenceNumber('spb-' . date('Ymd'));
        $noSpb     = 'SPPB-' . date('Ymd') . '-' . str_pad($seqNumber, 4, '0', STR_PAD_LEFT);

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

        // Simpan needed_date lewat query UPDATE terpisah sebagai jaring pengaman,
        // supaya nilainya tetap kesimpan meski karena sebab apapun kolom ini gagal
        // ikut ter-insert bareng kolom lain di query create() di atas.
        if ($request->filled('needed_date')) {
            \Illuminate\Support\Facades\DB::table('spb')
                ->where('id', $spb->id)
                ->update(['needed_date' => $request->input('needed_date')]);
            $spb->refresh();
        }

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
 
        $validator = app('validator')->make($request->all(), ['vendor_name' => 'required']);
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
 
        $vendorName = trim($request->input('vendor_name'));
 
        // Cari vendor yang sudah ada di master data (case-insensitive), kalau tidak ada, buat baru otomatis.
        $vendor = Vendor::whereRaw('LOWER(name) = ?', [strtolower($vendorName)])->first();
        if (!$vendor) {
            $vendor = Vendor::create([
                'name' => $vendorName,
            ]);
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
     * Edit harga/catatan penawaran vendor yang SUDAH ADA (bukan tambah baru).
     * Hanya Purchasing, hanya selama SPB masih berstatus "Permintaan Pengadaan".
     * Dipakai kalau salah input harga sebelumnya.
     */
    public function updateItemCondition(Request $request, $conditionId)
    {
        $validator = app('validator')->make($request->all(), [
            'price' => 'required|numeric|min:0',
        ]);
        if ($validator->fails()) {
            return Responses::sendError($validator->errors(), 'Validasi Gagal');
        }

        $userData = $this->get();
        $user     = $userData['user'];

        if ($user->role != 'Purchasing') {
            return Responses::sendError([], 'Hanya Purchasing yang bisa mengubah penawaran vendor');
        }

        $condition = SpbItemCondition::find($conditionId);
        if (!$condition) {
            return Responses::sendError([], 'Penawaran Vendor Not Found');
        }

        $item = SpbItem::find($condition->spb_item_id);
        $spb  = $item ? Spb::find($item->spb_id) : null;
        if (!$spb || $spb->status != 'Permintaan Pengadaan') {
            return Responses::sendError([], 'SPB harus berstatus Permintaan Pengadaan untuk mengubah penawaran');
        }

        $condition->price          = $request->input('price');
        $condition->condition_note = $request->input('condition_note');
        $condition->save();

        return Responses::sendResponse($condition->load('vendor'), 'Penawaran Vendor Berhasil Diubah');
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

            // Ambil PO yang sudah ada untuk SPB ini yang statusnya masih "PO Diterbitkan" (belum
            // diproses lebih lanjut / belum ada Receipt-Invoice-Payment). Ini dipakai supaya kalau
            // Purchasing sempat Mundur Tahap lalu konfirmasi ulang, PO yang sama diUPDATE, bukan
            // dibuatkan PO baru yang menumpuk jadi dobel.
            $existingOpenPos = SpbPurchaseOrder::where('spb_id', $spb->id)
                ->where('status', 'PO Diterbitkan')
                ->get()
                ->keyBy('vendor_id');

            $usedPoIds = [];

            foreach ($groups as $group) {
                $existingPo = $existingOpenPos->get($group['vendor_id']);

                if ($existingPo) {
                    // Vendor ini sudah punya PO dari konfirmasi sebelumnya (dan belum diproses) -> update saja
                    $existingPo->supplier   = $group['supplier'];
                    $existingPo->po_total   = $group['total'];
                    $existingPo->status     = 'PO Diterbitkan';
                    $existingPo->updated_by = $user->full_name;
                    $existingPo->save();
                    $po = $existingPo;
                } else {
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

                    $index++;
                }

                foreach ($group['items'] as $item) {
                    $item->spb_purchase_order_id = $po->id;
                    $item->save();
                }

                $usedPoIds[] = $po->id;
            }

            // PO lama yang statusnya masih "PO Diterbitkan" tapi vendornya sudah tidak dipilih lagi
            // di konfirmasi ini (belum ada progress apa pun) dianggap draft basi -> dibuang supaya
            // tidak menumpuk sebagai PO kosong/ganda. PO yang sudah lanjut ke Resolusi/Invoice/Selesai
            // TIDAK disentuh sama sekali.
            SpbPurchaseOrder::where('spb_id', $spb->id)
                ->where('status', 'PO Diterbitkan')
                ->whereNotIn('id', $usedPoIds)
                ->delete();

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
     * Mundur 1 tahap ke status sebelumnya, KHUSUS di ranah Purchasing.
     * Tidak menghapus data apa pun (barang, penawaran vendor, kondisi harga,
     * maupun PO yang sudah terlanjur diterbitkan) — hanya mengembalikan status
     * SPB supaya Purchasing bisa mengulang/mengoreksi tahap sebelumnya.
     * Tidak berlaku untuk status di ranah Admin (Menunggu Approval / Ditolak)
     * maupun status akhir (Selesai).
     */
    public function mundur($id)
    {
        $userData = $this->get();
        $user     = $userData['user'];

        if ($user->role != 'Purchasing') {
            return Responses::sendError([], 'Hanya Purchasing yang bisa memundurkan tahap SPB');
        }

        $spb = Spb::with('purchaseOrders')->find($id);
        if (!$spb) {
            return Responses::sendError([], 'SPB Not Found');
        }

        $previousStatus = [
            'Permintaan Pengadaan' => 'Permintaan Vendor',
            'PO Diterbitkan'       => 'Permintaan Pengadaan',
        ];

        if (!isset($previousStatus[$spb->status])) {
            return Responses::sendError([], 'Tahap SPB saat ini tidak bisa dimundurkan');
        }

        // Kalau mau mundur dari "PO Diterbitkan" ke "Permintaan Pengadaan" (Finalisasi Vendor,
        // misal karena harga/vendor yang dipilih salah), pastikan belum ada PO yang sudah
        // progress lebih jauh (Resolusi/Invoice/Selesai). Kalau dibiarkan, SPB bisa balik ke
        // tahap pilih vendor padahal ada PO yang barangnya sudah diterima/dibayar.
        if ($spb->status === 'PO Diterbitkan') {
            $hasProgressed = $spb->purchaseOrders->contains(function ($po) {
                return in_array($po->status, ['Resolusi', 'Invoice', 'Selesai']);
            });
            if ($hasProgressed) {
                return Responses::sendError([], 'Tidak bisa mundur ke Finalisasi Vendor karena sudah ada PO yang masuk tahap Resolusi/Invoice/Selesai. Mundurkan PO tersebut dulu (lewat tombol Mundur yang sama) sampai balik ke PO Diterbitkan.');
            }

            // Semua PO yang masih di "PO Diterbitkan" (belum ada Receipt/Invoice/Payment
            // sama sekali) dianggap draft yang batal terbit begitu SPB mundur ke Finalisasi
            // Vendor -> dihapus, supaya SPB beneran balik ke kondisi "belum ada PO" dan
            // gak nyangkut kayak dokumen resmi padahal SPB-nya bilang belum final.
            foreach ($spb->purchaseOrders as $po) {
                if ($po->status === 'PO Diterbitkan') {
                    SpbItem::where('spb_purchase_order_id', $po->id)->update(['spb_purchase_order_id' => null]);
                    $po->delete();
                }
            }
        }

        $spb->status     = $previousStatus[$spb->status];
        $spb->updated_by = $user->full_name;
        $spb->save();

        return Responses::sendResponse(
            $spb->load('items.conditions.vendor', 'items.requestedVendors.vendor', 'purchaseOrders.items'),
            'SPB Berhasil Dimundurkan ke Tahap ' . $spb->status
        );
    }

    /**
     * Mundur 1 tahap KHUSUS untuk status internal 1 Purchase Order (Resolusi/Invoice/Selesai),
     * dipakai kalau Purchasing salah catat Receipt/Invoice/Payment dan perlu koreksi.
     * Tidak menghapus data (resolusi_note, invoice_*, payment_* yang sudah terlanjur
     * diisi tetap ada, cuma status PO yang mundur supaya formnya bisa diisi ulang).
     * Tidak berlaku untuk status "PO Diterbitkan" (itu levelnya SPB, pakai endpoint mundur() di atas).
     */
    public function mundurPo($poId)
    {
        $userData = $this->get();
        $user     = $userData['user'];

        if ($user->role != 'Purchasing') {
            return Responses::sendError([], 'Hanya Purchasing yang bisa memundurkan tahap PO');
        }

        $po = SpbPurchaseOrder::find($poId);
        if (!$po) {
            return Responses::sendError([], 'Purchase Order Not Found');
        }

        $previousStatus = [
            'Resolusi' => 'PO Diterbitkan',
            'Invoice'  => 'Resolusi',
            'Selesai'  => 'Invoice',
        ];

        if (!isset($previousStatus[$po->status])) {
            return Responses::sendError([], 'Tahap PO saat ini tidak bisa dimundurkan');
        }

        $wasSelesai      = $po->status === 'Selesai';
        $po->status      = $previousStatus[$po->status];
        $po->updated_by  = $user->full_name;
        $po->save();

        // Kalau PO ini yang bikin SPB keseluruhan ikut ditandai "Selesai", dan sekarang
        // dimundurkan, SPB juga harus balik ke "PO Diterbitkan" (belum semua PO kelar lagi).
        if ($wasSelesai) {
            $spb = Spb::find($po->spb_id);
            if ($spb && $spb->status === 'Selesai') {
                $spb->status     = 'PO Diterbitkan';
                $spb->updated_by = $user->full_name;
                $spb->save();
            }
        }

        return Responses::sendResponse($po, 'PO Berhasil Dimundurkan ke Tahap ' . $po->status);
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
     * Simpan persentase Discount & PPh untuk 1 Purchase Order. Hanya Purchasing.
     * Dipakai di recap kecil (PO No / Up / Discount / PPN / PPh) pada preview PO.
     */
    public function updatePoTax(Request $request, $poId)
    {
        // Kalau field angka dikirim kosong ("" bukan beneran null), Laravel nganggep itu
        // "ada isinya" jadi tetap kena validasi numeric dan gagal. Diseragamkan dulu jadi
        // null supaya aturan "nullable" beneran jalan.
        $numericFields = ['discount_percent', 'ppn_percent', 'pph_percent'];
        $normalized    = [];
        foreach ($numericFields as $field) {
            $value = $request->input($field);
            $normalized[$field] = ($value === '' || $value === null) ? null : $value;
        }
        $request->merge($normalized);

        $validator = app('validator')->make($request->all(), [
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'ppn_percent'      => 'nullable|numeric|min:0|max:100',
            'pph_percent'      => 'nullable|numeric|min:0|max:100',
            'up_name'          => 'nullable|string|max:255',
            'no_sppb_manual'   => 'nullable|string|max:255',
        ]);
        if ($validator->fails()) {
            return Responses::sendError($validator->errors(), 'Validasi Gagal');
        }

        $userData = $this->get();
        $user     = $userData['user'];

        if ($user->role != 'Purchasing') {
            return Responses::sendError([], 'Hanya Purchasing yang bisa mengubah Discount/PPN/PPh PO');
        }

        $po = SpbPurchaseOrder::find($poId);
        if (!$po) {
            return Responses::sendError([], 'Purchase Order Not Found');
        }

        // Discount/PPN/PPh mencerminkan syarat yang sudah dicetak & dikirim ke vendor.
        // Begitu PO lewat dari "PO Diterbitkan" (sudah masuk Resolusi/Invoice/Selesai),
        // nilainya dikunci supaya dokumen yang sudah beredar tidak jadi tidak sinkron.
        if ($po->status !== 'PO Diterbitkan') {
            return Responses::sendError([], 'Discount, PPN & PPh tidak bisa diubah lagi setelah PO masuk tahap Resolusi/Invoice/Payment');
        }

        $po->discount_percent = $request->input('discount_percent');
        $po->ppn_percent      = $request->input('ppn_percent');
        $po->pph_percent      = $request->input('pph_percent');
        $po->up_name          = $request->input('up_name');
        $po->no_sppb_manual   = $request->input('no_sppb_manual');
        $po->tax_updated_by   = $user->full_name;
        $po->tax_updated_at   = \Carbon\Carbon::now();
        $po->updated_by       = $user->full_name;
        $po->save();

        return Responses::sendResponse($po, 'Discount, PPN, PPh & Info PO Berhasil Disimpan');
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