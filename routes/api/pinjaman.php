<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\PinjamanController;

/*
|--------------------------------------------------------------------------
| PINJAMAN MODULE ROUTES
|--------------------------------------------------------------------------
|
| Semua endpoint terkait Pinjaman & Cicilan.
| Hanya bisa diakses oleh user yang sudah login (auth:api).
|
*/

Route::prefix('pinjaman')->group(function () {

    // ======== UMUM / ANGGOTA ========

    // Simulasi cicilan (semua role bisa akses)
    Route::post('/simulasi', [PinjamanController::class, 'simulasi']);

    // List pinjaman user / koperasi
    Route::get('/', [PinjamanController::class, 'index']);

    // Detail pinjaman
    Route::get('/{id}', [PinjamanController::class, 'show']);

    // Ajukan pinjaman baru (anggota/kasir/admin)
    Route::post('/', [PinjamanController::class, 'store']);

    // Jadwal cicilan
    Route::get('/{id}/jadwal-cicilan', [PinjamanController::class, 'jadwalCicilan']);

    // ======== AKSI KHUSUS BERDASARKAN ROLE ========

    // Approve pinjaman (manajer/admin only)
    Route::put('/{id}/approve', [PinjamanController::class, 'approve'])
        ->middleware('role:manajer,admin');

    // Reject pinjaman (manajer/admin only)
    Route::put('/{id}/reject', [PinjamanController::class, 'reject'])
        ->middleware('role:manajer,admin');

    // Cairkan pinjaman (kasir/admin only)
    Route::put('/{id}/cairkan', [PinjamanController::class, 'cairkan'])
        ->middleware('role:kasir,admin');

    // Hapus pinjaman (admin only)
    Route::delete('/{id}', [PinjamanController::class, 'destroy'])
        ->middleware('role:admin');
});
