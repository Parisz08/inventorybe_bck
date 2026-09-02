<?php
// Taruh DI DALAM folder backend: C:\xampp\htdocs\inventorybe_bck\testzip2.php
// Buka: http://localhost/inventorybe_bck/testzip2.php

$app = require __DIR__ . '/bootstrap/app.php';
$app->boot();

echo "PHP Version: " . phpversion() . "<br>";
echo "App booted OK<br>";

try {
    $request = new \Illuminate\Http\Request();
    $export = new \App\Exports\StockBarangExport($request);
    $response = \Maatwebsite\Excel\Facades\Excel::download($export, 'test.xlsx');
    echo "<b style='color:green'>SUKSES! Tidak ada error saat generate Excel.</b>";
} catch (\Throwable $e) {
    echo "<b style='color:red'>ERROR: " . get_class($e) . "</b><br>";
    echo "Message: " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "File: " . $e->getFile() . " line " . $e->getLine() . "<br>";
}
