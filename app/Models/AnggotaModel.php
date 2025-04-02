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
        'nik',
        'nama_lengkap',
        'tempat_lahir',
        'tanggal_lahir',
        'status_merital',
        'golongan_darah',
        'email',
        'no_telp',
        'alamat',
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

    public function iuran_log()
    {
        return $this->hasOne(IuranLogModel::class, 'id_anggota', 'id_anggota');
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

