<?php

namespace App\Http\Controllers;

use App\Models\JamaahModel;
use App\Models\MasterJamaahModel;
use Illuminate\Http\Request;

class JamaahController extends Controller
{

    public function index()
    {
        return response()->json(MasterJamaahModel::all(), 200);
    }
}
