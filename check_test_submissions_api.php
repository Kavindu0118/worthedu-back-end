<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Test;
use App\Models\TestSubmission;
use Illuminate\Support\Facades\DB;

echo "=== Testing Test Submissions API Response ===\n\n";

// Get an instructor
$instructor = DB::table('instructors')->first();
$user = User::find($instructor->user_id);

echo "Instructor: {$user->name} ({$user->email})\n";
echo "Token: " . substr($user->api_token, 0, 20) . "...\n\n";

// Get a test that belongs to this instructor
$test = Test::whereHas('course', function($q) use ($instructor) {
    $q->where('instructor_id', $instructor->instructor_id);
})->first();

if (!$test) {
    echo "No tests found for this instructor\n";
    exit(1);
}

echo "Test ID: {$test->id}, Title: {$test->test_title}\n";

// Check submissions for this test
$submissions = TestSubmission::where('test_id', $test->id)
    ->with(['student:id,name,email', 'answers'])
    ->get();

echo "\nSubmissions for test {$test->id}:\n";
echo json_encode(['success' => true, 'data' => $submissions], JSON_PRETTY_PRINT);

echo "\n\n=== All Tests with Submissions ===\n";
$tests = Test::withCount('submissions')->get();
foreach ($tests as $t) {
    echo "Test {$t->id}: '{$t->test_title}' - {$t->submissions_count} submissions\n";
}

// Check what student relationship returns
echo "\n=== Checking student relationship ===\n";
$testSubmission = TestSubmission::with('student')->first();
if ($testSubmission) {
    echo "Submission ID: {$testSubmission->id}\n";
    echo "Student: " . ($testSubmission->student ? $testSubmission->student->name : "NULL - RELATIONSHIP NOT WORKING") . "\n";
}
