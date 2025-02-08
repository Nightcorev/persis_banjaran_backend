<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IuranLogModel extends Model
{
    use HasFactory;
    protected $table = 't_iuran_log';
    protected $primaryKey = 'id_iuran';
    protected $fillable = [
        'tanggal',
        'bulan',
        'tahun',
        'nominal',
        'pj',
        'pc',
        'pd',
        'pw',
        'pp',
        'log_input'
    ];
    public function anggota()
    {
        return $this->belongsTo(AnggotaModel::class, 'id_anggota', 'id_anggota');
    }
}
