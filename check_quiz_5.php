<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ModuleQuiz;
use App\Models\QuizAttempt;
use App\Models\Enrollment;

echo "=== Checking Quiz ID 5 ===\n\n";

$quiz = ModuleQuiz::with('module.course')->find(5);

if (!$quiz) {
    echo "❌ Quiz ID 5 not found!\n";
    
    // List available quizzes
    echo "\nAvailable quizzes:\n";
    $quizzes = ModuleQuiz::with('module.course')->get();
    foreach ($quizzes as $q) {
        echo "  Quiz {$q->id}: {$q->quiz_title} (Course: {$q->module->course->title})\n";
    }
    exit;
}

echo "✓ Quiz found: {$quiz->quiz_title}\n";
echo "  Course: {$quiz->module->course->title} (ID: {$quiz->module->course_id})\n";
echo "  Module: {$quiz->module->title}\n";
echo "  Max Attempts: " . ($quiz->max_attempts ?: 'Unlimited') . "\n";
echo "  Time Limit: " . ($quiz->time_limit ?: 'No limit') . " minutes\n";
echo "  Passing %: {$quiz->passing_percentage}%\n";

// Check quiz data
if ($quiz->quiz_data && is_array($quiz->quiz_data)) {
    echo "  Questions: " . count($quiz->quiz_data) . "\n";
    echo "\n  First question:\n";
    echo "    " . ($quiz->quiz_data[0]['question'] ?? 'N/A') . "\n";
} else {
    echo "  ❌ No quiz_data found!\n";
}

echo "\n=== Checking User 11 Enrollment ===\n\n";

$enrollment = Enrollment::where('learner_id', 11)
    ->where('course_id', $quiz->module->course_id)
    ->first();

if ($enrollment) {
    echo "✓ User 11 is enrolled in course {$quiz->module->course_id}\n";
    echo "  Status: {$enrollment->status}\n";
} else {
    echo "❌ User 11 is NOT enrolled in course {$quiz->module->course_id}\n";
    echo "\nEnrolling user now...\n";
    
    Enrollment::create([
        'learner_id' => 11,
        'course_id' => $quiz->module->course_id,
        'status' => 'active',
        'progress' => 0,
        'enrolled_at' => now(),
    ]);
    
    echo "✓ User enrolled successfully!\n";
}

echo "\n=== Checking Previous Attempts ===\n\n";

$attempts = QuizAttempt::where('quiz_id', 5)
    ->where('user_id', 11)
    ->get();

echo "Total attempts: " . $attempts->count() . "\n";
foreach ($attempts as $attempt) {
    echo "  Attempt {$attempt->attempt_number}: {$attempt->status}\n";
}

echo "\n✓ All checks complete. Try starting the quiz now!\n";
