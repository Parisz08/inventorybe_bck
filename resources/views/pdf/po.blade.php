<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>{{ $po->po_number }}</title>

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
    background:#fff;
    color:#000;
}

body{
    font-family:Arial,Helvetica,sans-serif;
    font-size:7.5px;
}

/* =========================
   BUTTON
========================= */

.no-print{
    width:100%;
    display:flex;
    justify-content:center;
    align-items:center;
    gap:10px;
    margin:12px 0;
}

.no-print button{
    padding:7px 15px;
    font-size:11px;
    cursor:pointer;
    border:1px solid #ccc;
    border-radius:4px;
    background:#fff;
}

.no-print .primary{
    background:#2dce89;
    color:#fff;
    border-color:#2dce89;
}

/* =========================
   PAGE
========================= */

.page{
    width:100%;
    display:flex;
    justify-content:center;
    align-items:flex-start;
}

.sheet{
    width:194mm;
    margin:0 auto;
    background:#fff;
    border:1.5px solid #000;
    overflow:hidden;
}

/* =========================
   HEADER
========================= */

.header{
    height:17mm;
    position:relative;
}

.logo{
    position:absolute;
    left:4mm;
    top:1.8mm;
    width:20mm;
    height:13.5mm;
    object-fit:contain;
}

.company{
    position:absolute;
    left:26mm;
    top:3mm;
    font-size:12px;
    font-weight:bold;
    white-space:nowrap;
}

.company .red{
    color:#c9272c;
}

.company .blue{
    color:#1c4e91;
}

.company-subtitle{
    position:absolute;
    left:26mm;
    top:8.5mm;
    font-size:5.7px;
    letter-spacing:.55px;
    color:#777;
    font-weight:bold;
}

/* =========================
   TITLE
========================= */

.title{
    height:11mm;
    text-align:center;
    padding-top:.5mm;
    font-size:15px;
    font-weight:bold;
}

/* =========================
   TOP INFO (Garis Dihilangkan & Posisi Disesuaikan)
========================= */

table.top-info{
    width:100%;
    border-collapse:collapse;
    table-layout:fixed;
}

.top-info td{
    vertical-align:top;
    padding:0;
}

.top-left{
    width:52%;
    padding:0 4mm 2mm 26mm !important; /* Diberi margin kiri 26mm agar sejajar dengan PT */
}

.top-right{
    width:48%;
    padding:0 0 2mm 4mm !important;
}

.top-inner{
    width:100%;
    border-collapse:collapse;
}

.top-inner td{
    padding:.65mm 0;
    vertical-align:top;
    font-size:7.5px;
}

.top-label,
.top-colon{
    border-bottom:none !important;
}

.top-value{
    border-bottom:.8px solid #000;
    padding-bottom:.3mm;
}

.top-label{
    width:12mm;
    white-space:nowrap;
}

.top-colon{
    width:2mm;
    text-align:center;
}

.top-value{
    height:4mm;
}

.supplier-name{
    font-size:8px;
    font-weight:bold;
}

/* =========================
   BOX
========================= */

table.full-box{
    width:100%;
    border-collapse:collapse;
    table-layout:fixed;
}

.full-box td{
    border:.8px solid #000;
    vertical-align:top;
    padding:1.6mm 0;
}

.box-title{
    margin:0 3.5mm 1.5mm;
    font-size:7px;
    font-weight:bold;
    text-decoration:underline;
    line-height:1.1;
}

.box-line{
    margin:0 3.5mm 1mm;
    font-size:7px;
    line-height:1.1;
}

.box-bold{
    font-size:7.5px;
    font-weight:bold;
}

.ship{
    width:34%;
}

.bill{
    width:34%;
}

.division{
    width:32%;
}

.division-content{
    text-align:center;
    padding-top:2mm;
}

.division-main{
    font-size:9px;
    font-weight:bold;
    margin-bottom:1.3mm;
}

.division-small{
    font-size:7px;
    margin-bottom:.8mm;
}

/* =========================
   SECOND BOX
========================= */

.origin{
    width:34%;
}

.shipping{
    width:23%;
}

.currency{
    width:13%;
}

.reference{
    width:30%;
}

.center-value{
    text-align:center;
    margin-top:3.5mm;
    font-size:7.5px;
}

.reference-value{
    margin:1mm 3.5mm 0;
    font-size:6.7px;
    font-style:italic;
    min-height:7mm;
}

/* =========================
   ITEM SPACER
========================= */

.items-spacer{
    height:2.5mm;
    border-bottom:.8px solid #000;
}

/* =========================
   ITEMS
========================= */

