<?php
/**
 * fix_vendor_php82.php
 *
 * Otomatis nambahin #[\ReturnTypeWillChange] di atas semua method
 * (offsetExists, offsetGet, offsetSet, offsetUnset, count, jsonSerialize,
 * getIterator, current, key, next, rewind, valid) di dalam folder vendor,
 * dan nambahin @ sebelum pemanggilan ->getClass() yang deprecated,
 * supaya kompatibel dengan PHP 8.1+ tanpa perlu edit manual satu-satu.
 *
 * CARA PAKAI:
 *   1. Taruh file ini di root folder backend (sejajar dengan folder vendor)
 *   2. Jalankan: php fix_vendor_php82.php
 *   3. Selesai, tinggal coba `php artisan migrate:status` lagi
 *
 * Aman dijalankan berkali-kali (tidak akan dobel patch kalau sudah ada).
 */

$vendorDir = __DIR__ . '/vendor';

$methods = [
    'offsetExists', 'offsetGet', 'offsetSet', 'offsetUnset', // ArrayAccess
    'count',                                                  // Countable
    'jsonSerialize',                                          // JsonSerializable
    'getIterator',                                            // IteratorAggregate
    'current', 'key', 'next', 'rewind', 'valid',              // Iterator
];

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
echo "Total file yang dipatch (ReturnTypeWillChange): {$totalFiles}\n";
echo "Total method yang dipatch: {$totalPatched}\n";

// =========================================================
// TAHAP 2: suppress deprecation dari ReflectionParameter::getClass()
// dengan menambahkan operator @ di depan pemanggilannya.
// =========================================================
$totalFiles2 = 0;
$totalPatched2 = 0;

$iterator2 = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($vendorDir, FilesystemIterator::SKIP_DOTS)
);

foreach ($iterator2 as $file) {
    if ($file->getExtension() !== 'php') {
        continue;
    }
    $path = $file->getPathname();
    $content = file_get_contents($path);
    if ($content === false || strpos($content, '->getClass()') === false) {
        continue;
    }

    $newContent = preg_replace_callback(
        '/(?<!@)((?:\$[a-zA-Z_][a-zA-Z0-9_]*|\)|\])->getClass\(\))/',
        function ($m) {
            return '@' . $m[1];
        },
        $content,
        -1,
        $count
    );

    if ($count > 0 && $newContent !== $content) {
        file_put_contents($path, $newContent);
        $totalFiles2++;
        $totalPatched2 += $count;
        echo "Patched {$count}x (getClass) -> " . str_replace($vendorDir, 'vendor', $path) . "\n";
    }
}

echo "\n=== TAHAP 2 SELESAI ===\n";
echo "Total file yang dipatch (getClass): {$totalFiles2}\n";
echo "Total pemanggilan yang dipatch: {$totalPatched2}\n";