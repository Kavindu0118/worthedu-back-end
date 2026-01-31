<?php

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Creating sample grade data for certificate...\n";
echo "=============================================\n\n";

$user = DB::table('users')->where('email', 'test@example.com')->first();
$enrollment = DB::table('enrollments')->where('learner_id', $user->id)->first();
$course = DB::table('courses')->where('id', $enrollment->course_id)->first();

echo "User: {$user->email}\n";
echo "Course: {$course->title}\n\n";

$moduleIds = DB::table('course_modules')->where('course_id', $course->id)->pluck('id');
$moduleId = $moduleIds[0] ?? null;

if (!$moduleId) {
    echo "No modules found\n";
    exit;
}

echo "Module ID: $moduleId\n\n";

// Create a quiz
echo "1. Creating Quiz...\n";
$quizId = DB::table('module_quizzes')->insertGetId([
    'module_id' => $moduleId,
    'quiz_title' => 'Sample Quiz',
    'quiz_description' => 'Test quiz for certificate',
    'quiz_data' => json_encode([]),
    'time_limit' => 30,
    'total_points' => 100,
    'passing_percentage' => 60,
    'created_at' => now(),
    'updated_at' => now(),
]);
echo "   ✓ Quiz created (ID: $quizId, Max: 100 points)\n";

// Create quiz attempt
$attemptId = DB::table('quiz_attempts')->insertGetId([
    'quiz_id' => $quizId,
    'user_id' => $user->id,
    'attempt_number' => 1,
    'started_at' => now(),
    'completed_at' => now(),
    'score' => 85,
    'points_earned' => 85,
    'total_points' => 100,
    'status' => 'completed',
    'passed' => true,
    'created_at' => now(),
    'updated_at' => now(),
]);
echo "   ✓ Quiz attempt created (Score: 85/100 = 85%)\n\n";

// Create an assignment
echo "2. Creating Assignment...\n";
$assignmentId = DB::table('module_assignments')->insertGetId([
    'module_id' => $moduleId,
    'assignment_title' => 'Sample Assignment',
    'instructions' => 'Test assignment for certificate',
    'due_date' => now()->addDays(7),
    'max_points' => 50,
    'created_at' => now(),
    'updated_at' => now(),
]);
echo "   ✓ Assignment created (ID: $assignmentId, Max: 50 points)\n";

// Create assignment submission
DB::table('assignment_submissions')->insert([
    'assignment_id' => $assignmentId,
    'user_id' => $user->id,
    'submission_text' => 'Sample submission',
    'submitted_at' => now(),
    'status' => 'graded',
    'marks_obtained' => 40,
    'graded_at' => now(),
    'created_at' => now(),
    'updated_at' => now(),
]);
echo "   ✓ Assignment submitted and graded (Score: 40/50 = 80%)\n\n";

// Update test score to be better
$test = DB::table('tests')->whereIn('module_id', $moduleIds)->first();
if ($test) {
    DB::table('test_submissions')
        ->where('test_id', $test->id)
        ->where('student_id', $user->id)
        ->update(['total_score' => 45]); // 90% of 50
    echo "3. Updated Test Score\n";
    echo "   ✓ Test score updated (Score: 45/50 = 90%)\n\n";
}

// Regenerate certificate
echo "4. Regenerating Certificate...\n";
$certificateController = new \App\Http\Controllers\CertificateController();
$certificate = $certificateController->generateCertificate($course->id, $user->id);

if ($certificate) {
    echo "   ✓ Certificate regenerated!\n";
    echo "   Certificate Number: {$certificate->certificate_number}\n";
    echo "   Final Grade: {$certificate->final_grade}%\n";
    echo "   Letter Grade: {$certificate->letter_grade}\n";
    echo "   Status: {$certificate->status}\n\n";
    
    echo "Grade Calculation:\n";
    echo "   Quizzes:     85/100 × 15% = " . (85 * 0.15) . "%\n";
    echo "   Assignments: 40/50  × 25% = " . (80 * 0.25) . "%\n";
    echo "   Tests:       45/50  × 60% = " . (90 * 0.60) . "%\n";
    echo "   ─────────────────────────────────────\n";
    echo "   Final Grade:                 " . ((85 * 0.15) + (80 * 0.25) + (90 * 0.60)) . "%\n";
} else {
    echo "   ✗ Failed to regenerate certificate\n";
}

echo "\n✓ Sample data created successfully!\n";
