<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return view('email/mailNotifLembur');
});


// ================================= FOR NO LOGIN ==========================================================
    // TEST
    $router->get('test', 'TestController@index');

    // LOGIN
    $router->post('/auth/login', 'AuthController@login');
    $router->get('/auth/logout', 'AuthController@logout');
    $router->get('create_akun_default', 'AkunController@create_akun_default');

    // EXPORT EXCEL
    $router->get('export-excel/barang-masuk', 'ExportExcelController@exportBarangMasuk');
    $router->get('export-excel/barang-keluar', 'ExportExcelController@exportBarangKeluar');
    $router->get('export-excel/stock-barang', 'ExportExcelController@exportStockBarang');
    // PDF
    $router->get('print-pdf/surat-barang-keluar', 'PrintPdfController@printSuratBarangKeluar');
    $router->get('print-pdf/stock-barang-qr-code', 'PrintPdfController@printStockQRCode');
    $router->get('print-pdf/sppb/{id}', 'PrintPdfController@printSppb');
    $router->get('print-pdf/po/{id}', 'PrintPdfController@printPo');
    $router->get('print-pdf/rfq', 'PrintPdfController@printRfq');
    // $router->get('print-pdf/spl', 'PrintPdfController@printSpl');

    $router->get('barang-masuk/cek-material', 'BarangMasukController@cekMaterial');
// ================================= FOR LOGIN ==========================================================
$router->group(['middleware' => 'jwt.tymon'], function () use ($router){
    $router->get('spb/index', 'SpbController@index');
$router->get('spb/show/{id}', 'SpbController@show');
$router->post('spb/create', 'SpbController@store');
$router->post('spb/approve/{id}', 'SpbController@approve');
$router->post('spb/item-condition/{itemId}', 'SpbController@addItemCondition');
$router->post('spb/item-condition/select/{conditionId}', 'SpbController@selectItemCondition');
$router->post('spb/item-request-vendor/{itemId}', 'SpbController@requestVendor');
$router->post('spb/item-request-vendor/remove/{requestedVendorId}', 'SpbController@unrequestVendor');
$router->post('spb/lanjut-penawaran/{id}', 'SpbController@lanjutPenawaran');
$router->post('spb/disposisi/{id}', 'SpbController@disposisi');
$router->post('spb/save-signature/{id}', 'SpbController@saveSignature');
$router->post('spb/purchase-order/save-signature/{poId}', 'SpbController@savePoSignature');
$router->post('spb/po/resolusi/{poId}', 'SpbController@resolusiPo');
$router->post('spb/po/invoice/{poId}', 'SpbController@invoicePo');
$router->post('spb/po/payment/{poId}', 'SpbController@paymentPo');
$router->post('spb/delete/{id}', 'SpbController@destroy');

// VENDOR
$router->get('vendor/index', 'VendorController@index');

    // DASHBOARD
    $router->get('dashboard/index', 'DashboardController@index');
    $router->get('dashboard/barang-min-stock', 'DashboardController@barangMinStock');
    $router->get('dashboard/barang-sering-terpakai', 'DashboardController@barangSeringTerpakai');
    // BARANG MASUK
    $router->get('barang-masuk/index', 'BarangMasukController@index');
    $router->get('barang-masuk/show/{id}', 'BarangMasukController@show');
    $router->post('barang-masuk/create', 'BarangMasukController@store');
    $router->post('barang-masuk/update/{id}', 'BarangMasukController@update');
    $router->post('barang-masuk/duplicate', 'BarangMasukController@duplicate');
    $router->post('barang-masuk/delete/{id}', 'BarangMasukController@destroy');
    // BARANG KELUAR
    $router->get('barang-keluar/index', 'BarangKeluarController@index');
    $router->get('barang-keluar/search-material', 'BarangKeluarController@searchMaterial');
    $router->get('barang-keluar/get-material', 'BarangKeluarController@getMaterial');
    $router->post('barang-keluar/create', 'BarangKeluarController@store');
    $router->post('barang-keluar/update/{id}', 'BarangKeluarController@update');
    $router->post('barang-keluar/update-description/{id}', 'BarangKeluarController@updateDesc');
    $router->post('barang-keluar/delete/{id}', 'BarangKeluarController@destroy');
    // STOCK BARANG
    $router->get('stock-barang/index', 'StockBarangController@index');
    $router->get('stock-barang/show/{id}', 'StockBarangController@show');
    $router->post('stock-barang/create', 'StockBarangController@store');
    $router->post('stock-barang/update/{id}', 'StockBarangController@update');
    $router->post('stock-barang/delete/{id}', 'StockBarangController@destroy');
    // AKUN
    $router->get('akun/index', 'AkunController@index');
    $router->get('akun/index-profile', 'AkunController@indexProfile');
    $router->get('akun/show/{id}', 'AkunController@show');
    $router->post('akun/create', 'AkunController@store');
    $router->post('akun/update/{id}', 'AkunController@update');
    $router->post('akun/delete/{id}', 'AkunController@destroy');
    $router->get('akun/create_akun_default', 'AkunController@create_akun_default');
    // IMPORT
    $router->post('import/import-data-karyawan', 'ImportController@importKaryawan');
    $router->post('import/setting-approval', 'ImportController@importSettingApproval');
    $router->post('import/import-data-users', 'ImportController@importUsers');

});