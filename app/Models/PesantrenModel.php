<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PesantrenModel extends Model
{
    use HasFactory;
    protected $table = 't_pesantren';
    protected $primaryKey = 'id_pesantren';
    protected $fillable = [
        'nama_pesantren',
        'nomor_pesantren',
        'tingkat',
        'nama_mudir',
        'jum_santri',
        'alamat',
        'no_kontak'
    ];

    public function master_jamaah()
    {
        return $this->belongsTo(MasterJamaahModel::class, 'id_master_jamaah', 'id_master_jamaah');
    }

    public function asatidz_tugas()
    {
        return $this->hasOne(AsatidzTugasModel::class, 'id_pesantren', 'id_pesantren');
    }
    public function jamaah_fasilitas()
    {
        return $this->hasOne(JamaahFasilitasModel::class, 'id_pesantren', 'id_pesantren');
    }
}
