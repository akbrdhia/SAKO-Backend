<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\CicilanController;

/*
|--------------------------------------------------------------------------
| CICILAN MODULE ROUTES
|--------------------------------------------------------------------------
|
| Dikelompokkan per prefix:
| - /pinjaman/{pinjamanId}/...   → Akses cicilan & history per pinjaman
| - /cicilan/{id}/...            → Akses detail & aksi per cicilan
|
*/

// ============================
// Prefix: /pinjaman/{pinjamanId}
// ============================
Route::prefix('pinjaman/{pinjamanId}')->group(function () {
    // List cicilan per pinjaman (user biasa)
    Route::get('/cicilan', [CicilanController::class, 'index']);
    // History pembayaran per pinjaman
    Route::get('/history-pembayaran', [CicilanController::class, 'historyPembayaran']);
});


// ============================
// Prefix: /cicilan/{id}
// ============================
Route::prefix('cicilan/{id}')->group(function () {
    // Detail cicilan + history pembayaran
    Route::get('/', [CicilanController::class, 'show']);
    // History pembayaran per cicilan
    Route::get('/pembayaran', [CicilanController::class, 'pembayaranCicilan']);
    // Preview pembayaran (kasir/admin)
    Route::post('/preview-bayar', [CicilanController::class, 'previewBayar'])
        ->middleware('role:kasir,admin');
    // Proses pembayaran (kasir/admin)
    Route::post('/bayar', [CicilanController::class, 'bayar'])
        ->middleware('role:kasir,admin');
});


// ============================
// Prefix: /cicilan (global list)
// ============================
// List semua cicilan (admin only, dengan filter heavy)
Route::get('/cicilan', [CicilanController::class, 'indexGlobal'])
    ->middleware('role:admin');
