<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;

class AnggotaModel
{
    // public function getAnggota()
    // {
    //     $sql = "SELECT t_anggota.id_anggota, nik, nama_lengkap, tanggal_lahir, nama_jamaah, no_telp, foto, status_aktif, keterangan, pendidikan, nama_pekerjaan
    //             FROM t_anggota
    //             INNER JOIN t_master_jamaah ON t_anggota.id_master_jamaah = t_master_jamaah.id_master_jamaah
    //             LEFT JOIN t_anggota_pendidikan ON t_anggota.id_anggota = t_anggota_pendidikan.id_anggota
    //             LEFT JOIN t_tingkat_pendidikan ON t_anggota_pendidikan.id_tingkat_pendidikan = t_tingkat_pendidikan.id_tingkat_pendidikan
    //             LEFT JOIN t_anggota_pekerjaan ON t_anggota.id_anggota = t_anggota_pekerjaan.id_anggota LEFT JOIN  t_master_pekerjaan ON t_anggota_pekerjaan.id_master_pekerjaan = t_master_pekerjaan.id_master_pekerjaan
    //             ORDER BY t_master_jamaah.id_master_jamaah, t_anggota.nama_lengkap ASC;";

    //     $result = DB::connection('pgsql')->select($sql);

    //     return $result;
    // }

    public function getAnggota($page, $perPage, $searchTerm = '')
    {
        $offset = ($page - 1) * $perPage;

        // Modifikasi query untuk menambahkan pencarian
        $sql = "SELECT t_anggota.id_anggota, nik, nama_lengkap, tanggal_lahir, nama_jamaah, no_telp, foto, status_aktif, keterangan, pendidikan, nama_pekerjaan
            FROM t_anggota
            INNER JOIN t_master_jamaah ON t_anggota.id_master_jamaah = t_master_jamaah.id_master_jamaah
            LEFT JOIN t_anggota_pendidikan ON t_anggota.id_anggota = t_anggota_pendidikan.id_anggota
            LEFT JOIN t_tingkat_pendidikan ON t_anggota_pendidikan.id_tingkat_pendidikan = t_tingkat_pendidikan.id_tingkat_pendidikan
            LEFT JOIN t_anggota_pekerjaan ON t_anggota.id_anggota = t_anggota_pekerjaan.id_anggota
            LEFT JOIN t_master_pekerjaan ON t_anggota_pekerjaan.id_master_pekerjaan = t_master_pekerjaan.id_master_pekerjaan
            WHERE (t_anggota.nama_lengkap ILIKE :searchTerm OR t_anggota.nik ILIKE :searchTerm)
            ORDER BY t_master_jamaah.id_master_jamaah, t_anggota.nama_lengkap ASC
            LIMIT :perPage OFFSET :offset";

        return DB::connection('pgsql')->select($sql, [
            'perPage' => $perPage,
            'offset' => $offset,
            'searchTerm' => "%{$searchTerm}%" // Menambahkan wildcard untuk pencarian
        ]);
    }

    public function getTotalAnggota($searchTerm = '')
    {
        // Modifikasi query untuk hitung total data berdasarkan pencarian
        $sql = "SELECT COUNT(*) AS total FROM t_anggota
            WHERE t_anggota.nama_lengkap ILIKE :searchTerm OR t_anggota.nik ILIKE :searchTerm";

        $result = DB::connection('pgsql')->select($sql, [
            'searchTerm' => "%{$searchTerm}%" // Menambahkan wildcard untuk pencarian
        ]);
        return $result[0]->total ?? 0;
    }
}

