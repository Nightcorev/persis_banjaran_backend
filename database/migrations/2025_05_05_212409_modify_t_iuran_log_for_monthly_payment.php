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
        Schema::table('t_iuran_log', function (Blueprint $table) {
            // Tambahkan kolom untuk menyimpan bulan yg dibayar (format JSON)
            // Pastikan tipe data JSON didukung oleh database Anda (PostgreSQL, MySQL 5.7+)
            $table->json('paid_months')->nullable()->after('tahun')->comment('Array nomor bulan yang dibayar [1, 2, ...]');

            // Hapus kolom 'bulan' yang lama jika tidak diperlukan lagi
            if (Schema::hasColumn('t_iuran_log', 'bulan')) {
                $table->dropColumn('bulan');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('t_iuran_log', function (Blueprint $table) {
            if (Schema::hasColumn('t_iuran_log', 'paid_months')) {
                $table->dropColumn('paid_months');
            }
            // Tambahkan kembali kolom 'bulan' jika perlu rollback
            // $table->tinyInteger('bulan')->nullable()->after('tahun');
        });
    }
};
