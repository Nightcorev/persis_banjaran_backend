<?php

use App\Http\Controllers\WebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AnggotaController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\IuranController;
use App\Http\Controllers\JamaahController;
use App\Http\Controllers\JamaahMonografiController;
use App\Http\Controllers\PesantrenController;
use App\Http\Controllers\JamaahFasilitasController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\MusyawarahController;
use App\Http\Controllers\BroadcastController;
use App\Http\Controllers\ResponBotController;
use App\Http\Controllers\TahunAktifController;

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

// Auth routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth.token');
Route::get('/dataUsers', [UserController::class, 'getDataUsers']);

// Protected routes
Route::middleware('auth.token')->group(function () {
    // --- Anggota ---
    Route::get('/anggota', [AnggotaController::class, 'index']);
    Route::get('/get_anggota/{id}', [AnggotaController::class, 'show']);
    Route::post('/add_anggota', [AnggotaController::class, 'store']);
    Route::put('/edit_anggota/{id}', [AnggotaController::class, 'update']);
    Route::delete('/delete_anggota/{id}', [AnggotaController::class, 'destroy']);

    Route::get('/anggota/detailed', [AnggotaController::class, 'indexDetailed']);
    Route::post('/anggota/export-excel', [AnggotaController::class, 'exportExcel']);
    Route::get('/anggota/all', [AnggotaController::class, 'selectAll']);
    Route::get('/anggota/{id}', [AnggotaController::class, 'show']);
    Route::post('/anggota', [AnggotaController::class, 'store'])->middleware('permission:data_anggota,add');
    Route::put('/anggota/{id}', [AnggotaController::class, 'update'])->middleware('permission:data_anggota,edit');
    Route::delete('/anggota/{id}', [AnggotaController::class, 'destroy'])->middleware('permission:data_anggota,delete');
    Route::get('/anggota/by-jamaah/{id_master_jamaah?}', [AnggotaController::class, 'indexByJamaah']);
    Route::get('/anggota/choice_by-jamaah/{id_master_jamaah?}', [AnggotaController::class, 'anggotaByJamaah']);
    Route::post('/upload-foto', [AnggotaController::class, 'uploadFoto']);
    Route::get('/advanced_statistic', [AnggotaController::class, 'advancedStatistic']);

    // --- Statistik & Data Pilihan ---
    Route::get('/advanced_statistic', [AnggotaController::class, 'advancedStatistic']);
    Route::get('/data_chart', [AnggotaController::class, 'chart']);
    Route::get('/data_monografi', [AnggotaController::class, 'statistik']);

    // --- Musyawarah ---
    Route::get('/data_musyawarah', [MusyawarahController::class, 'index']);
    Route::get('/detail_musyawarah/{id}', [MusyawarahController::class, 'view']);
    Route::post('/add_musyawarah', [MusyawarahController::class, 'store']);
    Route::put('/edit_musyawarah/{id}', [MusyawarahController::class, 'update']);
    Route::delete('/delete_musyawarah/{id}', [MusyawarahController::class, 'destroy']);
    Route::get('/musyawarah/pimpinan-cabang', [MusyawarahController::class, 'indexPimpinanCabang']);
    Route::post('/musyawarah/detail/{id_musyawarah}', [MusyawarahController::class, 'addDetail']);
    Route::put('/musyawarah/detail/{id_musyawarah}/{id_detail}', [MusyawarahController::class, 'updateDetail']);
    Route::delete('/musyawarah/detail/{id_musyawarah}/{id_detail}', [MusyawarahController::class, 'destroyDetail']);
    Route::get('/musyawarah/detail/{id_musyawarah}/{id_detail}', [MusyawarahController::class, 'showDetail']);

    // --- Jamaah Monografi & Data Jamaah ---
    Route::get('/data_jamaah', [JamaahMonografiController::class, 'index']);
    Route::post('/jamaah-monografi', [JamaahMonografiController::class, 'store']);
    Route::put('/jamaah-monografi/{id_jamaah}', [JamaahMonografiController::class, 'update']);
    Route::delete('/delete-jamaah/{id_jamaah}', [JamaahMonografiController::class, 'destroy']);
    Route::get('/jamaah-monografi/{id_master_jamaah}', [JamaahMonografiController::class, 'show']);

    // --- Data Pilihan DropDown ---
    Route::get('/data_choice_pribadi', [AnggotaController::class, 'getChoiceDataPribadi']);
    Route::get('/data_choice_pendidikan', [AnggotaController::class, 'getChoiceDataPendidikan']);
    Route::get('/data_choice_pekerjaan', [AnggotaController::class, 'getChoiceDataPekerjaan']);
    Route::get('/data_choice_keterampilan', [AnggotaController::class, 'getChoiceDataKeterampilan']);
    Route::get('/data_choice_minat', [AnggotaController::class, 'getChoiceDataMinat']);
    Route::get('/data_choice_jamaah', [JamaahMonografiController::class, 'getChoiceDataJamaah']);
    Route::get('/data_choice_advanced_statistic', [AnggotaController::class, 'dataChoiceAdvancedStatistic']);

    Route::post('/check_unique_fields', [AnggotaController::class, 'checkUniqueFields']);

    // --- Webhooks ---
    Route::get('webhooks', [WebhookController::class, 'verifyWebhook']);
    Route::post('webhooks', [WebhookController::class, 'handleWebhook']);

    // --- Data Tambahan ---
    Route::get('/pesantren/by-jamaah/{id_master_jamaah?}', [PesantrenController::class, 'indexByJamaah']);
    Route::get('/fasilitas/by-jamaah/{id_master_jamaah?}', [JamaahFasilitasController::class, 'indexByJamaah']);

    // --- Users ---
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::post('/', [UserController::class, 'store']);
        Route::put('/{id}', [UserController::class, 'update']);
        Route::delete('/{id}', [UserController::class, 'destroy']);
    });

    // --- Permissions ---
    Route::prefix('permissions')->group(function () {
        Route::get('/', [PermissionController::class, 'index']);
        Route::post('/', [PermissionController::class, 'store']);
        Route::put('/{id}', [PermissionController::class, 'update']);
        Route::delete('/{id}', [PermissionController::class, 'destroy']);
    });

    // --- Roles ---
    Route::prefix('roles')->group(function () {
        Route::get('/', [RoleController::class, 'index']);
        Route::post('/', [RoleController::class, 'store']);
        Route::put('/{id}', [RoleController::class, 'update']);
        Route::delete('/{id}', [RoleController::class, 'destroy']);
    });

    // --- Iuran ---
    Route::prefix('iuran')->group(function () {
        Route::get('/summary', [IuranController::class, 'summary']);
        Route::post('/pay-months', [IuranController::class, 'payMonths']);
        Route::get('/history/{anggotaId}', [IuranController::class, 'getHistory']);
        Route::post('/import', [IuranController::class, 'import']);
        Route::put('/verify-log/{iuranLog}', [IuranController::class, 'verifyLog']);
        Route::put('/reject-log/{iuranLog}', [IuranController::class, 'rejectLog']);
        Route::get('/pending-logs/{anggotaId}', [IuranController::class, 'getPendingLogs']);
        Route::get('/rekap-jamaah', [IuranController::class, 'getRekapJamaah']);
        Route::get('/rekap-jamaah/export', [IuranController::class, 'exportRekapJamaah']);
        Route::get('/template-pembayaran/{jamaah_id}', [IuranController::class, 'downloadTemplatePembayaran']);
        Route::get('/pending-count', [IuranController::class, 'getPendingCount']);
        Route::post('/send_reminder', [IuranController::class, 'sendReminder']);
        Route::post('/reminder/batch', [IuranController::class, 'sendBatchReminder']);
        Route::get('/tunggakan', [IuranController::class, 'getTunggakan']);
    });

    // --- Tahun Aktif ---
    Route::apiResource('tahun-aktif', TahunAktifController::class);

    // --- Chatbot & Broadcast ---
    Route::get('/chatbot', [ResponBotController::class, 'index']);
    Route::post('/chatbot', [ResponBotController::class, 'store']);
    Route::put('/chatbot/{responBot}', [ResponBotController::class, 'update']);
    Route::get('chatbot/{responBot}', [ResponBotController::class, 'show']);
    Route::delete('/chatbot/{responBot}', [ResponBotController::class, 'destroy']);

    Route::get('/broadcast', [BroadcastController::class, 'index']);
    Route::post('/broadcast', [BroadcastController::class, 'store']);
    Route::post('/upload-attachment', [BroadcastController::class, 'uploadAttachment']);
});