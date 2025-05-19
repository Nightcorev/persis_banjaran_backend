<?php

namespace App\Imports;

use App\Models\IuranLog;
use App\Models\AnggotaModel; // Sesuaikan nama model jika berbeda
use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow; // Penting untuk membaca berdasarkan nama header
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class IuranImport implements ToCollection, WithHeadingRow, WithValidation
{
    private int $tahunImport;
    private User $user;
    private float $biayaBulanan;
    private float $distPercentage;
    private array $distKeys;
    public array $errors = [];
    public int $processedRowCount = 0;
    public int $skippedRowCount = 0; // Untuk melacak baris yg dilewati

    public function __construct(int $tahun, User $user)
    {
        $this->tahunImport = $tahun;
        $this->user = $user;
        $this->biayaBulanan = config('iuran.monthly_fee', 10000);
        $this->distPercentage = config('iuran.distribution_percentage', 0.20);
        $this->distKeys = config('iuran.distribution_keys', ['pj', 'pc', 'pd', 'pw', 'pp']);
    }

    /**
     * @param Collection $rows
     */
    public function collection(Collection $rows)
    {
        $rowNumber = 1; // WithHeadingRow starts data from row 2, so Excel row is $rowNumber + 1
        foreach ($rows as $row) {
            Log::debug("Importing Row Data (Excel Row " . ($rowNumber + 1) . "): ", $row->toArray());

            // Sesuaikan nama header dengan yang ada di file Excel Anda
            // Kunci di sini HARUS cocok dengan slug header Excel
            $anggotaIdExcel = trim($row['id_anggota'] ?? null);
            // Menggunakan kunci yang sesuai dengan pesan error dan kemungkinan header Excel
            $monthsInput = (string)($row['bulan_dibayar_angka_dipisah_koma'] ?? $row['bulan_dibayar_angka_dipisah_koma_wajib'] ?? null);
            $tahunExcel = trim($row['tahun_wajib'] ?? null);
            $tanggalBayarStr = trim($row['tanggal_bayar_opsional_yyyymmdd'] ?? null);

            // Validasi Awal: ID Anggota dan Tahun tetap wajib ada di baris Excel
            if (empty($anggotaIdExcel) || empty($tahunExcel)) {
                $this->errors[] = "Baris Excel " . ($rowNumber + 1) . ": Kolom ID Anggota dan Tahun wajib diisi.";
                $rowNumber++;
                continue;
            }
            if ($tahunExcel != $this->tahunImport) {
                $this->errors[] = "Baris Excel " . ($rowNumber + 1) . ": Tahun ({$tahunExcel}) tidak sesuai dengan tahun filter ({$this->tahunImport}).";
                $rowNumber++;
                continue;
            }

            // Cari Anggota berdasarkan ID
            $anggota = AnggotaModel::find($anggotaIdExcel);
            if (!$anggota) {
                $this->errors[] = "Baris Excel " . ($rowNumber + 1) . ": Anggota dengan ID '{$anggotaIdExcel}' tidak ditemukan.";
                $rowNumber++;
                continue;
            }

            // Jika kolom bulan kosong, lewati proses pembayaran untuk anggota ini, tapi catat
            if (empty($monthsInput)) {
                Log::info("Row Excel " . ($rowNumber + 1) . " (ID Anggota: {$anggotaIdExcel}): Kolom bulan kosong, tidak ada pembayaran baru diproses.");
                $this->skippedRowCount++;
                $rowNumber++;
                continue;
            }

            // Proses bulan dari Excel
            $monthsStrProcessed = str_replace('.', ',', $monthsInput);
            $explodedMonths = explode(',', $monthsStrProcessed);
            $monthsFromExcel = collect($explodedMonths)
                ->map(fn($m) => (int)trim($m))
                ->filter(fn($m) => $m >= 1 && $m <= 12)
                ->unique()
                ->sort()
                ->values();

            if ($monthsFromExcel->isEmpty()) {
                $this->errors[] = "Baris Excel " . ($rowNumber + 1) . " (ID Anggota: {$anggotaIdExcel}): Format bulan '{$monthsInput}' tidak valid atau tidak menghasilkan bulan valid.";
                $rowNumber++;
                continue;
            }
            Log::info("Row Excel " . ($rowNumber + 1) . " - Bulan dari Excel (setelah parse):", $monthsFromExcel->toArray());


            // Ambil semua bulan yang sudah dibayar (Verified/Pending) untuk anggota ini di tahun ini
            $existingPaidOrPendingMonths = IuranLog::where('anggota_id', $anggota->id_anggota)
                ->where('tahun', $this->tahunImport)
                ->whereIn('status', ['Verified', 'Pending'])
                ->pluck('paid_months')
                ->flatMap(function ($jsonMonths) {
                    $decoded = json_decode($jsonMonths, true);
                    return is_array($decoded) ? $decoded : [];
                })
                ->map(fn($m) => (int)$m)
                ->unique();

            Log::info("Row Excel " . ($rowNumber + 1) . " - Bulan Sudah Ada (Verified/Pending):", $existingPaidOrPendingMonths->toArray());

            // Tentukan bulan baru yang akan diproses
            $newMonthsToProcess = $monthsFromExcel->diff($existingPaidOrPendingMonths)->sort()->values()->toArray();

            Log::info("Row Excel " . ($rowNumber + 1) . " - Bulan Baru Akan Diproses:", $newMonthsToProcess);

            if (empty($newMonthsToProcess)) {
                $this->skippedRowCount++;
                $rowNumber++;
                continue;
            }

            // Tentukan status & hitung nominal/distribusi berdasarkan newMonthsToProcess
            $isVerifier = $this->user && in_array($this->user->role->name_role, ['Bendahara', 'Super Admin']);
            $status = $isVerifier ? 'Verified' : 'Pending';
            $nominal = count($newMonthsToProcess) * $this->biayaBulanan;
            $distributionValue = $nominal * $this->distPercentage;

            $logData = [
                'anggota_id' => $anggota->id_anggota,
                'nominal' => $nominal,
                'tanggal' => !empty($tanggalBayarStr) ? Carbon::parse($tanggalBayarStr)->toDateString() : Carbon::now(),
                'tahun' => $this->tahunImport,
                'paid_months' => json_encode($newMonthsToProcess),
                'status' => $status,
                'pj_input_id' => $this->user ? $this->user->id : null,
                'verifikator_id' => ($status === 'Verified' && $this->user) ? $this->user->id : null,
            ];
            foreach ($this->distKeys as $key) {
                $logData[$key] = $distributionValue;
            }

            try {
                IuranLog::create($logData);
                $this->processedRowCount++;
            } catch (\Exception $e) {
                Log::error("Error importing row Excel " . ($rowNumber + 1) . " (ID Anggota: {$anggotaIdExcel}): " . $e->getMessage());
                $this->errors[] = "Baris Excel " . ($rowNumber + 1) . " (ID Anggota: {$anggotaIdExcel}): Gagal menyimpan data ke database.";
            }
            $rowNumber++;
        }
    }

    public function rules(): array
    {
        // Pastikan nama header di sini SAMA PERSIS dengan slug dari header di file Excel Anda
        // Jika header Excel adalah "Bulan Dibayar Angka Dipisah Koma", slugnya "bulan_dibayar_angka_dipisah_koma"
        return [
            'id_anggota' => 'required|integer',
            'niknomor_ktp_referensi' => 'nullable|string',
            'nama_anggota' => 'nullable|string',
            // Menggunakan kunci yang sesuai dengan pesan error, dan hanya 'nullable'
            'bulan_dibayar_angka_dipisah_koma' => 'nullable',
            'tahun_wajib' => 'required|integer|digits:4',
            'tanggal_bayar_opsional_yyyymmdd' => 'nullable|date_format:Y-m-d',
            'nominal_total_opsional' => 'nullable|numeric'
        ];
    }

    public function customValidationMessages()
    {
        return [
            'id_anggota.required' => 'Kolom "ID Anggota (Hidden)" wajib diisi.',
            'id_anggota.integer' => 'Kolom "ID Anggota (Hidden)" harus berupa angka.',
            // Tidak perlu pesan required untuk bulan jika sudah nullable
            'tahun_wajib.required' => 'Kolom "Tahun (Wajib)" wajib diisi.',
            // ... pesan lain jika perlu
        ];
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
    public function getProcessedRowCount(): int
    {
        return $this->processedRowCount;
    }
    public function getSkippedRowCount(): int
    {
        return $this->skippedRowCount;
    }
}
