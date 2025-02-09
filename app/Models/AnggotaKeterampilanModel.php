<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnggotaKeterampilanModel extends Model
{
    use HasFactory;
    protected $table = 't_anggota_keterampilan';
    protected $primaryKey = 'id_keterampilan';
    protected $fillable = [
        'lainnya'
    ];

    public function anggota()
    {
        return $this->belongsTo(AnggotaModel::class, 'id_anggota', 'id_anggota');
    }

    public function master_minat()
    {
        return $this->belongsTo(MasterMinatModel::class, 'id_minat', 'id_minat');
    }


}
