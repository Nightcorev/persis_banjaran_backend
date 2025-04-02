<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnggotaKeterampilanModel extends Model
{
    use HasFactory;
    protected $table = 't_anggota_keterampilan';
    protected $primaryKey = 'id_keterampilan';
    public $timestamps = false;
    protected $fillable = [
        'id_anggota',
        'id_master_keterampilan',
        'lainnya',
        'deskripsi'
    ];

    public function anggota()
    {
        return $this->belongsTo(AnggotaModel::class, 'id_anggota', 'id_anggota');
    }

    public function master_keterampilan()
    {
        return $this->belongsTo(MasterKeterampilanModel::class, 'id_master_keterampilan', 'id_master_keterampilan');
    }


}
