<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\AnggotaController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PesantrenController;
use App\Http\Controllers\JamaahController;
use App\Http\Controllers\JamaahMonografiController;
use App\Http\Controllers\JamaahFasilitasController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\IuranController as ControllersIuranController;
use App\Http\Controllers\BroadcastController;
use App\Http\Controllers\ResponBotController;
use App\Http\Controllers\MusyawarahController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ---------- AUTH ----------
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth.token');

// ---------- PROTECTED ROUTES ----------
Route::middleware('auth.token')->group(function () {

    // ---------- ANGGOTA ----------
    Route::get('/anggota', [AnggotaController::class, 'index']);
    Route::get('/anggota/all', [AnggotaController::class, 'selectAll']);
    Route::get('/anggota/{id}', [AnggotaController::class, 'show']);
    Route::post('/anggota', [AnggotaController::class, 'store'])->middleware('permission:data_anggota,add');
    Route::put('/anggota/{id}', [AnggotaController::class, 'update'])->middleware('permission:data_anggota,edit');
    Route::delete('/anggota/{id}', [AnggotaController::class, 'destroy'])->middleware('permission:data_anggota,delete');
    Route::get('/anggota/choice_by-jamaah/{id_master_jamaah?}', [AnggotaController::class, 'anggotaByJamaah']);
    Route::post('/upload-foto', [AnggotaController::class, 'uploadFoto']);

    // ---------- DATA & STATISTIK ----------
    Route::get('/data_chart', [AnggotaController::class, 'chart']);
    Route::get('/data_monografi', [AnggotaController::class, 'statistik']);
    Route::get('/jamaah-monografi/{id_master_jamaah}', [JamaahMonografiController::class, 'show']);
    Route::get('/data_choice_pribadi', [AnggotaController::class, 'getChoiceDataPribadi']);
    Route::get('/data_choice_pendidikan', [AnggotaController::class, 'getChoiceDataPendidikan']);
    Route::get('/data_choice_pekerjaan', [AnggotaController::class, 'getChoiceDataPekerjaan']);
    Route::get('/data_choice_keterampilan', [AnggotaController::class, 'getChoiceDataKeterampilan']);
    Route::get('/data_choice_minat', [AnggotaController::class, 'getChoiceDataMinat']);
    Route::get('/data_choice_jamaah', [JamaahMonografiController::class, 'getChoiceDataJamaah']);

    // ---------- PESANTREN & FASILITAS ----------
    Route::get('/pesantren/by-jamaah/{id_master_jamaah?}', [PesantrenController::class, 'indexByJamaah']);
    Route::get('/fasilitas/by-jamaah/{id_master_jamaah?}', [JamaahFasilitasController::class, 'indexByJamaah']);

    // ---------- PERMISSION & ROLE ----------
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

    // ---------- USERS ----------
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::post('/', [UserController::class, 'store']);
        Route::put('/{id}', [UserController::class, 'update']);
        Route::delete('/{id}', [UserController::class, 'destroy']);
    });

    // ---------- IURAN ----------
    Route::prefix('iuran')->group(function () {
        Route::get('/summary', [ControllersIuranController::class, 'summary']);
        Route::get('/payment/{id}', [ControllersIuranController::class, 'paymentDetail']);
        Route::post('/pay', [ControllersIuranController::class, 'store']);
        Route::put('/edit/{id}', [ControllersIuranController::class, 'updateNominal']);
        Route::put('/verify/{id}', [ControllersIuranController::class, 'verify']);
        Route::put('/reject/{id}', [ControllersIuranController::class, 'reject']);
        Route::delete('/{id}', [ControllersIuranController::class, 'destroy']);
        Route::post('/reminder/batch', [ControllersIuranController::class, 'sendBatchReminder']);
        Route::get('/tunggakan', [ControllersIuranController::class, 'getTunggakan']);
    });

    // ---------- CHATBOT ----------
    Route::prefix('chatbot')->group(function () {
        Route::get('/', [ResponBotController::class, 'index']);
        Route::post('/', [ResponBotController::class, 'store']);
        Route::put('/{responBot}', [ResponBotController::class, 'update']);
        Route::get('/{responBot}', [ResponBotController::class, 'show']);
        Route::delete('/{responBot}', [ResponBotController::class, 'destroy']);
    });

    // ---------- BROADCAST ----------
    Route::get('/broadcast', [BroadcastController::class, 'index']);
    Route::post('/broadcast', [BroadcastController::class, 'store']);
    Route::post('/upload-attachment', [BroadcastController::class, 'uploadAttachment']);

    // ---------- WEBHOOK ----------
    Route::get('/webhooks', [WebhookController::class, 'verifyWebhook']);
    Route::post('/webhooks', [WebhookController::class, 'handleWebhook']);
});

// ---------- PUBLIC / TESTING ROUTES (opsional, bisa dihapus jika tidak perlu) ----------
Route::get('/get_anggota/{id}', [AnggotaController::class, 'show']);
Route::post('/add_anggota', [AnggotaController::class, 'store']);
Route::put('/edit_anggota/{id}', [AnggotaController::class, 'update']);
Route::delete('/delete_anggota/{id}', [AnggotaController::class, 'destroy']);
Route::get('/advanced_statistic', [AnggotaController::class, 'advancedStatistic']);

// ---------- MUSYAWARAH ----------
Route::get('/musyawarah', [MusyawarahController::class, 'index']);
Route::get('/musyawarah/{id}', [MusyawarahController::class, 'view']);
Route::post('/musyawarah', [MusyawarahController::class, 'store']);
Route::put('/musyawarah/{id}', [MusyawarahController::class, 'update']);
Route::delete('/musyawarah/{id}', [MusyawarahController::class, 'destroy']);
Route::post('/musyawarah/detail/{id_musyawarah}', [MusyawarahController::class, 'addDetail']);
Route::put('/musyawarah/detail/{id_musyawarah}/{id_detail}', [MusyawarahController::class, 'updateDetail']);
Route::delete('/musyawarah/detail/{id_musyawarah}/{id_detail}', [MusyawarahController::class, 'destroyDetail']);
Route::get('/musyawarah/detail/{id_musyawarah}/{id_detail}', [MusyawarahController::class, 'showDetail']);

// ---------- JEMAAH DATA ----------
Route::get('/data_jamaah', [JamaahMonografiController::class, 'index']);
Route::get('/anggota/by-jamaah/{id_master_jamaah?}', [AnggotaController::class, 'indexByJamaah']);
