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
            $table->timestamp('tanggal')->comment('Tanggal dan waktu transaksi dicatat')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('t_iuran_log', function (Blueprint $table) {
            // Kembalikan ke tipe DATE jika rollback
            $table->date('tanggal')->comment('Tanggal transaksi dicatat')->change();
        });
    }
};