table.items{
    width:100%;
    border-collapse:collapse;
    table-layout:fixed;
}

.items th,
.items td{
    border:.8px solid #000;
}

.items th{
    height:7.5mm;
    padding:.6mm;
    text-align:center;
    vertical-align:middle;
    font-size:7px;
    font-weight:bold;
    line-height:1;
}

.items td{
    height:5.1mm;
    padding:.55mm 1.5mm;
    vertical-align:middle;
    font-size:6.8px;
    line-height:1;
}

.items .no{
    width:5%;
    text-align:center;
}

.items .name{
    width:31%;
}

.items .merek{
    width:16%;
}

.items .qty{
    width:8%;
    text-align:center;
}

.items .unit{
    width:8%;
    text-align:center;
}

.items .price{
    width:14%;
    text-align:right;
}

.items .total{
    width:18%;
    text-align:right;
}

.item-name{
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
}

/* =========================
   TOTAL
========================= */

table.totals{
    width:100%;
    border-collapse:collapse;
    table-layout:fixed;
}

.totals td{
    height:5mm;
    padding:.55mm 2.5mm;
    border:.8px solid #000;
    font-size:7px;
}

.totals .label{
    width:84%;
    text-align:right;
}

.totals .value{
    width:16%;
    text-align:right;
    white-space:nowrap;
}

.totals .grand td{
    height:6mm;
    font-size:8px;
    font-weight:bold;
    border-top:1.5px solid #000;
    border-bottom:1.5px solid #000;
}

/* =========================
   TERMS
========================= */

.terms{
    width:100%;
    min-height:8mm;
    display:grid;
    grid-template-columns:29mm 3mm 1fr;
    align-items:center;
    border-bottom:.8px solid #000;
}

.terms-label{
    padding:1.8mm 0;
    font-size:7.5px;
}

.terms-colon{
    text-align:center;
    font-size:7.5px;
}

.terms-value{
    padding:1.8mm 3.5mm 1.8mm 0;
    font-size:7.5px;
}

/* =========================
   REMARKS
========================= */

.remarks{
    width:100%;
    border-bottom:1.5px solid #000;
}

.remarks-title{
    height:6mm;
    padding:1.5mm 0;
    font-size:7.5px;
    font-weight:bold;
    border-bottom:.8px solid #000;
}

.remark{
    min-height:5mm;
    padding:1.25mm 0;
    font-size:6.8px;
    line-height:1;
    border-bottom:.7px solid #000;
}

.remark:last-child{
    border-bottom:none;
}

/* =========================
   SIGNATURE
========================= */

.signature{
    width:100%;
    height:23mm;
    display:grid;
    grid-template-columns:1fr 1fr;
}

.signature-box{
    position:relative;
    height:100%;
    text-align:center;
}

.signature-title{
    position:absolute;
    top:2.5mm;
    left:0;
    width:100%;
    font-size:7.5px;
}

.signature-name{
    position:absolute;
    bottom:5mm;
    left:50%;
    transform:translateX(-50%);
    min-width:25mm;
    padding:0 1.5mm .7mm;
    border-bottom:.8px solid #000;
    font-size:7.2px;
    white-space:nowrap;
}

.signature-role{
    position:absolute;
    bottom:1.3mm;
    left:0;
    width:100%;
    font-size:7.2px;
    font-weight:bold;
}

/* =========================
   FOOTER
========================= */

.footer{
    width:100%;
}

.distribution{
    width:100%;
    padding:1mm 0;
    font-size:6.7px;
    font-style:italic;
    line-height:1;
    border-bottom:.8px solid #000;
}

.disclaimer{
    width:100%;
    padding:.8mm 4mm;
    text-align:center;
    font-size:6.2px;
    line-height:1.1;
}

/* =========================
   PRINT
========================= */

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
    }

    .page{
        width:210mm;
        height:297mm;
        margin:0;
        padding:0;
        display:flex;
        justify-content:center;
        align-items:center;
    }

    .sheet{
        width:194mm;
        margin:0 auto;
        border:1.5px solid #000;
    }

    table,
    tr,
    td,
    th{
        page-break-inside:avoid!important;
    }

    body{
        -webkit-print-color-adjust:exact;
        print-color-adjust:exact;
    }
}
</style>
</head>

<body>

<div class="no-print">
    <button class="primary" onclick="window.print()">
        Print / Simpan sebagai PDF
    </button>

    <button onclick="window.close()">
        Tutup
    </button>
</div>

<div class="page">
<div class="sheet">

