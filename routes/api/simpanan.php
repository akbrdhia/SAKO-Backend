<?php

use App\Http\Controllers\API\SimpananController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:api'])->prefix('simpanan')->group(function () {
    Route::get('/', [SimpananController::class, 'index']);
    Route::post('/', [SimpananController::class, 'store'])->middleware('role:kasir,admin');
    Route::get('/{id}', [SimpananController::class, 'show']);
    Route::delete('/{id}', [SimpananController::class, 'destroy'])->middleware('role:admin');
    Route::get('/user/{userId}/total', [SimpananController::class, 'getTotalByUser']);
    Route::get('/user/{userId}/history', [SimpananController::class, 'getHistoryByUser']);
});
