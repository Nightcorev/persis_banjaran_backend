<?php

namespace App\Exports;

use App\Models\Jamaah;
use Maatwebsite\Excel\Concerns\FromCollection;

class RekapIuranJamaahExport implements FromCollection
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return Jamaah::all();
    }
}
