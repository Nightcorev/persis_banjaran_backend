<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnggotaIuranModel extends Model
{
    use HasFactory;
    protected $table = 't_anggota_iuran';
    protected $primaryKey = 'id_iuran';
    protected $fillable = [
        'bulan',
    ];

    public function anggota()
    {
        return $this->belongsTo(AnggotaModel::class, 'id_anggota', 'id_anggota');
    }

    public function tahun_aktif()
    {
        return $this->belongsTo(TahunAktifModel::class, 'id_tahun_aktif', 'id_tahun_aktif');
    }

}
