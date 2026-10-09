<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use App\Vendor;

/**
 * Export Master Data > Vendor, formatnya mengikuti file "List Nama Supplier" (sheet VENDOR):
 * judul 2 baris, header kuning, garis di semua sel, email berupa link, header dibekukan.
 *
 * Hasilnya mengikuti pencarian + filter yang sedang aktif di halaman Master Data, jadi yang
 * diexport = yang sedang tampil di layar:
 *   ?search=        kata kunci di nama, produk, PIC, telepon, email, termin bayar, alamat
 *   ?payment_term=  termin bayar yang mengandung teks ini ("__kosong__" = yang belum diisi)
 *   ?product=       produk/barang yang mengandung teks ini ("__kosong__" = yang belum diisi)
 *   ?completeness=  lengkap | tanpa_telepon | tanpa_email | tanpa_pic | tanpa_alamat
 */
class VendorExport implements FromArray, WithEvents
{
    const TITLE_ROW      = 1;
    const HEADER_ROW     = 2;
    const FIRST_DATA_ROW = 3;

    // Penanda "belum diisi" (sama dengan VENDOR_EMPTY di StockBarang.vue)
    const EMPTY_FILTER = '__kosong__';

    private $search;
    private $paymentTerm;
    private $product;
    private $completeness;
    private $vendors = null;

    // Lebar kolom A..I (sama dengan file contoh)
    private $widths = [
        'A' => 5.9, 'B' => 42.3, 'C' => 29.1, 'D' => 17.0, 'E' => 28.7,
        'F' => 16.0, 'G' => 10.0, 'H' => 36.1, 'I' => 47.7,
    ];

    public function __construct($request)
    {
        $this->search       = trim((string) $request->input('search', ''));
        $this->paymentTerm  = trim((string) $request->input('payment_term', ''));
        $this->product      = trim((string) $request->input('product', ''));
        $this->completeness = trim((string) $request->input('completeness', ''));
    }

    /** Kolom kosong = NULL atau cuma spasi. ($column selalu dari daftar tetap, bukan dari input user.) */
    private function whereEmpty($query, $column)
    {
        $query->where(function ($q) use ($column) {
            $q->whereNull($column)->orWhereRaw('TRIM(' . $column . ") = ''");
        });
    }

    private function whereFilled($query, $column)
    {
        $query->whereNotNull($column)->whereRaw('TRIM(' . $column . ") <> ''");
    }

    /**
     * Sama dengan filter di layar: "__kosong__" = yang belum diisi, selain itu cocok kalau isinya
     * MENGANDUNG teks yang diketik (besar/kecil huruf diabaikan).
     */
    private function whereContainsOrEmpty($query, $column, $value)
    {
        if ($value === self::EMPTY_FILTER) {
            $this->whereEmpty($query, $column);
        } else {
            $keyword = '%' . addcslashes(mb_strtolower($value), '%_\\') . '%';
            $query->whereRaw('LOWER(' . $column . ') LIKE ?', [$keyword]);
        }
    }

    private function getVendors()
    {
        if ($this->vendors === null) {
            $query = Vendor::orderBy('name', 'asc');

            if ($this->search !== '') {
                $keyword = '%' . addcslashes($this->search, '%_\\') . '%';
                $columns = ['name', 'product', 'pic', 'phone', 'email', 'payment_term', 'address'];
                $query->where(function ($q) use ($columns, $keyword) {
                    foreach ($columns as $column) {
                        $q->orWhere($column, 'LIKE', $keyword);
                    }
                });
            }

            if ($this->paymentTerm !== '') {
                $this->whereContainsOrEmpty($query, 'payment_term', $this->paymentTerm);
            }
            if ($this->product !== '') {
                $this->whereContainsOrEmpty($query, 'product', $this->product);
            }

            $emptyColumn = [
                'tanpa_telepon' => 'phone',
                'tanpa_email'   => 'email',
                'tanpa_pic'     => 'pic',
                'tanpa_alamat'  => 'address',
            ];
            if ($this->completeness === 'lengkap') {
                foreach (['phone', 'email', 'pic', 'address'] as $column) {
                    $this->whereFilled($query, $column);
                }
            } elseif (isset($emptyColumn[$this->completeness])) {
                $this->whereEmpty($query, $emptyColumn[$this->completeness]);
            }

            $this->vendors = $query->get();
        }

        return $this->vendors;
    }

    private function dash($value)
    {
        $value = trim((string) $value);
        return $value === '' ? '-' : $value;
    }

    /**
     * Aplikasi cuma menyimpan SATU isian telepon, sedangkan format Excel punya kolom HP dan
     * NO. TELP terpisah. Isian dipecah per nomor (dipisah baris baru, "/", ";" atau ","):
     * nomor yang berawalan 08 / 628 / 8xxxxxxxx dianggap HP, sisanya NO. TELP (telepon kantor).
     */
    private function splitPhones($raw)
    {
        $mobile   = [];
        $landline = [];

        foreach (preg_split('/[\r\n\/;,]+/', (string) $raw) as $token) {
            $token = trim($token);
            if ($token === '' || $token === '-') {
                continue;
            }
            $digits   = preg_replace('/\D+/', '', $token);
            $isMobile = strpos($digits, '08') === 0
                || strpos($digits, '628') === 0
                || preg_match('/^8\d{8,}$/', $digits);

            if ($isMobile) {
                $mobile[] = $token;
            } else {
                $landline[] = $token;
            }
        }

        return [
            $mobile   ? implode("\n", $mobile)   : '-',
            $landline ? implode("\n", $landline) : '-',
        ];
    }

