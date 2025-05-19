<?php

namespace App\Exports;

// use App\Models\AnggotaModel; // Tidak perlu lagi jika data sudah diproses
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection; // Import Collection

class TemplatePembayaranExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected Collection $processedAnggotaData; // Tipe diubah menjadi Collection
    protected int $tahun;

    // Terima Collection yang sudah diproses
    public function __construct(Collection $processedAnggotaData, int $tahun)
    {
        $this->processedAnggotaData = $processedAnggotaData;
        $this->tahun = $tahun;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->processedAnggotaData;
    }

    public function headings(): array
    {
        return [
            'ID Anggota', // Kolom A (akan di-hide)
            // 'NIK/Nomor Anggota (Referensi)', // Kolom B
            'Nama Anggota',             // Kolom C
            'Bulan Dibayar (Angka dipisah koma)', // Kolom D
            'Tahun (Wajib)',            // Kolom E
            'Tanggal Bayar (Opsional: YYYY-MM-DD)', // Kolom F
            'Nominal Total (Opsional)', // Kolom G
        ];
    }

    /**
     * @param array $item Data anggota yang sudah diproses dari controller
     */
    public function map($item): array // Parameter sekarang adalah array/objek dari $processedAnggotaData
    {
        return [
            $item['id_anggota'], // Kolom A
            // $item['nomor_ktp'] ?? '', // Kolom B
            $item['nama_lengkap'],   // Kolom C
            $item['paid_months_string'], // Kolom D (Bulan Dibayar - sudah terisi jika ada)
            $this->tahun, // Kolom E (Tahun - terisi)
            '', // Kolom F (Tanggal Bayar - kosong untuk diisi)
            '', // Kolom G (Nominal Total - kosong untuk diisi)
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Sembunyikan kolom pertama (ID Anggota)
        //$sheet->getColumnDimension('A')->setVisible(false);

        // Style header
        $sheet->getStyle('A1:F1')->getFont()->setBold(true);
        $sheet->getStyle('A1:F1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE0E0E0');

        // Beri warna pada kolom D jika ada isinya (menandakan sudah ada pembayaran)
        for ($row = 2; $row <= $sheet->getHighestRow(); $row++) {
            $cellValue = $sheet->getCell('C' . $row)->getValue();
            if (!empty($cellValue)) {
                $sheet->getStyle('C' . $row)->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFF0F8FF'); // Warna AliceBlue
            }
        }
        return [];
    }
}
