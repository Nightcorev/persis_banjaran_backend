<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

//*NOTE: bingung sama id_training di t_master_training sama t_anggota_training
class AnggotaTrainingModel extends Model
{
    use HasFactory;
    protected $table = 't_anggota_training';
    protected $primaryKey = 'id_training';
    protected $fillable = [
        'tingkat',
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
