<?php

namespace App\Http\Controllers;

use App\Exports\RekapIuranJamaahExport;
use App\Exports\TemplatePembayaranExport;
use App\Http\Controllers\Controller;
use App\Imports\IuranImport;
use Illuminate\Http\Request;
use App\Models\IuranLog;
use App\Models\AnggotaModel;
use App\Models\MasterJamaahModel;
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
        $tahunAktif = $request->input('tahun', TahunAktif::where('status', 'Aktif')->value('tahun'));
        if (!$tahunAktif) {
            return response()->json(['message' => 'Tahun iuran tidak valid.'], 400);
        }
        $perPage = $request->input('per_page', 10);
        $jamaahId = $request->input('jamaah_id');
        $searchTerm = $request->input('search');
        $statusFilter = $request->input('filter_status_bulan');

        $query = AnggotaModel::query()
            ->select(['t_anggota.id_anggota', 't_anggota.nama_lengkap', 't_anggota.id_master_jamaah', 't_master_jamaah.nama_jamaah'])
            ->join('t_master_jamaah', 't_anggota.id_master_jamaah', '=', 't_master_jamaah.id_master_jamaah')
            ->with(['iuranLogs' => function ($q) use ($tahunAktif) {
                $q->where('tahun', (int)$tahunAktif)
                    ->orderByRaw("CASE status WHEN 'Verified' THEN 1 WHEN 'Pending' THEN 2 WHEN 'Failed' THEN 3 ELSE 4 END")
                    ->orderBy('id', 'desc')
                    ->select('id', 'anggota_id', 'status', 'paid_months', 'catatan_verifikasi');
            }])
            ->where('t_anggota.status_aktif', 1)
            ->orderby('t_anggota.nama_lengkap');

        if ($jamaahId) {
            $query->where('t_anggota.id_master_jamaah', $jamaahId);
        }
        if ($searchTerm) {
            $query->where('t_anggota.nama_lengkap', 'ILIKE', '%' . $searchTerm . '%');
        }

        if ($statusFilter && in_array($statusFilter, ['Pending', 'Verified', 'Failed'])) {
            $query->whereHas('iuranLogs', function ($q_log) use ($tahunAktif, $statusFilter) {
                $q_log->where('tahun', (int)$tahunAktif)
                    ->where('status', $statusFilter);
            });
        }

        $paginatedData = $query->paginate($perPage);

        $paginatedData->getCollection()->transform(function ($item) {
            $bulanStatusProses = [];
            $catatanFailed = [];

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

                    foreach ($paidMonthsInLog as $month) {
                        if (!isset($bulanStatusProses[$month])) {
                            $bulanStatusProses[$month] = $log->status;
                            if ($log->status === 'Failed') {
                                $catatanFailed[$month] = $log->catatan_verifikasi;
                            }
                        }
                    }
                }
            }

            $finalBulanStatus = [];
            for ($i = 1; $i <= 12; $i++) {
                $status = $bulanStatusProses[$i] ?? 'Belum Lunas';
                $finalBulanStatus[$i] = [
                    'status' => $status,
                    'catatan' => ($status === 'Failed') ? ($catatanFailed[$i] ?? 'Tidak ada catatan.') : null,
                ];
            }

            $resultItem = new \stdClass();
            $resultItem->anggota_id = $item->id_anggota;
            $resultItem->nama_lengkap = $item->nama_lengkap;
            $resultItem->nama_jamaah = $item->nama_jamaah;
            $resultItem->bulan_status = $finalBulanStatus;

            return $resultItem;
        });

        return response()->json($paginatedData);
    }

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
    public function verifyLog(IuranLog $iuranLog)
    {
        if ($iuranLog->status !== 'Pending') {
            return response()->json(['message' => 'Hanya pembayaran pending yang bisa diverifikasi.'], 400);
        }

        try {
            $iuranLog->update([
                'status' => 'Verified',
                'verifikator_id' => Auth::id(),
                'catatan_verifikasi' => null,
            ]);
            return response()->json(['message' => 'Pembayaran berhasil diverifikasi.']);
        } catch (\Exception $e) {
            Log::error("Error verifyLog (ID: {$iuranLog->id}): " . $e->getMessage());
            return response()->json(['message' => 'Gagal memverifikasi pembayaran.'], 500);
        }
    }

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
    public function rejectLog(Request $request, IuranLog $iuranLog)
    {
        $validated = $request->validate([
            'catatan' => 'required|string|max:500',
        ]);

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

    public function sendBatchReminder(Request $request)
    {
        $validated = $request->validate([
            'anggota_ids' => 'required|array|min:1',
            'anggota_ids.*' => [
                'required',
                'integer',
                Rule::exists('t_anggota', 'id_anggota')
            ],
        ]);

        $anggotaIds = $validated['anggota_ids'];
        $sentCount = 0;
        $failedIds = [];

        $anggotas = AnggotaModel::whereIn('id_anggota', $anggotaIds)
            ->select('id_anggota', 'nama_lengkap', 'no_telp')
            ->get()
            ->keyBy('id_anggota');

        foreach ($anggotaIds as $id) {
            $anggota = $anggotas->get($id);

            if ($anggota) {
                try {
                    Log::info("Reminder Sent (Simulated):", [
                        'anggota_id' => $anggota->id_anggota,
                        'nama' => $anggota->nama_lengkap,
                        'no_telp' => $anggota->no_telp ?? 'N/A',
                        'sender_user_id' => Auth::id(),
                        'timestamp' => now()
                    ]);
                    $sentCount++;
                } catch (\Exception $e) {
                    Log::error("Failed sending reminder to Anggota ID: {$id}", ['error' => $e->getMessage()]);
                    $failedIds[] = $id;
                }
            } else {
                Log::warning("Anggota ID not found for reminder: {$id}");
                $failedIds[] = $id;
            }
        }

        $message = "Reminder (simulasi) berhasil dikirim ke {$sentCount} anggota.";
        if (!empty($failedIds)) {
            $message .= " Gagal mengirim ke ID: " . implode(', ', $failedIds) . ".";
            return response()->json(['message' => $message], 500);
        }

        return response()->json(['message' => $message]);
    }

    public function getTunggakan(Request $request)
    {
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

        $query = AnggotaModel::query()
            ->select([
                't_anggota.id_anggota',
                't_anggota.nama_lengkap',
                't_anggota.no_telp',
                't_master_jamaah.nama_jamaah',
                DB::raw('(SELECT SUM(nominal) FROM t_iuran_log WHERE t_iuran_log.anggota_id = t_anggota.id_anggota AND t_iuran_log.tahun = ? AND t_iuran_log.status = ?) as total_verified_paid')
            ])
            ->addBinding($tahunAktif, 'select')
            ->addBinding('Verified', 'select')
            ->join('t_master_jamaah', 't_anggota.id_master_jamaah', '=', 't_master_jamaah.id_master_jamaah')
            ->where('t_anggota.status_aktif', 1);

        $query->where(function ($subQuery) use ($tahunAktif, $totalHarusBayar) {
            $subQuery->whereRaw('(COALESCE((SELECT SUM(nominal) FROM t_iuran_log WHERE t_iuran_log.anggota_id = t_anggota.id_anggota AND t_iuran_log.tahun = ? AND t_iuran_log.status = ?), 0)) < ?', [
                $tahunAktif,
                'Verified',
                $totalHarusBayar
            ]);
        });

        if ($jamaahId) {
            $query->where('t_anggota.id_master_jamaah', $jamaahId);
        }
        if ($searchTerm) {
            $query->where('t_anggota.nama_lengkap', 'ILIKE', '%' . $searchTerm . '%');
        }
        $query->orderBy('t_anggota.nama_lengkap');
        $paginatedData = $query->paginate($perPage);

        $paginatedData->getCollection()->transform(function ($item) use ($biayaBulanan, $bulanSaatIni, $totalHarusBayar, $namaBulanIndonesia) {
            $totalVerifiedPaid = $item->total_verified_paid ?? 0;
            $bulanLunasTerakhir = floor($totalVerifiedPaid / $biayaBulanan);
            $jumlahBulanTunggakan = $bulanSaatIni - $bulanLunasTerakhir;
            $nominalTunggakan = max(0, $totalHarusBayar - $totalVerifiedPaid);

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
        $validated = $request->validate([
            'anggota_id' => ['required', Rule::exists('t_anggota', 'id_anggota')],
            'tahun' => 'required|integer|digits:4',
            'months' => 'required|array|min:1',
            'months.*' => 'required|integer|between:1,12',
            'role' => 'required|string|in:Pimpinan Jamaah,Bendahara,Super Admin'
        ]);

        $anggotaId = $validated['anggota_id'];
        $tahun = $validated['tahun'];
        $paidMonths = collect($validated['months'])->unique()->sort()->values()->toArray();
        $jumlahBulan = count($paidMonths);
        $inputterRole = $validated['role'];

        $biayaBulanan = config('iuran.monthly_fee', 10000);
        $totalNominal = $jumlahBulan * $biayaBulanan;

        $existingPaidMonths = IuranLog::where('anggota_id', $anggotaId)->where('tahun', $tahun)->whereIn('status', ['Verified', 'Pending'])->pluck('paid_months')->flatten()->unique();
        $alreadyPaidOrPending = collect($paidMonths)->intersect($existingPaidMonths);
        if ($alreadyPaidOrPending->isNotEmpty()) {
            return response()->json(['message' => 'Pembayaran gagal. Bulan berikut sudah lunas atau pending: ' . $alreadyPaidOrPending->implode(', ')], 422);
        }

        $isVerifier = in_array($inputterRole, ['Bendahara', 'Super Admin']);
        $status = $isVerifier ? 'Verified' : 'Pending';

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
                'pj_input_id' => Auth::id(),
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
            ->whereIn('status', ['Verified', 'Failed'])
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
                'verifikator_id'
            ]);
        return response()->json($history);
    }

    public function getPendingLogs(Request $request, $anggotaId)
    {
        $validated = $request->validate([
            'tahun' => 'required|integer|digits:4',
        ]);
        $tahun = $validated['tahun'];

        $pendingLogs = IuranLog::where('anggota_id', $anggotaId)
            ->where('tahun', $tahun)
            ->where('status', 'Pending')
            ->with('pjInput:id,name')
            ->orderBy('tanggal', 'asc')
            ->orderBy('id', 'asc')
            ->get([
                'id',
                'tanggal',
                'created_at',
                'nominal',
                'paid_months',
                'status',
                'pj_input_id'
            ]);
        return response()->json($pendingLogs);
    }

    public function import(Request $request)
    {
        $user = $request->user;
        if (!$user) {
            return response()->json(['message' => 'Akses tidak terautentikasi.'], 401);
        }

        $validator = Validator::make($request->all(), [
            'file_import' => 'required|file|mimes:xlsx,xls|max:5120',
            'tahun' => 'required|integer|digits:4',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal.', 'errors' => $validator->errors()], 422);
        }

        $file = $request->file('file_import');
        $tahunImport = $request->input('tahun');
        $import = new IuranImport($tahunImport, $user);

        DB::beginTransaction();
        try {
            Excel::import($import, $file);
            if (!empty($import->getErrors())) {
                DB::rollBack();
                return response()->json(['message' => 'Impor gagal. Ditemukan error pada data:', 'errors' => $import->getErrors()], 422);
            }
            DB::commit();
            return response()->json(['message' => "Impor berhasil. {$import->getProcessedRowCount()} data pembayaran dicatat."]);
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
            Log::error("Error processing import file: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'Terjadi kesalahan saat memproses file impor.'], 500);
        }
    }

    public function getRekapJamaah(Request $request)
    {
        $validated = $request->validate([
            'tahun' => 'required|integer|digits:4',
        ]);
        $tahun = $validated['tahun'];
        $monthlyFee = config('iuran.monthly_fee', 10000);
        $totalMonthsInYear = 12;

        $jamaahs = MasterJamaahModel::withCount(['anggota as jumlah_anggota' => function ($query) {
            $query->where('status_aktif', 1);
        }])
            ->with(['anggota' => function ($query) use ($tahun) {
                $query
                    ->with(['iuranLogs' => function ($logQuery) use ($tahun) {
                        $logQuery->where('tahun', $tahun)
                            ->where('status', 'Verified')
                            ->select('anggota_id', 'paid_months');
                    }]);
            }])
            ->orderBy('nama_jamaah')
            ->get();

        $rekapData = $jamaahs->map(function ($jamaah) use ($monthlyFee, $totalMonthsInYear, $tahun) {
            $totalSudahDibayarJamaah = 0;
            $totalAnggotaDiJamaah = $jamaah->jumlah_anggota;

            foreach ($jamaah->anggota as $anggota) {
                $uniqueVerifiedMonthsForAnggota = new Collection();
                foreach ($anggota->iuranLogs as $log) {
                    $decodedMonths = json_decode($log->paid_months, true);
                    if (is_array($decodedMonths)) {
                        $paidMonthsInLog = collect($decodedMonths)->map(fn($m) => (int)$m)->filter(fn($m) => $m > 0);
                        $uniqueVerifiedMonthsForAnggota = $uniqueVerifiedMonthsForAnggota->merge($paidMonthsInLog);
                    } else {
                        Log::warning("paid_months bukan JSON array valid untuk log ID: {$log->id} pada anggota ID: {$anggota->id_anggota}");
                    }
                }
                $countUniqueVerifiedMonths = $uniqueVerifiedMonthsForAnggota->unique()->count();
                $totalSudahDibayarJamaah += min($countUniqueVerifiedMonths, $totalMonthsInYear) * $monthlyFee;
            }

            $totalHarusBayarJamaahSetahun = $totalAnggotaDiJamaah * $totalMonthsInYear * $monthlyFee;
            $totalBelumDibayarJamaah = max(0, $totalHarusBayarJamaahSetahun - $totalSudahDibayarJamaah);

            return [
                'id_jamaah' => $jamaah->id_master_jamaah,
                'nama_jamaah' => $jamaah->nama_jamaah,
                'jumlah_anggota' => $totalAnggotaDiJamaah,
                'total_sudah_dibayar' => $totalSudahDibayarJamaah,
                'total_belum_dibayar' => $totalBelumDibayarJamaah,
            ];
        });

        return response()->json($rekapData);
    }

    public function exportRekapJamaah(Request $request)
    {
        $validated = $request->validate([
            'tahun' => 'required|integer|digits:4',
        ]);
        $tahun = $validated['tahun'];
        $monthlyFee = config('iuran.monthly_fee', 10000);
        $totalMonthsInYear = 12;

        $jamaahs = MasterJamaahModel::withCount(['anggota as jumlah_anggota' => function ($query) {
            $query->where('t_anggota.status_aktif', 1);
        }])
            ->with(['anggota' => function ($query) use ($tahun) {
                $query
                    ->with(['iuranLogs' => function ($logQuery) use ($tahun) {
                        $logQuery->where('tahun', $tahun)
                            ->where('status', 'Verified')
                            ->select('anggota_id', 'paid_months');
                    }]);
            }])
            ->orderBy('nama_jamaah')
            ->get();

        $dataForExport = $jamaahs->map(function ($jamaah) use ($monthlyFee, $totalMonthsInYear) {
            $totalSudahDibayarJamaah = 0;
            $totalAnggotaDiJamaah = $jamaah->jumlah_anggota;

            foreach ($jamaah->anggota as $anggota) {
                $uniqueVerifiedMonthsForAnggota = new Collection();
                foreach ($anggota->iuranLogs as $log) {
                    $decodedMonths = json_decode($log->paid_months, true);
                    if (is_array($decodedMonths)) {
                        $paidMonthsInLog = collect($decodedMonths)->map(fn($m) => (int)$m)->filter(fn($m) => $m > 0);
                        $uniqueVerifiedMonthsForAnggota = $uniqueVerifiedMonthsForAnggota->merge($paidMonthsInLog);
                    }
                }
                $countUniqueVerifiedMonths = $uniqueVerifiedMonthsForAnggota->unique()->count();
                $totalSudahDibayarJamaah += min($countUniqueVerifiedMonths, $totalMonthsInYear) * $monthlyFee;
            }

            $totalHarusBayarJamaahSetahun = $totalAnggotaDiJamaah * $totalMonthsInYear * $monthlyFee;
            $totalBelumDibayarJamaah = max(0, $totalHarusBayarJamaahSetahun - $totalSudahDibayarJamaah);

            return [
                'Nama Jamaah' => $jamaah->nama_jamaah,
                'Sudah Dibayar (Rp)' => $totalSudahDibayarJamaah,
                'Belum Dibayar (Rp)' => $totalBelumDibayarJamaah,
            ];
        })->toArray();

        $grandTotalSudahDibayar = array_sum(array_column($dataForExport, 'Sudah Dibayar (Rp)'));
        $grandTotalBelumDibayar = array_sum(array_column($dataForExport, 'Belum Dibayar (Rp)'));
        $dataForExport[] = [];
        $dataForExport[] = [
            'Nama Jamaah' => 'TOTAL KESELURUHAN',
            'Sudah Dibayar (Rp)' => $grandTotalSudahDibayar,
            'Belum Dibayar (Rp)' => $grandTotalBelumDibayar,
        ];

        return Excel::download(new RekapIuranJamaahExport($dataForExport), "rekap_iuran_jamaah_{$tahun}.xlsx");
    }

    public function downloadTemplatePembayaran(Request $request, $jamaah_id)
    {
        $validated = $request->validate([
            'tahun' => 'required|integer|digits:4',
        ]);
        $tahun = $validated['tahun'];

        $jamaah = MasterJamaahModel::find($jamaah_id);

        if (!$jamaah) {
            Log::error("Download Template - Jamaah dengan ID: {$jamaah_id} tidak ditemukan.");
            return response()->json(['message' => 'Data jamaah tidak ditemukan.'], 404);
        }

        $anggotaDenganIuran = AnggotaModel::where('id_master_jamaah', $jamaah_id)
            ->with(['iuranLogs' => function ($query) use ($tahun) {
                $query->where('tahun', $tahun)
                    ->whereIn('status', ['Verified', 'Pending'])
                    ->select('anggota_id', 'paid_months');
            }])
            ->select('id_anggota', 'nama_lengkap', 'nomor_ktp')
            ->where('status_aktif', 1)
            ->orderBy('nama_lengkap')
            ->get();

        Log::info("Download Template - Jamaah: {$jamaah->nama_jamaah}, Jumlah Anggota Ter-load: " . $anggotaDenganIuran->count());

        if ($anggotaDenganIuran->isEmpty()) {
            Log::warning("Download Template - Tidak ada anggota di jamaah '{$jamaah->nama_jamaah}' (ID: {$jamaah_id}) untuk dibuatkan template.");
            return response()->json(['message' => 'Tidak ada anggota di jamaah ini untuk dibuatkan template.'], 404);
        }

        $dataUntukExport = $anggotaDenganIuran->map(function ($anggota) {
            $paidMonthsSet = new Collection();
            if ($anggota->relationLoaded('iuranLogs')) {
                foreach ($anggota->iuranLogs as $log) {
                    $decodedMonths = json_decode($log->paid_months, true);
                    if (is_array($decodedMonths)) {
                        $paidMonthsInLog = collect($decodedMonths)->map(fn($m) => (int)$m)->filter(fn($m) => $m > 0);
                        $paidMonthsSet = $paidMonthsSet->merge($paidMonthsInLog);
                    }
                }
            }
            $paidMonthsString = $paidMonthsSet->unique()->sort()->implode(',');

            return [
                'id_anggota' => $anggota->id_anggota,
                'nama_lengkap' => $anggota->nama_lengkap,
                'paid_months_string' => $paidMonthsString,
            ];
        });

        return Excel::download(new TemplatePembayaranExport($dataUntukExport, $tahun), "template_pembayaran_{$jamaah->nama_jamaah}_{$tahun}.xlsx");
    }

    public function getPendingCount(Request $request)
    {
        $validated = $request->validate([
            'tahun' => 'required|integer|digits:4',
        ]);
        $tahun = $validated['tahun'];

        $pendingAnggotaCount = AnggotaModel::whereHas('iuranLogs', function ($query) use ($tahun) {
            $query->where('tahun', $tahun)->where('status', 'Pending');
        })->count();

        return response()->json(['pending_count' => $pendingAnggotaCount]);
    }
}
