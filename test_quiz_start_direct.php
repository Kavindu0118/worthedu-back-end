<?php
/**
 * Test learner quiz start directly through controller
 */

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Enrollment;
use App\Http\Controllers\LearnerQuizController;
use Illuminate\Support\Facades\Auth;

echo "=== Testing Learner Quiz Start with Multiple Questions (Direct) ===\n\n";

// Get a learner
$learner = User::where('role', 'learner')->first();
if (!$learner) {
    echo "No learner found\n";
    exit(1);
}

// Get quiz ID 12 (the one we just created with 3 questions)
$quizId = 12;
$quiz = \App\Models\ModuleQuiz::find($quizId);

if (!$quiz) {
    echo "Quiz not found\n";
    exit(1);
}

echo "Learner: {$learner->name}\n";
echo "Quiz: {$quiz->quiz_title} (ID: {$quiz->id})\n";
echo "Questions in database: " . count($quiz->quiz_data) . "\n\n";

// Check enrollment
$enrollment = Enrollment::where('learner_id', $learner->id)
    ->where('course_id', $quiz->module->course_id)
    ->first();

if (!$enrollment) {
    echo "Enrolling learner in course...\n";
    $enrollment = Enrollment::create([
        'learner_id' => $learner->id,
        'course_id' => $quiz->module->course_id,
        'enrollment_status' => 'active',
        'enrolled_at' => now(),
        'progress_percentage' => 0
    ]);
    echo "✅ Enrolled\n\n";
}

echo "Calling quiz start method...\n\n";

// Authenticate as the learner
Auth::setUser($learner);

// Call the controller method
$controller = new LearnerQuizController();
$response = $controller->start($quizId);

$responseData = json_decode($response->getContent(), true);

if ($responseData['success']) {
    $data = $responseData['data'];
    echo "✅ Quiz started successfully!\n";
    echo "Attempt ID: {$data['attempt_id']}\n";
    echo "Questions returned by API: " . count($data['questions']) . "\n\n";
    
    if (count($data['questions']) === 3) {
        echo "✅ SUCCESS! All 3 questions are returned!\n\n";
    } else {
        echo "❌ PROBLEM! Expected 3 questions, got " . count($data['questions']) . "\n\n";
    }
    
    foreach ($data['questions'] as $index => $question) {
        echo "Question " . ($index + 1) . ":\n";
        echo "  ID: {$question['id']}\n";
        echo "  Text: {$question['question_text']}\n";
        echo "  Points: {$question['points']}\n";
        echo "  Options: " . count($question['options']) . "\n";
        foreach ($question['options'] as $option) {
            echo "    - [{$option['id']}] {$option['option_text']}\n";
        }
        echo "\n";
    }
} else {
    echo "❌ Error: {$responseData['message']}\n";
}

echo "=== End ===\n";
