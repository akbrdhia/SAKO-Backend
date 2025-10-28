<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::get('/ping', fn() => response()->json(['message' => 'pong']));

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
