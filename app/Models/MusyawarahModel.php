<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MusyawarahModel extends Model
{
    use HasFactory;

    protected $table = 't_musyawarah';
    protected $primaryKey = 'id_musyawarah';
    protected $fillable = [
        'tgl_pelaksanaan',
        'tgl_akhir_jihad',
        'aktif'
    ];

    public function master_jamaah()
    {
        return $this->belongsTo(MasterJamaahModel::class, 'id_master_jamaah', 'id_master_jamaah');
    }

    public function musyawarah_detail()
    {
        return $this->hasMany(MusyawarahDetailModel::class, 'id_musyawarah', 'id_musyawarah');
    }
}
