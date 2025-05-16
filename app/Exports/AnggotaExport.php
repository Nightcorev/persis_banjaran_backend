<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Support\Collection;

class AnggotaExport implements FromCollection, WithHeadings, WithMapping
{
    protected $anggotaData;
    protected $selectedColumns;
    protected $columnMappings;

    public function __construct($anggotaData, $selectedColumns)
    {
        $this->anggotaData = $anggotaData;
        $this->selectedColumns = $selectedColumns;
        $this->columnMappings = $this->getColumnMappings();
    }

    public function collection()
    {
        return collect($this->anggotaData);
    }

    public function headings(): array
    {
        $headings = [];
        
        foreach ($this->selectedColumns as $column) {
            if (isset($this->columnMappings[$column])) {
                $headings[] = $this->columnMappings[$column]['title'];
            }
        }
        
        return $headings;
    }

    public function map($anggota): array
    {
        $row = [];
        
        foreach ($this->selectedColumns as $column) {
            if (isset($this->columnMappings[$column])) {
                $path = $this->columnMappings[$column]['path'];
                $value = $this->getValueFromPath($anggota, $path);
                $row[] = $value;
            }
        }
        
        return $row;
    }

    private function getValueFromPath($data, $path)
    {
        $keys = explode('.', $path);
        $value = $data;
        
        foreach ($keys as $key) {
            if (is_array($value) && array_key_exists($key, $value)) {
                $value = $value[$key];
            } else {
                return null;
            }
        }
        
        return $value;
    }

    private function getColumnMappings()
    {
        return [
            'nik' => ['title' => 'NIK', 'path' => 'personal.nomorAnggota'],
            'nomor_ktp' => ['title' => 'Nomor KTP', 'path' => 'personal.nomorKTP'],
            'nama_lengkap' => ['title' => 'Nama Lengkap', 'path' => 'personal.namaLengkap'],
            'tempat_lahir' => ['title' => 'Tempat Lahir', 'path' => 'personal.tempatLahir'],
            'tanggal_lahir' => ['title' => 'Tanggal Lahir', 'path' => 'personal.tanggalLahir'],
            'status_merital' => ['title' => 'Status Marital', 'path' => 'personal.statusMerital'],
            'no_telp' => ['title' => 'Nomor Telepon', 'path' => 'personal.nomorTelepon'],
            'no_wa' => ['title' => 'Nomor WhatsApp', 'path' => 'personal.nomorWA'],
            'alamat' => ['title' => 'Alamat', 'path' => 'personal.alamat'],
            'alamat_tinggal' => ['title' => 'Alamat Tinggal', 'path' => 'personal.alamatTinggal'],
            'nama_otonom' => ['title' => 'Otonom', 'path' => 'personal.namaOtonom'],
            'nama_jamaah' => ['title' => 'Jamaah', 'path' => 'personal.namaJamaah'],
            'status_aktif' => ['title' => 'Status Aktif', 'path' => 'personal.namaStatusAktif'],
            'tahun_masuk' => ['title' => 'Tahun Masuk', 'path' => 'personal.tahunMasuk'],
            'masa_aktif' => ['title' => 'Masa Aktif', 'path' => 'personal.masaAktif'],
            'kajian_rutin' => ['title' => 'Kajian Rutin', 'path' => 'personal.kajianRutin'],
            'tahun_haji' => ['title' => 'Tahun Haji', 'path' => 'personal.tahunHaji'],
            'keterangan' => ['title' => 'Keterangan', 'path' => 'personal.keterangan'],
            
            // Family
            'jumlah_tanggungan' => ['title' => 'Jumlah Tanggungan', 'path' => 'family.jumlahTanggungan'],
            'nama_istri' => ['title' => 'Nama Istri', 'path' => 'family.namaIstri'],
            'anggota_persistri' => ['title' => 'Anggota Persistri', 'path' => 'family.anggotaPersistri'],
            'status_kepemilikan_rumah' => ['title' => 'Status Kepemilikan Rumah', 'path' => 'family.statusKepemilikanRumah'],
            'jumlah_seluruh_anak' => ['title' => 'Jumlah Seluruh Anak', 'path' => 'family.jumlaSeluruhAnak'],
            'jumlah_anak_pemuda' => ['title' => 'Jumlah Anak Pemuda', 'path' => 'family.jumlaAnakPemuda'],
            'jumlah_anak_pemudi' => ['title' => 'Jumlah Anak Pemudi', 'path' => 'family.jumlaAnakPemudi'],
            
            // Education
            'tingkat_pendidikan' => ['title' => 'Tingkat Pendidikan', 'path' => 'education.namaTingkat'],
            'nama_sekolah' => ['title' => 'Nama Sekolah', 'path' => 'education.namaSekolah'],
            'jurusan' => ['title' => 'Jurusan', 'path' => 'education.jurusan'],
            'tahun_masuk_pendidikan' => ['title' => 'Tahun Masuk Pendidikan', 'path' => 'education.tahunMasuk'],
            'tahun_keluar' => ['title' => 'Tahun Keluar', 'path' => 'education.tahunKeluar'],
            'jenis_pendidikan' => ['title' => 'Jenis Pendidikan', 'path' => 'education.jenisPendidikan'],
            
            // Work
            'nama_pekerjaan' => ['title' => 'Pekerjaan', 'path' => 'work.namaPekerjaan'],
            'pekerjaan_lainnya' => ['title' => 'Pekerjaan Lainnya', 'path' => 'work.pekerjaanLainnya'],
            'nama_instansi' => ['title' => 'Nama Instansi', 'path' => 'work.namaInstansi'],
            'deskripsi_pekerjaan' => ['title' => 'Deskripsi Pekerjaan', 'path' => 'work.deskripsiPekerjaan'],
            'pendapatan' => ['title' => 'Pendapatan', 'path' => 'work.pendapatan'],
            
            // Skill
            'nama_keterampilan' => ['title' => 'Keterampilan', 'path' => 'skill.namaKeterampilan'],
            'keterampilan_lainnya' => ['title' => 'Keterampilan Lainnya', 'path' => 'skill.keterampilanLainnya'],
            'deskripsi_keterampilan' => ['title' => 'Deskripsi Keterampilan', 'path' => 'skill.deskripsiKeterampilan'],
            
            // Organization
            'keterlibatan_organisasi' => ['title' => 'Keterlibatan Organisasi', 'path' => 'organization.keterlibatanOrganisasi'],
            'nama_organisasi' => ['title' => 'Nama Organisasi', 'path' => 'organization.namaOrganisasi'],
        ];
    }
}