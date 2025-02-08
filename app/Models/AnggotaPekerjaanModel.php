<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnggotaPekerjaanModel extends Model
{
    use HasFactory;
    protected $table = 't_anggota_pekerjaan';
    protected $primaryKey = 'id_pekerjaan';
    protected $fillable = [
        'Lainnya',
    ];

    public function anggota()
    {
        return $this->belongsTo(AnggotaModel::class, 'id_anggota', 'id_anggota');
    }
    public function master_pekerjaan()
    {
        return $this->belongsTo(MasterPekerjaanModel::class, 'id_anggota', 'id_anggota');
    }
}
