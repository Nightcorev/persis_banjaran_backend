<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IuranPorsiModel extends Model
{
    use HasFactory;
    protected $table = 't_iuran_porsi';
    protected $primaryKey = 'id_porsi';
    protected $fillable = [
        'persentase',
    ];

    public function master_jamaah()
    {
        return $this->belongsTo(MasterJamaahModel::class, 'id_jamiyyah', 'id_master_jamaah');
    }

    public function master_otonom()
    {
        return $this->belongsTo(MasterOtonomModel::class, 'id_otonom', 'id_otonom');
    }

    public function tahun_aktif()
    {
        return $this->belongsTo(TahunAktifModel::class, 'id_tahun_aktif', 'id_tahun_aktif');
    }

}
