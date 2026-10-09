@php
    // Warna tiap vendor dibedakan supaya gampang dibaca di kertas.
    // [warna header & grand total, warna lebih terang buat harga vendor terpilih]
    $vendorPalette = [
        ['#b4c6e7', '#dae3f3'], // biru
        ['#c6e0b4', '#e2efda'], // hijau
        ['#f8cbad', '#fce4d6'], // oranye
        ['#d9c2ec', '#ece1f6'], // ungu
        ['#b7dee8', '#daeef3'], // tosca
        ['#f4b6c2', '#fadadf'], // pink
        ['#d0cece', '#e7e6e6'], // abu-abu
        ['#ffd966', '#fff2cc'], // emas
    ];
    $vendorColors = [];
    foreach ($vendors->values() as $vi => $v) {
        $vendorColors[$v->id] = $vendorPalette[$vi % count($vendorPalette)];
    }
    $fmt = function ($n) { return number_format($n, 0, ',', '.'); };
@endphp
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Perbandingan Harga - {{ $spb->no_spb }}</title>

<style>
@page{
    size:A4 landscape;
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
    font-family:Calibri,Arial,Helvetica,sans-serif;
    font-size:10px;
}

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

.page{
    width:100%;
    display:flex;
    justify-content:center;
    align-items:flex-start;
}

.sheet{
    width:280mm;
    margin:0 auto;
    background:#fff;
    padding:8mm 10mm;
}

/* Kotak kanan atas (Divisi / Kategori), ada garis bawah tiap barisnya */
.header-box{
    width:62mm;
    margin-left:auto;
    font-size:10px;
}
.header-box .hb-line{
    border-bottom:1.5px solid #000;
    padding:1.2mm 1mm;
    min-height:6mm;
}

.title{
    text-align:center;
    font-size:15px;
    font-weight:bold;
    margin-top:7mm;
}
.subtitle{
    text-align:center;
    font-size:15px;
    font-weight:bold;
    margin-bottom:5mm;
}

.spb-ref{
    font-size:9px;
    margin-bottom:1.5mm;
}

table.compare{
    width:100%;
    border-collapse:collapse;
    margin-top:1mm;
    table-layout:fixed;
}
table.compare th,
table.compare td{
    border:1px solid #000;
    padding:1.7mm 1.5mm;
    text-align:center;
    vertical-align:middle;
    font-size:10px;
    overflow:hidden;
}
table.compare thead th{
    font-weight:bold;
    background:#ffff00;
}
/* header vendor: warna ditimpa lewat inline style per vendor */
table.compare thead th.vendor-name{
    font-size:9.5px;
}
table.compare thead tr.sub th{
    font-size:9.5px;
}
table.compare td.item-name{
    text-align:left;
}
table.compare td.num{
    text-align:right;
    padding-right:2mm;
}
table.compare td.total-cell{
    font-weight:bold;
}
table.compare td.selected{
    font-weight:bold;
}
table.compare tr.foot-row td{
    padding-top:1.5mm;
    padding-bottom:1.5mm;
}
table.compare tr.foot-row td.label{
    text-align:right;
    font-weight:bold;
    padding-right:1.5mm;
}
table.compare tr.foot-row td.val{
    text-align:right;
    padding-right:2mm;
}
table.compare tr.grand-row td.val{
    font-weight:bold;
}
table.compare tr.payment-row td.val{
    text-align:center;
    font-weight:bold;
}

.print-date{
    text-align:left;
    margin-top:7mm;
    margin-left:12mm;
    font-size:10px;
}

.signature{
    width:100%;
    margin-top:4mm;
    display:flex;
    font-size:10px;
}

.signature-box{
    width:34%;
    text-align:center;
}
.signature-box.right{
    margin-left:auto;
}

.signature-name{
    margin:16mm 0 0;
    text-decoration:underline;
}

.signature-role{
    font-weight:bold;
    margin-top:1mm;
}

