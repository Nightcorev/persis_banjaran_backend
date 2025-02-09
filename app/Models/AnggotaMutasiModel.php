<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnggotaMutasiModel extends Model
{
    use HasFactory;
    protected $table = 't_anggota_mutasi';
    protected $primaryKey = 'id_mutasi';
    protected $fillable = [
        'asal',
        'tujuan'
    ];

    public function anggota()
    {
        return $this->belongsTo(AnggotaModel::class, 'id_anggota', 'id_anggota');
    }
}
