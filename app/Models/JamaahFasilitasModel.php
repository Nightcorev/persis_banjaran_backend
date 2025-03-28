<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JamaahFasilitasModel extends Model
{
    use HasFactory;
    protected $table = 't_jamaah_fasilitas';
    protected $primaryKey = 'id_fasilitas';
    protected $fillable = [
        'desktipsi',
        'foto'
    ];

    public function master_jamaah()
    {
        return $this->belongsTo(MasterJamaahModel::class, 'id_master_jamaah', 'id_master_jamaah');
    }
}
