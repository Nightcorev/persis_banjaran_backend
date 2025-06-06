<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnggotaPekerjaanModel extends Model
{
    use HasFactory;
    protected $table = 't_anggota_pekerjaan';
    protected $primaryKey = 'id_pekerjaan';
    public $timestamps = false;
    protected $fillable = [
        'id_anggota',
        'id_master_pekerjaan',
        'lainnya',
        'nama_instasi',
        'deskripsi_pekerjaan',
        'pendapatan',
    ];

    public function anggota()
    {
        return $this->belongsTo(AnggotaModel::class, 'id_anggota', 'id_anggota');
    }

    public function master_pekerjaan()
    {
        return $this->belongsTo('App\Models\MasterPekerjaanModel', 'id_master_pekerjaan', 'id_master_pekerjaan');
    }
}
