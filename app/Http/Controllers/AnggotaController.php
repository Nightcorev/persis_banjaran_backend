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
use illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class AnggotaController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/anggota",
     *     summary="Ambil daftar anggota dengan pagination dan pencarian nama",
     *     tags={"Anggota"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="perPage",
     *         in="query",
     *         required=false,
     *         description="Jumlah data per halaman (default: 10)",
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Nomor halaman (default: 1)",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="searchTerm",
     *         in="query",
     *         required=false,
     *         description="Pencarian berdasarkan nama lengkap anggota",
     *         @OA\Schema(type="string", example="Ahmad")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Daftar anggota berhasil diambil",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=200),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="last_page", type="integer", example=5),
     *                 @OA\Property(property="per_page", type="integer", example=10),
     *                 @OA\Property(property="total", type="integer", example=45),
     *                 @OA\Property(
     *                     property="data",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id_anggota", type="integer", example=1),
     *                         @OA\Property(property="nik", type="string", example="3201234567890001"),
     *                         @OA\Property(property="nama_lengkap", type="string", example="Ahmad Fauzi"),
     *                         @OA\Property(property="email", type="string", example="ahmad@example.com"),
     *                         @OA\Property(property="tanggal_lahir", type="string", format="date", example="1990-01-01"),
     *                         @OA\Property(property="nama_jamaah", type="string", example="Persis Banjaran"),
     *                         @OA\Property(property="no_telp", type="string", example="082112345678"),
     *                         @OA\Property(property="foto", type="string", example="foto.jpg"),
     *                         @OA\Property(property="status_aktif", type="string", example="Aktif"),
     *                         @OA\Property(property="keterangan", type="string", example="Ketua Jamaah"),
     *                         @OA\Property(property="pendidikan", type="string", example="S1"),
     *                         @OA\Property(property="nama_pekerjaan", type="string", example="PNS")
     *                     )
     *                 )
     *             )
     *         )
     *     )
     * )
     */

    public function index(Request $request)
    {
        $perPage = $request->input('perPage', 10);
        $page = $request->input('page', 1);
        $searchTerm = $request->input('searchTerm', '');

        $query = AnggotaModel::select(
            't_anggota.id_anggota',
            't_anggota.foto',
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
            DB::raw("CASE 
                    WHEN t_master_pekerjaan.nama_pekerjaan = 'Lainnya' 
                    THEN t_anggota_pekerjaan.lainnya 
                    ELSE t_master_pekerjaan.nama_pekerjaan 
                 END AS nama_pekerjaan")
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
    /**
     * @OA\Get(
     *     path="/api/anggota/{id}",
     *     summary="Menampilkan detail lengkap anggota berdasarkan ID",
     *     tags={"Anggota"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID anggota",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detail anggota ditemukan",
     *         @OA\JsonContent(
     *             @OA\Property(property="personal", type="object",
     *                 @OA\Property(property="nomorAnggota", type="string", example="3201234567890001"),
     *                 @OA\Property(property="nomorKTP", type="string", example="3201234567890001"),
     *                 @OA\Property(property="namaLengkap", type="string", example="Ahmad Fauzi"),
     *                 @OA\Property(property="tempatLahir", type="string", example="Bandung"),
     *                 @OA\Property(property="tanggalLahir", type="string", example="1990-01-01"),
     *                 @OA\Property(property="statusMerital", type="string", example="Menikah"),
     *                 @OA\Property(property="nomorTelepon", type="string", example="081234567890"),
     *                 @OA\Property(property="nomorWA", type="string", example="081234567890"),
     *                 @OA\Property(property="alamat", type="string", example="Jl. ABC No. 123"),
     *                 @OA\Property(property="alamatTinggal", type="string", example="Jl. DEF No. 456"),
     *                 @OA\Property(property="otonom", type="integer", example=2),
     *                 @OA\Property(property="namaOtonom", type="string", example="Pemuda"),
     *                 @OA\Property(property="jamaah", type="integer", example=3),
     *                 @OA\Property(property="namaJamaah", type="string", example="Persis Banjaran"),
     *                 @OA\Property(property="statusAktif", type="integer", example=1),
     *                 @OA\Property(property="namaStatusAktif", type="string", example="Aktif"),
     *                 @OA\Property(property="tahunMasuk", type="string", example="2015"),
     *                 @OA\Property(property="masaAktif", type="string", example="10 Tahun"),
     *                 @OA\Property(property="kajianRutin", type="string", example="Ya"),
     *                 @OA\Property(property="tahunHaji", type="string", example="2019"),
     *                 @OA\Property(property="keterangan", type="string", example="Pengurus Cabang")
     *             ),
     *             @OA\Property(property="family", type="object",
     *                 @OA\Property(property="jumlahTanggungan", type="integer", example=3),
     *                 @OA\Property(property="namaIstri", type="string", example="Siti Aminah"),
     *                 @OA\Property(property="anggotaPersistri", type="string", example="Ya"),
     *                 @OA\Property(property="statusKepemilikanRumah", type="string", example="Pribadi"),
     *                 @OA\Property(property="jumlaSeluruhAnak", type="integer", example=2),
     *                 @OA\Property(property="jumlaAnakPemuda", type="integer", example=1),
     *                 @OA\Property(property="jumlaAnakPemudi", type="integer", example=1)
     *             ),
     *             @OA\Property(property="education", type="object",
     *                 @OA\Property(property="tingkat", type="integer", example=4),
     *                 @OA\Property(property="namaTingkat", type="string", example="S1"),
     *                 @OA\Property(property="namaSekolah", type="string", example="Universitas ABC"),
     *                 @OA\Property(property="jurusan", type="string", example="Teknik Informatika"),
     *                 @OA\Property(property="tahunMasuk", type="string", example="2010"),
     *                 @OA\Property(property="tahunKeluar", type="string", example="2014"),
     *                 @OA\Property(property="jenisPendidikan", type="string", example="Formal")
     *             ),
     *             @OA\Property(property="work", type="object",
     *                 @OA\Property(property="pekerjaan", type="integer", example=5),
     *                 @OA\Property(property="namaPekerjaan", type="string", example="Guru"),
     *                 @OA\Property(property="pekerjaanLainnya", type="string", example="Dosen"),
     *                 @OA\Property(property="namaInstansi", type="string", example="Universitas XYZ"),
     *                 @OA\Property(property="deskripsiPekerjaan", type="string", example="Mengajar mahasiswa"),
     *                 @OA\Property(property="pendapatan", type="string", example="Rp5.000.000")
     *             ),
     *             @OA\Property(property="skill", type="object",
     *                 @OA\Property(property="keterampilan", type="integer", example=3),
     *                 @OA\Property(property="namaKeterampilan", type="string", example="Desain Grafis"),
     *                 @OA\Property(property="keterampilanLainnya", type="string", example="Animasi"),
     *                 @OA\Property(property="deskripsiKeterampilan", type="string", example="Menguasai Adobe Illustrator")
     *             ),
     *             @OA\Property(property="interest", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="minat", type="string", example="Olahraga"),
     *                     @OA\Property(property="minatLainnya", type="string", example="Basket")
     *                 )
     *             ),
     *             @OA\Property(property="organization", type="object",
     *                 @OA\Property(property="keterlibatanOrganisasi", type="string", example="Aktif"),
     *                 @OA\Property(property="namaOrganisasi", type="string", example="Pemuda Persis")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="ID tidak valid",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Invalid ID")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Data anggota tidak ditemukan"
     *     )
     * )
     */

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
                'fotoURL' => $anggota->foto
                    ? "http://localhost:8000/storage/uploads/{$anggota->foto}"
                    : "http://localhost:8000/storage/uploads/persis_default.jpeg",
                'nomorAnggota' => $anggota->nik ?? null,
                'nomorKTP' => $anggota->nomor_ktp ?? null,
                'namaLengkap' => $anggota->nama_lengkap ?? null,
                'tempatLahir' => $anggota->tempat_lahir ?? null,
                'tanggalLahir' => $anggota->tanggal_lahir ?? null,
                'statusMerital' => $anggota->status_merital ?? null,
                'nomorTelepon' => $anggota->no_telp ?? null,
                'nomorWA' => $anggota->no_wa ?? null,
                'alamat' => $anggota->alamat ?? null,
                'alamatTinggal' => $anggota->alamat_tinggal ?? null,
                'otonom' => $anggota->id_otonom ?? null,
                'namaOtonom' => $otonom->nama_otonom ?? null,
                'jamaah' => $anggota->id_master_jamaah ?? null,
                'namaJamaah' => $jamaah->nama_jamaah ?? null,
                'statusAktif' => $anggota->status_aktif ?? null,
                'namaStatusAktif' => $statusMapping[$anggota->status_aktif] ?? null,
                'tahunMasuk' => $anggota->tahun_masuk_anggota ?? null,
                'masaAktif' => $anggota->masa_aktif_anggota ?? null,
                'kajianRutin' => $anggota->kajian_rutin ?? null,
                'tahunHaji' => $anggota->tahun_haji ?? null,
                'keterangan' => $anggota->keterangan ?? null,
            ],
            'family' => [
                'jumlahTanggungan' => $keluarga->jumlah_tanggungan ?? null,
                'namaIstri' => $keluarga->nama_istri ?? null,
                'anggotaPersistri' => $keluarga->anggota_persistri ?? null,
                'statusKepemilikanRumah' => $keluarga->status_kepemilikan_rumah ?? null,
                'jumlaSeluruhAnak' => $keluarga->jumlah_seluruh_anak ?? null,
                'jumlaAnakPemuda' => $keluarga->jumlah_anak_pemuda ?? null,
                'jumlaAnakPemudi' => $keluarga->jumlah_anak_pemudi ?? null,
            ],
            'education' => [
                'tingkat' => $pendidikan->id_tingkat_pendidikan ?? null,
                'namaTingkat' => $tingkatPendidikan->pendidikan ?? null,
                'namaSekolah' => $pendidikan->instansi ?? null,
                'jurusan' => $pendidikan->jurusan ?? null,
                'tahunMasuk' => $pendidikan->tahun_masuk ?? null,
                'tahunKeluar' => $pendidikan->tahun_keluar ?? null,
                'jenisPendidikan' => $pendidikan->jenis_pendidikan ?? null,
            ],
            'work' => [
                'pekerjaan' => $pekerjaan->id_master_pekerjaan ?? null,
                'namaPekerjaan' => $masterPekerjaan->nama_pekerjaan ?? null,
                'pekerjaanLainnya' => $pekerjaan->lainnya ?? null,
                'namaInstansi' => $pekerjaan->nama_instasi ?? null,
                'deskripsiPekerjaan' => $pekerjaan->deskripsi_pekerjaan ?? null,
                'pendapatan' => $pekerjaan->pendapatan ?? null,
            ],
            'skill' => [
                'keterampilan' => $keterampilan->id_master_keterampilan ?? null,
                'namaKeterampilan' => $masterKeterampilan->nama_keterampilan ?? null,
                'keterampilanLainnya' => $keterampilan->lainnya ?? null,
                'deskripsiKeterampilan' => $keterampilan->deskripsi ?? null,
            ],
            'interest' => $minat->map(function ($item) use ($masterMinat) {
                $namaMinat = $masterMinat->where('id_master_minat', $item->id_master_minat)->first();
                return [
                    'minat' => $namaMinat->nama_minat ?? null,
                    'minatLainnya' => $item->lainnya ?? null,
                ];
            }),

            'organization' => [
                'keterlibatanOrganisasi' => $organisasi->keterlibatan_organisasi ?? null,
                'namaOrganisasi' => $organisasi->nama_organisasi ?? null,
            ]
        ]);
    }


    /**
     * @OA\Post(
     *     path="/api/anggota",
     *     summary="Menyimpan data anggota beserta relasi lengkap",
     *     tags={"Anggota"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="personal", type="object",
     *                 @OA\Property(property="nomorAnggota", type="string"),
     *                 @OA\Property(property="nomorKTP", type="string"),
     *                 @OA\Property(property="namaLengkap", type="string"),
     *                 @OA\Property(property="tempatLahir", type="string"),
     *                 @OA\Property(property="tanggalLahir", type="string", format="date"),
     *                 @OA\Property(property="statusMerital", type="string"),
     *                 @OA\Property(property="nomorTelepon", type="string"),
     *                 @OA\Property(property="nomorWA", type="string"),
     *                 @OA\Property(property="alamat", type="string"),
     *                 @OA\Property(property="alamatTinggal", type="string"),
     *                 @OA\Property(property="otonom", type="integer"),
     *                 @OA\Property(property="jamaah", type="integer"),
     *                 @OA\Property(property="statusAktif", type="string"),
     *                 @OA\Property(property="tahunMasuk", type="integer"),
     *                 @OA\Property(property="masaAktif", type="integer"),
     *                 @OA\Property(property="kajianRutin", type="string"),
     *                 @OA\Property(property="tahunHaji", type="integer"),
     *                 @OA\Property(property="keterangan", type="string"),
     *             ),
     *             @OA\Property(property="family", type="object",
     *                 @OA\Property(property="jumlahTanggungan", type="integer"),
     *                 @OA\Property(property="namaIstri", type="string"),
     *                 @OA\Property(property="anggotaPersistri", type="boolean"),
     *                 @OA\Property(property="statusKepemilikanRumah", type="string"),
     *                 @OA\Property(property="jumlaSeluruhAnak", type="integer"),
     *                 @OA\Property(property="jumlaAnakPemuda", type="integer"),
     *                 @OA\Property(property="jumlaAnakPemudi", type="integer"),
     *             ),
     *             @OA\Property(property="education", type="object",
     *                 @OA\Property(property="tingkat", type="integer"),
     *                 @OA\Property(property="namaSekolah", type="string"),
     *                 @OA\Property(property="jurusan", type="string"),
     *                 @OA\Property(property="tahunMasuk", type="integer"),
     *                 @OA\Property(property="tahunKeluar", type="integer"),
     *                 @OA\Property(property="jenisPendidikan", type="string"),
     *             ),
     *             @OA\Property(property="work", type="object",
     *                 @OA\Property(property="pekerjaan", type="integer"),
     *                 @OA\Property(property="pekerjaanLainnya", type="string"),
     *                 @OA\Property(property="namaInstansi", type="string"),
     *                 @OA\Property(property="deskripsiPekerjaan", type="string"),
     *                 @OA\Property(property="pendapatan", type="integer"),
     *             ),
     *             @OA\Property(property="skill", type="object",
     *                 @OA\Property(property="keterampilan", type="integer"),
     *                 @OA\Property(property="keterampilanLainnya", type="string"),
     *                 @OA\Property(property="deskripsiKeterampilan", type="string"),
     *             ),
     *             @OA\Property(property="interest", type="array",
     *                 @OA\Items(type="object",
     *                     @OA\Property(property="minat", type="string"),
     *                     @OA\Property(property="minatLainnya", type="string"),
     *                 )
     *             ),
     *             @OA\Property(property="organization", type="object",
     *                 @OA\Property(property="keterlibatanOrganisasi", type="string"),
     *                 @OA\Property(property="namaOrganisasi", type="string"),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Data berhasil disimpan",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Data berhasil disimpan")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error"
     *     )
     * )
     */

    // Create data
    public function store(Request $request)
    {
        Log::info('Data request:', $request->all());

        $dataPersonal = $request->input('personal');

        $encryptedName = null;

        if (!empty($dataPersonal['fotoURL'])) {
            $fullUrl = $dataPersonal['fotoURL'];
            $relativePath = str_replace("http://localhost:8000/storage/uploads/", "", $fullUrl);

            $fileName = basename($relativePath);
            $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION) ?: 'png';
            $encryptedName = Str::random(10) . '.' . $fileExtension;

            $oldPath = public_path("storage/uploads/" . $relativePath);
            $newPath = public_path("storage/uploads/" . $encryptedName);

            Log::info("Old Path: " . $oldPath);
            Log::info("New Path: " . $newPath);

            if (file_exists($oldPath)) {
                rename($oldPath, $newPath);
                Log::info("File berhasil dipindahkan.");
            } else {
                Log::error("File tidak ditemukan di path: " . $oldPath);
            }
        }

        $anggota = AnggotaModel::create([
            'foto' => $encryptedName ?? null,
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
            'kajian_rutin' => $dataPersonal['kajianRutin'] ?? null,
            'tahun_haji' => $dataPersonal['tahunHaji'] ?? null,
            'keterangan' => $dataPersonal['keterangan'] ?? null,
        ]);

        $keluarga = $request->input('family');
        AnggotaKeluargaModel::create([
            'id_anggota' => $anggota->id_anggota,
            'jumlah_tanggungan' => $keluarga['jumlahTanggungan'] ?? null,
            'nama_istri' => $keluarga['namaIstri'] ?? null,
            'anggota_persistri' => $keluarga['anggotaPersistri'] ?? null,
            'status_kepemilikan_rumah' => $keluarga['statusKepemilikanRumah'] ?? null,
            'jumlah_seluruh_anak' => $keluarga['jumlaSeluruhAnak'] ?? null,
            'jumlah_anak_pemuda' => $keluarga['jumlaAnakPemuda'] ?? null,
            'jumlah_anak_pemudi' => $keluarga['jumlaAnakPemudi'] ?? null,
        ]);

        $pendidikan = $request->input('education');
        AnggotaPendidikanModel::create([
            'id_anggota' => $anggota->id_anggota,
            'id_tingkat_pendidikan' => $pendidikan['tingkat'] ?? null,
            'instansi' => $pendidikan['namaSekolah'] ?? null,
            'jurusan' => $pendidikan['jurusan'] ?? null,
            'tahun_masuk' => $pendidikan['tahunMasuk'] ?? null,
            'tahun_keluar' => $pendidikan['tahunKeluar'] ?? null,
            'jenis_pendidikan' => $pendidikan['jenisPendidikan'] ?? null,
        ]);

        $pekerjaan = $request->input('work');
        AnggotaPekerjaanModel::create([
            'id_anggota' => $anggota->id_anggota,
            'id_master_pekerjaan' => $pekerjaan['pekerjaan'] ?? null,
            'lainnya' => $pekerjaan['pekerjaanLainnya'] ?? null,
            'nama_instasi' => $pekerjaan['namaInstansi'] ?? null,
            'deskripsi_pekerjaan' => $pekerjaan['deskripsiPekerjaan'] ?? null,
            'pendapatan' => $pekerjaan['pendapatan'] ?? null,
        ]);

        $keterampilan = $request->input('skill');
        AnggotaKeterampilanModel::create([
            'id_anggota' => $anggota->id_anggota,
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
            'id_anggota' => $anggota->id_anggota,
            'keterlibatan_organisasi' => $organisasi['keterlibatanOrganisasi'] ?? null,
            'nama_organisasi' => $organisasi['namaOrganisasi'] ?? null,
        ]);

        return response()->json(['message' => 'Data berhasil disimpan'], 201);
    }

    // Update data
    /**
     * @OA\Put(
     *     path="/api/anggota/{id}",
     *     summary="Update data anggota lengkap (personal, keluarga, pendidikan, pekerjaan, keterampilan, minat, organisasi)",
     *     tags={"Anggota"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID anggota yang ingin diperbarui",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="personal", type="object",
     *                 @OA\Property(property="nomorAnggota", type="string"),
     *                 @OA\Property(property="nomorKTP", type="string"),
     *                 @OA\Property(property="namaLengkap", type="string"),
     *                 @OA\Property(property="tempatLahir", type="string"),
     *                 @OA\Property(property="tanggalLahir", type="string", format="date"),
     *                 @OA\Property(property="statusMerital", type="string"),
     *                 @OA\Property(property="nomorTelepon", type="string"),
     *                 @OA\Property(property="nomorWA", type="string"),
     *                 @OA\Property(property="alamat", type="string"),
     *                 @OA\Property(property="alamatTinggal", type="string"),
     *                 @OA\Property(property="otonom", type="integer"),
     *                 @OA\Property(property="jamaah", type="integer"),
     *                 @OA\Property(property="statusAktif", type="boolean"),
     *                 @OA\Property(property="tahunMasuk", type="integer"),
     *                 @OA\Property(property="masaAktif", type="string"),
     *                 @OA\Property(property="kajianRutin", type="string"),
     *                 @OA\Property(property="tahunHaji", type="integer"),
     *                 @OA\Property(property="keterangan", type="string")
     *             ),
     *             @OA\Property(property="family", type="object",
     *                 @OA\Property(property="jumlahTanggungan", type="integer"),
     *                 @OA\Property(property="namaIstri", type="string"),
     *                 @OA\Property(property="anggotaPersistri", type="string"),
     *                 @OA\Property(property="statusKepemilikanRumah", type="string"),
     *                 @OA\Property(property="jumlaSeluruhAnak", type="integer"),
     *                 @OA\Property(property="jumlaAnakPemuda", type="integer"),
     *                 @OA\Property(property="jumlaAnakPemudi", type="integer")
     *             ),
     *             @OA\Property(property="education", type="object",
     *                 @OA\Property(property="tingkat", type="integer"),
     *                 @OA\Property(property="namaSekolah", type="string"),
     *                 @OA\Property(property="jurusan", type="string"),
     *                 @OA\Property(property="tahunMasuk", type="integer"),
     *                 @OA\Property(property="tahunKeluar", type="integer"),
     *                 @OA\Property(property="jenisPendidikan", type="string")
     *             ),
     *             @OA\Property(property="work", type="object",
     *                 @OA\Property(property="pekerjaan", type="integer"),
     *                 @OA\Property(property="pekerjaanLainnya", type="string"),
     *                 @OA\Property(property="namaInstansi", type="string"),
     *                 @OA\Property(property="deskripsiPekerjaan", type="string"),
     *                 @OA\Property(property="pendapatan", type="integer")
     *             ),
     *             @OA\Property(property="skill", type="object",
     *                 @OA\Property(property="keterampilan", type="integer"),
     *                 @OA\Property(property="keterampilanLainnya", type="string"),
     *                 @OA\Property(property="deskripsiKeterampilan", type="string")
     *             ),
     *             @OA\Property(
     *                 property="interest",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="minat", type="string"),
     *                     @OA\Property(property="minatLainnya", type="string")
     *                 )
     *             ),
     *             @OA\Property(property="organization", type="object",
     *                 @OA\Property(property="keterlibatanOrganisasi", type="string"),
     *                 @OA\Property(property="namaOrganisasi", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Berhasil memperbarui data anggota",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Data berhasil diperbarui")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Data anggota tidak ditemukan"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validasi gagal"
     *     )
     * )
     */

    public function update(Request $request, $id)
    {
        Log::info('Data request:', $request->all());

        // Cari anggota berdasarkan ID
        $anggota = AnggotaModel::findOrFail($id);

        if (!empty($dataPersonal['fotoURL'])) {
            $fullUrl = $request->input('personal.fotoURL');
            $relativePath = str_replace("http://localhost:8000/storage/uploads/", "", $fullUrl);

            if ($relativePath !== $anggota->foto) {
                $fileName = basename($relativePath);
                $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION) ?: 'png';
                $encryptedName = Str::random(10) . '.' . $fileExtension;

                $oldPath = public_path("storage/uploads/" . $anggota->foto);
                $newPath = public_path("storage/uploads/" . $encryptedName);

                // Hapus file lama jika ada
                if (file_exists($oldPath) && $anggota->foto !== "default") {
                    unlink($oldPath);
                    Log::info("File lama dihapus: " . $oldPath);
                }

                // Pindahkan file baru
                if (file_exists(public_path("storage/uploads/" . $relativePath))) {
                    rename(public_path("storage/uploads/" . $relativePath), $newPath);
                    Log::info("File baru disimpan: " . $newPath);

                    // Update nama file di database
                    $anggota->foto = $encryptedName;
                } else {
                    Log::error("File baru tidak ditemukan: " . $relativePath);
                }
            }
        }


        // Update data personal
        $dataPersonal = $request->input('personal');
        $anggota->update([
            'foto' => $encryptedName ?? $anggota->foto,
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
    /**
     * @OA\Delete(
     *     path="/api/anggota/{id}",
     *     summary="Hapus data anggota beserta semua data relasinya",
     *     tags={"Anggota"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID anggota yang ingin dihapus",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Data berhasil dihapus",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Data berhasil dihapus")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Data anggota tidak ditemukan"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Terjadi kesalahan saat menghapus data",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Terjadi kesalahan saat menghapus data")
     *         )
     *     )
     * )
     */

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

    /**
     * @OA\Get(
     *     path="/api/data_monografi",
     *     summary="Menampilkan statistik monografi anggota (Persis, Persistri, Pemuda, Pemudi)",
     *     tags={"Anggota"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Statistik berhasil diambil",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="integer", example=200),
     *             @OA\Property(
     *                 property="data_monografi",
     *                 type="object",
     *                 @OA\Property(property="jum_persis", type="integer", example=120),
     *                 @OA\Property(property="jum_persistri", type="integer", example=90),
     *                 @OA\Property(property="jum_pemuda", type="integer", example=70),
     *                 @OA\Property(property="jum_pemudi", type="integer", example=65)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Terjadi kesalahan saat mengambil data"
     *     )
     * )
     */

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

    /**
     * @OA\Get(
     *     path="/api/data_chart",
     *     summary="Menampilkan data statistik untuk kebutuhan chart (anggota, pendidikan, pekerjaan, keterampilan, mubaligh)",
     *     tags={"Anggota"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Data statistik chart berhasil diambil",
     *         @OA\JsonContent(
     *             @OA\Property(property="anggota", type="array", @OA\Items(
     *                 @OA\Property(property="id_master_jamaah", type="integer", example=1),
     *                 @OA\Property(property="nama_jamaah", type="string", example="Banjaran"),
     *                 @OA\Property(property="jum_persis", type="integer", example=30),
     *                 @OA\Property(property="jum_persistri", type="integer", example=20),
     *                 @OA\Property(property="jum_pemuda", type="integer", example=15),
     *                 @OA\Property(property="jum_pemudi", type="integer", example=10)
     *             )),
     *             @OA\Property(property="pendidikan", type="array", @OA\Items(
     *                 @OA\Property(property="tingkat_pendidikan", type="string", example="S1"),
     *                 @OA\Property(property="jumlah_anggota", type="integer", example=50)
     *             )),
     *             @OA\Property(property="pekerjaan", type="array", @OA\Items(
     *                 @OA\Property(property="nama_pekerjaan", type="string", example="Guru"),
     *                 @OA\Property(property="jumlah_anggota", type="integer", example=25)
     *             )),
     *             @OA\Property(property="keterampilan", type="array", @OA\Items(
     *                 @OA\Property(property="nama_keterampilan", type="string", example="Menjahit"),
     *                 @OA\Property(property="jumlah_anggota", type="integer", example=10)
     *             )),
     *             @OA\Property(property="mubaligh", type="array", @OA\Items(
     *                 @OA\Property(property="id_master_jamaah", type="integer", example=1),
     *                 @OA\Property(property="nama_jamaah", type="string", example="Banjaran"),
     *                 @OA\Property(property="jumlah_anggota", type="integer", example=5)
     *             ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Terjadi kesalahan saat mengambil data chart"
     *     )
     * )
     */

    public function chart()
    {
        // Data jumlah anggota Persis per Jamaah
        $dataPersisPerJamaah = MasterJamaahModel::withCount([
            'anggota as jum_persis' => function ($query) {
                $query->where('id_otonom', 1)
                    ->where('status_aktif', 1);
            }
        ])->get()->keyBy('id_master_jamaah');

        // Data monografi jamaah
        $dataAnggotaPerJamaah = MasterJamaahModel::with('monografi')
            ->get()
            ->map(function ($jamaah) use ($dataPersisPerJamaah) {
                $persisData = $dataPersisPerJamaah[$jamaah->id_master_jamaah] ?? null;
                return [
                    'id_master_jamaah' => $jamaah->id_master_jamaah,
                    'nama_jamaah' => $jamaah->nama_jamaah,
                    'jum_persis' => $persisData ? $persisData->jum_persis : 0,
                    'jum_persistri' => $jamaah->monografi->jum_persistri ?? 0,
                    'jum_pemuda' => $jamaah->monografi->jum_pemuda ?? 0,
                    'jum_pemudi' => $jamaah->monografi->jum_pemudi ?? 0,
                ];
            });

        // Data pendidikan anggota
        $dataPendidikan = AnggotaPendidikanModel::with('tingkat_pendidikan')
            ->selectRaw('id_tingkat_pendidikan, COUNT(id_anggota) as jumlah_anggota')
            ->groupBy('id_tingkat_pendidikan')
            ->orderBy('id_tingkat_pendidikan')
            ->get()
            ->map(function ($item) {
                return [
                    'tingkat_pendidikan' => $item->tingkat_pendidikan->pendidikan ?? 'Tidak Diketahui',
                    'jumlah_anggota' => $item->jumlah_anggota,
                ];
            });

        // Data pekerjaan anggota
        $dataPekerjaan = MasterPekerjaanModel::withCount(['anggota_pekerjaan as jumlah_anggota'])
            ->orderBy('nama_pekerjaan')
            ->get()
            ->map(function ($item) {
                return [
                    'nama_pekerjaan' => $item->nama_pekerjaan,
                    'jumlah_anggota' => $item->jumlah_anggota,
                ];
            });

        // Data keterampilan anggota
        $dataKeterampilan = MasterKeterampilanModel::where('nama_keterampilan', '!=', 'Tidak Ada')
            ->withCount(['anggota_keterampilan as jumlah_anggota'])
            ->orderBy('nama_keterampilan')
            ->get()
            ->map(function ($item) {
                return [
                    'nama_keterampilan' => $item->nama_keterampilan,
                    'jumlah_anggota' => $item->jumlah_anggota,
                ];
            });

        // Data mubaligh per jamaah (dari monografi)
        $dataMubaligh = MasterJamaahModel::with('monografi')
            ->orderBy('nama_jamaah')
            ->get()
            ->map(function ($jamaah) {
                return [
                    'id_master_jamaah' => $jamaah->id_master_jamaah,
                    'nama_jamaah' => $jamaah->nama_jamaah,
                    'jumlah_anggota' => $jamaah->monografi->jum_mubaligh ?? 0,
                ];
            });

        return response()->json([
            'anggota' => $dataAnggotaPerJamaah,
            'pendidikan' => $dataPendidikan,
            'pekerjaan' => $dataPekerjaan,
            'keterampilan' => $dataKeterampilan,
            'mubaligh' => $dataMubaligh,
        ], 200);
    }


    /**
     * @OA\Get(
     *     path="/api/data_choice_pribadi",
     *     tags={"Anggota"},
     *     security={{"bearerAuth":{}}},
     *     summary="Ambil data pilihan Jamaah & Otonom",
     *     description="Endpoint ini digunakan untuk mengambil pilihan jamaah dan otonom untuk form data pribadi anggota.",
     *     @OA\Response(
     *         response=200,
     *         description="Berhasil mengambil data pilihan",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="jamaah",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id_master_jamaah", type="integer", example=1),
     *                     @OA\Property(property="nama_jamaah", type="string", example="Banjaran")
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="otonom",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id_otonom", type="integer", example=2),
     *                     @OA\Property(property="nama_otonom", type="string", example="Pemuda")
     *                 )
     *             )
     *         )
     *     )
     * )
     */

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

    /**
     * @OA\Get(
     *     path="/api/anggota/by-jamaah/{id_master_jamaah}",
     *     tags={"Anggota"},
     *     summary="Ambil data anggota berdasarkan ID Jamaah",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id_master_jamaah",
     *         in="path",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="perPage",
     *         in="query",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="searchTerm",
     *         in="query",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response=200, description="Data anggota berhasil diambil")
     * )
     */

    public function indexByJamaah(Request $request, $id_master_jamaah = null)
    {
        $perPage = $request->input('perPage');
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
                return $query->whereRaw('LOWER(nama_lengkap) LIKE ?', ["%" . strtolower($searchTerm) . "%"]);
            })
            ->orderBy('id_master_jamaah', 'asc');

        $anggota = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json(['status' => 200, 'data' => $anggota], 200);
    }

    public function anggotaByJamaah($id_master_jamaah = null)
    {
        $query = AnggotaModel::with([
            'master_jamaah'

        ])->when($id_master_jamaah, function ($query, $id_master_jamaah) {
            return $query->where('id_master_jamaah', $id_master_jamaah);
        })->where('t_anggota.status_aktif', 1)
            ->orderBy('id_master_jamaah', 'asc');
        // Ambil semua data tanpa paginasi
        $anggota = $query->get();

        // Kembalikan respons dalam format JSON
        return response()->json([
            'status' => 200,
            'data' => $anggota,
        ], 200);
    }



    /**
     * @OA\Get(
     *     path="/api/anggota/all",
     *     tags={"Anggota"},
     *     summary="Ambil semua data anggota (untuk dropdown/select)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="searchTerm",
     *         in="query",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response=200, description="Data anggota berhasil diambil")
     * )
     */

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

    /**
     * @OA\Get(
     *     path="/api/data_choice_pendidikan",
     *     tags={"Anggota"},
     *     summary="Ambil pilihan data pendidikan",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Data pendidikan berhasil diambil")
     * )
     */

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

    /**
     * @OA\Get(
     *     path="/api/data_choice_pekerjaan",
     *     tags={"Anggota"},
     *     summary="Ambil pilihan data pekerjaan",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Data pekerjaan berhasil diambil")
     * )
     */

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

    /**
     * @OA\Get(
     *     path="/api/data_choice_keterampilan",
     *     tags={"Anggota"},
     *     summary="Ambil pilihan data keterampilan",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Data keterampilan berhasil diambil")
     * )
     */

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


    /**
     * @OA\Get(
     *     path="/api/data_choice_minat",
     *     tags={"Anggota"},
     *     summary="Ambil pilihan data minat",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Data minat berhasil diambil")
     * )
     */

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

    /**
     * @OA\Get(
     *     path="/api/statistik/maps",
     *     tags={"Anggota"},
     *     summary="Ambil data koordinat jamaah untuk pemetaan",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Data lokasi berhasil diambil")
     * )
     */

    public function mapsMonographic()
    {
        $anggota = MasterJamaahModel::select('nama_jamaah', 'lokasi_lat', 'lokasi_long')
            ->whereNotNull('lokasi_lat')
            ->whereNotNull('lokasi_long')
            ->get();


        return response()->json(['status' => 200, 'data' => $anggota], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/statistik/advanced/pilihan",
     *     tags={"Anggota"},
     *     summary="Ambil pilihan data untuk statistik lanjutan",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Pilihan data statistik lanjutan berhasil diambil")
     * )
     */

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

    /**
     * @OA\Post(
     *     path="/api/statistik/advanced",
     *     tags={"Anggota"},
     *     summary="Ambil statistik lanjutan berdasarkan filter",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="pendidikan", type="integer", example=1),
     *             @OA\Property(property="pekerjaan", type="integer", example=2),
     *             @OA\Property(property="keahlian", type="integer", example=3)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Statistik lanjutan berhasil diambil")
     * )
     */

    public function advancedStatistic(Request $request)
    {
        $query = AnggotaModel::query();

        if ($request->filled('pendidikan')) {
            $query->whereHas('anggota_pendidikan', function ($q) use ($request) {
                $q->where('id_tingkat_pendidikan', $request->pendidikan);
            });
        }

        if ($request->filled('pekerjaan')) {
            $query->whereHas('anggota_pekerjaan', function ($q) use ($request) {
                $q->where('id_master_pekerjaan', $request->pekerjaan);
            });
        }

        if ($request->filled('keterampilan')) {
            $query->whereHas('anggota_keterampilan', function ($q) use ($request) {
                $q->where('id_master_keterampilan', $request->keterampilan);
            });
        }

        if ($request->filled('minat')) {
            $query->whereHas('anggota_minat', function ($q) use ($request) {
                $q->where('id_master_minat', $request->minat);
            });
        }

        if ($request->filled('master_otonom')) {
            $query->where('id_otonom', $request->otonom);
        }

        if ($request->filled('status_aktif')) {
            $query->where('status_aktif', $request->status_aktif);
        }

        if ($request->filled('status_merital')) {
            $query->where('status_merital', $request->status_merital);
        }

        if ($request->filled('tanggungan')) {
            $query->whereHas('anggota_keluarga', function ($q) use ($request) {
                $q->where('jumlah_tanggungan', $request->tanggungan);
            });
        }

        if ($request->filled('pendapatan')) {
            $query->whereHas('anggota_pekerjaan', function ($q) use ($request) {
                $q->where('pendapatan', $request->pendapatan);
            });
        }

        $result = $query->join('t_master_jamaah as mj', 't_anggota.id_master_jamaah', '=', 'mj.id_master_jamaah')
            ->selectRaw('mj.nama_jamaah, COUNT(t_anggota.id_anggota) as jumlah_anggota')
            ->groupBy('mj.nama_jamaah')
            ->orderByDesc('jumlah_anggota')
            ->get();

        return response()->json($result, 200);
    }



    public function uploadFoto(Request $request)
    {
        $request->validate([
            'file' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'namaFoto' => 'required|string'
        ]);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filename = $request->namaFoto;

            $path = $file->storeAs('uploads', $filename, 'public');

            return response()->json([
                'success' => true,
                'filename' => $filename,
                'path' => "/storage/$path", // image disimpan di storage\app\public\uploads
                'url' => asset("storage/$path")
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Upload gagal'], 400);
    }
}
