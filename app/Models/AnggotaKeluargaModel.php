<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnggotaKeluargaModel extends Model
{
    use HasFactory;
    protected $table = 't_anggota_keluarga';
    protected $primaryKey = 'id_keluarga';
    protected $fillable = [
        'nama_keluarga',
        'hubungan',
    ];

    public function anggota()
    {
        return $this->belongsTo(AnggotaModel::class, 'id_anggota', 'id_anggota');
    }

    public function master_otonom()
    {
        return $this->belongsTo(MasterOtonomModel::class, 'id_otonom', 'id_otonom');
    }

}
