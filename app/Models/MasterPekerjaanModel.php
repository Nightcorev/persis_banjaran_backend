<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterPekerjaanModel extends Model
{
    use HasFactory;
    protected $table = 't_master_pekerjaan';
    protected $primaryKey = 'id_master_pekerjaan';
    protected $fillable = [
        'nama_pekerjaan',
    ];

    public function anggota_pekerjaan()
    {
        return $this->hasOne(AnggotaPekerjaanModel::class, 'id_master_pekerjaan', 'id_master_pekerjaan');
    }
}
