<?php
/**
 * Test learner starting a quiz with multiple questions
 */

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Enrollment;

echo "=== Testing Learner Quiz Start API with Multiple Questions ===\n\n";

// Get a learner
$learner = User::where('role', 'learner')->whereNotNull('api_token')->first();
if (!$learner) {
    echo "No learner with API token found\n";
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
    Enrollment::create([
        'learner_id' => $learner->id,
        'course_id' => $quiz->module->course_id,
        'enrollment_status' => 'active',
        'enrolled_at' => now(),
        'progress_percentage' => 0
    ]);
}

echo "Making API call to start quiz...\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost/learning-lms/public/api/learner/quizzes/{$quizId}/start");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $learner->api_token,
    'Content-Type: application/json',
    'Accept: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response:\n";
$responseData = json_decode($response, true);

if ($httpCode === 200 && isset($responseData['data'])) {
    $data = $responseData['data'];
    echo "✅ Quiz started successfully!\n";
    echo "Attempt ID: {$data['attempt_id']}\n";
    echo "Questions returned: " . count($data['questions']) . "\n\n";
    
    foreach ($data['questions'] as $index => $question) {
        echo "Question " . ($index + 1) . ":\n";
        echo "  ID: {$question['id']}\n";
        echo "  Text: {$question['question_text']}\n";
        echo "  Options: " . count($question['options']) . "\n";
        echo "\n";
    }
} else {
    echo "❌ Error:\n";
    print_r($responseData);
}

echo "\n=== End ===\n";
