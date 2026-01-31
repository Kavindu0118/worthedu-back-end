<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\PaymentMethodController;
use App\Http\Controllers\StripePaymentController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\InstructorSubmissionController;
use App\Http\Controllers\InstructorTestController;
use App\Http\Controllers\StudentTestController;
use App\Http\Middleware\ApiTokenAuth;
use Illuminate\Http\Request;

// Public login
Route::post('/login', [AuthController::class, 'login']);
// Public registration for learners
Route::post('/register', [AuthController::class, 'register']);
// Public registration for instructors
Route::post('/register-instructor', [AuthController::class, 'registerInstructor']);

// Debug endpoint - remove after fixing
Route::post('/debug-note', function(Request $request) {
    return response()->json([
        'all_data' => $request->all(),
        'has_file' => $request->hasFile('attachment'),
        'has_key' => $request->has('attachment'),
        'files' => $request->allFiles(),
        'headers' => $request->headers->all(),
    ]);
});

// Public course routes
Route::get('/courses', [CourseController::class, 'index']);
Route::get('/courses/{id}', [CourseController::class, 'show']);

// Stripe webhook (no authentication required)
Route::post('/webhooks/stripe', [StripeWebhookController::class, 'handleWebhook']);

// Protected routes using middleware class directly (no Kernel registration required)
Route::middleware([ApiTokenAuth::class])->group(function () {
	Route::get('/me', [AuthController::class, 'me']);
	Route::post('/logout', [AuthController::class, 'logout']);
	
	// Enrollment routes
	Route::get('/enrollments', [EnrollmentController::class, 'index']);
	Route::post('/enrollments', [EnrollmentController::class, 'store']);
	Route::get('/enrollments/{id}', [EnrollmentController::class, 'show']);
	Route::put('/enrollments/{id}/progress', [EnrollmentController::class, 'updateProgress']);
	Route::delete('/enrollments/{id}', [EnrollmentController::class, 'destroy']);
	Route::get('/courses/{courseId}/enrollment-status', [EnrollmentController::class, 'checkEnrollmentStatus']);
	
	// Payment routes
	Route::post('/payments/create-intent', [PaymentController::class, 'createIntent']);
	Route::post('/payments/confirm', [PaymentController::class, 'confirmPayment']);
	Route::get('/payments/history', [PaymentController::class, 'history']);
	Route::get('/enrollments/{enrollmentId}/payment', [PaymentController::class, 'getEnrollmentPayment']);
	Route::post('/payments/refund', [PaymentController::class, 'requestRefund']);
	Route::get('/payments/{paymentId}/receipt', [PaymentController::class, 'downloadReceipt']);
	
	// Coupon routes
	Route::post('/coupons/validate', [CouponController::class, 'validate']);
	Route::post('/coupons/apply', [CouponController::class, 'apply']);
	
	// Payment method routes
	Route::get('/payment-methods', [PaymentMethodController::class, 'index']);
	Route::post('/payment-methods', [PaymentMethodController::class, 'store']);
	Route::delete('/payment-methods/{id}', [PaymentMethodController::class, 'destroy']);
	Route::put('/payment-methods/{id}/set-default', [PaymentMethodController::class, 'setDefault']);
	
	// Stripe payment routes
	Route::get('/stripe/enrollment-status/{courseId}', [StripePaymentController::class, 'checkEnrollmentStatus']);
	Route::post('/stripe/create-payment-intent', [StripePaymentController::class, 'createPaymentIntent']);
	Route::post('/stripe/confirm-enrollment', [StripePaymentController::class, 'confirmEnrollment']);
	Route::get('/stripe/payment-status/{paymentIntentId}', [StripePaymentController::class, 'getPaymentStatus']);
	
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
		
		// Assignment submission grading
		Route::get('/assignments/{assignmentId}/submissions', [InstructorSubmissionController::class, 'getAssignmentSubmissions']);
		Route::get('/submissions/{submissionId}', [InstructorSubmissionController::class, 'getSubmissionDetails']);
		Route::put('/submissions/{submissionId}/grade', [InstructorSubmissionController::class, 'gradeSubmission']);
		Route::get('/submissions/{submissionId}/download', [InstructorSubmissionController::class, 'downloadSubmissionFile']);
		Route::get('/modules/{moduleId}/submissions', [InstructorSubmissionController::class, 'getModuleSubmissions']);

		// Test management
		Route::get('/courses/{courseId}/tests', [InstructorTestController::class, 'getTestsByCourse']);
		Route::post('/tests', [InstructorTestController::class, 'store']);
		Route::get('/tests/{testId}', [InstructorTestController::class, 'show']);
		Route::put('/tests/{testId}', [InstructorTestController::class, 'update']);
		Route::delete('/tests/{testId}', [InstructorTestController::class, 'destroy']);
		Route::get('/tests/{testId}/submissions', [InstructorTestController::class, 'getSubmissions']);
		Route::get('/test-submissions/{submissionId}', [InstructorTestController::class, 'getSubmissionDetails']);
		Route::post('/test-submissions/{submissionId}/grade', [InstructorTestController::class, 'gradeSubmission']);
		Route::post('/tests/{testId}/publish-results', [InstructorTestController::class, 'publishResults']);
		Route::get('/tests/{testId}/statistics', [InstructorTestController::class, 'getStatistics']);
	});
	
	// Learner routes
	Route::prefix('learner')->group(function () {
		// Dashboard
		Route::get('/dashboard', [\App\Http\Controllers\LearnerDashboardController::class, 'index']);
		Route::get('/stats', [\App\Http\Controllers\LearnerDashboardController::class, 'stats']);
		Route::get('/activity', [\App\Http\Controllers\LearnerDashboardController::class, 'activity']);
		Route::get('/streak', [\App\Http\Controllers\LearnerDashboardController::class, 'streak']);
		Route::get('/recommendations', [\App\Http\Controllers\LearnerDashboardController::class, 'recommendations']);
		Route::get('/performance', [\App\Http\Controllers\LearnerDashboardController::class, 'performance']);
		
		// Certificates (using dedicated CertificateController)
		Route::get('/certificates', [\App\Http\Controllers\CertificateController::class, 'index']);
		Route::get('/certificates/{id}', [\App\Http\Controllers\CertificateController::class, 'show']);
		Route::get('/courses/{courseId}/certificate', [\App\Http\Controllers\CertificateController::class, 'getByCourse']);
		
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
		
		// Tests
		Route::get('/tests/{testId}', [StudentTestController::class, 'show']);
		Route::post('/tests/{testId}/start', [StudentTestController::class, 'startTest']);
		Route::post('/test-submissions/{submissionId}/submit', [StudentTestController::class, 'submitTest']);
		Route::post('/test-submissions/{submissionId}/upload', [StudentTestController::class, 'uploadFile']);
		Route::post('/test-submissions/{submissionId}/autosave', [StudentTestController::class, 'autosave']);
		Route::get('/tests/{testId}/results', [StudentTestController::class, 'getResults']);
		
		// Notifications
		Route::get('/notifications', [\App\Http\Controllers\LearnerNotificationController::class, 'index']);
		Route::get('/notifications/unread-count', [\App\Http\Controllers\LearnerNotificationController::class, 'unreadCount']);
		Route::post('/notifications/{id}/read', [\App\Http\Controllers\LearnerNotificationController::class, 'markAsRead']);
		Route::post('/notifications/read-all', [\App\Http\Controllers\LearnerNotificationController::class, 'markAllAsRead']);
		Route::delete('/notifications/{id}', [\App\Http\Controllers\LearnerNotificationController::class, 'destroy']);
	});
	
	// Student routes (alias for learner test routes - for frontend compatibility)
	Route::prefix('student')->group(function () {
		Route::get('/tests/{testId}', [StudentTestController::class, 'show']);
		Route::post('/tests/{testId}/start', [StudentTestController::class, 'startTest']);
		Route::post('/test-submissions/{submissionId}/submit', [StudentTestController::class, 'submitTest']);
		Route::post('/test-submissions/{submissionId}/upload', [StudentTestController::class, 'uploadFile']);
		Route::post('/test-submissions/{submissionId}/autosave', [StudentTestController::class, 'autosave']);
		Route::get('/tests/{testId}/results', [StudentTestController::class, 'getResults']);
		
		// Alternate paths (without 'test-' prefix) for frontend compatibility
		Route::post('/submissions/{submissionId}/submit', [StudentTestController::class, 'submitTest']);
		Route::post('/submissions/{submissionId}/upload', [StudentTestController::class, 'uploadFile']);
		Route::post('/submissions/{submissionId}/autosave', [StudentTestController::class, 'autosave']);
	});

	// Admin routes
	Route::prefix('admin')->group(function () {
		Route::get('/students', [AdminController::class, 'getAllStudents']);
		Route::get('/instructors', [AdminController::class, 'getAllInstructors']);
		Route::get('/instructors/{instructorId}', [AdminController::class, 'getInstructorDetails']);
		Route::put('/instructors/{instructorId}/status', [AdminController::class, 'updateInstructorStatus']);
		Route::get('/instructors/{instructorId}/cv', [AdminController::class, 'downloadInstructorCV']);
		Route::get('/courses', [AdminController::class, 'getAllCourses']);
		Route::delete('/courses/{courseId}', [AdminController::class, 'deleteCourse']);
	});
});
