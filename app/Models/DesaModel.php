<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DesaModel extends Model
{
    use HasFactory;
    protected $table = 't_desa';
    protected $primaryKey = 'id_desa';
    protected $fillable = [
        'nama_desa'
    ];
}
