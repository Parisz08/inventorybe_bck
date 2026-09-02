<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use DB;

class BarangKeluarExport implements FromQuery, WithHeadings, WithMapping, WithChunkReading
{
    protected $no_sj;
    protected $material_code;
    protected $material_name;
    protected $type;
    protected $unit;
    protected $description;
    protected $divisi;
    protected $date;

    public function __construct($request)
    {
        $this->no_sj = $request->no_sj;
        $this->material_code = $request->material_code;
        $this->material_name = $request->material_name;
        $this->type          = $request->type;
        $this->unit          = $request->unit;
        $this->description   = $request->description;
        $this->divisi        = $request->divisi;
        $this->date          = $request->date;
    }

    public function query()
    {
        $master = DB::table('barang_keluar')
                    ->leftJoin('stock_barang', 'barang_keluar.material_code', '=', 'stock_barang.material_code')
                    ->select('barang_keluar.id','barang_keluar.material_code','material_name','specification','type','unit','qty','description','divisi','no_sj','date','diserahkan','disetujui','diterima','barang_keluar.created_by','barang_keluar.created_at');

        if (!empty($this->no_sj)) {
            $master->where('no_sj', $this->no_sj);
        }
        if (!empty($this->material_code)) {
            $master->where('material_code', $this->material_code);
        }
        if (!empty($this->material_name)) {
            $master->where('material_name', 'LIKE', '%' . $this->material_name . '%');
        }
        if (!empty($this->type)) {
            $master->where('type', $this->type);
        }
        if (!empty($this->unit)) {
            $master->where('unit', $this->unit);
        }
        if (!empty($this->description)) {
            $master->where('description', 'LIKE', '%' . $this->description . '%');
        }
        if (!empty($this->divisi)) {
            $master->where('divisi', $this->divisi);
        }
        if (!empty($this->date)) {
            $date      = $this->date;
            $dateStart = date(substr($date, 0, 10));
            $dateEnd   = date(substr($date, -10));
            $master->whereDate('date', '>=', $dateStart);
            $master->whereDate('date', '<=', $dateEnd);
        }

        return $master->orderBy('barang_keluar.created_at', 'DESC');
    }

    public function headings(): array
    {
        return [
            'No. Surat Jalan', 'Kode Material', 'Nama Barang', 'Spesifikasi', 'Tipe',
            'Satuan', 'Qty', 'Keterangan', 'Divisi', 'Tanggal',
            'Diserahkan', 'Disetujui', 'Diterima', 'Dibuat Oleh', 'Dibuat Pada',
        ];
    }

    public function map($row): array
    {
        return [
            $row->no_sj,
            $row->material_code,
            $row->material_name,
            $row->specification,
            $row->type,
            $row->unit,
            $row->qty,
            $row->description,
            $row->divisi,
            $row->date,
            $row->diserahkan,
            $row->disetujui,
            $row->diterima,
            $row->created_by,
            $row->created_at,
        ];
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}