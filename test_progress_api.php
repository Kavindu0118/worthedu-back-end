<?php

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Testing Progress API\n";
echo "===================\n\n";

// Use the raw token (it will be hashed by the middleware)
$learnerToken = '92d131214dcb8f0df93a814acceeefecbcd41266e22013e2e24d3d350701bbcc';

echo "Using token: " . substr($learnerToken, 0, 20) . "...\n\n";

// Test 1: Dashboard with progress details
echo "1. Testing Dashboard with Progress Details\n";
echo "-------------------------------------------\n";
$ch = curl_init('http://127.0.0.1:8000/api/learner/dashboard');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $learnerToken,
    'Accept: application/json',
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: " . $httpCode . "\n";
$data = json_decode($response, true);

if ($data && isset($data['data']['continueLearning'])) {
    echo "Continue Learning Courses with Progress:\n";
    foreach ($data['data']['continueLearning'] as $course) {
        echo "\n  Course: " . $course['title'] . "\n";
        echo "  Overall Progress: " . $course['progress'] . "%\n";
        
        if (isset($course['progressDetails'])) {
            $details = $course['progressDetails'];
            echo "  Progress Details:\n";
            echo "    Overall: {$details['completed_items']}/{$details['total_items']} items ({$details['overall_percentage']}%)\n";
            echo "    Modules: {$details['modules']['completed']}/{$details['modules']['total']} ({$details['modules']['percentage']}%)\n";
            echo "    Quizzes: {$details['quizzes']['completed']}/{$details['quizzes']['total']} ({$details['quizzes']['percentage']}%)\n";
            echo "    Assignments: {$details['assignments']['completed']}/{$details['assignments']['total']} ({$details['assignments']['percentage']}%)\n";
            echo "    Tests: {$details['tests']['completed']}/{$details['tests']['total']} ({$details['tests']['percentage']}%)\n";
        }
    }
} else {
    echo "No continue learning courses or error\n";
    echo "Response: " . substr($response, 0, 500) . "\n";
}

echo "\n\n";

// Test 2: Get course with detailed progress
echo "2. Testing Course Details with Progress\n";
echo "----------------------------------------\n";

// Get first enrolled course
$user = DB::table('users')->where('api_token', hash('sha256', $learnerToken))->first();
if (!$user) {
    echo "User not found!\n\n";
} else {
    echo "User ID: " . $user->id . "\n";
    
    $enrollment = DB::table('enrollments')
        ->where('learner_id', $user->id)
        ->first();
    
    if (!$enrollment) {
        echo "No enrollments found for user ID " . $user->id . "\n\n";
    } else {
    $courseId = $enrollment->course_id;
    echo "Testing with course ID: " . $courseId . "\n\n";
    
    $ch = curl_init("http://127.0.0.1:8000/api/learner/courses/{$courseId}");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $learnerToken,
        'Accept: application/json',
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Status: " . $httpCode . "\n";
    $data = json_decode($response, true);
    
    if ($data && isset($data['data']['progressDetails'])) {
        $details = $data['data']['progressDetails'];
        echo "\nCourse: " . $data['data']['title'] . "\n";
        echo "Progress Details:\n";
        echo "  Overall: {$details['completed_items']}/{$details['total_items']} items ({$details['overall_percentage']}%)\n";
        echo "  Modules: {$details['modules']['completed']}/{$details['modules']['total']} ({$details['modules']['percentage']}%)\n";
        echo "  Quizzes: {$details['quizzes']['completed']}/{$details['quizzes']['total']} ({$details['quizzes']['percentage']}%)\n";
        echo "  Assignments: {$details['assignments']['completed']}/{$details['assignments']['total']} ({$details['assignments']['percentage']}%)\n";
        echo "  Tests: {$details['tests']['completed']}/{$details['tests']['total']} ({$details['tests']['percentage']}%)\n";
    } else {
        echo "No progress details found\n";
        echo "Response: " . substr($response, 0, 500) . "\n";
    }
    
    echo "\n\n";
    
    // Test 3: Get progress endpoint
    echo "3. Testing Progress Endpoint\n";
    echo "----------------------------\n";
    
    $ch = curl_init("http://127.0.0.1:8000/api/learner/courses/{$courseId}/progress");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $learnerToken,
        'Accept: application/json',
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Status: " . $httpCode . "\n";
    $data = json_decode($response, true);
    
    if ($data && isset($data['data']['progressDetails'])) {
        $details = $data['data']['progressDetails'];
        echo "\nProgress Tracking:\n";
        echo "  Overall: {$details['completed_items']}/{$details['total_items']} items ({$details['overall_percentage']}%)\n";
        echo "\nBreakdown:\n";
        echo "  📚 Modules: {$details['modules']['completed']}/{$details['modules']['total']} ({$details['modules']['percentage']}%)\n";
        echo "  ❓ Quizzes: {$details['quizzes']['completed']}/{$details['quizzes']['total']} ({$details['quizzes']['percentage']}%)\n";
        echo "  📝 Assignments: {$details['assignments']['completed']}/{$details['assignments']['total']} ({$details['assignments']['percentage']}%)\n";
        echo "  📋 Tests: {$details['tests']['completed']}/{$details['tests']['total']} ({$details['tests']['percentage']}%)\n";
    } else {
        echo "Error or no progress details\n";
        echo "Response: " . substr($response, 0, 500) . "\n";
    }
    }
}

echo "\n\nDone!\n";
