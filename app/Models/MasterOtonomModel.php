<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterOtonomModel extends Model
{
    use HasFactory;

    protected $table = 't_master_otonom';
    protected $primaryKey = 'id_otonom';
    protected $fillable = [
        'nama_otonom'
    ];

    public function anggota()
    {
        return $this->hasOne(AnggotaModel::class, 'id_otonom', 'id_otonom');
    }

    public function master_iuran()
    {
        return $this->hasOne(MasterIuranModel::class, 'id_otonom', 'id_otonom');
    }
    public function iuran_porsi()
    {
        return $this->hasOne(IuranPorsiModel::class, 'id_otonom', 'id_otonom');
    }
}
