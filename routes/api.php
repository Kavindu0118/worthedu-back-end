<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CourseController;
use App\Http\Middleware\ApiTokenAuth;
use Illuminate\Http\Request;

// Handle CORS preflight for all API routes
Route::options('{any}', function (Request $request) {
    return response('', 200)
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
})->where('any', '.*');

// Public login
Route::post('/login', [AuthController::class, 'login']);
// Public registration for learners
Route::post('/register', [AuthController::class, 'register']);
// Public registration for instructors
Route::post('/register-instructor', [AuthController::class, 'registerInstructor']);

// Public course routes
Route::get('/courses', [CourseController::class, 'index']);
Route::get('/courses/{id}', [CourseController::class, 'show']);

// Protected routes using middleware class directly (no Kernel registration required)
Route::middleware([ApiTokenAuth::class])->group(function () {
	Route::get('/me', [AuthController::class, 'me']);
	Route::post('/logout', [AuthController::class, 'logout']);
	
	// Instructor course management routes
	Route::prefix('instructor')->group(function () {
		Route::post('/courses', [CourseController::class, 'store']);
		Route::get('/courses', [CourseController::class, 'instructorCourses']);
		Route::put('/courses/{id}', [CourseController::class, 'update']);
		Route::delete('/courses/{id}', [CourseController::class, 'destroy']);
	});
});
