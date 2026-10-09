<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DB;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\BarangMasukExport;
use App\Exports\BarangKeluarExport;
use App\Exports\StockBarangExport;
use App\Exports\VendorExport;

class ExportExcelController extends Controller
{
    
    public function exportBarangMasuk(Request $request)
    {
        set_time_limit(300);
        return Excel::download(new BarangMasukExport($request), 'Data Barang Masuk.xlsx');
    }

    public function exportBarangKeluar(Request $request)
    {
        set_time_limit(300);
        return Excel::download(new BarangKeluarExport($request), 'Data Barang Keluar.xlsx');
    }

    public function exportStockBarang(Request $request)
    {
        set_time_limit(300);
        return Excel::download(new StockBarangExport($request), 'Data Stock Barang.xlsx');
    }

    public function exportVendor(Request $request)
    {
        set_time_limit(300);
        return Excel::download(new VendorExport($request), 'Data Vendor.xlsx');
    }

}