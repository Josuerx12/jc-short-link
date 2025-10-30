<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LinksController;

Route::get('{shortCode}', [LinksController::class, 'redirectToOriginalUrl']);
