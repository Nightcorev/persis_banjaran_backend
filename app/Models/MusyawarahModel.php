<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MusyawarahModel extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $table = 't_musyawarah';
    protected $primaryKey = 'id_musyawarah';
    protected $fillable = [
        'id_master_jamaah',
        'tgl_pelaksanaan',
        'tgl_akhir_jihad',
        'aktif',
        'tingkat_musyawarah',
        'no_sk'
    ];

    protected $casts = [
        'id_musyawarah' => 'integer',
        'id_master_jamaah' => 'integer',
        'tgl_pelaksanaan' => 'date',
        'tgl_akhir_jihad' => 'date',
        'aktif' => 'boolean',
        'no_sk' => 'string'
    ];

    protected $attributes = [
        'tingkat_musyawarah' => 'jamaah'
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::deleting(function($musyawarah) {
            $musyawarah->musyawarah_detail()->delete();
        });
    }

    public function master_jamaah()
    {
        return $this->belongsTo(MasterJamaahModel::class, 'id_master_jamaah', 'id_master_jamaah');
    }

    public function musyawarah_detail()
    {
        return $this->hasMany(MusyawarahDetailModel::class, 'id_musyawarah', 'id_musyawarah');
    }
    
    public function getJabatanAttribute()
    {
        return $this->musyawarah_detail->pluck('jabatan');
    }
}
