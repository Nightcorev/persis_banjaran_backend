<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterIuranModel extends Model
{
    use HasFactory;
    protected $table = 't_iuran_master';
    protected $primaryKey = 'id_iuran_master';
    protected $fillable = [
        'besar_iuran',
        'tahun_aktif_awal',
        'tahun_aktif_akhir'
    ];

    public function master_otonom()
    {
        return $this->belongsTo(MasterOtonomModel::class, 'id_otonom', 'id_otonom');
    }
}
