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
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->unique()->after('id');
            $table->foreignId('role_id')->constrained()->onDelete('cascade')->after('username');
            $table->foreignId('id_anggota')->constrained('t_anggota', 'id_anggota')->onDelete('cascade')->after('role_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
            $table->dropForeign(['role_id', 'id_anggota']);
            $table->dropColumn(['username', 'role_id', 'id_anggota']);
        });
    }
};
