<?php

namespace App\Http\Controllers;

use App\Models\AnggotaKeluargaModel;
use App\Models\AnggotaKeterampilanModel;
use App\Models\AnggotaMinatModel;
use App\Models\AnggotaPekerjaanModel;
use App\Models\JamaahMonografiModel;
use App\Models\AnggotaPendidikanModel;
use App\Models\MasterJamaahModel;
use App\Models\MasterOtonomModel;
use Illuminate\Http\Request;
use App\Models\AnggotaModel;
use Illuminate\Support\Facades\Log;
use App\Models\TingkatPendidikanModel;
use App\Models\MasterPekerjaanModel;
use App\Models\MasterKeterampilanModel;
use App\Models\MasterMinatModel;
use App\Models\AnggotaOrganisasiModel;

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
            't_master_pekerjaan.nama_pekerjaan',
        )
            ->join('t_master_jamaah', 't_anggota.id_master_jamaah', '=', 't_master_jamaah.id_master_jamaah')
            ->leftJoin('t_anggota_pendidikan', 't_anggota.id_anggota', '=', 't_anggota_pendidikan.id_anggota')
            ->leftJoin('t_tingkat_pendidikan', 't_anggota_pendidikan.id_tingkat_pendidikan', '=', 't_tingkat_pendidikan.id_tingkat_pendidikan')
            ->leftJoin('t_anggota_pekerjaan', 't_anggota.id_anggota', '=', 't_anggota_pekerjaan.id_anggota')
            ->leftJoin('t_master_pekerjaan', 't_anggota_pekerjaan.id_master_pekerjaan', '=', 't_master_pekerjaan.id_master_pekerjaan')
            ->orderBy('t_master_jamaah.id_master_jamaah')
            ->orderBy('t_anggota.id_anggota', 'desc');

        if (!empty($searchTerm)) {
            $query->where('t_anggota.nama_lengkap', 'like', "%{$searchTerm}%");
        }

        $total = $query->count();
        $anggota = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json(['status' => 200, 'data' => $anggota], 200);
    }


    // Get single data
    public function show($id)
    {
        // Validate that the ID is a valid integer
        if (!is_numeric($id) || (int) $id != $id) {
            return response()->json(['error' => 'Invalid ID'], 400);  // Return an error response
        }

        $anggota = AnggotaModel::findOrFail($id);

        $keluarga = AnggotaKeluargaModel::where('id_anggota', $anggota->id_anggota)->first();
        $pendidikan = AnggotaPendidikanModel::where('id_anggota', $anggota->id_anggota)->first();
        $pekerjaan = AnggotaPekerjaanModel::where('id_anggota', $anggota->id_anggota)->first();
        $keterampilan = AnggotaKeterampilanModel::where('id_anggota', $anggota->id_anggota)->first();
        $minat = AnggotaMinatModel::where('id_anggota', $anggota->id_anggota)->get();
        $organisasi = AnggotaOrganisasiModel::where('id_anggota', $anggota->id_anggota)->first();
        $jamaah = MasterJamaahModel::where('id_master_jamaah', $anggota->id_master_jamaah)->first();
        $otonom = MasterOtonomModel::where('id_otonom', $anggota->id_otonom)->first();
        $tingkatPendidikan = TingkatPendidikanModel::where('id_tingkat_pendidikan', $pendidikan->id_tingkat_pendidikan)->first();
        $masterPekerjaan = MasterPekerjaanModel::where('id_master_pekerjaan', $pekerjaan->id_master_pekerjaan)->first();
        $masterKeterampilan = MasterKeterampilanModel::where('id_master_keterampilan', $keterampilan->id_master_keterampilan)->first();
        $masterMinat = MasterMinatModel::whereIn('id_master_minat', $minat->pluck('id_master_minat'))->get();

        $statusMapping = [
            1 => 'Aktif',
            0 => 'Tidak Aktif',
            2 => 'Meninggal Dunia',
            3 => 'Mutasi'
        ];
        return response()->json([
            'personal' => [
                'nomorAnggota' => $anggota->nik ?? '-',
                'nomorKTP' => $anggota->nomor_ktp ?? '-',
                'namaLengkap' => $anggota->nama_lengkap ?? '-',
                'tempatLahir' => $anggota->tempat_lahir ?? '-',
                'tanggalLahir' => $anggota->tanggal_lahir ?? '-',
                'statusMerital' => $anggota->status_merital ?? '-',
                'nomorTelepon' => $anggota->no_telp ?? '-',
                'nomorWA' => $anggota->no_wa ?? '-',
                'alamat' => $anggota->alamat ?? '-',
                'alamatTinggal' => $anggota->alamat_tinggal ?? '-',
                'otonom' => $anggota->id_otonom ?? '-',
                'namaOtonom' => $otonom->nama_otonom ?? '-',
                'jamaah' => $anggota->id_master_jamaah ?? '-',
                'namaJamaah' => $jamaah->nama_jamaah ?? '-',
                'statusAktif' => $anggota->status_aktif ?? '-',
                'namaStatusAktif' => $statusMapping[$anggota->status_aktif] ?? '-',
                'tahunMasuk' => $anggota->tahun_masuk_anggota ?? '-',
                'masaAktif' => $anggota->masa_aktif_anggota ?? '-',
                'kajianRutin' => $anggota->kajian_rutin ?? '-',
                'tahunHaji' => $anggota->tahun_haji ?? '-',
                'keterangan' => $anggota->keterangan ?? '-',
            ],
            'family' => [
                'jumlahTanggungan' => $keluarga->jumlah_tanggungan ?? '-',
                'namaIstri' => $keluarga->nama_istri ?? '-',
                'anggotaPersistri' => $keluarga->anggota_persistri ?? '-',
                'statusKepemilikanRumah' => $keluarga->status_kepemilikan_rumah ?? '-',
                'jumlaSeluruhAnak' => $keluarga->jumlah_seluruh_anak ?? '-',
                'jumlaAnakPemuda' => $keluarga->jumlah_anak_pemuda ?? '-',
                'jumlaAnakPemudi' => $keluarga->jumlah_anak_pemudi ?? '-',
            ],
            'education' => [
                'tingkat' => $pendidikan->id_tingkat_pendidikan ?? '-',
                'namaTingkat' => $tingkatPendidikan->pendidikan ?? '-',
                'namaSekolah' => $pendidikan->instansi ?? '-',
                'jurusan' => $pendidikan->jurusan ?? '-',
                'tahunMasuk' => $pendidikan->tahun_masuk ?? '-',
                'tahunKeluar' => $pendidikan->tahun_keluar ?? '-',
                'jenisPendidikan' => $pendidikan->jenis_pendidikan ?? '-',
            ],
            'work' => [
                'pekerjaan' => $pekerjaan->id_master_pekerjaan ?? '-',
                'namaPekerjaan' => $masterPekerjaan->nama_pekerjaan ?? '-',
                'pekerjaanLainnya' => $pekerjaan->lainnya ?? '-',
                'namaInstansi' => $pekerjaan->nama_instasi ?? '-',
                'deskripsiPekerjaan' => $pekerjaan->deskripsi_pekerjaan ?? '-',
                'pendapatan' => $pekerjaan->pendapatan ?? '-',
            ],
            'skill' => [
                'keterampilan' => $keterampilan->id_master_keterampilan ?? '-',
                'namaKeterampilan' => $masterKeterampilan->nama_keterampilan ?? '-',
                'keterampilanLainnya' => $keterampilan->lainnya ?? '-',
                'deskripsiKeterampilan' => $keterampilan->deskripsi ?? '-',
            ],
            'interest' => $minat->map(function ($item) use ($masterMinat) {
                $namaMinat = $masterMinat->where('id_master_minat', $item->id_master_minat)->first();
                return [
                    'minat' => $namaMinat->nama_minat ?? '-',
                    'minatLainnya' => $item->lainnya ?? '-',
                ];
            }),

            'organization' => [
                'keterlibatanOrganisasi' => $organisasi->keterlibatan_organisasi ?? '-',
                'namaOrganisasi' => $organisasi->nama_organisasi ?? '-',
            ]
        ]);
    }



    // Create data
    public function store(Request $request)
    {
        Log::info('Data request:', $request->all());

        $dataPersonal = $request->input('personal');
        $anggota = AnggotaModel::create([
            'nik' => $dataPersonal['nomorAnggota'] ?? null,
            'nomor_ktp' => $dataPersonal['nomorKTP'] ?? null,
            'nama_lengkap' => $dataPersonal['namaLengkap'] ?? null,
            'tempat_lahir' => $dataPersonal['tempatLahir'] ?? null,
            'tanggal_lahir' => $dataPersonal['tanggalLahir'] ?? null,
            'status_merital' => $dataPersonal['statusMerital'] ?? null,
            // 'golongan_darah' => $dataPersonal['golonganDarah'] ?? null,
            // 'email' => $dataPersonal['email'] ?? null,
            'no_telp' => $dataPersonal['nomorTelepon'] ?? null,
            'no_wa' => $dataPersonal['nomorWA'] ?? null,
            'alamat' => $dataPersonal['alamat'] ?? null,
            'alamat_tinggal' => $dataPersonal['alamatTinggal'] ?? null,
            'id_otonom' => $dataPersonal['otonom'] ?? null,
            'id_master_jamaah' => $dataPersonal['jamaah'] ?? null,
            'status_aktif' => $dataPersonal['statusAktif'] ?? null,
            'tahun_masuk_anggota' => $dataPersonal['tahunMasuk'] ?? null,
            'masa_aktif_anggota' => $dataPersonal['masaAktif'] ?? null,
            'kajian_rutin' => $dataPersonal['kajianRutin'] ?? '',
            'tahun_haji' => $dataPersonal['tahunHaji'] ?? null,
            'keterangan' => $dataPersonal['keterangan'] ?? null,
        ]);

        $keluarga = $request->input('family');
        AnggotaKeluargaModel::create([
            'id_anggota' => $anggota->id_anggota ?? null,
            'jumlah_tanggungan' => $keluarga['jumlahTanggungan'],
            'nama_istri' => $keluarga['namaIstri'],
            'anggota_persistri' => $keluarga['anggotaPersistri'],
            'status_kepemilikan_rumah' => $keluarga['statusKepemilikanRumah'],
            'jumlah_seluruh_anak' => $keluarga['jumlaSeluruhAnak'],
            'jumlah_anak_pemuda' => $keluarga['jumlaAnakPemuda'],
            'jumlah_anak_pemudi' => $keluarga['jumlaAnakPemudi'],
        ]);

        $pendidikan = $request->input('education');
        AnggotaPendidikanModel::create([
            'id_anggota' => $anggota->id_anggota ?? null,
            'id_tingkat_pendidikan' => $pendidikan['tingkat'] ?? null,
            'instansi' => $pendidikan['namaSekolah'] ?? null,
            'jurusan' => $pendidikan['jurusan'] ?? null,
            'tahun_masuk' => $pendidikan['tahunMasuk'] ?? null,
            'tahun_keluar' => $pendidikan['tahunKeluar'] ?? null,
            'jenis_pendidikan' => $pendidikan['jenisPendidikan'] ?? null,
        ]);

        $pekerjaan = $request->input('work');
        AnggotaPekerjaanModel::create([
            'id_anggota' => $anggota->id_anggota ?? null,
            'id_master_pekerjaan' => $pekerjaan['pekerjaan'] ?? null,
            'lainnya' => $pekerjaan['pekerjaanLainnya'] ?? null,
            'nama_instasi' => $pekerjaan['namaInstansi'] ?? null,
            'deskripsi_pekerjaan' => $pekerjaan['deskripsiPekerjaan'] ?? null,
            'pendapatan' => $pekerjaan['pendapatan'] ?? null,
        ]);

        $keterampilan = $request->input('skill');
        AnggotaKeterampilanModel::create([
            'id_anggota' => $anggota->id_anggota ?? null,
            'id_master_keterampilan' => $keterampilan['keterampilan'] ?? null,
            'lainnya' => $keterampilan['keterampilanLainnya'] ?? null,
            'deskripsi' => $keterampilan['deskripsiKeterampilan'] ?? null,
        ]);

        foreach ($request->input('interest', []) as $minat) {
            $masterMinat = MasterMinatModel::where('nama_minat', $minat["minat"])->first();
            AnggotaMinatModel::create([
                'id_anggota' => $anggota->id_anggota ?? null,
                'id_master_minat' => $masterMinat->id_master_minat ?? null,
                'lainnya' => $minat['minatLainnya'] ?? null,
            ]);
        }

        $organisasi = $request->input('organization');
        AnggotaOrganisasiModel::create([
            'id_anggota' => $anggota->id_anggota ?? null,
            'keterlibatan_organisasi' => $organisasi['keterlibatanOrganisasi'] ?? null,
            'nama_organisasi' => $organisasi['namaOrganisasi'] ?? null,
        ]);

        return response()->json(['message' => 'Data berhasil disimpan'], 201);
    }

    // Update data
    public function update(Request $request, $id)
    {
        Log::info('Data request:', $request->all());

        // Cari anggota berdasarkan ID
        $anggota = AnggotaModel::findOrFail($id);

        // Update data personal
        $dataPersonal = $request->input('personal');
        $anggota->update([
            'nik' => $dataPersonal['nomorAnggota'] ?? $anggota->nik,
            'nomor_ktp' => $dataPersonal['nomorKTP'] ?? $anggota->nomor_ktp,
            'nama_lengkap' => $dataPersonal['namaLengkap'] ?? $anggota->nama_lengkap,
            'tempat_lahir' => $dataPersonal['tempatLahir'] ?? $anggota->tempat_lahir,
            'tanggal_lahir' => $dataPersonal['tanggalLahir'] ?? $anggota->tanggal_lahir,
            'status_merital' => $dataPersonal['statusMerital'] ?? $anggota->status_merital,
            'no_telp' => $dataPersonal['nomorTelepon'] ?? $anggota->no_telp,
            'no_wa' => $dataPersonal['nomorWA'] ?? $anggota->no_wa,
            'alamat' => $dataPersonal['alamat'] ?? $anggota->alamat,
            'alamat_tinggal' => $dataPersonal['alamatTinggal'] ?? $anggota->alamat_tinggal,
            'id_otonom' => $dataPersonal['otonom'] ?? $anggota->id_otonom,
            'id_master_jamaah' => $dataPersonal['jamaah'] ?? $anggota->id_master_jamaah,
            'status_aktif' => $dataPersonal['statusAktif'] ?? $anggota->status_aktif,
            'tahun_masuk_anggota' => $dataPersonal['tahunMasuk'] ?? $anggota->tahun_masuk_anggota,
            'masa_aktif_anggota' => $dataPersonal['masaAktif'] ?? $anggota->masa_aktif_anggota,
            'kajian_rutin' => $dataPersonal['kajianRutin'] ?? $anggota->kajian_rutin,
            'tahun_haji' => $dataPersonal['tahunHaji'] ?? $anggota->tahun_haji,
            'keterangan' => $dataPersonal['keterangan'] ?? $anggota->keterangan,
        ]);

        // Update atau insert data keluarga
        $keluarga = $request->input('family');
        AnggotaKeluargaModel::updateOrCreate(
            ['id_anggota' => $anggota->id_anggota],
            [
                'jumlah_tanggungan' => $keluarga['jumlahTanggungan'] ?? null,
                'nama_istri' => $keluarga['namaIstri'] ?? null,
                'anggota_persistri' => $keluarga['anggotaPersistri'] ?? null,
                'status_kepemilikan_rumah' => $keluarga['statusKepemilikanRumah'] ?? null,
                'jumlah_seluruh_anak' => $keluarga['jumlaSeluruhAnak'] ?? null,
                'jumlah_anak_pemuda' => $keluarga['jumlaAnakPemuda'] ?? null,
                'jumlah_anak_pemudi' => $keluarga['jumlaAnakPemudi'] ?? null,
            ]
        );

        // Update atau insert data pendidikan
        $pendidikan = $request->input('education');
        AnggotaPendidikanModel::updateOrCreate(
            ['id_anggota' => $anggota->id_anggota],
            [
                'id_tingkat_pendidikan' => $pendidikan['tingkat'] ?? null,
                'instansi' => $pendidikan['namaSekolah'] ?? null,
                'jurusan' => $pendidikan['jurusan'] ?? null,
                'tahun_masuk' => $pendidikan['tahunMasuk'] ?? null,
                'tahun_keluar' => $pendidikan['tahunKeluar'] ?? null,
                'jenis_pendidikan' => $pendidikan['jenisPendidikan'] ?? null,
            ]
        );

        // Update atau insert data pekerjaan
        $pekerjaan = $request->input('work');
        AnggotaPekerjaanModel::updateOrCreate(
            ['id_anggota' => $anggota->id_anggota],
            [
                'id_master_pekerjaan' => $pekerjaan['pekerjaan'] ?? null,
                'lainnya' => $pekerjaan['pekerjaanLainnya'] ?? null,
                'nama_instasi' => $pekerjaan['namaInstansi'] ?? null,
                'deskripsi_pekerjaan' => $pekerjaan['deskripsiPekerjaan'] ?? null,
                'pendapatan' => $pekerjaan['pendapatan'] ?? null,
            ]
        );

        // Update atau insert data keterampilan
        $keterampilan = $request->input('skill');
        AnggotaKeterampilanModel::updateOrCreate(
            ['id_anggota' => $anggota->id_anggota],
            [
                'id_master_keterampilan' => $keterampilan['keterampilan'] ?? null,
                'lainnya' => $keterampilan['keterampilanLainnya'] ?? null,
                'deskripsi' => $keterampilan['deskripsiKeterampilan'] ?? null,
            ]
        );

        // Update atau insert data minat
        AnggotaMinatModel::where('id_anggota', $anggota->id_anggota)->delete(); // Hapus semua minat lama
        foreach ($request->input('interest', []) as $minat) {
            $masterMinat = MasterMinatModel::where('nama_minat', $minat["minat"])->first();
            AnggotaMinatModel::create([
                'id_anggota' => $anggota->id_anggota,
                'id_master_minat' => $masterMinat->id_master_minat ?? null,
                'lainnya' => $minat['minatLainnya'] ?? null,
            ]);
        }

        // Update atau insert data organisasi
        $organisasi = $request->input('organization');
        AnggotaOrganisasiModel::updateOrCreate(
            ['id_anggota' => $anggota->id_anggota],
            [
                'keterlibatan_organisasi' => $organisasi['keterlibatanOrganisasi'] ?? null,
                'nama_organisasi' => $organisasi['namaOrganisasi'] ?? null,
            ]
        );

        return response()->json(['message' => 'Data berhasil diperbarui'], 200);
    }

    // Delete data
    public function destroy($id)
    {
        try {
            // Cari anggota berdasarkan ID
            $anggota = AnggotaModel::findOrFail($id);

            // Hapus semua data terkait berdasarkan id_anggota
            AnggotaKeluargaModel::where('id_anggota', $anggota->id_anggota)->delete();
            AnggotaPendidikanModel::where('id_anggota', $anggota->id_anggota)->delete();
            AnggotaPekerjaanModel::where('id_anggota', $anggota->id_anggota)->delete();
            AnggotaKeterampilanModel::where('id_anggota', $anggota->id_anggota)->delete();
            AnggotaMinatModel::where('id_anggota', $anggota->id_anggota)->delete();
            AnggotaOrganisasiModel::where('id_anggota', $anggota->id_anggota)->delete();

            // Hapus data anggota utama
            $anggota->delete();

            Log::info("Data anggota dengan ID $id berhasil dihapus.");

            return response()->json(['message' => 'Data berhasil dihapus'], 200);
        } catch (\Exception $e) {
            Log::error("Gagal menghapus data anggota dengan ID $id. Error: " . $e->getMessage());

            return response()->json(['message' => 'Terjadi kesalahan saat menghapus data'], 500);
        }
    }

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

    public function indexByJamaah(Request $request, $id_master_jamaah = null)
    {
        $perPage = $request->input('perPage', 5);
        $page = $request->input('page', 1);
        $searchTerm = $request->input('searchTerm', '');

        $query = AnggotaModel::with([
            'master_jamaah',
            'anggota_pendidikan',
            'anggota_pekerjaan.master_pekerjaan' // This uses dot notation for nested relationships
        ])
            ->when($id_master_jamaah, function ($query, $id_master_jamaah) {
                return $query->where('id_master_jamaah', $id_master_jamaah);
            })
            ->when($searchTerm, function ($query, $searchTerm) {
                return $query->where('nama_lengkap', 'like', "%{$searchTerm}%");
            })
            ->orderBy('id_master_jamaah', 'asc');

        if (!empty($searchTerm)) {
            $query->where('nama_lengkap', 'like', "%{$searchTerm}%");
        }

        $anggota = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json(['status' => 200, 'data' => $anggota], 200);
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
    public function getChoiceDataPendidikan()
    {
        $dataPendidikan = TingkatPendidikanModel::select(
            'id_tingkat_pendidikan',
            'pendidikan'
        )->get();

        return response()->json([
            'pendidikan' => $dataPendidikan,
        ], 200);
    }

    public function getChoiceDataPekerjaan()
    {
        $dataPekerjaan = MasterPekerjaanModel::select(
            'id_master_pekerjaan',
            'nama_pekerjaan'
        )->get();

        return response()->json([
            'pekerjaan' => $dataPekerjaan
        ], 200);
    }

    public function getChoiceDataKeterampilan()
    {
        $dataKeterampilan = MasterKeterampilanModel::select(
            'id_master_keterampilan',
            'nama_keterampilan'
        )->get();

        return response()->json([
            'keterampilan' => $dataKeterampilan
        ], 200);
    }

    public function getChoiceDataMinat()
    {
        $dataMinat = MasterMinatModel::select(
            'id_master_minat',
            'nama_minat'
        )->get();

        return response()->json([
            'minat' => $dataMinat
        ], 200);
    }

    public function mapsMonographic()
    {
        $anggota = MasterJamaahModel::select('nama_jamaah', 'lokasi_lat', 'lokasi_long')
            ->whereNotNull('lokasi_lat')
            ->whereNotNull('lokasi_long')
            ->get();


        return response()->json(['status' => 200, 'data' => $anggota], 200);
    }

    public function pilihanDataAdvancedStatistic()
    {

        $dataPendidikan = TingkatPendidikanModel::select(
            'id_tingkat_pendidikan',
            'pendidikan'
        )->get();

        $dataPekerjaan = MasterPekerjaanModel::select(
            'id_master_pekerjaan',
            'nama_pekerjaan'
        )->get();

        $dataKeahlian = MasterMinatModel::select(
            'id_minat',
            'nama_minat'
        )->get();

        return response()->json([
            'pendidikan' => $dataPendidikan,
            'pekerjaan' => $dataPekerjaan,
            'keahlian' => $dataKeahlian
        ], 200);

    }

    public function advancedStatistic(Request $request)
    {
        $pendidikan = $request->input('pendidikan');
        $pekerjaan = $request->input('pekerjaan');
        $keahlian = $request->input('keahlian');

        $query = AnggotaModel::query();

        if (!empty($pendidikan)) {
            $query->whereHas('anggota_pendidikan', function ($q) use ($pendidikan) {
                $q->where('id_tingkat_pendidikan', $pendidikan);
            });
        }

        if (!empty($pekerjaan)) {
            $query->whereHas('anggota_pekerjaan', function ($q) use ($pekerjaan) {
                $q->where('id_master_pekerjaan', $pekerjaan);
            });
        }

        if (!empty($keahlian)) {
            $query->whereHas('anggota_keterampilan', function ($q) use ($keahlian) {
                $q->where('id_minat', $keahlian);
            });
        }

        $result = $query->join('t_master_jamaah as mj', 't_anggota.id_master_jamaah', '=', 'mj.id_master_jamaah')
            ->selectRaw('mj.nama_jamaah, COUNT(t_anggota.id_anggota) as jumlah_anggota')
            ->groupBy('mj.nama_jamaah')
            ->orderByDesc('jumlah_anggota')
            ->get();

        return response()->json($result, 200);
    }
}
