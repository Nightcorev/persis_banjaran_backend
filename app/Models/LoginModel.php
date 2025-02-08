<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoginModel extends Model
{
    use HasFactory;
    protected $table = 't_login';
    protected $primaryKey = 'id_login';
    protected $fillable = [
        'username',
        'password',
        'level'
    ];
}
