<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class RekapIuranJamaahExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return collect($this->data);
    }

    public function headings(): array
    {
        // Ambil header dari data pertama (jika ada)
        if (isset($this->data[0])) {
            return array_keys($this->data[0]);
        }
        return ['Nama Jamaah', 'Sudah Dibayar (Rp)', 'Belum Dibayar (Rp)']; // Fallback
    }
}
