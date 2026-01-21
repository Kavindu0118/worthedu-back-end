<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Test Access Diagnosis\n";
echo "=====================\n\n";

// Get the token from command line
if ($argc < 2) {
    echo "Usage: php diagnose_test_access.php YOUR_TOKEN\n";
    exit(1);
}

$token = $argv[1];

// Find user by token
$user = DB::table('users')
    ->where('api_token', $token)
    ->first();

if (!$user) {
    echo "❌ Invalid token - user not found\n";
    exit(1);
}

echo "User Information:\n";
echo "  ID: {$user->id}\n";
echo "  Name: {$user->name}\n";
echo "  Email: {$user->email}\n";
echo "  Role: {$user->role}\n\n";

if ($user->role !== 'instructor') {
    echo "❌ User is not an instructor (role: {$user->role})\n";
    echo "   Only instructors can create/manage tests\n";
    exit(1);
}

echo "✓ User is an instructor\n\n";

// Check courses
$courses = DB::table('courses')
    ->where('instructor_id', $user->id)
    ->get();

echo "Instructor's Courses:\n";
if ($courses->isEmpty()) {
    echo "  ❌ No courses found\n";
} else {
    foreach ($courses as $course) {
        echo "  • Course ID: {$course->id} - {$course->title}\n";
        
        // Check modules
        $modules = DB::table('modules')
            ->where('course_id', $course->id)
            ->get();
        
        foreach ($modules as $module) {
            echo "    - Module ID: {$module->id} - {$module->title}\n";
        }
    }
}

echo "\n";

// If they're trying to access course 3
if (isset($argv[2])) {
    $courseId = $argv[2];
    echo "Checking access to Course ID: {$courseId}\n";
    
    $course = DB::table('courses')
        ->where('id', $courseId)
        ->first();
    
    if (!$course) {
        echo "  ❌ Course not found\n";
    } else {
        echo "  Course: {$course->title}\n";
        echo "  Instructor ID: {$course->instructor_id}\n";
        
        if ($course->instructor_id == $user->id) {
            echo "  ✓ You own this course\n";
        } else {
            echo "  ❌ You DON'T own this course\n";
            echo "     This course belongs to instructor ID: {$course->instructor_id}\n";
            echo "     You are instructor ID: {$user->id}\n";
            
            // Find the actual owner
            $owner = DB::table('users')
                ->where('id', $course->instructor_id)
                ->first();
            if ($owner) {
                echo "     Course owner: {$owner->name} ({$owner->email})\n";
            }
        }
    }
}

echo "\n✅ Diagnosis complete\n";
