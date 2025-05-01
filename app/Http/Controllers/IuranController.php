<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\IuranLog;
use App\Models\AnggotaModel;
use App\Models\TahunAktif;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class IuranController extends Controller
{
    // GET /iuran/summary
    /**
     * @OA\Get(
     *     path="/api/iuran/summary",
     *     tags={"Iuran"},
     *     summary="Get ringkasan iuran tahunan per anggota",
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Jumlah item per halaman",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="jamaah_id",
     *         in="query",
     *         description="Filter berdasarkan ID jamaah",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Filter berdasarkan nama anggota",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response=200, description="Berhasil mengambil data"),
     *     @OA\Response(response=404, description="Tahun aktif tidak ditemukan"),
     *     security={{"bearerAuth": {}}}
     * )
     */
    public function summary(Request $request)
    {
        $tahunAktif = TahunAktif::where('status', 'Aktif')->value('tahun');
        if (!$tahunAktif) {
            return response()->json(['message' => 'Tidak ada tahun iuran aktif'], 404);
        }

        $perPage = $request->input('per_page', 10);
        $jamaahId = $request->input('jamaah_id');
        $searchTerm = $request->input('search');
        Log::info('Tahun Aktif digunakan:', [$tahunAktif]);

        $query = AnggotaModel::query()
            ->select([
                't_anggota.id_anggota as anggota_id',
                't_anggota.nama_lengkap',
                't_master_jamaah.nama_jamaah'
            ])
            ->where('t_anggota.status_aktif', 1)
            ->join('t_master_jamaah', 't_anggota.id_master_jamaah', '=', 't_master_jamaah.id_master_jamaah')
            ->withSum(['iuranLogs as total_nominal_bayar_tahun_ini' => function ($q) use ($tahunAktif) {
                $q->where('tahun', (int)$tahunAktif)->where('status', 'Verified');
            }], 'nominal');
        // Eager loading dihapus, diganti Query Builder di transform

        // Filter Jamaah
        if ($jamaahId) {
            $query->where('t_anggota.id_master_jamaah', $jamaahId);
        }

        // Filter Search (Case-Insensitive)
        if ($searchTerm) {
            // --- PERUBAHAN DI SINI ---
            // Gunakan ILIKE untuk PostgreSQL atau LOWER() untuk kompatibilitas lebih luas
            // Jika Anda yakin selalu pakai PostgreSQL:
            $query->where('t_anggota.nama_lengkap', 'ILIKE', '%' . $searchTerm . '%');

            // Alternatif (lebih portabel antar database):
            // $query->whereRaw('LOWER(t_anggota.nama_lengkap) LIKE ?', ['%' . strtolower($searchTerm) . '%']);
            // --- AKHIR PERUBAHAN ---
        }

        $paginatedData = $query->paginate($perPage);

        // Proses data menggunakan Query Builder (seperti sebelumnya)
        $paginatedData->getCollection()->transform(function ($item) use ($tahunAktif) {
            $manualLogsCollection = DB::table('t_iuran_log')
                ->where('anggota_id', $item->anggota_id)
                ->where('tahun', (int)$tahunAktif)
                ->orderBy('tanggal', 'desc')
                ->orderBy('id', 'desc')
                ->get();

            //Log::info('Query Builder Logs for Anggota ID: ' . $item->anggota_id . ' in Year: ' . $tahunAktif, $manualLogsCollection->toArray());

            $latestLog = $manualLogsCollection->first();
            $latestPendingFailedLog = $manualLogsCollection
                ->whereIn('status', ['Pending', 'Failed'])
                ->first();

            $item->status_terakhir = $latestLog ? $latestLog->status : null;
            $item->id_pembayaran_terakhir_untuk_aksi = $latestPendingFailedLog ? $latestPendingFailedLog->id : null;
            $item->nominal_terakhir = $latestLog ? $latestLog->nominal : null;
            $item->tanggal_input_terakhir = $latestLog ? Carbon::parse($latestLog->tanggal)->toDateString() : null;
            $item->catatan_verifikasi = $latestLog && $latestLog->status === 'Failed' ? $latestLog->catatan_verifikasi : null;
            $item->total_nominal_bayar_tahun_ini = $item->total_nominal_bayar_tahun_ini ?? 0;

            //Log::info('Summary Data Prepared (using QB):', [ /* ... data log ... */ ]);

            return $item;
        });

        return response()->json($paginatedData);
    }

    // GET /iuran/payment/{id}
    /**
     * @OA\Get(
     *     path="/api/iuran/payment/{id}",
     *     tags={"Iuran"},
     *     summary="Detail pembayaran iuran",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Detail pembayaran ditemukan"),
     *     @OA\Response(response=404, description="Data tidak ditemukan"),
     *     security={{"bearerAuth": {}}}
     * )
     */
    public function paymentDetail($id)
    {
        try {
            // Temukan log berdasarkan ID, atau gagal dengan 404
            $log = IuranLog::with([
                // Muat relasi anggota, pilih kolom yang benar termasuk PK 'id_anggota'
                'anggota' => function ($query) {
                    $query->select('id_anggota', 'nama_lengkap', 'id_master_jamaah'); // Pilih kolom yang dibutuhkan
                },
                // Muat relasi jamaah melalui anggota
                'anggota.master_jamaah' => function ($query) {
                    // Asumsi PK t_master_jamaah adalah 'id_master_jamaah'
                    $query->select('id_master_jamaah', 'nama_jamaah'); // Pilih kolom yang dibutuhkan
                }
            ])
                ->findOrFail($id); // Gunakan findOrFail untuk otomatis 404 jika tidak ditemukan

            // Otorisasi (Contoh: hanya user terkait atau bendahara/superadmin yg boleh lihat?)
            // Anda bisa menambahkan Gate atau Policy di sini jika perlu
            // if (!Gate::allows('view-iuran-detail', $log)) {
            //     abort(403, 'Akses ditolak.');
            // }

            return response()->json(['data' => $log]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Data pembayaran tidak ditemukan.'], 404);
        } catch (\Exception $e) {
            Log::error("Error fetching payment detail (ID: {$id}): " . $e->getMessage());
            return response()->json(['message' => 'Terjadi kesalahan saat mengambil data detail.'], 500);
        }
    }


    // POST /iuran/pay
    /**
     * @OA\Post(
     *     path="/api/iuran/pay",
     *     tags={"Iuran"},
     *     summary="Input pembayaran iuran baru",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"payments"},
     *             @OA\Property(
     *                 property="payments",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="anggota_id", type="integer"),
     *                     @OA\Property(property="nominal", type="number")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Pembayaran berhasil disimpan"),
     *     @OA\Response(response=400, description="Tahun aktif tidak tersedia"),
     *     @OA\Response(response=422, description="Validasi gagal"),
     *     @OA\Response(response=500, description="Kesalahan server"),
     *     security={{"bearerAuth": {}}}
     * )
     */
    public function store(Request $request)

    {
        // if (!Gate::allows('input-iuran')) {
        //      abort(403, 'Anda tidak punya hak akses untuk input iuran.');
        // }

        $validated = $request->validate([
            'payments' => 'required|array|min:1',
            'payments.*.anggota_id' => 'required|exists:t_anggota,id_anggota',
            'payments.*.nominal' => 'required|numeric|min:1',
        ]);

        $tahunAktif = TahunAktif::where('status', 'Aktif')->value('tahun');
        if (!$tahunAktif) {
            return response()->json(['message' => 'Tidak ada tahun iuran aktif'], 400);
        }

        $distPercentage = config('iuran.distribution_percentage', 0.20);
        $distKeys = config('iuran.distribution_keys', ['pj', 'pc', 'pd', 'pw', 'pp']);
        $userId = Auth::id();
        $now = Carbon::now();

        DB::beginTransaction();
        try {
            foreach ($validated['payments'] as $payment) {
                $nominal = $payment['nominal'];
                $distributionValue = $nominal * $distPercentage;

                $logData = [
                    'anggota_id' => $payment['anggota_id'],
                    'nominal' => $nominal,
                    'tanggal' => $now->toDateString(),
                    'tahun' => $tahunAktif,
                    'status' => 'Pending',
                    'pj_input_id' => $userId,
                ];

                // Isi kolom distribusi
                foreach ($distKeys as $key) {
                    $logData[$key] = $distributionValue;
                }

                Log::info('Logs Create data on Log Iuran : ' . json_encode($logData));

                IuranLog::create($logData);
            }
            DB::commit();
            return response()->json(['message' => 'Data pembayaran berhasil disimpan (Pending)'], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error store iuran: " . $e->getMessage()); // Log error
            return response()->json(['message' => 'Terjadi kesalahan saat menyimpan data.'], 500);
        }
    }

    // PUT /iuran/edit/{id}
    /**
     * @OA\Put(
     *     path="/api/iuran/edit/{id}",
     *     tags={"Iuran"},
     *     summary="Update nominal pembayaran",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nominal"},
     *             @OA\Property(property="nominal", type="number")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Berhasil diperbarui"),
     *     @OA\Response(response=403, description="Tidak bisa edit"),
     *     @OA\Response(response=422, description="Validasi gagal"),
     *     security={{"bearerAuth": {}}}
     * )
     */
    public function updateNominal(Request $request, $id)

    {
        $log = IuranLog::findOrFail($id);
        $user = Auth::user(); // Dapatkan user yg terautentikasi

        // Middleware sudah cek permission 'iuran,update'
        // Tambahan: Cek kepemilikan jika user adalah PJ
        // if ($user->role->name_role === 'Pimpinan Jamaah' && $log->pj_input_id !== $user->id) {
        //      abort(403, 'Anda hanya dapat mengedit data yang Anda input.');
        // }
        // Bendahara (diasumsikan lolos middleware) boleh edit semua yg pending/failed
        if (!in_array($log->status, ['Pending', 'Failed'])) {
            abort(403, 'Hanya data Pending atau Failed yang bisa diedit.');
        }


        $validated = $request->validate([
            'nominal' => 'required|numeric|min:1',
        ]);

        $nominal = $validated['nominal'];
        $distPercentage = config('iuran.distribution_percentage', 0.20);
        $distKeys = config('iuran.distribution_keys', ['pj', 'pc', 'pd', 'pw', 'pp']);
        $distributionValue = $nominal * $distPercentage;

        $logData = ['nominal' => $nominal, 'status' => 'Pending'];
        foreach ($distKeys as $key) {
            $logData[$key] = $distributionValue;
        }

        $log->update($logData);

        return response()->json(['message' => 'Nominal pembayaran berhasil diperbarui.']);
    }

    // PUT /iuran/verify/{id}
    /**
     * @OA\Put(
     *     path="/api/iuran/verify/{id}",
     *     tags={"Iuran"},
     *     summary="Verifikasi pembayaran iuran",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Pembayaran diverifikasi"),
     *     @OA\Response(response=403, description="Tidak diizinkan verifikasi"),
     *     security={{"bearerAuth": {}}}
     * )
     */
    public function verify($id)

    {
        //  if (!Gate::allows('verifikasi-iuran')) {
        //      abort(403, 'Anda tidak punya hak akses untuk verifikasi.');
        //  }

        $log = IuranLog::whereIn('status', ['Pending'])->findOrFail($id); // Hanya bisa verif yg Pending

        $log->update([
            'status' => 'Verified',
            'verifikator_id' => Auth::id(),
            'catatan_verifikasi' => null // Hapus catatan jika ada sblmnya
            // Anda bisa menambahkan kolom 'verified_at' jika perlu
        ]);

        return response()->json(['message' => 'Pembayaran berhasil diverifikasi.']);
    }

    // PUT /iuran/reject/{id}
    /**
     * @OA\Put(
     *     path="/api/iuran/reject/{id}",
     *     tags={"Iuran"},
     *     summary="Tolak pembayaran iuran",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"catatan"},
     *             @OA\Property(property="catatan", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Pembayaran ditolak"),
     *     @OA\Response(response=403, description="Tidak diizinkan"),
     *     @OA\Response(response=422, description="Validasi gagal"),
     *     security={{"bearerAuth": {}}}
     * )
     */
    public function reject(Request $request, $id)

    {
        //  if (!Gate::allows('verifikasi-iuran')) { // Asumsi Bendahara yg bisa reject jg
        //      abort(403, 'Anda tidak punya hak akses untuk menolak pembayaran.');
        //  }

        $validated = $request->validate([
            'catatan' => 'required|string|max:500',
        ]);

        $log = IuranLog::whereIn('status', ['Pending'])->findOrFail($id); // Hanya bisa reject yg Pending

        $log->update([
            'status' => 'Failed',
            'catatan_verifikasi' => $validated['catatan'],
            'verifikator_id' => Auth::id(),
            // Anda bisa menambahkan kolom 'failed_at' jika perlu
        ]);

        return response()->json(['message' => 'Pembayaran ditolak (Failed).']);
    }

    // DELETE /iuran/{id}
    /**
     * @OA\Delete(
     *     path="/api/iuran/{id}",
     *     tags={"Iuran"},
     *     summary="Hapus pembayaran iuran",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Berhasil dihapus"),
     *     @OA\Response(response=403, description="Tidak bisa dihapus"),
     *     security={{"bearerAuth": {}}}
     * )
     */
    public function destroy($id)

    {
        $log = IuranLog::findOrFail($id);
        $user = Auth::user();

        // Middleware sudah cek permission 'iuran,delete'
        // Tambahan: Cek kepemilikan jika user adalah PJ
        // if ($user->role->name_role === 'Pimpinan Jamaah' && $log->pj_input_id !== $user->id) {
        //      abort(403, 'Anda hanya dapat menghapus data yang Anda input.');
        // }
        // Bendahara (diasumsikan lolos middleware) boleh hapus semua yg pending/failed
        if (!in_array($log->status, ['Pending', 'Failed'])) {
            abort(403, 'Hanya data Pending atau Failed yang bisa dihapus.');
        }

        $log->delete();

        return response()->json(['message' => 'Data pembayaran berhasil dihapus.'], 200); // Atau 204 No Content
    }

    public function sendBatchReminder(Request $request)
    {
        // Otorisasi menggunakan middleware di route, atau Gate di sini
        // if (!Gate::allows('kirim-reminder-iuran')) { // Ganti dengan permission Anda
        //     abort(403, 'Akses ditolak.');
        // }

        $validated = $request->validate([
            'anggota_ids' => 'required|array|min:1',
            // Pastikan ID yang dikirim benar-benar ada di tabel anggota
            'anggota_ids.*' => [
                'required',
                'integer',
                Rule::exists('t_anggota', 'id_anggota') // Validasi exists ke PK anggota
            ],
        ]);

        $anggotaIds = $validated['anggota_ids'];
        $sentCount = 0;
        $failedIds = [];

        // Ambil data anggota yang relevan (nama, no telp)
        // Gunakan nama model Anggota yang benar
        $anggotas = AnggotaModel::whereIn('id_anggota', $anggotaIds)
            ->select('id_anggota', 'nama_lengkap', 'no_telp')
            ->get()
            ->keyBy('id_anggota'); // Jadikan ID sebagai key array

        foreach ($anggotaIds as $id) {
            $anggota = $anggotas->get($id);

            if ($anggota) {
                try {
                    // --- LOGIKA PENGIRIMAN REMINDER ---
                    // Saat ini hanya logging, ganti dengan integrasi WA API nanti

                    Log::info("Reminder Sent (Simulated):", [
                        'anggota_id' => $anggota->id_anggota,
                        'nama' => $anggota->nama_lengkap,
                        'no_telp' => $anggota->no_telp ?? 'N/A',
                        'sender_user_id' => Auth::id(), // User yg mengirim
                        'timestamp' => now()
                    ]);

                    // Anda bisa menambahkan logika untuk mengambil detail tunggakan
                    // dari database jika diperlukan untuk isi pesan WA nanti.

                    $sentCount++;
                    // --- AKHIR LOGIKA ---

                } catch (\Exception $e) {
                    Log::error("Failed sending reminder to Anggota ID: {$id}", ['error' => $e->getMessage()]);
                    $failedIds[] = $id;
                }
            } else {
                Log::warning("Anggota ID not found for reminder: {$id}");
                $failedIds[] = $id; // Anggap gagal jika data anggota tidak ditemukan
            }
        }

        $message = "Reminder (simulasi) berhasil dikirim ke {$sentCount} anggota.";
        if (!empty($failedIds)) {
            $message .= " Gagal mengirim ke ID: " . implode(', ', $failedIds) . ".";
            return response()->json(['message' => $message], 500); // Kembalikan error jika ada yg gagal
        }

        return response()->json(['message' => $message]);
    }

    public function getTunggakan(Request $request)
    {
        // ... (Otorisasi, ambil tahun aktif, bulan saat ini, biaya bulanan, total harus bayar - sama seperti sebelumnya) ...
        $tahunAktif = TahunAktif::where('status', 'Aktif')->value('tahun');
        if (!$tahunAktif) {
            return response()->json(['message' => 'Tidak ada tahun iuran aktif'], 404);
        }
        $bulanSaatIni = Carbon::now()->month;
        $namaBulanIndonesia = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
        $biayaBulanan = config('iuran.monthly_fee', 10000);
        $totalHarusBayar = $bulanSaatIni * $biayaBulanan;

        $perPage = $request->input('per_page', 10);
        $jamaahId = $request->input('jamaah_id');
        $searchTerm = $request->input('search');

        // Query dasar ke AnggotaModel
        $query = AnggotaModel::query()
            ->select([
                't_anggota.id_anggota',
                't_anggota.nama_lengkap',
                't_anggota.no_telp',
                't_master_jamaah.nama_jamaah',
                // Tetap hitung total verified paid untuk ditampilkan
                DB::raw('(SELECT SUM(nominal) FROM t_iuran_log WHERE t_iuran_log.anggota_id = t_anggota.id_anggota AND t_iuran_log.tahun = ? AND t_iuran_log.status = ?) as total_verified_paid')
            ])
            // Binding untuk Raw Select Query
            ->addBinding($tahunAktif, 'select')
            ->addBinding('Verified', 'select')
            // Join dengan jamaah
            ->join('t_master_jamaah', 't_anggota.id_master_jamaah', '=', 't_master_jamaah.id_master_jamaah')
            // Filter hanya anggota yang aktif (jika ada kolom status_aktif)
            ->where('t_anggota.status_aktif', 1); // Aktifkan jika perlu

        // --- PINDAHKAN FILTER TUNGGAKAN KE WHERE ---
        $query->where(function ($subQuery) use ($tahunAktif, $totalHarusBayar) {
            // Kondisi 1: Total verified < total seharusnya ATAU Kondisi 2: Total verified IS NULL (belum bayar sama sekali)
            $subQuery->whereRaw('(COALESCE((SELECT SUM(nominal) FROM t_iuran_log WHERE t_iuran_log.anggota_id = t_anggota.id_anggota AND t_iuran_log.tahun = ? AND t_iuran_log.status = ?), 0)) < ?', [
                $tahunAktif,        // Binding untuk subquery tahun
                'Verified',       // Binding untuk subquery status
                // 0,             // Nilai default jika COALESCE (tidak perlu di binding)
                $totalHarusBayar  // Binding untuk perbandingan <
            ]);
            // COALESCE digunakan untuk mengganti NULL (jika belum ada pembayaran verified) dengan 0
            // agar perbandingan < $totalHarusBayar tetap bekerja.
        });
        // --- AKHIR FILTER WHERE ---


        // HAPUS KLAUSA HAVING YANG LAMA:
        // ->having('total_verified_paid', '<', $totalHarusBayar)
        // ->orHavingRaw('total_verified_paid IS NULL');


        // Filter Jamaah
        if ($jamaahId) {
            $query->where('t_anggota.id_master_jamaah', $jamaahId);
        }

        // Filter Search (Case-Insensitive untuk PostgreSQL)
        if ($searchTerm) {
            $query->where('t_anggota.nama_lengkap', 'ILIKE', '%' . $searchTerm . '%');
        }

        // Urutkan
        $query->orderBy('t_anggota.id_anggota');

        // Paginate
        $paginatedData = $query->paginate($perPage);

        // Proses data untuk menambahkan detail tunggakan (sama seperti sebelumnya)
        $paginatedData->getCollection()->transform(function ($item) use ($biayaBulanan, $bulanSaatIni, $totalHarusBayar, $namaBulanIndonesia) {
            $totalVerifiedPaid = $item->total_verified_paid ?? 0; // Handle NULL dari select
            $bulanLunasTerakhir = floor($totalVerifiedPaid / $biayaBulanan);
            $jumlahBulanTunggakan = $bulanSaatIni - $bulanLunasTerakhir;
            $nominalTunggakan = max(0, $totalHarusBayar - $totalVerifiedPaid); // Pastikan tidak negatif

            $detailBulan = '';
            if ($jumlahBulanTunggakan > 0) {
                $bulanMulaiTunggakan = $bulanLunasTerakhir + 1;
                if ($jumlahBulanTunggakan == 1) {
                    $detailBulan = $namaBulanIndonesia[$bulanMulaiTunggakan] ?? 'Bulan tidak valid';
                } else {
                    $detailBulan = ($namaBulanIndonesia[$bulanMulaiTunggakan] ?? '?') . ' - ' . ($namaBulanIndonesia[$bulanSaatIni] ?? '?');
                }
            }

            return (object)[
                'anggota_id' => $item->id_anggota,
                'nama_lengkap' => $item->nama_lengkap,
                'nama_jamaah' => $item->nama_jamaah,
                'no_telp' => $item->no_telp,
                'bulan_lunas_terakhir' => (int)$bulanLunasTerakhir,
                'jumlah_bulan_tunggakan' => $jumlahBulanTunggakan,
                'nominal_tunggakan' => $nominalTunggakan,
                'detail_bulan_tunggakan' => $detailBulan,
            ];
        });

        return response()->json($paginatedData);
    }
}
