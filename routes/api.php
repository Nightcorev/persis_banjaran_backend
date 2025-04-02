<?php

use App\Http\Controllers\WebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AnggotaController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\JamaahMonografiController;
use App\Http\Controllers\PesantrenController;
use App\Http\Controllers\JamaahFasilitasController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;

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

// Route untuk login dan logout
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth.token');

// Routes yang membutuhkan auth.token middleware
Route::middleware('auth.token')->group(function () {
    // Route untuk Anggota
    Route::get('/anggota', [AnggotaController::class, 'index']);
    Route::get('/anggota/all', [AnggotaController::class, 'selectAll']);
    Route::get('/anggota/{id}', [AnggotaController::class, 'show']);

    Route::post('/anggota', [AnggotaController::class, 'store'])
        ->middleware('permission:data_anggota,add');
    Route::put('/anggota/{id}', [AnggotaController::class, 'update'])
        ->middleware('permission:data_anggota,edit');
    Route::delete('/anggota/{id}', [AnggotaController::class, 'destroy'])
        ->middleware('permission:data_anggota,delete');
    Route::get('/anggota/by-jamaah/{id_master_jamaah?}', [AnggotaController::class, 'indexByJamaah']);

    // Route untuk Data Jamaah dan Statistik
    Route::get('/data_jamaah', [JamaahMonografiController::class, 'index']);
    Route::get('/data_monografi', [AnggotaController::class, 'statistik']);
    Route::get('/jamaah-monografi/{id_master_jamaah}', [JamaahMonografiController::class, 'show']);

    // Route tambahan
    Route::get('/data_chart', [AnggotaController::class, 'chart']);
    Route::get('/data_choice_pribadi', [AnggotaController::class, 'getChoiceDataPribadi']);

    // Route untuk Webhooks
    Route::get('webhooks', [WebhookController::class, 'verifyWebhook']);
    Route::post('webhooks', [WebhookController::class, 'handleWebhook']);

    // Route tambahan dari feat/data_monografi
    Route::get('/pesantren/by-jamaah/{id_master_jamaah?}', [PesantrenController::class, 'indexByJamaah']);
    Route::get('/fasilitas/by-jamaah/{id_master_jamaah?}', [JamaahFasilitasController::class, 'indexByJamaah']);

    // Route untuk Permissions
    Route::prefix('permissions')->group(function () {
        Route::get('/', [PermissionController::class, 'index']);
        Route::post('/', [PermissionController::class, 'store']);
        Route::put('/{id}', [PermissionController::class, 'update']);
        Route::delete('/{id}', [PermissionController::class, 'destroy']);
    });

    // Route untuk Roles
    Route::prefix('roles')->group(function () {
        Route::get('/', [RoleController::class, 'index']);
        Route::post('/', [RoleController::class, 'store']);
        Route::put('/{id}', [RoleController::class, 'update']);
        Route::delete('/{id}', [RoleController::class, 'destroy']);
    });

    // Route untuk Users
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::post('/', [UserController::class, 'store']);
        Route::put('/{id}', [UserController::class, 'update']);
        Route::delete('/{id}', [UserController::class, 'destroy']);
    });
});
