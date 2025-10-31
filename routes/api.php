<?php

use App\Http\Controllers\API\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | PUBLIC ROUTES
    |--------------------------------------------------------------------------
    */
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::get('/', function () {
        return response()->json(['message' => 'The SAKO API is running smoothly.']);
    });

    /*
    |--------------------------------------------------------------------------
    | PROTECTED ROUTES
    |--------------------------------------------------------------------------
    */
    Route::middleware(['auth:api'])->group(function () {

        // Auth endpoints
        Route::prefix('auth')->group(function () {
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::post('/refresh', [AuthController::class, 'refresh']);
            Route::post('/change-password', [AuthController::class, 'changePassword']);
        });

        // Test routes untuk cek role middleware
        Route::get('/test-anggota', function () {
            return response()->json([
                'message' => 'Hello Anggota!',
                'user' => auth()->user()->nama,
            ]);
        })->middleware('role:anggota');

        Route::get('/test-kasir', function () {
            return response()->json([
                'message' => 'Hello Kasir!',
                'user' => auth()->user()->nama,
            ]);
        })->middleware('role:kasir');

        Route::get('/test-manajer', function () {
            return response()->json([
                'message' => 'Hello Manajer!',
                'user' => auth()->user()->nama,
            ]);
        })->middleware('role:manajer');

        Route::get('/test-admin', function () {
            return response()->json([
                'message' => 'Hello Admin!',
                'user' => auth()->user()->nama,
            ]);
        })->middleware('role:admin');
    });
});
