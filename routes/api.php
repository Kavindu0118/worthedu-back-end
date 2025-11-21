<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

// Minimal login route
Route::post('/login', [AuthController::class, 'login']);
