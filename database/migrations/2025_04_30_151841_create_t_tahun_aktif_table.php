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
        Schema::dropIfExists('t_tahun_aktif');

        Schema::create('t_tahun_aktif', function (Blueprint $table) {
            $table->id(); // PK bigIncrements
            $table->year('tahun')->unique(); // Tahun unik
            $table->tinyInteger('bulan_awal');
            $table->tinyInteger('bulan_akhir');
            $table->enum('status', ['Aktif', 'Tidak Aktif'])->default('Aktif');
            $table->timestamps(); // created_at, updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_tahun_aktif');
    }
};
