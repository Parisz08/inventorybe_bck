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

.header-right{
    text-align:right;
    font-size:11px;
}

.title{
    text-align:center;
    font-size:16px;
    font-weight:bold;
    margin-top:2mm;
}
.subtitle{
    text-align:center;
    font-size:16px;
    font-weight:bold;
    margin-bottom:4mm;
}

.spb-ref{
    font-size:9.5px;
    margin-bottom:2mm;
}

table.compare{
    width:100%;
    border-collapse:collapse;
    margin-top:1mm;
}
table.compare th,
table.compare td{
    border:1px solid #000;
    padding:1.8mm 1.5mm;
    text-align:center;
    vertical-align:middle;
    font-size:10px;
}
table.compare thead th{
    font-weight:bold;
    background:#ffff00;
}
table.compare td.item-name{
    text-align:left;
}
table.compare td.selected{
    background:#e6f7ec;
    font-weight:bold;
}
table.compare tr.foot-row td{
    font-weight:bold;
}
table.compare tr.foot-row td.label{
    text-align:right;
}
table.compare tr.grand-row td{
    background:#f2f2f2;
}

.print-date{
    text-align:left;
    margin-top:8mm;
    font-size:10px;
}

.signature{
    width:100%;
    margin-top:5mm;
    display:grid;
    grid-template-columns:1fr 1fr;
    font-size:10px;
}

.signature-box{
    text-align:center;
}

.signature-name{
    margin:9mm 0 0;
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

<div class="header-right">
    {{ $spb->divisi ?: '-' }}
    @if($kategoriLabel)
    <div>{{ $kategoriLabel }}</div>
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
    <th colspan="2">{{ $vendor->name }}</th>
    @endforeach
</tr>
<tr>
    @foreach($vendors as $vendor)
    <th>HARGA</th>
    <th>TOTAL</th>
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
    @php $cell = $row['vendors'][$vendor->id]; @endphp
    <td class="{{ $cell['selected'] ? 'selected' : '' }}">{{ $cell['price'] !== null ? number_format($cell['price'], 0, ',', '.') : '-' }}</td>
    <td class="{{ $cell['selected'] ? 'selected' : '' }}">{{ $cell['total'] !== null ? number_format($cell['total'], 0, ',', '.') : '-' }}</td>
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
    <td colspan="2">{{ number_format($vendorTotals[$vendor->id]['total'], 0, ',', '.') }}</td>
    @endforeach
</tr>
<tr class="foot-row">
    <td colspan="4" class="label">PPN {{ $ppnPercent }}%</td>
    @foreach($vendors as $vendor)
    <td colspan="2">{{ number_format($vendorTotals[$vendor->id]['ppn'], 0, ',', '.') }}</td>
    @endforeach
</tr>
<tr class="foot-row grand-row">
    <td colspan="4" class="label">GRAND TOTAL</td>
    @foreach($vendors as $vendor)
    <td colspan="2">{{ number_format($vendorTotals[$vendor->id]['grand_total'], 0, ',', '.') }}</td>
    @endforeach
</tr>
<tr class="foot-row">
    <td colspan="4" class="label">PAYMENT</td>
    @foreach($vendors as $vendor)
    <td colspan="2">{{ $vendor->payment_term ?: '-' }}</td>
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
    <div class="signature-name">Randy</div>
    <div class="signature-role">Purchasing</div>
</div>

<div class="signature-box">
    <div>Disetujui Oleh,</div>
    <div class="signature-name">Robinan</div>
    <div class="signature-role">Direktur</div>
</div>

</div>

</div>
</div>

</body>
</html>