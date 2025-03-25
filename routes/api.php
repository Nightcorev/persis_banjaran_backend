<?php

use App\Http\Controllers\WebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AnggotaController;
use App\Http\Controllers\JamaahController;
use App\Http\Controllers\JamaahMonografiController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/anggota', [AnggotaController::class, 'index']);
Route::get('/anggota/all', [AnggotaController::class, 'selectAll']);
Route::get('/anggota/{id}', [AnggotaController::class, 'show']);
Route::post('/anggota', [AnggotaController::class, 'store']);
Route::put('/anggota/{id}', [AnggotaController::class, 'update']);
Route::delete('/anggota/{id}', [AnggotaController::class, 'destroy']);
Route::get('/anggota/by-jamaah/{id_master_jamaah?}', [AnggotaController::class, 'indexByJamaah']);

Route::get('/data_jamaah', [JamaahMonografiController::class, 'index']);
Route::get('/data_monografi', [AnggotaController::class, 'statistik']);
Route::get('/jamaah-monografi/{id_master_jamaah}', [JamaahMonografiController::class, 'show']);

Route::get('/data_chart', [AnggotaController::class, 'chart']);
Route::get('/data_choice_pribadi', [AnggotaController::class, 'getChoiceDataPribadi']);

Route::get('webhooks', [WebhookController::class, 'verifyWebhook']);
Route::post('webhooks', [WebhookController::class, 'handleWebhook']);

Route::prefix('permissions')->group(function () {
    Route::get('/', [PermissionController::class, 'index']);
    Route::post('/', [PermissionController::class, 'store']);
    Route::put('/{id}', [PermissionController::class, 'update']);
    Route::delete('/{id}', [PermissionController::class, 'destroy']);
});

Route::prefix('roles')->group(function () {
    Route::get('/', [RoleController::class, 'index']);
    Route::post('/', [RoleController::class, 'store']);
    Route::put('/{id}', [RoleController::class, 'update']);
    Route::delete('/{id}', [RoleController::class, 'destroy']);
});

Route::prefix('users')->group(function () {
    Route::get('/', [UserController::class, 'index']);
    Route::post('/', [UserController::class, 'store']);
    Route::put('/{id}', [UserController::class, 'update']);
    Route::delete('/{id}', [UserController::class, 'destroy']);
});
