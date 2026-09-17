<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DB;
use Carbon\Carbon;
use App\Karyawan;
use App\StockBarang;
use App\Spb;
use App\SpbPurchaseOrder;
use App\SpbItem;
use App\Vendor;

class PrintPdfController extends Controller
{

    /**
     * Surat permintaan penawaran harga ke 1 vendor untuk 1 SPPB.
     * 1 surat berisi semua barang di SPPB itu yang diminta ke vendor tsb.
     */
    public function printRfq(Request $request)
    {
        $spbId    = $request->input('spb_id');
        $vendorId = $request->input('vendor_id');

        $spb    = Spb::find($spbId);
        $vendor = Vendor::find($vendorId);

        if (!$spb || !$vendor) {
            abort(404, 'SPB atau Vendor Not Found');
        }

        $items = SpbItem::where('spb_id', $spb->id)
                    ->whereHas('requestedVendors', function ($q) use ($vendorId) {
                        $q->where('vendor_id', $vendorId);
                    })->get();

        if ($items->count() < 1) {
            abort(404, 'Vendor ini belum diminta penawaran untuk barang di SPPB ini');
        }

        return view('pdf.rfq', compact('spb', 'vendor', 'items'));
    }

    public function printSppb($id)
    {
        $spb = Spb::with('items')->find($id);

        if (!$spb) {
            abort(404, 'SPPB Not Found');
        }

        foreach ($spb->items as $item) {
            $stock = StockBarang::where('material_code', $item->material_code)->first();
            $item->actual_stock = $stock ? $stock->stock_barang : null;
            $item->min_stock    = $stock ? $stock->min_stock : null;
        }

        // Format nomor ringkas buat preview (SPPB-0023), ambil segmen angka urut
        // paling belakang dari no_spb asli (SPPB-20260908-0023) tanpa mengubah data aslinya.
        $lastDash    = strrpos($spb->no_spb, '-');
        $shortNumber = $lastDash !== false ? substr($spb->no_spb, $lastDash + 1) : $spb->no_spb;
        $noSpbShort  = 'SPPB-' . $shortNumber;

        return view('pdf.sppb', compact('spb', 'noSpbShort'));
    }

    public function printPo($id)
    {
        $po = SpbPurchaseOrder::with('items', 'vendor', 'spb')->find($id);

        if (!$po) {
            abort(404, 'Purchase Order Not Found');
        }

        if (!$po->sign_dibuat || !$po->sign_disetujui) {
            abort(403, 'Nama penandatangan PO (Dibuat/Diajukan/Disetujui Oleh) belum diisi. Isi dulu sebelum bisa preview.');
        }

        // Total sebelum diskon (jumlah harga semua barang)
        $subtotal = 0;
        foreach ($po->items as $item) {
            $condition = $item->conditions()->where('selected', true)->first();
            $item->unit_price = $condition ? $condition->price : 0;
            $item->line_total = $item->unit_price * $item->qty;
            $subtotal += $item->line_total;
        }

        // Ikuti persis formula di template Excel PO perusahaan:
        // Total = Jumlah - Discount
        // DPP Lain = Total x (11/12)
        // PPN 12% = DPP Lain x 12%
        // Grand Total = Total + PPN
        // PPh persentase diisi manual oleh Purchasing (nilainya ditampilkan di recap, tidak mengubah Grand Total).
        $discountPercent = $po->discount_percent ?? 0;
        $discount         = $subtotal * ($discountPercent / 100);
        $total            = $subtotal - $discount;
        $dppLain          = $total * (11 / 12);

        $ppnPercent       = $po->ppn_percent ?? 12;
        $ppn              = $dppLain * ($ppnPercent / 100);
        $grandTotal       = $total + $ppn;

        $pphPercent       = $po->pph_percent ?? 0;
        $pph              = $dppLain * ($pphPercent / 100);

        return view('pdf.po', compact('po', 'subtotal', 'discountPercent', 'discount', 'total', 'dppLain', 'ppn', 'ppnPercent', 'grandTotal', 'pphPercent', 'pph'));
    }

    public function printSuratBarangKeluar(Request $request)
    {
        $data = DB::table('barang_keluar')
                ->leftJoin('stock_barang', 'barang_keluar.material_code', '=', 'stock_barang.material_code')
                ->select('barang_keluar.id','barang_keluar.material_code','material_name','unit','stock_barang','qty','divisi','description','date','diserahkan','disetujui','diterima','barang_keluar.created_by','barang_keluar.created_at')
                ->where('barang_keluar.no_sj', $request->no_sj)
                ->get();

        return view('pdf.suratBarangKeluar', compact('data'));
    }

    public function printStockQRCode(Request $request)
    {
        $master = StockBarang::withCount(['totalBarangMasuk' => function($query) {
                        $query->select(DB::raw('SUM(qty)'));
                    },
                    'totalBarangKeluar' => function($query) {
                        $query->select(DB::raw('SUM(qty)'));
                    }]);
        if(!empty($request->input('material_code'))){
            $result = $master->where('material_code', $request->material_code);
        }
        if(!empty($request->input('material_name'))){
            $result = $master->where('material_name', 'LIKE', "%".$request->material_name."%");
        }
        if(!empty($request->input('type'))){
            $result = $master->where('type', 'LIKE', "%".$request->type."%");
        }
        if(!empty($request->input('unit'))){
            $result = $master->where('unit', 'LIKE', "%".$request->unit."%");
        }
        if(!empty($request->input('storage_location'))){
            $result = $master->where('storage_location', 'LIKE', "%".$request->storage_location."%");
        }
        
        if (empty($request->input('material_code')) && empty($request->input('material_name')) && empty($request->input('type')) && empty($request->input('unit')) && empty($request->input('storage_location')) ) {
            $result = $master->orderBy('material_name', 'ASC')->get();
        }else{
            $result = $master->orderBy('material_name', 'ASC')->get();
        }

        $data  = $result;

        return view('pdf.printStockQRCode', compact('data'));
    }

}