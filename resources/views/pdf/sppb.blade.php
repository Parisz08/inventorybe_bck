<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ $spb->no_spb }}</title>
  <style>
    @page { size: A4 landscape; margin: 10mm 12mm; }
    * { box-sizing: border-box; }
    body {
      font-family: 'Calibri', Arial, sans-serif;
      font-size: 12px;
      color: #000;
      margin: 0;
      padding: 0;
    }
    .sheet { width: 100%; max-width: 1050px; margin: 0 auto; padding: 10px; }

    /* Header */
    .header-table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
    .header-table td { vertical-align: middle; padding: 0; }
    .logo-cell { width: 90px; }
    .logo-cell img { width: 80px; }
    .company-cell { padding-left: 10px; }
    .company-cell h1 { font-size: 15px; margin: 0; font-weight: bold; }
    .company-cell p { font-size: 10px; margin: 0; }
    .req-box { border: 1px solid #000; font-size: 11px; }
    .req-box td { border: 1px solid #000; padding: 3px 6px; }
    .req-box td.label { width: 60px; font-weight: bold; }
    .req-box td.sep { width: 10px; text-align: center; }

    .doc-title {
      text-align: center;
      font-size: 20px;
      font-weight: bold;
      margin: 18px 0 14px 0;
      line-height: 1.3;
    }

    /* Items table */
    table.items { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    table.items th, table.items td {
      border: 1px solid #000;
      padding: 3px 4px;
      font-size: 10px;
    }
    table.items th {
      text-align: center;
      font-weight: bold;
      background-color: #f2f2f2;
    }
    table.items td.no { text-align: center; width: 24px; }
    table.items td.qty { text-align: center; width: 40px; }
    table.items td.unit { text-align: center; width: 50px; }
    table.items td.kategori { text-align: center; width: 50px; }
    table.items td.code { text-align: center; width: 75px; }
    table.items td.stock { text-align: center; width: 60px; }

    /* Footer section: kategori legend + catatan */
    table.footer-info { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
    table.footer-info td { vertical-align: top; padding: 0; font-size: 10.5px; }
    table.footer-info .kategori-col { width: 45%; }
    table.footer-info .catatan-col { width: 55%; padding-left: 14px; }
    table.kategori-legend { width: 100%; border-collapse: collapse; }
    table.kategori-legend td { padding: 1px 4px; font-size: 10.5px; }
    table.kategori-legend td.code { width: 18px; font-weight: bold; }
    .footer-info h6 { font-size: 11px; font-weight: bold; margin: 0 0 4px 0; }

    /* Signature */
    table.signature { width: 100%; border-collapse: collapse; margin-top: 10px; }
    table.signature td { text-align: center; vertical-align: top; padding: 0 10px; font-size: 11px; }
    table.signature .sign-title { font-weight: bold; margin-bottom: 55px; }
    table.signature .sign-name { border-top: 1px solid #000; display: inline-block; min-width: 140px; padding-top: 4px; }
    table.signature .sign-role { font-size: 10.5px; margin-top: 2px; }

    .doc-no { text-align: right; font-size: 10px; margin-top: 20px; }

    @media print {
      .no-print { display: none !important; }
      body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    }
    .no-print { text-align: center; margin-bottom: 14px; margin-top: 24px; display: flex; justify-content: center; gap: 24px; }
    .no-print button {
      padding: 8px 18px; font-size: 13px; cursor: pointer;
      border-radius: 4px; border: 1px solid #ccc; background: #fff;
    }
    .no-print button.primary { background: #2dce89; color: #fff; border-color: #2dce89; }
  </style>
</head>
<body>

  <div class="no-print">
    <button class="primary" onclick="window.print()">🖨️ Print / Simpan sebagai PDF</button>
    <button onclick="window.close()">Tutup</button>
  </div>

  <div class="sheet">
    <!-- ============ HEADER ============ -->
    <table class="header-table">
      <tr>
        <td class="logo-cell"><img src="{{ URL('bck.png') }}" alt="logo"></td>
        <td class="company-cell">
          <h1>PT. BUANA CENTRA KARYA</h1>
          <p>PIPE MANUFACTURING &amp; STEEL FABRICATION</p>
        </td>
        <td style="width: 230px;">
          <table class="req-box">
            <tr><td class="label">Req. No.</td><td class="sep">:</td><td>{{ $spb->no_spb }}</td></tr>
            <tr><td class="label">Divisi</td><td class="sep">:</td><td>{{ $spb->divisi }}</td></tr>
            <tr><td class="label">Tanggal</td><td class="sep">:</td><td>{{ \Carbon\Carbon::parse($spb->request_date)->format('d-m-Y') }}</td></tr>
          </table>
        </td>
      </tr>
    </table>

    <div class="doc-title">SURAT PERMOHONAN PERMINTAAN BARANG<br>(SPPB)</div>

    <!-- ============ ITEMS ============ -->
    <table class="items">
      <thead>
        <tr>
          <th style="width: 24px;">NO</th>
          <th style="width: 75px;">KODE MATERIAL</th>
          <th>URAIAN / NAMA BARANG</th>
          <th>SPESIFIKASI</th>
          <th style="width: 65px;">MEREK</th>
          <th style="width: 50px;">KATEGORI</th>
          <th style="width: 40px;">QTY</th>
          <th style="width: 50px;">UNIT /<br>SATUAN</th>
          <th style="width: 60px;">STOK<br>AKTUAL / MIN</th>
          <th>KETERANGAN</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($spb->items as $i => $item)
        <tr>
          <td class="no">{{ $i + 1 }}</td>
          <td class="code">{{ $item->material_code ?: '-' }}</td>
          <td>{{ $item->material_name }}</td>
          <td>{{ $item->specification ?: '-' }}</td>
          <td>{{ $item->merek ?: '-' }}</td>
          <td class="kategori">{{ $item->kategori ?: '-' }}</td>
          <td class="qty">{{ $item->qty }}</td>
          <td class="unit">{{ $item->unit }}</td>
          <td class="stock">{{ $item->actual_stock !== null ? $item->actual_stock . ' / ' . $item->min_stock : '-' }}</td>
          <td>{{ $item->note }}</td>
        </tr>
        @endforeach
        @for ($i = count($spb->items); $i < 10; $i++)
        <tr>
          <td class="no">{{ $i + 1 }}</td>
          <td>&nbsp;</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>
        </tr>
        @endfor
      </tbody>
    </table>

    <!-- ============ KATEGORI LEGEND + CATATAN ============ -->
    <table class="footer-info">
      <tr>
        <td class="kategori-col">
          <h6>KATEGORI :</h6>
          <table class="kategori-legend">
            <tr><td class="code">A</td><td>ASET</td><td class="code">E</td><td>JASA</td></tr>
            <tr><td class="code">B</td><td>CONSUMABLE</td><td class="code">F</td><td>MAINTENANCE</td></tr>
            <tr><td class="code">C</td><td>SPAREPART</td><td class="code">G</td><td>STATIONARY</td></tr>
            <tr><td class="code">D</td><td>TOOLS</td><td class="code">H</td><td>LAIN-LAIN</td></tr>
          </table>
        </td>
        <td class="catatan-col">
          <h6>Catatan :</h6>
          <p>Barang Dibutuhkan Pada Tanggal {{ $spb->needed_date ? \Carbon\Carbon::parse($spb->needed_date)->format('d/m/Y') : '........../........../' . date('Y') }}</p>
          <p>Untuk Pengadaan Barang Paling Cepat 3 Hari Kerja</p>
          <p>dan Terhitung Dari Tanggal Penerbitan PO</p>
        </td>
      </tr>
    </table>

    <!-- ============ SIGNATURE ============ -->
    <table class="signature">
      <tr>
        <td style="width: 33%;">
          <div class="sign-title">Diajukan Oleh,</div>
          <div class="sign-name">{{ $spb->created_by }}</div>
          <div class="sign-role">User</div>
        </td>
        <td style="width: 34%;">
          <div class="sign-title">Ditinjau Oleh,</div>
          <div class="sign-name">{{ $spb->ditinjau_oleh ?: '' }}</div>
          <div class="sign-role">Manager Dept.</div>
        </td>
        <td style="width: 33%;">
          <div class="sign-title">Disetujui Oleh,</div>
          <div class="sign-name">{{ $spb->approved_by ?: '' }}</div>
          <div class="sign-role">Direktur</div>
        </td>
      </tr>
    </table>

    <div class="doc-no">Doc. No. BCK-QF-P12.2</div>
  </div>

</body>
</html>