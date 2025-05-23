<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnggotaPendidikanModel extends Model
{
    use HasFactory;
    protected $table = 't_anggota_pendidikan';
    protected $primaryKey = 'id_pendidikan';
    public $timestamps = false;
    protected $fillable = [
        'id_anggota',
        'id_tingkat_pendidikan',
        'instansi',
        'jurusan',
        // 'tahun_masuk',
        // 'tahun_keluar',
        // 'jenis_pendidikan'
    ];

    public function anggota()
    {
        return $this->belongsTo(AnggotaModel::class, 'id_anggota', 'id_anggota');
    }

    public function tingkat_pendidikan()
    {
        return $this->belongsTo(TingkatPendidikanModel::class, 'id_tingkat_pendidikan', 'id_tingkat_pendidikan');
    }
}
