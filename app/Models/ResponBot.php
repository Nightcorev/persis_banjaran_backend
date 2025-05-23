<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResponBot extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang terhubung dengan model.
     *
     * @var string
     */
    protected $table = 't_respon_bot';

    /**
     * Nama primary key tabel.
     *
     * @var string
     */
    protected $primaryKey = 'id_respon_bot';

    public $incrementing = true;

    protected $keyType = 'int';

    /**
     * Atribut yang dapat diisi secara massal.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'keyword',
        'function',
        'pesan',
        'jawaban',
        'tipe_respon',
    ];

    /**
     * Tipe data atribut yang harus di-cast.
     * (Tidak ada cast khusus yang diperlukan untuk text)
     *
     * @var array<string, string>
     */
    // protected $casts = [];
    public $timestamps = false;
}
