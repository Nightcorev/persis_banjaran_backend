<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IuranLog extends Model
{
    use HasFactory;
    protected $table = 't_iuran_log';

    protected $fillable = [
        'anggota_id',
        'nominal',
        'tanggal',
        'tahun',
        'paid_months', // <-- Tambahkan ini
        'pj',
        'pc',
        'pd',
        'pw',
        'pp',
        'status',
        'catatan_verifikasi',
        'verifikator_id',
        'pj_input_id',
    ];

    protected $casts = [
        'status' => 'string',
        'nominal' => 'decimal:2',
        'pj' => 'decimal:2',
        'pc' => 'decimal:2',
        'pd' => 'decimal:2',
        'pw' => 'decimal:2',
        'pp' => 'decimal:2',
        'tanggal' => 'date',
        'tanggal' => 'datetime:Y-m-d H:i:s',
        'paid_months' => 'array',
    ];

    // Relasi (sama seperti sebelumnya)
    public function anggota()
    {
        return $this->belongsTo(AnggotaModel::class, 'anggota_id', 'id_anggota');
    } // Sesuaikan nama model/PK
    public function verifikator()
    {
        return $this->belongsTo(User::class, 'verifikator_id');
    }
    public function pjInput()
    {
        return $this->belongsTo(User::class, 'pj_input_id');
    }
}
