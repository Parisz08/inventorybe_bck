<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Permintaan Penawaran - {{ $vendor->name }}</title>

<style>
@page{
    size:A4 portrait;
    margin:0;
}

*{
    box-sizing:border-box;
}

html,body{
    margin:0;
    padding:0;
    width:100%;
    height:100%;
    background:#f3f3f3;
    color:#000;
}

body{
    font-family:Calibri,Arial,sans-serif;
    font-size:12px;
}

.no-print{
    width:100%;
    display:flex;
    justify-content:center;
    align-items:center;
    gap:20px;
    margin:20px 0;
}

.no-print button{
    padding:8px 18px;
    font-size:13px;
    cursor:pointer;
    border-radius:4px;
    border:1px solid #ccc;
    background:#fff;
}

.no-print button.primary{
    background:#2dce89;
    color:#fff;
    border-color:#2dce89;
}

.page{
    width:100%;
    min-height:calc(100vh - 80px);
    display:flex;
    justify-content:center;
    align-items:center;
    padding:10px 0;
}

.sheet{
    width:190mm;
    min-height:270mm;
    margin:0 auto;
    padding:12mm 15mm;
    border:1.7px solid #000;
    background:#fff;
    overflow:hidden;
}

/* HEADER */
.header-table{
    width:100%;
    border-collapse:collapse;
    margin-bottom:3mm;
}

.header-table td{
    vertical-align:middle;
    padding:0;
}

.logo-cell{
    width:25mm;
}

.logo-cell img{
    width:21mm;
    height:auto;
}

.company-cell{
    padding-left:3mm!important;
}

.company-cell h1{
    font-size:15px;
    margin:0;
    font-weight:bold;
}

.company-cell p{
    font-size:9px;
    margin:1px 0 0 0;
    letter-spacing:.3px;
}

hr.head-line{
    border:none;
    border-top:1.7px solid #000;
    margin:2mm 0 5mm 0;
}

/* NOMOR & TANGGAL */
table.doc-meta{
    width:100%;
    border-collapse:collapse;
    margin-bottom:5mm;
    font-size:11px;
}

table.doc-meta td{
    padding:.6mm 0;
    vertical-align:top;
}

table.doc-meta .label{
    width:28mm;
}

table.doc-meta .sep{
    width:4mm;
}

/* TITLE */
.doc-title{
    text-align:center;
    font-size:15px;
    font-weight:bold;
    text-decoration:underline;
    margin:3mm 0 6mm 0;
}

/* KEPADA */
.kepada-box{
    margin-bottom:5mm;
    font-size:11.5px;
    line-height:1.5;
}

.kepada-box .nama-vendor{
    font-weight:bold;
}

/* BODY TEXT */
.body-text{
    font-size:11.5px;
    line-height:1.6;
    text-align:justify;
    margin-bottom:4mm;
}

/* ITEMS */
table.items{
    width:100%;
    border-collapse:collapse;
    margin-bottom:5mm;
    table-layout:fixed;
}

table.items th,
table.items td{
    border:1px solid #000;
    padding:2mm 2mm;
    font-size:10.5px;
}

table.items th{
    text-align:center;
    vertical-align:middle;
    font-weight:bold;
    background:#f2f2f2;
    line-height:1.1;
}

table.items td{
    vertical-align:middle;
    line-height:1.2;
}

table.items td.no{
    width:6%;
    text-align:center;
}

table.items td.nama{
    width:36%;
}

table.items td.spec{
    width:28%;
}

table.items td.qty{
    width:12%;
    text-align:center;
}

table.items td.unit{
    width:18%;
    text-align:center;
}

/* SIGNATURE */
table.signature{
    width:100%;
    border-collapse:collapse;
    margin-top:12mm;
}

table.signature td{
    text-align:center;
    vertical-align:top;
    padding:0;
    font-size:11px;
}

.sign-col{
    width:65mm;
    display:inline-block;
}

table.signature .sign-title{
    font-weight:normal;
    margin-bottom:16mm;
}

table.signature .sign-name{
    border-top:1px solid #000;
    display:inline-block;
    min-width:55mm;
    padding-top:1.5mm;
    font-size:11px;
    font-weight:bold;
}

table.signature .sign-role{
    font-size:10.5px;
    font-weight:bold;
    margin-top:1mm;
}

.doc-no{
    text-align:right;
    font-size:8px;
    margin-top:8mm;
}

