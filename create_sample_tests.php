<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "=== Updating Test Data ===\n\n";

// Update the existing test to be visible and active
$updated = DB::table('tests')
    ->where('id', 1)
    ->update([
        'status' => 'active',
        'visibility_status' => 'visible',
        'start_date' => Carbon::now()->subDay(),
        'end_date' => Carbon::now()->addDays(7),
        'updated_at' => now(),
    ]);

echo "✓ Updated test ID 1 to be active and visible\n";

// Get all modules with courses
$modules = DB::table('course_modules')
    ->join('courses', 'course_modules.course_id', '=', 'courses.id')
    ->select('course_modules.*', 'courses.title as course_title')
    ->get();

echo "\nCreating sample tests for modules without tests...\n";

foreach ($modules as $module) {
    // Check if module already has a test
    $existingTest = DB::table('tests')->where('module_id', $module->id)->first();
    
    if (!$existingTest) {
        // Create an active test
        $testId = DB::table('tests')->insertGetId([
            'module_id' => $module->id,
            'course_id' => $module->course_id,
            'test_title' => 'Module Assessment: ' . $module->module_title,
            'test_description' => 'Assessment test for ' . $module->module_title,
            'instructions' => 'Complete all questions within the time limit.',
            'start_date' => Carbon::now()->subDay(),
            'end_date' => Carbon::now()->addDays(14),
            'time_limit' => 45,
            'max_attempts' => 2,
            'total_marks' => 50,
            'passing_marks' => 20,
            'status' => 'active',
            'visibility_status' => 'visible',
            'grading_status' => 'not_started',
            'results_published' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        echo "  ✓ Created test for '{$module->module_title}' (Course: {$module->course_title})\n";
    }
}

// Show all tests now
echo "\n=== All Tests ===\n";
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
    
    echo "\nTest ID {$test->id}: {$test->test_title}\n";
    echo "   Course: {$test->course_title} (ID: {$test->course_id})\n";
    echo "   Module: {$test->module_title} (ID: {$test->module_id})\n";
    echo "   Status: {$status} | Visibility: {$test->visibility_status}\n";
    echo "   Dates: {$test->start_date} to {$test->end_date}\n";
    echo "   Time Limit: {$test->time_limit} min | Max Attempts: {$test->max_attempts}\n";
}

echo "\n=== Done ===\n";