@media print{
    .no-print{ display:none; }
    table, tr, td, th{ page-break-inside:avoid !important; }
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

<div class="header-box">
    <div class="hb-line">{{ mb_strtoupper($spb->divisi ?: '-') }}</div>
    @if($kategoriLabel)
    <div class="hb-line">{{ mb_strtoupper($kategoriLabel) }}</div>
    @endif
</div>

<div class="title">ANALISA PERBANDINGAN HARGA</div>
<div class="subtitle">PT. BUANA CENTRA KARYA</div>

<div class="spb-ref">SPPB : {{ $spb->no_spb }}</div>

<table class="compare">
<thead>
<tr>
    <th rowspan="3" style="width:4%;">NO.</th>
    <th rowspan="3" style="width:26%;">NAMA BARANG</th>
    <th rowspan="3" style="width:5%;">QTY</th>
    <th rowspan="3" style="width:7%;">SATUAN</th>
    <th colspan="{{ $vendors->count() * 2 }}">COMPANY / SUPPLIER</th>
</tr>
<tr>
    @foreach($vendors as $vendor)
    <th colspan="2" class="vendor-name" style="background:{{ $vendorColors[$vendor->id][0] }};">{{ $vendor->name }}</th>
    @endforeach
</tr>
<tr class="sub">
    @foreach($vendors as $vendor)
    <th style="background:{{ $vendorColors[$vendor->id][0] }};">HARGA</th>
    <th style="background:{{ $vendorColors[$vendor->id][0] }};">TOTAL</th>
    @endforeach
</tr>
</thead>
<tbody>
@forelse($matrix as $i => $row)
<tr>
    <td>{{ $i + 1 }}</td>
    <td class="item-name">{{ $row['item']->material_name }}</td>
    <td>{{ $row['item']->qty }}</td>
    <td>{{ $row['item']->unit }}</td>
    @foreach($vendors as $vendor)
    @php
        $cell  = $row['vendors'][$vendor->id];
        $style = $cell['selected'] ? 'background:' . $vendorColors[$vendor->id][1] . ';' : '';
    @endphp
    <td class="num {{ $cell['selected'] ? 'selected' : '' }}" style="{{ $style }}">{{ $cell['price'] !== null ? $fmt($cell['price']) : '' }}</td>
    <td class="num total-cell {{ $cell['selected'] ? 'selected' : '' }}" style="{{ $style }}">{{ $cell['total'] !== null ? $fmt($cell['total']) : '-' }}</td>
    @endforeach
</tr>
@empty
<tr>
    <td colspan="{{ 4 + ($vendors->count() * 2) }}">Belum ada penawaran vendor untuk SPPB ini.</td>
</tr>
@endforelse

<tr class="foot-row">
    <td colspan="4" class="label">TOTAL</td>
    @foreach($vendors as $vendor)
    <td colspan="2" class="val">{{ $fmt($vendorTotals[$vendor->id]['total']) }}</td>
    @endforeach
</tr>
<tr class="foot-row">
    <td colspan="4" class="label">PPN {{ $ppnPercent }}%</td>
    @foreach($vendors as $vendor)
    <td colspan="2" class="val">{{ $fmt($vendorTotals[$vendor->id]['ppn']) }}</td>
    @endforeach
</tr>
<tr class="foot-row grand-row">
    <td colspan="4" class="label">GRAND TOTAL</td>
    @foreach($vendors as $vendor)
    <td colspan="2" class="val" style="background:{{ $vendorColors[$vendor->id][0] }};">{{ $fmt($vendorTotals[$vendor->id]['grand_total']) }}</td>
    @endforeach
</tr>
<tr class="foot-row payment-row">
    <td colspan="4" class="label">PAYMENT</td>
    @foreach($vendors as $vendor)
    <td colspan="2" class="val">{{ $vendor->payment_term ?: '-' }}</td>
    @endforeach
</tr>
</tbody>
</table>

<div class="print-date">
{{ $tanggalCetak }}
</div>

<div class="signature">

<div class="signature-box">
    <div>Dibuat Oleh,</div>
    <div class="signature-name">Randy Wahyudi</div>
    <div class="signature-role">Purchasing</div>
</div>

<div class="signature-box right">
    <div>Disetujui Oleh,</div>
    <div class="signature-name">ROBINAND</div>
    <div class="signature-role">Direktur</div>
</div>

</div>

</div>
</div>

</body>
</html>