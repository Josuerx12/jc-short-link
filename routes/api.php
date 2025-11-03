<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
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

    // Dashboard

    Route::get('dashboard/overview', [DashboardController::class, 'overview']);
});

Route::middleware('guest')->group(function () { 
    // Auth routes
    Route::post('auth/login', [AuthController::class, 'login']);
    Route::post('auth/register', [AuthController::class, 'register']);
    
    // Links routes
    Route::get('links/short/{shortCode}', [LinksController::class, 'getLinkByShortCode']);
});

Route::middleware('optional.auth')->post('links', [LinksController::class, 'create']);
