<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TahunAktif extends Model
{
    use HasFactory;

    protected $table = 't_tahun_aktif';

    protected $fillable = [
        'tahun',
        'bulan_awal',
        'bulan_akhir',
        'status',
    ];

    // Casting jika diperlukan
    protected $casts = [
        'tahun' => 'integer',
        'bulan_awal' => 'integer',
        'bulan_akhir' => 'integer',
    ];
}
