<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnggotaOrganisasiModel extends Model
{
    use HasFactory;

    protected $table = 't_anggota_organisasi';
    protected $primaryKey = 'id_organisasi';
    public $timestamps = false;
    protected $fillable = [
        'id_anggota',
        'nama_organisasi',
        'jabatan',
        'tingkat',
        'tahun_mulai',
        'tahun_selesai',
        'keterlibatan_organisasi'
    ];

    public function anggota()
    {
        return $this->belongsTo(AnggotaModel::class, 'id_anggota', 'id_anggota');
    }

}
