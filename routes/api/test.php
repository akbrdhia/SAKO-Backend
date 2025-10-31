<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| TEST ROUTES (ROLE-BASED)
|--------------------------------------------------------------------------
|
| Endpoint untuk uji coba role berdasarkan middleware 'role'.
| Hanya bisa diakses user yang punya role sesuai.
|
*/

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
