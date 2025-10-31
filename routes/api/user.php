<?php

use App\Http\Controllers\API\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| USER MANAGEMENT ROUTES
|--------------------------------------------------------------------------
|
| Endpoint untuk manajemen user (index, show, store, update, delete, suspend, activate).
| Semua route ini dilindungi oleh middleware auth:api dari api.php.
|
*/

Route::prefix('users')->group(function () {

    // List users (kasir, manajer, admin)
    Route::get('/', [UserController::class, 'index'])
        ->middleware('role:kasir,manajer,admin');

    // Create new user (kasir, admin)
    Route::post('/', [UserController::class, 'store'])
        ->middleware('role:kasir,admin');

    // Show user detail
    Route::get('/{id}', [UserController::class, 'show']);

    // Update user
    Route::put('/{id}', [UserController::class, 'update']);

    // Delete user (admin only)
    Route::delete('/{id}', [UserController::class, 'destroy'])
        ->middleware('role:admin');

    // Suspend user (admin only)
    Route::put('/{id}/suspend', [UserController::class, 'suspend'])
        ->middleware('role:admin');

    // Activate user (admin only)
    Route::put('/{id}/activate', [UserController::class, 'activate'])
        ->middleware('role:admin');
});
