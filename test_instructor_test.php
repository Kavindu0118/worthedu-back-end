<?php
/**
 * Test instructor test creation
 */

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\DB;

echo "=== Testing Instructor Test Creation ===\n\n";

// Find an instructor with a course
$instructor = DB::table('instructors')
    ->join('courses', 'instructors.instructor_id', '=', 'courses.instructor_id')
    ->join('course_modules', 'courses.id', '=', 'course_modules.course_id')
    ->select('instructors.*', 'courses.id as course_id', 'courses.title as course_title', 
             'course_modules.id as module_id', 'course_modules.module_title')
    ->first();

if (!$instructor) {
    echo "No instructor with course and module found!\n";
    exit(1);
}

echo "Found instructor: instructor_id={$instructor->instructor_id}, user_id={$instructor->user_id}\n";
echo "Course: {$instructor->course_title} (id={$instructor->course_id})\n";
echo "Module: {$instructor->module_title} (id={$instructor->module_id})\n\n";

// Get user and their token
$user = User::find($instructor->user_id);
if (!$user) {
    echo "User not found!\n";
    exit(1);
}

echo "User: {$user->name} ({$user->email}), role: {$user->role}\n";
$token = $user->api_token;
echo "Token: " . ($token ? substr($token, 0, 20) . "..." : "NULL") . "\n\n";

// Test API call
$testData = [
    'module_id' => $instructor->module_id,
    'test_title' => 'API Test ' . date('H:i:s'),
    'test_description' => 'Test created via API',
    'instructions' => 'Follow the instructions',
    'start_date' => date('Y-m-d H:i:s'),
    'end_date' => date('Y-m-d H:i:s', strtotime('+7 days')),
    'time_limit' => 60,
    'max_attempts' => 1,
    'total_marks' => 100,
    'passing_marks' => 50,
    'questions' => [
        [
            'question_text' => 'What is 2+2?',
            'question_type' => 'multiple_choice',
            'options' => json_encode(['3', '4', '5', '6']),
            'correct_answer' => '4',
            'marks' => 10,
            'order_index' => 1
        ]
    ]
];

echo "Test data:\n";
print_r($testData);

echo "\n--- Making API call ---\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/learning-lms/public/api/instructor/tests');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Accept: application/json',
    'Content-Type: application/json'
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: {$httpCode}\n";
echo "Response: " . substr($response, 0, 1000) . "\n";

// Check logs
echo "\n--- Checking latest log entries ---\n";
$logFile = 'storage/logs/laravel.log';
if (file_exists($logFile)) {
    $lines = file($logFile);
    $recentLines = array_slice($lines, -20);
    foreach ($recentLines as $line) {
        if (strpos($line, 'getInstructorId') !== false || strpos($line, 'instructor') !== false) {
            echo $line;
        }
    }
}

echo "\n=== Test Complete ===\n";
