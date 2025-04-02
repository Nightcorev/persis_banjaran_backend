<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterKeterampilanModel extends Model
{
    use HasFactory;
    protected $table = 't_master_keterampilan';
    protected $primaryKey = 'id_master_keterampilan';
    protected $fillable = [
        'nama_keterampilan',
    ];

    public function anggota_keterampilan()
    {
        return $this->hasOne(AnggotaKeterampilanModel::class, 'id_master_keterampilan', 'id_master_keterampilan');
    }
}
