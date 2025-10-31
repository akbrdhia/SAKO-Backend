<?php

use App\Http\Controllers\API\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| AUTH PUBLIC ROUTES
|--------------------------------------------------------------------------
|
| Endpoint untuk login dan route auth publik lainnya.
| Tidak butuh autentikasi (auth:api).
|
*/

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});
