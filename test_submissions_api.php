<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Test;
use App\Models\TestSubmission;
use Illuminate\Support\Facades\DB;

echo "=== Testing Updated API Response Structure ===\n\n";

// Get a test with submissions
$testWithSubmission = Test::whereHas('submissions')->first();

if (!$testWithSubmission) {
    echo "No tests with submissions found. Creating test data...\n";
    exit(1);
}

echo "Test: {$testWithSubmission->id} - {$testWithSubmission->test_title}\n";
echo "Course ID: {$testWithSubmission->course_id}\n\n";

// Get instructor for this course
$course = $testWithSubmission->course;
$instructorId = $course->instructor_id;
$instructor = DB::table('instructors')->where('instructor_id', $instructorId)->first();
$instructorUser = User::find($instructor->user_id);

echo "Instructor: {$instructorUser->name} ({$instructorUser->email})\n\n";

// Simulate API call
$submissions = TestSubmission::where('test_id', $testWithSubmission->id)
    ->with(['student:id,name,email', 'answers.question'])
    ->orderBy('submitted_at', 'desc')
    ->get();

$submittedCount = $submissions->whereIn('submission_status', ['submitted', 'late'])->count();
$gradedCount = $submissions->whereIn('grading_status', ['graded', 'published'])->count();
$averageScore = $submissions->whereNotNull('total_score')->avg('total_score');

$response = [
    'success' => true,
    'data' => [
        'test' => $testWithSubmission->only(['id', 'test_title', 'total_marks', 'passing_marks']),
        'submissions' => $submissions,
        'statistics' => [
            'total_submissions' => $submissions->count(),
            'submitted_count' => $submittedCount,
            'in_progress_count' => $submissions->where('submission_status', 'in_progress')->count(),
            'graded_count' => $gradedCount,
            'pending_grading' => $submittedCount - $gradedCount,
            'average_score' => $averageScore ? round($averageScore, 2) : null,
            'total_marks' => $testWithSubmission->total_marks,
        ]
    ]
];

echo "=== API Response Structure ===\n";
echo json_encode($response, JSON_PRETTY_PRINT);
echo "\n";
