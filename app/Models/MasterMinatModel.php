<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterMinatModel extends Model
{
    use HasFactory;
    protected $table = 't_master_minat';
    protected $primaryKey = 'id_minat';
    protected $fillable = [
        'nama_minat',
    ];

    public function anggota_keterampilan()
    {
        return $this->hasOne(AnggotaPekerjaanModel::class, 'id_anggota', 'id_anggota');
    }
}
