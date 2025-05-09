<?php

namespace App\Imports;

use App\Models\IuranLog;
use App\Models\AnggotaModel; // Sesuaikan nama model
use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow; // Membaca berdasarkan nama header
use Maatwebsite\Excel\Concerns\WithValidation; // Untuk validasi bawaan library
use Illuminate\Support\Facades\Validator; // Untuk validasi manual tambahan
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

// Gunakan WithHeadingRow agar bisa akses kolom by nama header
class IuranImport implements ToCollection, WithHeadingRow, WithValidation
{
    private $tahunImport;
    private $user;
    private $biayaBulanan;
    private $distPercentage;
    private $distKeys;
    public $errors = []; // Menyimpan error per baris
    public $processedRowCount = 0;

    public function __construct(int $tahun, User $user)
    {
        $this->tahunImport = $tahun;
        $this->user = $user; // User yg melakukan import
        $this->biayaBulanan = config('iuran.monthly_fee', 10000);
        $this->distPercentage = config('iuran.distribution_percentage', 0.20);
        $this->distKeys = config('iuran.distribution_keys', ['pj', 'pc', 'pd', 'pw', 'pp']);
    }

    /**
     * @param Collection $rows Koleksi baris dari Excel
     */
    public function collection(Collection $rows)
    {
        $rowNumber = 1; // Mulai dari 1 karena heading row di-skip
        foreach ($rows as $row) {
            $rowNumber++;
            // Bersihkan data input
            $nik = trim($row['niknomor_anggota_wajib'] ?? null); // Sesuaikan nama header
            $monthsStr = trim($row['bulan_dibayar_angka_dipisah_koma_wajib'] ?? null);
            $tahunExcel = trim($row['tahun_wajib'] ?? null);
            $tanggalBayarStr = trim($row['tanggal_bayar_opsional_yyyy_mm_dd'] ?? null);

            // --- Validasi Awal ---
            if (empty($nik) || empty($monthsStr) || empty($tahunExcel)) {
                $this->errors[] = "Baris {$rowNumber}: Kolom NIK/Nomor Anggota, Bulan Dibayar, dan Tahun wajib diisi.";
                continue;
            }
            if ($tahunExcel != $this->tahunImport) {
                $this->errors[] = "Baris {$rowNumber}: Tahun ({$tahunExcel}) tidak sesuai dengan tahun filter ({$this->tahunImport}).";
                continue;
            }

            // Cari Anggota berdasarkan NIK/Nomor KTP
            // Sesuaikan 'nomor_ktp' dengan nama kolom di tabel t_anggota Anda
            $anggota = AnggotaModel::where('nik', $nik)->first();
            if (!$anggota) {
                $this->errors[] = "Baris {$rowNumber}: Anggota dengan NIK/Nomor Anggota '{$nik}' tidak ditemukan.";
                continue;
            }

            // --- DEBUGGING PROSES BULAN ---
            Log::info("Row {$rowNumber} (NIK: {$nik}) - Bulan String Mentah: '{$monthsStr}'");
            $monthsStrProcessed = str_replace('.', ',', $monthsStr); // Ganti titik dengan koma
            $monthsStrProcessed = preg_replace('/\s+/', '', $monthsStrProcessed); // Hapus spasi
            Log::info("Row {$rowNumber} - Bulan String Setelah Replace: '{$monthsStrProcessed}'");

            $explodedMonths = explode(',', $monthsStrProcessed);
            Log::info("Row {$rowNumber} - Hasil Explode:", $explodedMonths);

            $months = collect($explodedMonths)
                ->map(fn($m) => (int)trim($m)) // Trim spasi & konversi ke integer
                ->filter(fn($m) => $m >= 1 && $m <= 12) // Hanya ambil bulan valid (1-12)
                ->unique() // Hapus duplikat
                ->sort() // Urutkan
                ->values() // Reset keys
                ->toArray(); // Jadikan array PHP biasa

            Log::info("Row {$rowNumber} - Hasil Array Bulan Final:", $months);
            // --- AKHIR DEBUGGING ---

            if (empty($months)) {
                $this->errors[] = "Baris {$rowNumber}: Format bulan '{$monthsStr}' tidak valid atau tidak menghasilkan bulan valid.";
                continue;
            }

            // Cek duplikasi pembayaran
            $existingPaidMonths = IuranLog::where('anggota_id', $anggota->id_anggota)->where('tahun', $this->tahunImport)->whereIn('status', ['Verified', 'Pending'])->pluck('paid_months')->flatten()->unique();
            $alreadyPaidOrPending = collect($months)->intersect($existingPaidMonths);
            if ($alreadyPaidOrPending->isNotEmpty()) {
                $this->errors[] = "Baris {$rowNumber} (NIK: {$nik}): Bulan " . $alreadyPaidOrPending->implode(', ') . " sudah lunas/pending.";
                continue;
            }

            // Tentukan status
            $isVerifier = $this->user && in_array($this->user->role->name_role, ['Bendahara', 'Super Admin']);
            $status = $isVerifier ? 'Verified' : 'Pending';
            $nominal = count($months) * $this->biayaBulanan;
            $distributionValue = $nominal * $this->distPercentage;

            // Siapkan data log
            $logData = [
                'anggota_id' => $anggota->id_anggota,
                'nominal' => $nominal,
                'tanggal' => !empty($row['tanggal_bayar']) ? Carbon::parse($row['tanggal_bayar'])->toDateTimeString() : Carbon::now(),
                'tahun' => $this->tahunImport,
                'paid_months' => json_encode($months),
                'status' => $status,
                'pj_input_id' => $this->user ? $this->user->id : null,
                'verifikator_id' => ($status === 'Verified' && $this->user) ? $this->user->id : null,
            ];
            foreach ($this->distKeys as $key) {
                $logData[$key] = $distributionValue;
            }

            // Simpan ke database (bisa di-wrap transaction di controller)
            try {
                IuranLog::create($logData);
                $this->processedRowCount++;
            } catch (\Exception $e) {
                Log::error("Error importing row {$rowNumber} (NIK: {$nik}): " . $e->getMessage());
                $this->errors[] = "Baris {$rowNumber} (NIK: {$nik}): Gagal menyimpan data ke database.";
            }
        }
    }

    // Validasi bawaan library (opsional, bisa dihapus jika validasi manual cukup)
    public function rules(): array
    {
        return [
            // Sesuaikan nama header di Excel
            'niknomor_anggota_wajib' => 'required|string',
            'bulan_dibayar_angka_dipisah_koma_wajib' => 'required',
            'tahun_wajib' => 'required|integer|digits:4',
            'tanggal_bayar_opsional_yyyy_mm_dd' => 'nullable|date_format:Y-m-d',
            'nominal_total_opsional' => 'nullable|numeric'
        ];
    }

    // Custom validation messages (opsional)
    public function customValidationMessages()
    {
        return [
            'niknomor_anggota_wajib.required' => 'Kolom NIK/Nomor Anggota wajib diisi.',
            // ... pesan lain ...
        ];
    }

    // Method untuk mendapatkan error setelah import
    public function getErrors(): array
    {
        return $this->errors;
    }
    // Method untuk mendapatkan jumlah baris yg diproses
    public function getProcessedRowCount(): int
    {
        return $this->processedRowCount;
    }
}
