<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TingkatPendidikanModel extends Model
{
    use HasFactory;

    protected $table = 't_tingkat_pendidikan';
    protected $primaryKey = 'id_tingkat_pendidikan';
    protected $fillable = [
        'pendidikan',
    ];

    public function pendidikan()
    {
        return $this->hasOne(AnggotaPendidikanModel::class, 'id_tingkat_pendidikan', 'id_tingkat_pendidikan');
    }
}