    /** Baris-baris sheet: judul, header, lalu data vendor. */
    public function buildRows($vendors)
    {
        $rows   = [];
        $rows[] = ["DAFTAR NAMA VENDOR / SUPPLIER\nPT. BUANA CENTRA KARYA", '', '', '', '', '', '', '', ''];
        $rows[] = ['NO', 'NAMA VENDOR', 'PRODUK YANG DISUPLAI', 'PIC', 'HP', 'NO. TELP', 'PAYMENT', 'EMAIL', 'ALAMAT'];

        $no = 1;
        foreach ($vendors as $vendor) {
            list($hp, $telp) = $this->splitPhones($vendor->phone);
            $rows[] = [
                $no++,
                $this->dash($vendor->name),
                $this->dash($vendor->product),
                $this->dash($vendor->pic),
                $hp,
                $telp,
                $this->dash($vendor->payment_term),
                $this->dash($vendor->email),
                $this->dash($vendor->address),
            ];
        }

        return $rows;
    }

    public function array(): array
    {
        return $this->buildRows($this->getVendors());
    }

    /** Perkiraan jumlah baris tampilan sebuah teks di kolom selebar $width (untuk tinggi baris). */
    private function estimateLines($text, $width)
    {
        $perLine = max(1, (int) floor($width * 1.05));
        $lines   = 0;
        foreach (preg_split('/\r\n|\r|\n/', (string) $text) as $part) {
            $lines += max(1, (int) ceil(mb_strlen($part) / $perLine));
        }
        return $lines;
    }

    /** Semua pengaturan tampilan sheet. Dipisah dari event supaya mudah dibaca/diuji. */
    public function applyStyles($sheet, $rows)
    {
        $lastRow = max(count($rows), self::HEADER_ROW);
        $first   = self::FIRST_DATA_ROW;

        $sheet->setTitle('VENDOR');

        foreach ($this->widths as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        // ---- Judul (baris 1) ----
        $sheet->mergeCells('A1:I1');
        $sheet->getRowDimension(self::TITLE_ROW)->setRowHeight(45.75);
        $sheet->getStyle('A1:I1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 18],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders'   => ['bottom' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        // ---- Header (baris 2) ----
        $sheet->getRowDimension(self::HEADER_ROW)->setRowHeight(28.5);
        $sheet->getStyle('A2:I2')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 12],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFF00']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        // ---- Data ----
        if ($lastRow >= $first) {
            $sheet->getStyle('A' . $first . ':I' . $lastRow)->applyFromArray([
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ]);

            $horizontal = [
                'A' => Alignment::HORIZONTAL_CENTER, 'B' => Alignment::HORIZONTAL_LEFT,
                'C' => Alignment::HORIZONTAL_CENTER, 'D' => Alignment::HORIZONTAL_LEFT,
                'E' => Alignment::HORIZONTAL_CENTER, 'F' => Alignment::HORIZONTAL_CENTER,
                'G' => Alignment::HORIZONTAL_RIGHT,  'H' => Alignment::HORIZONTAL_CENTER,
                'I' => Alignment::HORIZONTAL_LEFT,
            ];
            foreach ($horizontal as $col => $align) {
                $sheet->getStyle($col . $first . ':' . $col . $lastRow)->getAlignment()->setHorizontal($align);
            }
            $sheet->getStyle('B' . $first . ':B' . $lastRow)->getFont()->setBold(true);

            // Isi tiap sel ditulis sebagai TEKS (supaya telepon / payment tidak berubah jadi angka,
            // dan isian yang berawalan "=" tidak dibaca sebagai rumus), email dibuat link.
            $letters = ['B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'];
            for ($r = $first; $r <= $lastRow; $r++) {
                $row    = $rows[$r - 1];
                $maxLines = 1;

                foreach ($letters as $i => $col) {
                    $text = (string) $row[$i + 1];
                    $sheet->setCellValueExplicit($col . $r, $text, DataType::TYPE_STRING);
                    $maxLines = max($maxLines, $this->estimateLines($text, $this->widths[$col]));
                }

                $email = trim((string) $row[7]);
                if ($email !== '-' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $sheet->getCell('H' . $r)->getHyperlink()->setUrl('mailto:' . $email);
                    $sheet->getStyle('H' . $r)->getFont()->setUnderline('single')->getColor()->setRGB('0563C1');
                }

                $sheet->getRowDimension($r)->setRowHeight(max(30, $maxLines * 15 + 4));
            }
        }

        // ---- Fitur lembar kerja ----
        $sheet->freezePane('A3');
        $sheet->setAutoFilter('A2:I' . $lastRow);

        $pageSetup = $sheet->getPageSetup();
        $pageSetup->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
        $pageSetup->setPaperSize(PageSetup::PAPERSIZE_A4);
        $pageSetup->setFitToPage(true);
        $pageSetup->setFitToWidth(1);
        $pageSetup->setFitToHeight(0);
        $pageSetup->setRowsToRepeatAtTopByStartAndEnd(1, 2);
        $sheet->getPageMargins()->setLeft(0.3)->setRight(0.3)->setTop(0.4)->setBottom(0.4);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $this->applyStyles($event->sheet->getDelegate(), $this->buildRows($this->getVendors()));
            },
        ];
    }
}