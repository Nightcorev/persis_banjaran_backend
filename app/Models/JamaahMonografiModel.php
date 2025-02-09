<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JamaahMonografiModel extends Model
{
    use HasFactory;
    protected $table = 't_jamaah_monografi';
    protected $fillable = [
        'jum_persistri',
        'jum_pemuda',
        'jum_pemudi',
        'jum_mubaligh',
        'jum_asatidz',
        'jum_santri_ra',
        'jum_santri_md',
        'jum_santri_mi',
        'jum_santri_tsn',
        'jum_santri_smp',
        'jum_santri_ma'
    ];

    public function master_jamaah()
    {
        return $this->belongsTo(MasterJamaahModel::class, 'id_jamaah', 'id_master_jamaah');
    }
}