<!-- HEADER -->
<div class="header">

    <img src="{{ URL('bck.png') }}"
         class="logo"
         alt="Logo BCK">

    <div class="company">
        <span class="red">PT.</span>
        <span class="blue">BUANA CENTRA KARYA</span>
    </div>

    <div class="company-subtitle">
        STEEL PIPE MANUFACTURE &amp; FABRICATOR
    </div>

</div>

<!-- TITLE -->
<div class="title">
    PURCHASE ORDER
</div>

<!-- SUPPLIER / PO -->
<table class="top-info">
<tr>

<td class="top-left">

<table class="top-inner">

<tr>
<td class="top-label">Kepada</td>
<td class="top-colon">:</td>
<td class="top-value supplier-name">
    {{ $po->supplier }}
</td>
</tr>

<tr>
<td></td>
<td></td>
<td class="top-value">
    {{ optional($po->vendor)->address ?: '' }}
</td>
</tr>

<tr>
<td></td>
<td></td>
<td class="top-value">
@if(optional($po->vendor)->phone)
    Telp. {{ $po->vendor->phone }}
@endif
</td>
</tr>

</table>

</td>

<td class="top-right">

<table class="top-inner">

<tr>
<td class="top-label" style="width: 22mm;">PO No.</td>
<td class="top-colon">:</td>
<td class="top-value">
    {{ $po->po_number }}
</td>
</tr>

<tr>
<td class="top-label" style="width: 22mm;">Tanggal / Date</td>
<td class="top-colon">:</td>
<td class="top-value">
    {{ $po->po_date ? \Carbon\Carbon::parse($po->po_date)->format('d F Y') : '' }}
</td>
</tr>

<tr>
<td class="top-label" style="width: 22mm;">Up</td>
<td class="top-colon">:</td>
<td class="top-value">
    {{ $po->up_name ?: ($po->sign_dibuat ?: '') }}
</td>
</tr>

</table>

</td>

</tr>
</table>

<!-- SHIP / BILL / DIVISI -->
<table class="full-box">

<tr>

<td class="ship">

<div class="box-title">
Kirim ke / Ship to:
</div>

<div class="box-line box-bold">
PT. Buana Centra Karya
</div>

<div class="box-line">
Jln. Raya Merak KM. 115 Rawa Arum,
</div>

<div class="box-line">
Cilegon Banten 42436
</div>

<div class="box-line">
Phone : 0254-572111/574222
</div>

<div class="box-line">
Fax : 0254-572333
</div>

</td>

<td class="bill">

<div class="box-title">
Alamatkan Tagihan &amp; Lampirkan ke /<br>
<i>Mail Invoice and Attachment to:</i>
</div>

<div class="box-line box-bold">
PT. Buana Centra Karya
</div>

<div class="box-line box-bold">
NPWP : 96.846.940.3-417.000
</div>

</td>

<td class="division">

<div class="box-title">
Divisi :
</div>

<div class="division-content">

<div class="division-main">
{{ optional($po->spb)->divisi ?: 'HEAD QUARTER' }}
</div>

<div class="division-small">
Office
</div>

<div class="division-small">
SPPB - {{ $po->no_sppb_manual ?: (optional($po->spb)->no_spb ?: '-') }}
</div>

</div>

</td>

</tr>

</table>

<!-- ORIGIN / SHIPPING / CURRENCY -->
<table class="full-box">

<tr>

<td class="origin">

<div class="box-title">
Negara Asal / Country of Origin:
</div>

<div class="center-value">
INDONESIA
</div>

</td>

<td class="shipping">

<div class="box-title">
Shipping Terms
</div>

<div class="center-value">
{{ data_get($po,'shipping_term') ?: data_get($po,'shipping_terms') ?: '-' }}
</div>

</td>

<td class="currency">

<div class="box-title">
Currency
</div>

<div class="center-value">
RUPIAH
</div>

</td>

<td class="reference">

<div class="box-title">
Dasar Acuan No. PP / Kontrak /<br>
<i>Reference PP No./ Contract :</i>
</div>

<div class="reference-value">
{{ data_get($po,'reference_no') ?: data_get($po,'contract_no') ?: optional($po->spb)->no_spb ?: '-' }}
</div>

</td>

</tr>

</table>

<div class="items-spacer"></div>

@php
    $items = $po->items ?? [];
    $itemCount = count($items);
    $displayRows = 11;
@endphp

<!-- ITEMS -->
<table class="items">

<thead>

<tr>

<th class="no">
No.
</th>

<th class="name">
Nama Barang
</th>

<th class="merek">
Merek
</th>

<th class="qty">
Jumlah
</th>