/* PRINT */
@media print{

    .no-print{
        display:none!important;
    }

    @page{
        size:A4 portrait;
        margin:0;
    }

    html,
    body{
        width:210mm;
        height:297mm;
        margin:0;
        padding:0;
        overflow:hidden;
        background:#fff;
    }

    .page{
        width:210mm;
        height:297mm;
        min-height:297mm;
        margin:0;
        padding:0;
        display:flex;
        justify-content:center;
        align-items:center;
    }

    .sheet{
        width:190mm;
        min-height:270mm;
        margin:0 auto;
        padding:12mm 15mm;
        border:1.7px solid #000;
        background:#fff;
        overflow:hidden;
    }

    body{
        -webkit-print-color-adjust:exact;
        print-color-adjust:exact;
    }

    table,
    tr,
    td,
    th{
        page-break-inside:avoid!important;
    }
}
</style>
</head>

<body>

<div class="no-print">
    <button class="primary" onclick="window.print()">🖨️ Print / Simpan sebagai PDF</button>
    <button onclick="window.close()">Tutup</button>
</div>

<div class="page">
<div class="sheet">

<!-- HEADER -->
<table class="header-table">
<tr>

<td class="logo-cell">
<img src="{{ URL('bck.png') }}" alt="logo">
</td>

<td class="company-cell">
<h1>PT. BUANA CENTRA KARYA</h1>
<p>PIPE MANUFACTURING &amp; STEEL FABRICATION</p>
</td>

</tr>
</table>
<hr class="head-line">

<!-- NOMOR & TANGGAL -->
<table class="doc-meta">
<tr>
<td class="label">No. Ref. SPPB</td>
<td class="sep">:</td>
<td>{{ $spb->no_spb }}</td>
</tr>
<tr>
<td class="label">Tanggal</td>
<td class="sep">:</td>
<td>{{ \Carbon\Carbon::now()->format('d-m-Y') }}</td>
</tr>
<tr>
<td class="label">Perihal</td>
<td class="sep">:</td>
<td>Permintaan Penawaran Harga</td>
</tr>
</table>

<!-- TITLE -->
<div class="doc-title">
PERMINTAAN PENAWARAN HARGA
</div>

<!-- KEPADA -->
<div class="kepada-box">
Kepada Yth.<br>
<span class="nama-vendor">{{ $vendor->name }}</span><br>
@if($vendor->address)
{{ $vendor->address }}<br>
@endif
@if($vendor->phone)
Telp. {{ $vendor->phone }}
@endif
</div>

<!-- BODY -->
<div class="body-text">
Dengan hormat,
</div>

<div class="body-text">
Sehubungan dengan kebutuhan pengadaan barang di perusahaan kami, dengan ini kami bermaksud meminta
penawaran harga untuk barang-barang sebagai berikut:
</div>

<!-- ITEMS -->
<table class="items">
<thead>
<tr>
<th style="width:6%;">NO</th>
<th style="width:36%;">NAMA BARANG</th>
<th style="width:28%;">SPESIFIKASI</th>
<th style="width:12%;">QTY</th>
<th style="width:18%;">SATUAN</th>
</tr>
</thead>
<tbody>

@foreach($items as $i => $item)
<tr>
<td class="no">{{ $i + 1 }}</td>
<td class="nama">{{ $item->material_name }}{{ $item->merek ? ' (' . $item->merek . ')' : '' }}</td>
<td class="spec">{{ $item->specification ?: '-' }}</td>
<td class="qty">{{ $item->qty }}</td>
<td class="unit">{{ $item->unit ?: '-' }}</td>
</tr>
@endforeach

</tbody>
</table>

<div class="body-text">
Mohon kesediaan Bapak/Ibu untuk mengirimkan penawaran harga (beserta syarat pembayaran dan estimasi
waktu pengiriman) selambat-lambatnya 7 (tujuh) hari kerja sejak surat ini diterima, melalui kontak yang
biasa digunakan.
</div>

<div class="body-text">
Atas perhatian dan kerja samanya, kami ucapkan terima kasih.
</div>

<!-- SIGNATURE -->
<table class="signature">
<tr>
<td style="width:50%;">
<div class="sign-col">
<div class="sign-title">Menyetujui,</div>
<div class="sign-name">&nbsp;</div>
<div class="sign-role">{{ $vendor->name }}</div>
</div>
</td>
<td style="width:50%;">
<div class="sign-col">
<div class="sign-title">Hormat kami,</div>
<div class="sign-name">&nbsp;</div>
<div class="sign-role">Purchasing</div>
</div>
</td>
</tr>
</table>

<div class="doc-no">
Doc. No. BCK-QF-P12.3
</div>

</div>
</div>

<script>
setTimeout(function () { window.print(); }, 600);
</script>

</body>
</html>