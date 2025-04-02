<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnggotaTrainingModel extends Model
{
    use HasFactory;
    protected $table = 't_anggota_training';
    protected $primaryKey = 'id_training';
    public $timestamps = false;
    protected $fillable = [
        'id_anggota',
        'nama_training',
        'tempat',
        'tanggal'
    ];
    public function anggota()
    {
        return $this->belongsTo(AnggotaModel::class, 'id_anggota', 'id_anggota');
    }

    public function asatidz()
    {
        return $this->hasOne(AsatidzModel::class, 'id_training', 'id_training');
    }
}
