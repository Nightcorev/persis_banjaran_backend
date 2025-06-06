<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnggotaKeluargaModel extends Model
{
    use HasFactory;
    protected $table = 't_anggota_keluarga';
    protected $primaryKey = 'id_keluarga';
    public $timestamps = false;
    protected $fillable = [
        'id_anggota',
        'jumlah_tanggungan',
        'nama_istri',
        'anggota_persistri',
        'jumlah_seluruh_anak',
        'jumlah_anak_pemuda',
        'jumlah_anak_pemudi',
        'status_kepemilikan_rumah'
    ];

    public function anggota()
    {
        return $this->belongsTo(AnggotaModel::class, 'id_anggota', 'id_anggota');
    }

}
