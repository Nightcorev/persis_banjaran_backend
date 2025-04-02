<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AsatidzTugasModel extends Model
{
    use HasFactory;
    protected $table = 't_asatidz_tugas';
    protected $primaryKey = 'id_tugas';
    protected $fillable = [
        'id_asatidz',
        'id_pesantren',
        'jabatan',
        'mulai_tugas',
        'no_sk',
        'tmt_sk',
        'aktif',
    ];

    public function pesantren()
    {
        return $this->belongsTo(PesantrenModel::class, 'id_pesantren', 'id_pesantren');
    }

    public function asatidz()
    {
        return $this->belongsTo(AsatidzModel::class, 'id_asatidz', 'id_asatidz');
    }
}
