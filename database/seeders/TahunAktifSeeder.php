<?php

namespace Database\Seeders;

use App\Models\TahunAktif;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TahunAktifSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        TahunAktif::firstOrCreate(
            ['tahun' => Carbon::now()->year], // Gunakan tahun saat ini
            [
                'bulan_awal' => 1,
                'bulan_akhir' => 12,
                'status' => 'Aktif',
            ]
        );
        // Tambahkan tahun lain jika perlu (misal tahun lalu tidak aktif)
        // TahunAktif::updateOrCreate(
        //     ['tahun' => Carbon::now()->subYear()->year],
        //     [
        //         'bulan_awal' => 1,
        //         'bulan_akhir' => 12,
        //         'status' => 'Tidak Aktif',
        //     ]
        // );
    }
}
