<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Test API Authorization Check\n";
echo "============================\n\n";

// Simulate the request from the frontend
$bearerToken = $argv[1] ?? null;

if (!$bearerToken) {
    echo "Usage: php test_instructor_auth.php YOUR_BEARER_TOKEN [courseId]\n";
    echo "\nTo get your token, check localStorage in browser console:\n";
    echo "  localStorage.getItem('api_token')\n\n";
    exit(1);
}

// Hash the token like the middleware does
$hashedToken = hash('sha256', $bearerToken);

echo "1. Token Lookup:\n";
echo "   Bearer Token: " . substr($bearerToken, 0, 20) . "...\n";
echo "   Hashed Token: " . substr($hashedToken, 0, 20) . "...\n\n";

// Find user
$user = DB::table('users')
    ->where('api_token', $hashedToken)
    ->first();

if (!$user) {
    echo "❌ FAILED: User not found with this token\n";
    echo "\nDebugging steps:\n";
    echo "1. Check if token exists in database:\n";
    echo "   SELECT id, name, email, role FROM users WHERE api_token LIKE '" . substr($hashedToken, 0, 10) . "%';\n\n";
    
    // Show all instructor tokens
    $instructors = DB::table('users')
        ->where('role', 'instructor')
        ->get(['id', 'name', 'email', 'api_token']);
    
    echo "Available instructor accounts:\n";
    foreach ($instructors as $instructor) {
        echo "  • ID: {$instructor->id}, Name: {$instructor->name}, Email: {$instructor->email}\n";
        echo "    Token hash: " . substr($instructor->api_token, 0, 20) . "...\n";
    }
    exit(1);
}

echo "✓ User Found:\n";
echo "   ID: {$user->id}\n";
echo "   Name: {$user->name}\n";
echo "   Email: {$user->email}\n";
echo "   Role: {$user->role}\n\n";

// Check role
echo "2. Role Check:\n";
if ($user->role !== 'instructor') {
    echo "   ❌ FAILED: User role is '{$user->role}', not 'instructor'\n";
    echo "   Only instructors can create/manage tests\n";
    exit(1);
}
echo "   ✓ User is an instructor\n\n";

// Check course ownership
$courseId = $argv[2] ?? null;

if ($courseId) {
    echo "3. Course Ownership Check (Course ID: {$courseId}):\n";
    
    $course = DB::table('courses')
        ->where('id', $courseId)
        ->first();
    
    if (!$course) {
        echo "   ❌ FAILED: Course not found\n";
        exit(1);
    }
    
    echo "   Course Title: {$course->title}\n";
    echo "   Course Instructor ID: {$course->instructor_id}\n";
    echo "   Your ID: {$user->id}\n";
    
    if ($course->instructor_id != $user->id) {
        echo "   ❌ FAILED: You don't own this course\n";
        
        $owner = DB::table('users')
            ->where('id', $course->instructor_id)
            ->first();
        
        if ($owner) {
            echo "   Course belongs to: {$owner->name} ({$owner->email})\n";
        }
        
        echo "\nYour courses:\n";
        $yourCourses = DB::table('courses')
            ->where('instructor_id', $user->id)
            ->get();
        
        if ($yourCourses->isEmpty()) {
            echo "   (none)\n";
        } else {
            foreach ($yourCourses as $c) {
                echo "   • ID: {$c->id} - {$c->title}\n";
            }
        }
        exit(1);
    }
    
    echo "   ✓ You own this course\n\n";
    
    // Check modules
    echo "4. Course Modules:\n";
    $modules = DB::table('course_modules')
        ->where('course_id', $courseId)
        ->get();
    
    if ($modules->isEmpty()) {
        echo "   ⚠ No modules found in this course\n";
    } else {
        foreach ($modules as $module) {
            echo "   • Module ID: {$module->id} - {$module->module_title}\n";
        }
    }
}

echo "\n✅ ALL CHECKS PASSED\n";
echo "You should be able to create tests for course {$courseId}\n";
