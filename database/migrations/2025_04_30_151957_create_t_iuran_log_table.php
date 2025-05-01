<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Hapus tabel jika sudah ada untuk idempotensi (opsional)
        Schema::dropIfExists('t_iuran_log');

        Schema::create('t_iuran_log', function (Blueprint $table) {
            $table->id(); // PK bigIncrements

            // Asumsi tabel t_anggota sudah ada dengan PK `id`
            $table->foreignId('anggota_id')->constrained('t_anggota', 'id_anggota')->onDelete('cascade');

            $table->decimal('nominal', 15, 2)->comment('Nominal pembayaran');
            $table->date('tanggal')->comment('Tanggal transaksi dicatat');
            $table->year('tahun')->comment('Tahun iuran yg dibayar');

            // Kolom distribusi (asumsi 20% per bagian)
            $table->decimal('pj', 15, 2)->default(0);
            $table->decimal('pc', 15, 2)->default(0);
            $table->decimal('pd', 15, 2)->default(0);
            $table->decimal('pw', 15, 2)->default(0);
            $table->decimal('pp', 15, 2)->default(0);

            // Status & Verifikasi
            $table->enum('status', ['Pending', 'Verified', 'Failed'])->default('Pending');
            $table->text('catatan_verifikasi')->nullable();

            // Asumsi tabel users sudah ada dengan PK `id`
            $table->foreignId('verifikator_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('pj_input_id')->nullable()->constrained('users')->onDelete('set null'); // User PJ yg input

            $table->timestamps(); // created_at, updated_at

            // Indexes untuk performa query
            $table->index(['anggota_id', 'tahun', 'status']);
            $table->index('status');
            $table->index('tahun');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_iuran_log');
    }
};
