<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\NoteController;
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
		Route::get('/dashboard', [CourseController::class, 'dashboard']);
		Route::post('/courses', [CourseController::class, 'store']);
		Route::get('/courses', [CourseController::class, 'instructorCourses']);
		Route::get('/courses/{id}', [CourseController::class, 'showInstructorCourse']);
		Route::put('/courses/{id}', [CourseController::class, 'update']);
		Route::delete('/courses/{id}', [CourseController::class, 'destroy']);
		
		// Module management
		Route::post('/courses/{courseId}/modules', [ModuleController::class, 'store']);
		Route::put('/modules/{id}', [ModuleController::class, 'update']);
		Route::delete('/modules/{id}', [ModuleController::class, 'destroy']);
		
		// Quiz management
		Route::post('/modules/{moduleId}/quizzes', [QuizController::class, 'store']);
		Route::put('/quizzes/{id}', [QuizController::class, 'update']);
		Route::delete('/quizzes/{id}', [QuizController::class, 'destroy']);
		
		// Assignment management
		Route::post('/modules/{moduleId}/assignments', [AssignmentController::class, 'store']);
		Route::put('/assignments/{id}', [AssignmentController::class, 'update']);
		Route::delete('/assignments/{id}', [AssignmentController::class, 'destroy']);
		
		// Note management
		Route::post('/modules/{moduleId}/notes', [NoteController::class, 'store']);
		Route::put('/notes/{id}', [NoteController::class, 'update']);
		Route::delete('/notes/{id}', [NoteController::class, 'destroy']);
	});
	
	// Learner routes
	Route::prefix('learner')->group(function () {
		// Dashboard
		Route::get('/dashboard', [\App\Http\Controllers\LearnerDashboardController::class, 'index']);
		Route::get('/stats', [\App\Http\Controllers\LearnerDashboardController::class, 'stats']);
		Route::get('/activity', [\App\Http\Controllers\LearnerDashboardController::class, 'activity']);
		
		// Profile
		Route::get('/profile', [\App\Http\Controllers\LearnerProfileController::class, 'show']);
		Route::put('/profile', [\App\Http\Controllers\LearnerProfileController::class, 'update']);
		Route::post('/profile/avatar', [\App\Http\Controllers\LearnerProfileController::class, 'uploadAvatar']);
		Route::delete('/profile/avatar', [\App\Http\Controllers\LearnerProfileController::class, 'deleteAvatar']);
		Route::put('/profile/password', [\App\Http\Controllers\LearnerProfileController::class, 'changePassword']);
		
		// Courses
		Route::get('/courses', [\App\Http\Controllers\LearnerCourseController::class, 'index']);
		Route::get('/courses/available', [\App\Http\Controllers\LearnerCourseController::class, 'available']);
		Route::get('/courses/{id}', [\App\Http\Controllers\LearnerCourseController::class, 'show']);
		Route::post('/courses/{id}/enroll', [\App\Http\Controllers\LearnerCourseController::class, 'enroll']);
		Route::get('/courses/{id}/progress', [\App\Http\Controllers\LearnerCourseController::class, 'progress']);
		
		// Lessons (Course Modules)
		Route::get('/lessons/{id}', [\App\Http\Controllers\LearnerLessonController::class, 'show']);
		Route::post('/lessons/{id}/start', [\App\Http\Controllers\LearnerLessonController::class, 'start']);
		Route::put('/lessons/{id}/progress', [\App\Http\Controllers\LearnerLessonController::class, 'updateProgress']);
		Route::post('/lessons/{id}/complete', [\App\Http\Controllers\LearnerLessonController::class, 'complete']);
		
		// Assignments
		Route::get('/assignments', [\App\Http\Controllers\LearnerAssignmentController::class, 'index']);
		Route::get('/assignments/{id}', [\App\Http\Controllers\LearnerAssignmentController::class, 'show']);
		Route::post('/assignments/{id}/submit', [\App\Http\Controllers\LearnerAssignmentController::class, 'submit']);
		Route::get('/assignments/{id}/submission', [\App\Http\Controllers\LearnerAssignmentController::class, 'getSubmission']);
		
		// Quizzes
		Route::get('/quizzes', [\App\Http\Controllers\LearnerQuizController::class, 'index']);
		Route::get('/quizzes/{id}', [\App\Http\Controllers\LearnerQuizController::class, 'show']);
		Route::post('/quizzes/{id}/start', [\App\Http\Controllers\LearnerQuizController::class, 'start']);
		Route::put('/quiz-attempts/{attemptId}/answer', [\App\Http\Controllers\LearnerQuizController::class, 'submitAnswer']);
		Route::post('/quiz-attempts/{attemptId}/submit', [\App\Http\Controllers\LearnerQuizController::class, 'submitQuiz']);
		Route::get('/quiz-attempts/{attemptId}', [\App\Http\Controllers\LearnerQuizController::class, 'getAttempt']);
		
		// Notifications
		Route::get('/notifications', [\App\Http\Controllers\LearnerNotificationController::class, 'index']);
		Route::get('/notifications/unread-count', [\App\Http\Controllers\LearnerNotificationController::class, 'unreadCount']);
		Route::post('/notifications/{id}/read', [\App\Http\Controllers\LearnerNotificationController::class, 'markAsRead']);
		Route::post('/notifications/read-all', [\App\Http\Controllers\LearnerNotificationController::class, 'markAllAsRead']);
		Route::delete('/notifications/{id}', [\App\Http\Controllers\LearnerNotificationController::class, 'destroy']);
	});
});
