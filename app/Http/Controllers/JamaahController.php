<?php

namespace App\Http\Controllers;

use App\Models\JamaahModel;
use App\Models\MasterJamaahModel;
use Illuminate\Http\Request;

/**
 * @OA\Tag(name="Jamaah", description="API untuk mengelola data monografi Jamaah")
 */
class JamaahController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/data_jamaah",
     *     tags={"Jamaah"},
     *     summary="Ambil semua data Jamaah",
     *     @OA\Response(response=200, description="Sukses", @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Jamaah"))),
     * )
     */
    public function index()
    {
        return response()->json(MasterJamaahModel::all(), 200);
    }
}
