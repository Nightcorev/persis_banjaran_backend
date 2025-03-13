<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MusyawarahDetailModel extends Model
{
    use HasFactory;
    protected $table = 't_musyawarah_detail';
    protected $primaryKey = 'id_musyawarah_detail';
    protected $fillable = [
        'jabatan',
        'aktif'
    ];

    public function musyawarah()
    {
        return $this->belongsTo(MusyawarahModel::class, 'id_musyawarah', 'id_musyawarah');
    }

    public function anggota()
    {
        return $this->belongsTo(AnggotaModel::class, 'id_anggota', 'id_anggota');
    }
}
