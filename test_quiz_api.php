<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ModuleQuiz;
use App\Models\CourseModule;
use App\Models\Enrollment;
use App\Models\User;

echo "=== Testing Quiz API ===\n\n";

// Get first quiz
$quiz = ModuleQuiz::first();
echo "Quiz ID: " . $quiz->id . "\n";
echo "Quiz Title (accessor): " . $quiz->title . "\n";
echo "Quiz Title (direct): " . $quiz->quiz_title . "\n";
echo "Module ID: " . $quiz->module_id . "\n";

// Test module relationship
try {
    $module = $quiz->module;
    if ($module) {
        echo "Module found: " . $module->id . "\n";
        echo "Module course_id: " . $module->course_id . "\n";
        
        // Test course relationship
        try {
            $course = $module->course;
            if ($course) {
                echo "Course found: " . $course->id . " - " . $course->title . "\n";
            } else {
                echo "ERROR: Course not found!\n";
            }
        } catch (Exception $e) {
            echo "ERROR loading course: " . $e->getMessage() . "\n";
        }
    } else {
        echo "ERROR: Module not found!\n";
    }
} catch (Exception $e) {
    echo "ERROR loading module: " . $e->getMessage() . "\n";
}

// Test with eager loading
echo "\n=== Testing Eager Loading ===\n";
try {
    $quizWithRelations = ModuleQuiz::with(['module.course'])->first();
    echo "Quiz loaded successfully\n";
    echo "Has module: " . ($quizWithRelations->module ? 'Yes' : 'No') . "\n";
    if ($quizWithRelations->module) {
        echo "Module course_id: " . $quizWithRelations->module->course_id . "\n";
        echo "Has course: " . ($quizWithRelations->module->course ? 'Yes' : 'No') . "\n";
        if ($quizWithRelations->module->course) {
            echo "Course title: " . $quizWithRelations->module->course->title . "\n";
        }
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Test Complete ===\n";
