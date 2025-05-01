<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class BroadcastModel extends Model
{
    use HasFactory;
    protected $table = 't_broadcast';
    protected $primaryKey = 'id_broadcast';
    public $timestamps = false;
    protected $fillable = [
        'headline',
        'deskripsi',
        'tipe_broadcast',
        'nama_file',
        'status_pengiriman',
        'waktu_pengiriman',
        'tujuan',
    ];
}
