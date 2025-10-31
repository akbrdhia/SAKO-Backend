<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| File utama untuk mendaftarkan semua route API.
| Masing-masing domain (auth, user, test, dll) dipisah ke file terpisah
| agar lebih mudah dibaca dan dikelola.
|
*/

Route::prefix('v1')->group(function () {
    /*
    |--------------------------------------------------------------------------
    | PUBLIC ROUTES
    |--------------------------------------------------------------------------
    */
    Route::get('/', function () {
        return response()->json(['message' => 'The SAKO API is running smoothly.']);
    });

    // Public Auth
    require __DIR__ . '/api/api_auth.php';
    /*
    |--------------------------------------------------------------------------
    | PROTECTED ROUTES
    |--------------------------------------------------------------------------
    |
    | Semua route di bawah ini hanya bisa diakses oleh user yang sudah login
    | (karena dilindungi oleh middleware auth:api).
    |
    */
    Route::middleware(['auth:api'])->group(function () {
        // Group: AUTH (me, logout, refresh, change-password)
        require __DIR__ . '/api/api_auth_protected.php';
        // Group: TEST ROUTES (role based)
        require __DIR__ . '/api/api_test.php';
        // Group: USER MANAGEMENT
        require __DIR__ . '/api/api_user.php';
    });
});
