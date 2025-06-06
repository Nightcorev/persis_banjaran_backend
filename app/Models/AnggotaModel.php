<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnggotaModel extends Model
{
    use HasFactory;

    protected $table = 't_anggota';
    protected $primaryKey = 'id_anggota';
    public $timestamps = false;
    protected $fillable = [
        'niat',
        'nama_lengkap',
        'tempat_lahir',
        'tanggal_lahir',
        'status_merital',
        'no_telp',
        'alamat_ktp',
        'masa_aktif_anggota',
        'foto',
        'status_aktif',
        'keterangan',
        'id_otonom',
        'id_master_jamaah',
        'nomor_ktp',
        'tahun_masuk_anggota',
        'tahun_haji',
        'kajian_rutin',
        'no_wa',
        'alamat_tinggal',
    ];

    /**
     * Menunjukkan jika ID bukan auto-incrementing. (Biarkan false jika auto-increment)
     *
     * @var bool
     */
    public $incrementing = true; // Biasanya true untuk PK auto-increment

    /**
     * Tipe data primary key. (Biarkan 'int' jika integer)
     *
     * @var string
     */
    protected $keyType = 'int'; // Atau 'string' jika PK bukan integer

    public function master_jamaah()
    {
        return $this->belongsTo(MasterJamaahModel::class, 'id_master_jamaah', 'id_master_jamaah');
    }

    public function master_otonom()
    {
        return $this->belongsTo(MasterOtonomModel::class, 'id_otonom', 'id_otonom');
    }

    public function anggota_iuran()
    {
        return $this->hasOne(AnggotaIuranModel::class, 'id_anggota', 'id_anggota');
    }

    public function anggota_keluarga()
    {
        return $this->hasOne(AnggotaKeluargaModel::class, 'id_anggota', 'id_anggota');
    }

    public function anggota_keterampilan()
    {
        return $this->hasOne(AnggotaKeterampilanModel::class, 'id_anggota', 'id_anggota');
    }

    public function anggota_minat()
    {
        return $this->hasOne(AnggotaMinatModel::class, 'id_anggota', 'id_anggota');
    }

    public function anggota_mutasi()
    {
        return $this->hasOne(AnggotaMutasiModel::class, 'id_anggota', 'id_anggota');
    }

    public function anggota_organisasi()
    {
        return $this->hasOne(AnggotaOrganisasiModel::class, 'id_anggota', 'id_anggota');
    }

    public function anggota_pekerjaan()
    {
        return $this->hasOne(AnggotaPekerjaanModel::class, 'id_anggota', 'id_anggota');
    }

    public function anggota_pendidikan()
    {
        return $this->hasOne(AnggotaPendidikanModel::class, 'id_anggota', 'id_anggota');
    }

    public function anggota_training()
    {
        return $this->hasOne(AnggotaTrainingModel::class, 'id_anggota', 'id_anggota');
    }

    public function asatidz()
    {
        return $this->hasOne(AsatidzModel::class, 'id_anggota', 'id_anggota');
    }

    /**
     * Mendapatkan semua log iuran yang terkait dengan anggota ini.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function iuranLogs()
    {
        // Pastikan nama model IuranLog sudah benar
        // Argumen ketiga (local key) harus sama dengan $primaryKey model ini
        //return $this->hasMany(IuranLog::class, 'anggota_id', $this->getKeyName());
        return $this->hasMany(IuranLog::class, 'anggota_id', 'id_anggota');
        // atau hardcode jika yakin: return $this->hasMany(IuranLog::class, 'anggota_id', 'id_anggota');
    }


    public function master_pekerjaan()
    {
        return $this->hasOneThrough(
            'App\Models\MasterPekerjaanModel',
            'App\Models\AnggotaPekerjaanModel',
            'id_anggota', // Foreign key on AnggotaPekerjaanModel
            'id_master_pekerjaan', // Foreign key on MasterPekerjaanModel
            'id_anggota', // Local key on AnggotaModel
            'id_master_pekerjaan' // Local key on AnggotaPekerjaanModel
        );
    }
}
