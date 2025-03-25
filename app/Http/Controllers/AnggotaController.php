<?php

namespace App\Http\Controllers;

use App\Models\AnggotaKeterampilanModel;
use App\Models\AnggotaPekerjaanModel;
use App\Models\JamaahMonografiModel;
use App\Models\AnggotaPendidikanModel;
use App\Models\MasterJamaahModel;
use App\Models\MasterOtonomModel;
use Illuminate\Http\Request;
use App\Models\AnggotaModel;

class AnggotaController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('perPage', 10);
        $page = $request->input('page', 1);
        $searchTerm = $request->input('searchTerm', '');

        $query = AnggotaModel::select(
            't_anggota.id_anggota',
            't_anggota.nik',
            't_anggota.nama_lengkap',
            't_anggota.email',
            't_anggota.tanggal_lahir',
            't_master_jamaah.nama_jamaah',
            't_anggota.no_telp',
            't_anggota.foto',
            't_anggota.status_aktif',
            't_anggota.keterangan',
            't_tingkat_pendidikan.pendidikan',
            't_master_pekerjaan.nama_pekerjaan'
        )
            ->join('t_master_jamaah', 't_anggota.id_master_jamaah', '=', 't_master_jamaah.id_master_jamaah')
            ->leftJoin('t_anggota_pendidikan', 't_anggota.id_anggota', '=', 't_anggota_pendidikan.id_anggota')
            ->leftJoin('t_tingkat_pendidikan', 't_anggota_pendidikan.id_tingkat_pendidikan', '=', 't_tingkat_pendidikan.id_tingkat_pendidikan')
            ->leftJoin('t_anggota_pekerjaan', 't_anggota.id_anggota', '=', 't_anggota_pekerjaan.id_anggota')
            ->leftJoin('t_master_pekerjaan', 't_anggota_pekerjaan.id_master_pekerjaan', '=', 't_master_pekerjaan.id_master_pekerjaan')
            ->orderBy('t_master_jamaah.id_master_jamaah')
            ->orderBy('t_anggota.nama_lengkap', 'asc');

        if (!empty($searchTerm)) {
            $query->where('t_anggota.nama_lengkap', 'like', "%{$searchTerm}%");
        }

        $total = $query->count();
        $anggota = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json(['status' => 200, 'data' => $anggota], 200);
    }


    // Get single data
    public function show($id) {}

    // Create data
    public function store(Request $request) {}

    // Update data
    public function update(Request $request, $id) {}

    // Delete data
    public function destroy($id) {}

    public function statistik()
    {
        $jumlahPersis = AnggotaModel::where('status_aktif', 1)
            ->selectRaw('COUNT(id_anggota) AS jumlah')
            ->groupBy('id_otonom')
            ->orderBy('id_otonom', 'ASC')
            ->get()
            ->sum('jumlah');

        $jumlahLainnya = JamaahMonografiModel::selectRaw('SUM(jum_persistri) as jum_persistri, 
                        SUM(jum_pemuda) as jum_pemuda, SUM(jum_pemudi) as jum_pemudi')->first();

        $dataMonografi = $jumlahLainnya ? $jumlahLainnya->toArray() : [];
        $dataMonografi['jum_persis'] = $jumlahPersis;

        return response()->json([
            'status' => 200,
            'data_monografi' => $dataMonografi
        ], 200);
    }

    public function chart()
    {

        $dataPersisPerJamaah = MasterJamaahModel::select(
            't_master_jamaah.id_master_jamaah',
            't_master_jamaah.nama_jamaah'
        )
            ->withCount([
                'anggota as jum_persis' => function ($query) {
                    $query->where('id_otonom', 1)
                        ->where('status_aktif', 1);
                }
            ])
            ->orderBy('t_master_jamaah.id_master_jamaah')
            ->get()
            ->keyBy('id_master_jamaah');

        $dataAnggotaPerJamaah = MasterJamaahModel::select(
            't_master_jamaah.id_master_jamaah',
            't_master_jamaah.nama_jamaah',
            't_jamaah_monografi.jum_persistri',
            't_jamaah_monografi.jum_pemuda',
            't_jamaah_monografi.jum_pemudi'
        )
            ->leftJoin('t_jamaah_monografi', 't_master_jamaah.id_master_jamaah', '=', 't_jamaah_monografi.id_jamaah')
            ->orderBy('t_master_jamaah.id_master_jamaah')
            ->get()
            ->map(function ($item) use ($dataPersisPerJamaah) {
                $persisData = $dataPersisPerJamaah[$item->id_master_jamaah] ?? null;

                return [
                    'id_master_jamaah' => $item->id_master_jamaah,
                    'nama_jamaah' => $item->nama_jamaah,
                    'jum_persis' => $persisData ? $persisData->jum_persis : 0,
                    'jum_persistri' => $item->jum_persistri ?? 0,
                    'jum_pemuda' => $item->jum_pemuda ?? 0,
                    'jum_pemudi' => $item->jum_pemudi ?? 0,
                ];
            });



        $dataPendidikan = AnggotaPendidikanModel::with('tingkat_pendidikan')
            ->selectRaw('id_tingkat_pendidikan, COUNT(id_anggota) as jumlah_anggota')
            ->groupBy('id_tingkat_pendidikan')
            ->orderBy('id_tingkat_pendidikan')
            ->get()
            ->map(function ($item) {
                return [
                    'tingkat_pendidikan' => $item->tingkat_pendidikan->pendidikan ?? 'Tidak Diketahui',
                    'jumlah_anggota' => $item->jumlah_anggota
                ];
            });

        $dataPekerjaan = AnggotaPekerjaanModel::selectRaw('t_master_pekerjaan.nama_pekerjaan, COUNT(t_anggota_pekerjaan.id_anggota) as jumlah_anggota')
            ->join('t_master_pekerjaan', 't_anggota_pekerjaan.id_master_pekerjaan', '=', 't_master_pekerjaan.id_master_pekerjaan')
            ->groupBy('t_master_pekerjaan.nama_pekerjaan')
            ->orderBy('t_master_pekerjaan.nama_pekerjaan')
            ->get();

        $dataKeterampilan = AnggotaKeterampilanModel::selectRaw('t_master_minat.nama_minat, COUNT(t_anggota_keterampilan.id_anggota) as jumlah_anggota')
            ->join('t_master_minat', 't_anggota_keterampilan.id_minat', '=', 't_master_minat.id_minat')
            ->where('t_master_minat.nama_minat', '!=', 'Tidak Ada') // Mengecualikan 'Tidak Ada'
            ->groupBy('t_master_minat.nama_minat')
            ->orderBy('t_master_minat.nama_minat')
            ->get();

        $dataMubaligh = MasterJamaahModel::select(
            't_master_jamaah.id_master_jamaah',
            't_master_jamaah.nama_jamaah',
            't_jamaah_monografi.jum_mubaligh as jumlah_anggota'
        )
            ->leftJoin('t_jamaah_monografi', 't_master_jamaah.id_master_jamaah', '=', 't_jamaah_monografi.id_jamaah')
            ->orderBy('t_master_jamaah.nama_jamaah')
            ->get();



        return response()->json([
            'anggota' => $dataAnggotaPerJamaah,
            'pendidikan' => $dataPendidikan,
            'pekerjaan' => $dataPekerjaan,
            'keterampilan' => $dataKeterampilan,
            'mubaligh' => $dataMubaligh
        ], 200);
    }

    public function getChoiceDataPribadi()
    {
        $dataJamaah = MasterJamaahModel::select(
            'id_master_jamaah',
            'nama_jamaah'
        )->get();

        $dataOtonom = MasterOtonomModel::select(
            'id_otonom',
            'nama_otonom'
        )->get();

        return response()->json([
            'jamaah' => $dataJamaah,
            'otonom' => $dataOtonom
        ], 200);
    }

    public function selectAll(Request $request)
    {
        $searchTerm = $request->input('searchTerm', ''); // Kata kunci pencarian

        // Query untuk memuat data dengan kolom tertentu
        $query = AnggotaModel::select(
            't_anggota.id_anggota',
            't_anggota.nik',
            't_anggota.nama_lengkap',
            't_anggota.email'
        )->orderBy('t_anggota.nama_lengkap', 'asc');

        // Tambahkan filter pencarian jika ada
        if (!empty($searchTerm)) {
            $query->where('t_anggota.nama_lengkap', 'like', "%{$searchTerm}%");
        }

        // Ambil semua data tanpa paginasi
        $anggota = $query->get();

        // Kembalikan respons dalam format JSON
        return response()->json([
            'status' => 200,
            'data' => $anggota,
        ], 200);
    }
}
