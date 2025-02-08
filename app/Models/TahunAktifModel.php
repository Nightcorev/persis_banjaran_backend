<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TahunAktifModel extends Model
{
    use HasFactory;
    protected $table = 't_tahun_aktif';
    protected $primaryKey = 'id_tahun_aktif';
    protected $fillable = [
        'tahun',
        'bulan_awal',
        'bulan_akhir',
        'status'
    ];

    public function anggota_iuran()
    {
        return $this->hasOne(AnggotaIuranModel::class, 'id_tahun_aktif', 'id_tahun_aktif');
    }

    public function iuran_porsi()
    {
        return $this->hasOne(IuranPorsiModel::class, 'id_tahun_aktif', 'id_tahun_aktif');
    }
}
