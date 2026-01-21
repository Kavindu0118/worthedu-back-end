<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

echo "=== Learner Tests Integration Verification ===\n\n";

// 1. Check CourseModule model has tests relationship
echo "1. CHECKING COURSEMODULE MODEL:\n";
$moduleClass = new ReflectionClass(\App\Models\CourseModule::class);
if ($moduleClass->hasMethod('tests')) {
    echo "   ✓ CourseModule has 'tests' relationship\n";
} else {
    echo "   ❌ CourseModule missing 'tests' relationship\n";
}

// 2. Check Test model
echo "\n2. CHECKING TEST MODEL:\n";
$testClass = new ReflectionClass(\App\Models\Test::class);
echo "   ✓ Test model exists\n";
if ($testClass->hasMethod('module')) {
    echo "   ✓ Test has 'module' relationship\n";
}
if ($testClass->hasMethod('submissions')) {
    echo "   ✓ Test has 'submissions' relationship\n";
}

// 3. Check database tables
echo "\n3. DATABASE TABLES:\n";
$tables = ['tests', 'test_questions', 'test_submissions', 'test_answers'];
foreach ($tables as $table) {
    $exists = DB::getSchemaBuilder()->hasTable($table);
    echo "   " . ($exists ? "✓" : "❌") . " {$table} table " . ($exists ? "exists" : "MISSING") . "\n";
}

// 4. Check existing test data
echo "\n4. TEST DATA:\n";
$testsCount = DB::table('tests')->count();
echo "   Total tests in database: {$testsCount}\n";

if ($testsCount > 0) {
    $tests = DB::table('tests')
        ->join('course_modules', 'tests.module_id', '=', 'course_modules.id')
        ->join('courses', 'tests.course_id', '=', 'courses.id')
        ->select('tests.*', 'course_modules.module_title', 'courses.title as course_title')
        ->get();
    
    foreach ($tests as $test) {
        $now = Carbon::now();
        $status = 'unknown';
        if ($now->lt($test->start_date)) {
            $status = 'scheduled';
        } elseif ($now->between($test->start_date, $test->end_date)) {
            $status = 'active';
        } else {
            $status = 'closed';
        }
        
        echo "\n   Test ID {$test->id}: {$test->test_title}\n";
        echo "      Course: {$test->course_title}\n";
        echo "      Module: {$test->module_title}\n";
        echo "      Status: {$status} (DB: {$test->status})\n";
        echo "      Visibility: {$test->visibility_status}\n";
        echo "      Dates: {$test->start_date} to {$test->end_date}\n";
    }
}

// 5. Check learner enrollment
echo "\n5. LEARNER ENROLLMENTS:\n";
$enrollments = DB::table('enrollments')
    ->join('users', 'enrollments.learner_id', '=', 'users.id')
    ->join('courses', 'enrollments.course_id', '=', 'courses.id')
    ->select('enrollments.*', 'users.name as learner_name', 'courses.title as course_title')
    ->limit(5)
    ->get();

if ($enrollments->isEmpty()) {
    echo "   (no enrollments found)\n";
} else {
    foreach ($enrollments as $e) {
        echo "   • {$e->learner_name} enrolled in '{$e->course_title}' (Course ID: {$e->course_id})\n";
    }
}

// 6. Test the LearnerCourseController method exists
echo "\n6. LEARNER COURSE CONTROLLER:\n";
$controllerClass = new ReflectionClass(\App\Http\Controllers\LearnerCourseController::class);
if ($controllerClass->hasMethod('formatTestForLearner')) {
    echo "   ✓ formatTestForLearner method exists\n";
} else {
    echo "   ❌ formatTestForLearner method missing\n";
}

// 7. Create sample test data if none exists
if ($testsCount == 0) {
    echo "\n7. CREATING SAMPLE TEST DATA:\n";
    
    // Get a module with a course
    $module = DB::table('course_modules')->first();
    
    if ($module) {
        $testId = DB::table('tests')->insertGetId([
            'module_id' => $module->id,
            'course_id' => $module->course_id,
            'test_title' => 'Sample Midterm Exam',
            'test_description' => 'This is a sample midterm examination covering all topics from Module 1.',
            'instructions' => 'Read all questions carefully. You have 60 minutes to complete this test.',
            'start_date' => Carbon::now()->subDay(),
            'end_date' => Carbon::now()->addDays(7),
            'time_limit' => 60,
            'max_attempts' => 2,
            'total_marks' => 100,
            'passing_marks' => 40,
            'status' => 'active',
            'visibility_status' => 'visible',
            'grading_status' => 'not_started',
            'results_published' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        echo "   ✓ Created test ID: {$testId}\n";
        echo "   Module: {$module->module_title} (ID: {$module->id})\n";
        echo "   Course ID: {$module->course_id}\n";
        
        // Also create a scheduled test
        $testId2 = DB::table('tests')->insertGetId([
            'module_id' => $module->id,
            'course_id' => $module->course_id,
            'test_title' => 'Final Examination',
            'test_description' => 'Comprehensive final examination.',
            'instructions' => 'This is the final exam. Good luck!',
            'start_date' => Carbon::now()->addDays(14),
            'end_date' => Carbon::now()->addDays(21),
            'time_limit' => 90,
            'max_attempts' => 1,
            'total_marks' => 150,
            'passing_marks' => 60,
            'status' => 'scheduled',
            'visibility_status' => 'visible',
            'grading_status' => 'not_started',
            'results_published' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        echo "   ✓ Created scheduled test ID: {$testId2}\n";
    } else {
        echo "   ❌ No modules found to create test\n";
    }
} else {
    echo "\n7. SAMPLE TEST DATA: Already exists\n";
}

echo "\n=== Verification Complete ===\n";
echo "\nTo test the API endpoint:\n";
echo "1. Login as a learner enrolled in a course that has tests\n";
echo "2. Call: GET /api/learner/courses/{courseId}\n";
echo "3. Check that 'tests' array appears in each module\n";
