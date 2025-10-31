<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\LinksController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    // Auth routes 
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::post('auth/logout-all', [AuthController::class, 'logoutAll']);
    Route::get('auth/me', [AuthController::class, 'me']);

    // Links routes
    Route::get('links', [LinksController::class, 'findAll']);
    Route::get('links/{id}', [LinksController::class, 'getById']);
    Route::post('links', [LinksController::class, 'create']);
});

Route::middleware('guest')->group(function () { 
    // Auth routes
    Route::post('auth/login', [AuthController::class, 'login']);
    Route::post('auth/register', [AuthController::class, 'register']);
});
