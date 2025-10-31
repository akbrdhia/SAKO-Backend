<?php

use App\Http\Controllers\API\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| AUTH PROTECTED ROUTES
|--------------------------------------------------------------------------
|
| Endpoint Auth yang butuh autentikasi (auth:api).
| Contohnya: me, logout, refresh, change-password.
|
*/

Route::prefix('auth')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
});
