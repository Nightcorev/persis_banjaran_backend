<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Imports\IuranImport;
use Illuminate\Http\Request;
use App\Models\IuranLog;
use App\Models\AnggotaModel;
use App\Models\TahunAktif;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

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
        // ... (Kode ambil tahunAktif, filter, pagination - sama) ...
        $tahunAktif = $request->input('tahun', TahunAktif::where('status', 'Aktif')->value('tahun'));
        if (!$tahunAktif) {
            return response()->json(['message' => 'Tahun iuran tidak valid.'], 400);
        }
        //Log::info('Summary - Tahun digunakan:', [$tahunAktif]);
        $perPage = $request->input('per_page', 10);
        $jamaahId = $request->input('jamaah_id');
        $searchTerm = $request->input('search');

        $query = AnggotaModel::query()
            ->select(['t_anggota.id_anggota', 't_anggota.nama_lengkap', 't_anggota.id_master_jamaah', 't_master_jamaah.nama_jamaah'])
            ->join('t_master_jamaah', 't_anggota.id_master_jamaah', '=', 't_master_jamaah.id_master_jamaah')

            // Ambil SEMUA log di tahun aktif, termasuk Failed
            ->with(['iuranLogs' => function ($q) use ($tahunAktif) {
                $q->where('tahun', (int)$tahunAktif)
                    // Urutkan berdasarkan status: Verified > Pending > Failed > Lainnya (berdasarkan ID terbaru)
                    ->orderByRaw("CASE status WHEN 'Verified' THEN 1 WHEN 'Pending' THEN 2 WHEN 'Failed' THEN 3 ELSE 4 END")
                    ->orderBy('id', 'desc') // Jika status sama, ambil yg terbaru
                    ->select('id', 'anggota_id', 'status', 'paid_months', 'catatan_verifikasi'); // Ambil catatan juga
            }])
            ->where('t_anggota.status_aktif', 1);

        if ($jamaahId) {
            $query->where('t_anggota.id_master_jamaah', $jamaahId);
        }
        if ($searchTerm) {
            $query->where('t_anggota.nama_lengkap', 'ILIKE', '%' . $searchTerm . '%');
        }

        $paginatedData = $query->paginate($perPage);

        // Proses data untuk menambahkan bulan_status
        $paginatedData->getCollection()->transform(function ($item) {
            $bulanStatusProses = []; // Simpan status final per bulan
            $catatanFailed = []; // Simpan catatan jika failed

            //Log::info('Eager Loaded Logs for Anggota ID: ' . $item->id_anggota, $item->relationLoaded('iuranLogs') ? $item->iuranLogs->toArray() : 'RELATION NOT LOADED');

            if ($item->relationLoaded('iuranLogs')) {
                foreach ($item->iuranLogs as $log) {
                    $decodedMonths = json_decode($log->paid_months, true);
                    if (!is_array($decodedMonths)) {
                        Log::warning('paid_months JSON tidak valid log ID: ' . $log->id);
                        $decodedMonths = [];
                    }
                    $paidMonthsInLog = collect($decodedMonths)->map(fn($m) => (int)$m)->filter(fn($m) => $m > 0);

                    if ($paidMonthsInLog->isEmpty()) {
                        continue;
                    }

                    // Tetapkan status berdasarkan prioritas (Verified > Pending > Failed)
                    foreach ($paidMonthsInLog as $month) {
                        if (!isset($bulanStatusProses[$month])) { // Jika belum ada status, langsung set
                            $bulanStatusProses[$month] = $log->status;
                            if ($log->status === 'Failed') {
                                $catatanFailed[$month] = $log->catatan_verifikasi; // Simpan catatan
                            }
                        }
                        // Jika sudah ada TAPI bukan Verified, bisa ditimpa oleh status yg lebih tinggi
                        // (Karena sudah diurutkan, Verified akan diproses duluan)
                        // Tidak perlu logika prioritas eksplisit di sini karena sudah diurutkan query
                    }
                }
            }

            // Tentukan status akhir dan tambahkan catatan jika failed
            $finalBulanStatus = [];
            for ($i = 1; $i <= 12; $i++) {
                $status = $bulanStatusProses[$i] ?? 'Belum Lunas';
                $finalBulanStatus[$i] = [
                    'status' => $status,
                    'catatan' => ($status === 'Failed') ? ($catatanFailed[$i] ?? 'Tidak ada catatan.') : null,
                ];
            }

            // Buat objek hasil
            $resultItem = new \stdClass();
            $resultItem->anggota_id = $item->id_anggota;
            $resultItem->nama_lengkap = $item->nama_lengkap;
            $resultItem->nama_jamaah = $item->nama_jamaah;
            $resultItem->bulan_status = $finalBulanStatus; // Kirim objek status & catatan

            // Log::info('Summary Data Prepared (Monthly Final Fix 4):', [
            //     'anggota_id' => $resultItem->anggota_id,
            //     'bulan_status' => $resultItem->bulan_status,
            // ]);

            return $resultItem;
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
    public function verifyLog(IuranLog $iuranLog) // Gunakan type-hinting
    {
        // Otorisasi (Hanya Bendahara/Admin)
        // if (!Gate::allows('verifikasi-iuran')) { abort(403); }

        // Pastikan hanya bisa verifikasi yg Pending
        if ($iuranLog->status !== 'Pending') {
            return response()->json(['message' => 'Hanya pembayaran pending yang bisa diverifikasi.'], 400);
        }

        try {
            $iuranLog->update([
                'status' => 'Verified',
                'verifikator_id' => Auth::id(),
                'catatan_verifikasi' => null, // Hapus catatan jika ada
            ]);
            return response()->json(['message' => 'Pembayaran berhasil diverifikasi.']);
        } catch (\Exception $e) {
            Log::error("Error verifyLog (ID: {$iuranLog->id}): " . $e->getMessage());
            return response()->json(['message' => 'Gagal memverifikasi pembayaran.'], 500);
        }
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
    public function rejectLog(Request $request, IuranLog $iuranLog) // Gunakan type-hinting
    {
        // Otorisasi (Hanya Bendahara/Admin)
        // if (!Gate::allows('verifikasi-iuran')) { abort(403); } // Asumsi permission sama

        $validated = $request->validate([
            'catatan' => 'required|string|max:500', // Wajib ada alasan
        ]);

        // Pastikan hanya bisa reject yg Pending
        if ($iuranLog->status !== 'Pending') {
            return response()->json(['message' => 'Hanya pembayaran pending yang bisa ditolak.'], 400);
        }

        try {
            $iuranLog->update([
                'status' => 'Failed',
                'catatan_verifikasi' => $validated['catatan'],
                'verifikator_id' => Auth::id(),
            ]);
            return response()->json(['message' => 'Pembayaran ditolak (Failed).']);
        } catch (\Exception $e) {
            Log::error("Error rejectLog (ID: {$iuranLog->id}): " . $e->getMessage());
            return response()->json(['message' => 'Gagal menolak pembayaran.'], 500);
        }
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

    public function payMonths(Request $request)
    {
        // Otorisasi tetap bisa dicek via middleware/gate jika perlu

        $validated = $request->validate([
            'anggota_id' => ['required', Rule::exists('t_anggota', 'id_anggota')],
            'tahun' => 'required|integer|digits:4',
            'months' => 'required|array|min:1',
            'months.*' => 'required|integer|between:1,12',
            // --- VALIDASI ROLE DARI FRONTEND ---
            'role' => 'required|string|in:Pimpinan Jamaah,Bendahara,Super Admin' // Sesuaikan nama role
            // --- AKHIR VALIDASI ROLE ---
        ]);

        $anggotaId = $validated['anggota_id'];
        $tahun = $validated['tahun'];
        $paidMonths = collect($validated['months'])->unique()->sort()->values()->toArray();
        $jumlahBulan = count($paidMonths);
        $inputterRole = $validated['role']; // Ambil role dari request

        $biayaBulanan = config('iuran.monthly_fee', 10000);
        $totalNominal = $jumlahBulan * $biayaBulanan;

        // Cek duplikasi pembayaran (sama)
        $existingPaidMonths = IuranLog::where('anggota_id', $anggotaId)->where('tahun', $tahun)->whereIn('status', ['Verified', 'Pending'])->pluck('paid_months')->flatten()->unique();
        $alreadyPaidOrPending = collect($paidMonths)->intersect($existingPaidMonths);
        if ($alreadyPaidOrPending->isNotEmpty()) {
            return response()->json(['message' => 'Pembayaran gagal. Bulan berikut sudah lunas atau pending: ' . $alreadyPaidOrPending->implode(', ')], 422);
        }

        // --- PENENTUAN STATUS BERDASARKAN ROLE DARI REQUEST ---
        $isVerifier = in_array($inputterRole, ['Bendahara', 'Super Admin']); // Cek role dari request
        $status = $isVerifier ? 'Verified' : 'Pending';
        // --- AKHIR PENENTUAN STATUS ---

        // Hitung distribusi (sama)
        $distPercentage = config('iuran.distribution_percentage', 0.20);
        $distKeys = config('iuran.distribution_keys', ['pj', 'pc', 'pd', 'pw', 'pp']);
        $distributionValue = $totalNominal * $distPercentage;

        DB::beginTransaction();
        try {
            $logData = [
                'anggota_id' => $anggotaId,
                'nominal' => $totalNominal,
                'tanggal' => Carbon::now(),
                'tahun' => $tahun,
                'paid_months' => json_encode($paidMonths),
                'status' => $status,
                'pj_input_id' => Auth::id(), // Tetap catat siapa yg login & input
                // Set verifikator_id jika status langsung Verified
                'verifikator_id' => ($status === 'Verified') ? Auth::id() : null,
            ];
            foreach ($distKeys as $key) {
                $logData[$key] = $distributionValue;
            }

            IuranLog::create($logData);

            DB::commit();
            return response()->json(['message' => 'Pembayaran berhasil dicatat (' . $status . ')'], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error payMonths: " . $e->getMessage());
            return response()->json(['message' => 'Terjadi kesalahan saat menyimpan pembayaran.'], 500);
        }
    }

    public function getHistory(Request $request, $anggotaId)
    {
        $validated = $request->validate([
            'tahun' => 'required|integer|digits:4',
        ]);
        $tahun = $validated['tahun'];

        $history = IuranLog::where('anggota_id', $anggotaId)
            ->where('tahun', $tahun)
            // --- FILTER STATUS BARU ---
            ->whereIn('status', ['Verified', 'Failed'])
            // --- AKHIR FILTER ---
            ->with('verifikator:id,name')
            ->orderBy('tanggal', 'desc')
            ->orderBy('id', 'desc')
            ->get([
                'id',
                'tanggal',
                'created_at',
                'nominal',
                'paid_months',
                'status',
                'catatan_verifikasi',
                'verifikator_id' // atau 'pj_input_id.name' jika perlu penginput
            ]);
        return response()->json($history);
    }

    public function getPendingLogs(Request $request, $anggotaId)
    {
        // Otorisasi (Hanya Bendahara/Admin)
        // if (!Gate::allows('verifikasi-iuran')) { abort(403); }

        $validated = $request->validate([
            'tahun' => 'required|integer|digits:4',
        ]);
        $tahun = $validated['tahun'];

        $pendingLogs = IuranLog::where('anggota_id', $anggotaId)
            ->where('tahun', $tahun)
            ->where('status', 'Pending') // Hanya ambil yang Pending
            ->with('pjInput:id,name') // Ambil nama PJ yang input
            ->orderBy('tanggal', 'asc') // Urutkan dari yang terlama pending
            ->orderBy('id', 'asc')
            ->get([
                'id',
                'tanggal',
                'created_at',
                'nominal',
                'paid_months',
                'status',
                'pj_input_id' // Ambil pj_input_id
            ]);
        //Log::info('Pending Logs:', $pendingLogs->toArray()); // Log pending logs
        return response()->json($pendingLogs);
    }

    public function import(Request $request)
    {
        // Otorisasi (Hanya Admin/Bendahara) - Bisa via middleware atau Gate
        // if (!Gate::allows('import-iuran')) { abort(403); }

        // --- DEBUGGING AUTH ---
        $user = $request->user; // Dapatkan user yg login
        Log::info('Import request initiated by User:', [$user ? $user->id : 'NULL']);
        if (!$user) {
            // Seharusnya tidak terjadi jika middleware auth:sanctum aktif
            return response()->json(['message' => 'Akses tidak terautentikasi.'], 401);
        }
        // --- AKHIR DEBUGGING AUTH ---

        // Log request data sebelum validasi untuk cek file_import
        Log::info('Import Request Data (Before Validation):', $request->all());
        Log::info('Has file_import?', ['hasFile' => $request->hasFile('file_import')]);
        Log::info('File object:', ['file' => $request->file('file_import')]);


        $validator = Validator::make($request->all(), [
            'file_import' => 'required|file|mimes:xlsx,xls|max:5120', // Tambah max size (misal 5MB)
            'tahun' => 'required|integer|digits:4',
        ]);

        if ($validator->fails()) {
            Log::error('Import Validation Failed:', $validator->errors()->toArray());
            return response()->json(['message' => 'Validasi gagal.', 'errors' => $validator->errors()], 422);
        }

        $file = $request->file('file_import');
        $tahunImport = $request->input('tahun');

        // Buat instance import object, kirim user yg sudah diautentikasi
        $import = new IuranImport($tahunImport, $user);

        DB::beginTransaction();
        try {
            Excel::import($import, $file);

            if (!empty($import->getErrors())) {
                DB::rollBack();
                Log::warning('Import completed with data errors:', $import->getErrors());
                return response()->json([
                    'message' => 'Impor gagal. Ditemukan error pada data:',
                    'errors' => $import->getErrors()
                ], 422);
            }

            DB::commit();
            return response()->json([
                'message' => "Impor berhasil. {$import->getProcessedRowCount()} data pembayaran dicatat."
            ]);
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            DB::rollBack();
            $failures = $e->failures();
            $errorMessages = [];
            foreach ($failures as $failure) {
                $errorMessages[] = "Baris " . $failure->row() . ": " . implode(', ', $failure->errors());
            }
            Log::error('Excel Validation Exception:', $errorMessages);
            return response()->json(['message' => 'Impor gagal karena validasi data Excel.', 'errors' => $errorMessages], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error processing import file: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]); // Log trace
            return response()->json(['message' => 'Terjadi kesalahan saat memproses file impor.'], 500);
        }
    }
}
