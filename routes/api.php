<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Middleware\ApiTokenAuth;

// Public login
Route::post('/login', [AuthController::class, 'login']);
// Public registration for learners
Route::post('/register', [AuthController::class, 'register']);

// Protected routes using middleware class directly (no Kernel registration required)
Route::middleware([ApiTokenAuth::class])->group(function () {
	Route::get('/me', [AuthController::class, 'me']);
	Route::post('/logout', [AuthController::class, 'logout']);
});
