<?php

namespace App\Http\Controllers;

use App\Vendor;
use App\Http\Library\Responses;
use App\Http\Traits\LoggedUser;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    use LoggedUser;

    public function index()
    {
        $data = Vendor::orderBy('name', 'asc')->get();
        return Responses::sendResponse($data, 'Vendor Retrieved Successfully');
    }

    public function show($id)
    {
        $data = Vendor::find($id);
        if (!$data) {
            return Responses::sendError([], 'Vendor Not Found');
        }
        return Responses::sendResponse($data, 'Vendor Detail Retrieved Successfully');
    }

    public function store(Request $request)
    {
        $validator = app('validator')->make($request->all(), [
            'name' => 'required|string|max:255',
        ]);
        if ($validator->fails()) {
            return Responses::sendError($validator->errors(), 'Validasi Gagal');
        }

        $name = trim($request->input('name'));

        // Jaga-jaga nama vendor yang sama (case-insensitive) supaya gak dobel dengan
        // vendor yang mungkin sudah otomatis kesimpan lewat alur "Permintaan Vendor" di
        // SPPB (requestVendor() di SpbController pakai pengecekan yang sama).
        $exists = Vendor::whereRaw('LOWER(name) = ?', [strtolower($name)])->first();
        if ($exists) {
            return Responses::sendError([], 'Vendor dengan nama ini sudah ada di Master Data');
        }

        $vendor = Vendor::create([
            'name'         => $name,
            'product'      => $request->input('product'),
            'pic'          => $request->input('pic'),
            'phone'        => $request->input('phone'),
            'payment_term' => $request->input('payment_term'),
            'email'        => $request->input('email'),
            'address'      => $request->input('address'),
        ]);

        return Responses::sendResponse($vendor, 'Vendor Berhasil Ditambahkan');
    }

    public function update(Request $request, $id)
    {
        $validator = app('validator')->make($request->all(), [
            'name' => 'required|string|max:255',
        ]);
        if ($validator->fails()) {
            return Responses::sendError($validator->errors(), 'Validasi Gagal');
        }

        $vendor = Vendor::find($id);
        if (!$vendor) {
            return Responses::sendError([], 'Vendor Not Found');
        }

        $name = trim($request->input('name'));

        $exists = Vendor::whereRaw('LOWER(name) = ?', [strtolower($name)])
            ->where('id', '!=', $vendor->id)
            ->first();
        if ($exists) {
            return Responses::sendError([], 'Vendor dengan nama ini sudah ada di Master Data');
        }

        $vendor->name         = $name;
        $vendor->product      = $request->input('product');
        $vendor->pic          = $request->input('pic');
        $vendor->phone        = $request->input('phone');
        $vendor->payment_term = $request->input('payment_term');
        $vendor->email        = $request->input('email');
        $vendor->address      = $request->input('address');
        $vendor->save();

        return Responses::sendResponse($vendor, 'Vendor Berhasil Diubah');
    }

    public function destroy($id)
    {
        $vendor = Vendor::find($id);
        if (!$vendor) {
            return Responses::sendError([], 'Vendor Not Found');
        }

        // Kalau vendor ini sudah pernah dipakai di SPPB (diminta penawaran/jadi vendor
        // terpilih/ada di PO), jangan dihapus permanen — bisa bikin riwayat SPPB lama jadi
        // rusak referensinya. Cukup kasih tahu, jangan dihapus.
        $used = \Illuminate\Support\Facades\DB::table('spb_item_requested_vendors')->where('vendor_id', $id)->exists()
            || \Illuminate\Support\Facades\DB::table('spb_item_conditions')->where('vendor_id', $id)->exists()
            || \Illuminate\Support\Facades\DB::table('spb_purchase_orders')->where('vendor_id', $id)->exists();

        if ($used) {
            return Responses::sendError([], 'Vendor ini sudah pernah dipakai di riwayat SPPB, tidak bisa dihapus. Bisa diedit datanya kalau perlu.');
        }

        $vendor->delete();

        return Responses::sendResponse([], 'Vendor Berhasil Dihapus');
    }
}