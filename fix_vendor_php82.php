<?php
/**
 * fix_vendor_php82.php
 *
 * Otomatis nambahin #[\ReturnTypeWillChange] di atas semua method
 * (offsetExists, offsetGet, offsetSet, offsetUnset, count, jsonSerialize,
 * getIterator, current, key, next, rewind, valid) di dalam folder vendor,
 * supaya kompatibel dengan PHP 8.1+ tanpa perlu edit manual satu-satu.
 *
 * CARA PAKAI:
 *   1. Taruh file ini di root folder backend (sejajar dengan folder vendor)
 *   2. Jalankan: php fix_vendor_php82.php
 *   3. Selesai, tinggal coba `php artisan migrate:status` lagi
 *
 * Aman dijalankan berkali-kali (tidak akan dobel nambahin attribute
 * kalau sudah ada).
 */

$vendorDir = __DIR__ . '/vendor';

$methods = [
    'offsetExists', 'offsetGet', 'offsetSet', 'offsetUnset', // ArrayAccess
    'count',                                                  // Countable
    'jsonSerialize',                                          // JsonSerializable
    'getIterator',                                            // IteratorAggregate
    'current', 'key', 'next', 'rewind', 'valid',              // Iterator
];

$pattern = '/(?<!#\[\\\\ReturnTypeWillChange\]\n)(\s*)(public\s+function\s+(?:' . implode('|', $methods) . ')\s*\()/';

$totalFiles = 0;
$totalPatched = 0;

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($vendorDir, FilesystemIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if ($file->getExtension() !== 'php') {
        continue;
    }

    $path = $file->getPathname();
    $content = file_get_contents($path);
    if ($content === false) {
        continue;
    }

    $originalContent = $content;

    // Cari tiap baris "public function offsetGet(...)" dst yang BELUM ada
    // #[\ReturnTypeWillChange] di baris tepat sebelumnya, lalu sisipkan.
    $lines = explode("\n", $content);
    $newLines = [];
    $patchedInFile = 0;

    foreach ($lines as $i => $line) {
        $isTargetMethod = false;
        foreach ($methods as $m) {
            if (preg_match('/^\s*public\s+function\s+' . preg_quote($m, '/') . '\s*\(/', $line)) {
                $isTargetMethod = true;
                break;
            }
        }

        if ($isTargetMethod) {
            $prevLine = end($newLines);
            $alreadyHasAttribute = $prevLine !== false && strpos($prevLine, 'ReturnTypeWillChange') !== false;

            if (!$alreadyHasAttribute) {
                // ambil indentasi dari baris method-nya
                preg_match('/^(\s*)/', $line, $m);
                $indent = $m[1] ?? '';
                $newLines[] = $indent . '#[\\ReturnTypeWillChange]';
                $patchedInFile++;
            }
        }

        $newLines[] = $line;
    }

    if ($patchedInFile > 0) {
        $newContent = implode("\n", $newLines);
        file_put_contents($path, $newContent);
        $totalFiles++;
        $totalPatched += $patchedInFile;
        echo "Patched {$patchedInFile}x -> " . str_replace($vendorDir, 'vendor', $path) . "\n";
    }
}

echo "\n=== SELESAI ===\n";
echo "Total file yang dipatch : {$totalFiles}\n";
echo "Total method yang dipatch: {$totalPatched}\n";
