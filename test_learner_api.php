<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\LearnerCourseController;
use Illuminate\Http\Request;

echo "=== Testing Learner Course API with Tests ===\n\n";

// Find a learner with enrollments
$enrollment = DB::table('enrollments')
    ->join('users', 'enrollments.learner_id', '=', 'users.id')
    ->where('users.role', 'learner')
    ->select('enrollments.*', 'users.id as user_id', 'users.name', 'users.email')
    ->first();

if (!$enrollment) {
    echo "❌ No learner enrollments found\n";
    exit(1);
}

echo "Testing with learner: {$enrollment->name} ({$enrollment->email})\n";
echo "Course ID: {$enrollment->course_id}\n\n";

// Authenticate as the learner
$user = \App\Models\User::find($enrollment->user_id);
Auth::setUser($user);

// Create controller and make request
$controller = new LearnerCourseController();
$response = $controller->show($enrollment->course_id);
$data = json_decode($response->getContent(), true);

if (!$data['success']) {
    echo "❌ API Error: {$data['message']}\n";
    exit(1);
}

echo "✅ API Response received\n\n";

$courseData = $data['data'];
echo "Course: {$courseData['title']}\n";
echo "Modules: " . count($courseData['modules']) . "\n\n";

// Check each module for tests
$totalTests = 0;
foreach ($courseData['modules'] as $module) {
    echo "Module: {$module['title']}\n";
    echo "  Notes: " . count($module['notes']) . "\n";
    echo "  Quizzes: " . count($module['quizzes']) . "\n";
    echo "  Assignments: " . count($module['assignments']) . "\n";
    
    if (isset($module['tests'])) {
        echo "  Tests: " . count($module['tests']) . "\n";
        $totalTests += count($module['tests']);
        
        foreach ($module['tests'] as $test) {
            echo "    • {$test['test_title']}\n";
            echo "      Status: {$test['status']}\n";
            echo "      Total Marks: {$test['total_marks']}\n";
            echo "      Time Limit: {$test['time_limit']} min\n";
            echo "      Attempts: {$test['attempts_used']}/{$test['max_attempts']}\n";
            echo "      Submission: " . ($test['submission_status'] ?? 'none') . "\n";
        }
    } else {
        echo "  Tests: ❌ NOT INCLUDED\n";
    }
    echo "\n";
}

echo "=== Summary ===\n";
echo "Total tests found in response: {$totalTests}\n";

if ($totalTests > 0) {
    echo "✅ Tests integration working!\n";
} else {
    echo "⚠ No tests found - check if the course has tests in visible modules\n";
}
