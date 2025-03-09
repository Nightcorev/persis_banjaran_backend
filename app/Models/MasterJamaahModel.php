<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
    protected $table = 't_master_jamaah';
    protected $primaryKey = 'id_master_jamaah';
    protected $fillable = [
        'nama_jamaah',
        'tgl_pelaksanaan',
        'tgl_akhir_jihad'
    ];

    public function anggota()
    {
        return $this->hasOne(AnggotaModel::class, 'id_master_jamaah', 'id_master_jamaah');
    }

    public function pesantren()
    {
        return $this->hasOne(PesantrenModel::class, 'id_master_jamaah', 'id_master_jamaah');
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
        return $this->hasOne(MusyawarahModel::class, 'id_musyawarah', 'id_master_jamaah');
    }

    public function musyawarahDetail()
    {
        return $this->hasOneThrough(
            MusyawarahDetailModel::class,
            MusyawarahModel::class,
            'id_master_jamaah', // Foreign key di `t_musyawarah`
            'id_musyawarah', // Foreign key di `t_musyawarah_detail`
            'id_master_jamaah', // Primary key di `t_master_jamaah`
            'id_musyawarah' // Primary key di `t_musyawarah`
        );
    }

    public function monografi(): HasOne
    {
        return $this->hasOne(JamaahMonografiModel::class, 'id_jamaah', 'id_master_jamaah');
    }

}

