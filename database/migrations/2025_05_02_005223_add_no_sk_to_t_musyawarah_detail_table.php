<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNoSkToTMusyawarahDetailTable extends Migration
{
    public function up()
    {
        Schema::table('t_musyawarah_detail', function (Blueprint $table) {
            $table->string('no_sk', 255)->nullable()->after('id_anggota');
        });
    }

    public function down()
    {
        Schema::table('t_musyawarah_detail', function (Blueprint $table) {
            $table->dropColumn('no_sk');
        });
    }
}

