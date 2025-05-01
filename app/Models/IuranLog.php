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
        'status' => 'string', // Atau gunakan Enum class jika dibuat
        'nominal' => 'decimal:2',
        'pj' => 'decimal:2',
        'pc' => 'decimal:2',
        'pd' => 'decimal:2',
        'pw' => 'decimal:2',
        'pp' => 'decimal:2',
        'tanggal' => 'date',
        'tahun' => 'integer',
    ];

    // Relasi
    public function anggota()
    {
        // Sesuaikan 'App\Models\Anggota' jika nama model/namespace berbeda
        return $this->belongsTo(AnggotaModel::class, 'anggota_id');
    }

    public function verifikator()
    {
        // Sesuaikan 'App\Models\User' jika nama model/namespace berbeda
        return $this->belongsTo(User::class, 'verifikator_id');
    }

    public function pjInput()
    {
        // Sesuaikan 'App\Models\User' jika nama model/namespace berbeda
        return $this->belongsTo(User::class, 'pj_input_id');
    }
}
