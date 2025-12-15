<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Enrollment;
use App\Models\ModuleQuiz;
use App\Models\Course;

echo "=== Enrolling Users in Courses with Quizzes ===\n\n";

// Get the first user (test@example.com)
$user = User::where('email', 'test@example.com')->first();

if (!$user) {
    echo "User not found!\n";
    exit;
}

echo "User: " . $user->email . " (ID: " . $user->id . ")\n\n";

// Get all quizzes and their courses
$quizzes = ModuleQuiz::with('module.course')->get();

if ($quizzes->isEmpty()) {
    echo "No quizzes found.\n";
    exit;
}

$courseIds = [];
foreach ($quizzes as $quiz) {
    if ($quiz->module && $quiz->module->course) {
        $courseId = $quiz->module->course_id;
        $courseIds[$courseId] = $quiz->module->course->title;
    }
}

echo "Found " . count($courseIds) . " course(s) with quizzes:\n";
foreach ($courseIds as $courseId => $courseTitle) {
    echo "  - Course ID: $courseId - $courseTitle\n";
}

echo "\n";

// Enroll user in each course
foreach ($courseIds as $courseId => $courseTitle) {
    $existingEnrollment = Enrollment::where('learner_id', $user->id)
        ->where('course_id', $courseId)
        ->first();
    
    if ($existingEnrollment) {
        echo "✅ Already enrolled in: $courseTitle\n";
    } else {
        Enrollment::create([
            'learner_id' => $user->id,
            'course_id' => $courseId,
            'status' => 'active',
            'progress' => 0,
            'enrolled_at' => now(),
        ]);
        echo "✅ Enrolled in: $courseTitle\n";
    }
}

echo "\n========================================\n";
echo "✅ Enrollment complete!\n";
echo "The user can now access quizzes.\n";
echo "========================================\n";
