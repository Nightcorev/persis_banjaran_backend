<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnggotaMinatModel extends Model
{

    use HasFactory;
    protected $table = 't_anggota_minat';
    protected $primaryKey = 'id_minat';
    public $timestamps = false;
    protected $fillable = [
        'id_anggota',
        'id_master_minat',
        'lainnya',
    ];

    public function anggota()
    {
        return $this->belongsTo(AnggotaModel::class, 'id_anggota', 'id_anggota');
    }

    public function master_minat()
    {
        return $this->belongsTo(MasterMinatModel::class, 'id_master_minat', 'id_master_minat');
    }
}
