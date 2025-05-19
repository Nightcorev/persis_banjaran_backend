<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @OA\Schema(
 *     schema="Jamaah",
 *     type="object",
 *     title="Jamaah",
 *     properties={
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="nama_jamaah", type="string", example="Jamaah Al-Munawwar"),
 *         @OA\Property(property="tgl_pelaksanaan", type="string", format="date", example="2023-01-01"),
 *         @OA\Property(property="tgl_akhir_jihad", type="string", format="date", example="2023-12-31")
 *     }
 * )
 */
class MasterJamaahModel extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = 't_master_jamaah';
    protected $primaryKey = 'id_master_jamaah';

    protected $fillable = [
        'nama_jamaah',
        'alamat',
        'aktif',
        'lokasi_map',
        'lokasi_lat',
        'lokasi_long',
        'id_otonom'
    ];

    protected $casts = [
        'aktif' => 'boolean',
        'lokasi_lat' => 'float',
        'lokasi_long' => 'float'
    ];


    public function anggota()
    {
        // Argumen kedua adalah foreign key di tabel t_anggota (anggota)
        // Argumen ketiga adalah local key di tabel t_master_jamaah (jamaah)
        return $this->hasMany(AnggotaModel::class, 'id_master_jamaah', 'id_master_jamaah');
    }

    public function pesantren()
    {
        return $this->hasMany(PesantrenModel::class, 'id_master_jamaah', 'id_master_jamaah');
    }

    public function iuran_porsi()
    {
        return $this->hasOne(IuranPorsiModel::class, 'id_jamiyyah', 'id_master_jamaah');
    }

    public function jamaah_fasilitas()
    {
        return $this->hasOne(JamaahFasilitasModel::class, 'id_master_jamaah', 'id_master_jamaah');
    }

    public function jamaah_monografi()
    {
        return $this->hasOne(JamaahMonografiModel::class, 'id_jamah', 'id_master_jamaah');
    }

    public function musyawarah()
    {
        return $this->hasMany(MusyawarahModel::class, 'id_master_jamaah', 'id_master_jamaah');
    }

    public function monografi(): HasOne
    {
        return $this->hasOne(JamaahMonografiModel::class, 'id_jamaah', 'id_master_jamaah');
    }

    public function jumlahPersis()
    {
        return AnggotaModel::where('id_master_jamaah', $this->id_master_jamaah)
            ->where('status_aktif', 1)
            ->count();
    }
}
