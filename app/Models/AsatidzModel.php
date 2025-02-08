<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AsatidzModel extends Model
{
    use HasFactory;
    protected $table = 't_asatidz';
    protected $fillable = [
        'tingkat',
        'tempat',
        'tanggal'
    ];

    public function anggota()
    {
        return $this->belongsTo(AnggotaModel::class, 'id_anggota', 'id_anggota');
    }
    public function anggota_training()
    {
        return $this->belongsTo(AnggotaTrainingModel::class, 'id_training', 'id_training');
    }

    public function asatidz_tugas()
    {
        return $this->hasOne(AsatidzTugasModel::class, 'id_asatidz', 'id_asatidz');
    }
}
