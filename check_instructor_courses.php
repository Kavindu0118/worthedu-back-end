<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Quick Access Check\n";
echo "==================\n\n";

// Get all instructors with their courses
$instructors = DB::table('users')
    ->where('role', 'instructor')
    ->get();

echo "Instructors in system:\n";
foreach ($instructors as $instructor) {
    echo "\n👤 {$instructor->name} (ID: {$instructor->id})\n";
    echo "   Email: {$instructor->email}\n";
    
    $courses = DB::table('courses')
        ->where('instructor_id', $instructor->id)
        ->get();
    
    if ($courses->isEmpty()) {
        echo "   Courses: (none)\n";
    } else {
        echo "   Courses:\n";
        foreach ($courses as $course) {
            echo "     • ID: {$course->id} - {$course->title}\n";
        }
    }
}

echo "\n\nCourse ID 3 Details:\n";
$course3 = DB::table('courses')->where('id', 3)->first();

if ($course3) {
    echo "Title: {$course3->title}\n";
    echo "Instructor ID: {$course3->instructor_id}\n";
    
    $owner = DB::table('users')->where('id', $course3->instructor_id)->first();
    if ($owner) {
        echo "Owner: {$owner->name} ({$owner->email})\n";
        echo "Owner Role: {$owner->role}\n";
    }
} else {
    echo "(Not found)\n";
}