<th class="unit">
Satuan
</th>

<th class="price">
Harga Satuan
</th>

<th class="total">
Jumlah Harga
</th>

</tr>

</thead>

<tbody>

@foreach($items as $i => $item)

<tr>

<td class="no">
{{ $i + 1 }}
</td>

<td class="name">

<div class="item-name">

{{ $item->material_name }}

</div>

</td>

<td class="merek">
{{ $item->merek ?: '-' }}
</td>

<td class="qty">
{{ $item->qty }}
</td>

<td class="unit">
{{ $item->unit }}
</td>

<td class="price">
{{ number_format($item->unit_price,0,',','.') }}
</td>

<td class="total">
{{ number_format($item->line_total,0,',','.') }}
</td>

</tr>

@endforeach


@for($i = $itemCount; $i < $displayRows; $i++)

<tr>

<td class="no">
{{ $i + 1 }}
</td>

<td class="name"></td>

<td class="merek"></td>

<td class="qty"></td>

<td class="unit"></td>

<td class="price"></td>

<td class="total">
-
</td>

</tr>

@endfor

</tbody>

</table>

@php
    $dppLain = $total * 11 / 12;
@endphp

<!-- TOTAL -->

<table class="totals">

<tr>
<td class="label">
Jumlah
</td>

<td class="value">
{{ number_format($subtotal,0,',','.') }}
</td>
</tr>

@if($discountPercent > 0)
<tr>
<td class="label">
Discount {{ $discountPercent }}%
</td>

<td class="value">
{{ number_format($discount,0,',','.') }}
</td>
</tr>
@endif

<tr>
<td class="label">
Total
</td>

<td class="value">
{{ number_format($total,0,',','.') }}
</td>
</tr>

<tr>
<td class="label">
DPP Lain
</td>

<td class="value">
{{ number_format($dppLain,0,',','.') }}
</td>
</tr>

<tr>
<td class="label">
PPN {{ rtrim(rtrim(number_format($ppnPercent,2,',','.'), '0'), ',') ?: '0' }}%
</td>

<td class="value">
{{ number_format($ppn,0,',','.') }}
</td>
</tr>

@if($pphPercent > 0)
<tr>
<td class="label">
PPh (Jasa) {{ rtrim(rtrim(number_format($pphPercent,2,',','.'), '0'), ',') ?: '0' }}%
</td>

<td class="value">
{{ number_format($pph,0,',','.') }}
</td>
</tr>
@endif

<tr class="grand">

<td class="label">
Grand Total
</td>

<td class="value">
{{ number_format($grandTotal,0,',','.') }}
</td>

</tr>

</table>

<!-- TERMS -->

<div class="terms">

<div class="terms-label">
Term / Condition
</div>

<div class="terms-colon">
:
</div>

<div class="terms-value">
{{ optional($po->vendor)->payment_term ?: '-' }}
</div>

</div>

<!-- REMARKS -->

<div class="remarks">

<div class="remarks-title">
Catatan :
</div>

<div class="remark">
- Pengiriman Barang Paling Lambat 3 Hari Setelah PO Diterbitkan Kepada Pihak Supplier / Vendor
</div>

<div class="remark">
- Barang Akan Kami Kembalikan Apabila Tidak Sesuai Dengan Pesanan (PO)
</div>

<div class="remark">
- Semua Pengiriman Barang Harus Disertakan Dengan Nota / Faktur Dan Kwitansi
</div>

<div class="remark">
- Nomor PO Harus Dicantumkan Dalam Invoice
</div>

</div>

<!-- SIGNATURE -->

<div class="signature">

<div class="signature-box">

<div class="signature-title">
Dibuat Oleh :
</div>

<div class="signature-name">
{{ $po->sign_dibuat ?: '' }}
</div>

<div class="signature-role">
Purchasing
</div>

</div>


<div class="signature-box">

<div class="signature-title">
Disetujui Oleh :
</div>

<div class="signature-name">
{{ $po->sign_disetujui ?: '' }}
</div>

<div class="signature-role">
Direktur
</div>

</div>

</div>

<!-- FOOTER -->

<div class="footer">

<div class="distribution">
Distribusi: 1. Pemasok (Supplier), 2. Keuangan &amp; Akuntansi, 3. Arsip
</div>

<div class="disclaimer">
Dokumen ini milik PT. BCK, isi dari dokumen ini tidak diperkenankan untuk digandakan atau disalin baik seluruh atau sebagian tanpa izin tertulis dari PT. BCK.
</div>

</div>

</div>
</div>

</body>
</html>