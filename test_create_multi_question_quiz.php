<?php
/**
 * Test creating a quiz with multiple questions
 */

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\CourseModule;

echo "=== Testing Quiz Creation with Multiple Questions ===\n\n";

// Find an instructor user with a token
$instructor = User::where('role', 'instructor')->whereNotNull('api_token')->first();
if (!$instructor) {
    echo "No instructor with API token found\n";
    exit(1);
}

// Make sure instructor relationship exists
if (!$instructor->instructor) {
    echo "Instructor relationship not found\n";
    exit(1);
}

// Get their module
$module = CourseModule::whereHas('course', function($q) use ($instructor) {
    $q->where('instructor_id', $instructor->instructor->instructor_id);
})->first();

if (!$module) {
    echo "No module found for instructor\n";
    exit(1);
}

echo "Instructor: {$instructor->name}\n";
echo "Module: {$module->module_title} (ID: {$module->id})\n";
echo "Token: " . substr($instructor->api_token, 0, 20) . "...\n\n";

// Create quiz data with MULTIPLE questions
$quizData = [
    'quiz_title' => 'Test Multiple Questions Quiz',
    'quiz_description' => 'This quiz has multiple questions',
    'total_points' => 30,
    'time_limit' => 15,
    'quiz_data' => [
        [
            'question' => 'What is 2 + 2?',
            'options' => ['2', '3', '4', '5'],
            'correct_answer' => '4',
            'points' => 10
        ],
        [
            'question' => 'What is 3 * 3?',
            'options' => ['6', '8', '9', '12'],
            'correct_answer' => '9',
            'points' => 10
        ],
        [
            'question' => 'What is 10 / 2?',
            'options' => ['3', '4', '5', '6'],
            'correct_answer' => '5',
            'points' => 10
        ]
    ]
];

echo "Creating quiz with " . count($quizData['quiz_data']) . " questions...\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost/learning-lms/public/api/instructor/modules/{$module->id}/quizzes");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($quizData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $instructor->api_token,
    'Content-Type: application/json',
    'Accept: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response:\n";
$responseData = json_decode($response, true);
print_r($responseData);

if ($httpCode === 201 && isset($responseData['quiz'])) {
    $quizId = $responseData['quiz']['id'];
    echo "\n✅ Quiz created successfully! ID: $quizId\n";
    
    // Verify the quiz was saved correctly
    $quiz = \App\Models\ModuleQuiz::find($quizId);
    echo "\nVerifying saved quiz...\n";
    echo "Questions in database: " . count($quiz->quiz_data) . "\n";
    
    foreach ($quiz->quiz_data as $index => $question) {
        echo "  Q" . ($index + 1) . ": " . $question['question'] . "\n";
    }
}

echo "\n=== End ===\n";
