<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterMinatModel extends Model
{
    use HasFactory;
    protected $table = 't_master_minat';
    protected $primaryKey = 'id_master_minat';
    protected $fillable = [
        'nama_minat',
    ];

    public function anggota_minat()
    {
        return $this->hasOne(AnggotaMinatModel::class, 'id_master_minat', 'id_master_minat');
    }
}
