<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Fixing Course Instructor IDs ===\n\n";

// Get all courses with their current instructor_id
$courses = DB::table('courses')->get();

foreach($courses as $course) {
    echo "Course {$course->id}: {$course->title}\n";
    echo "  Current instructor_id: {$course->instructor_id}\n";
    
    // Check if this instructor_id exists in instructors table
    $instructor = DB::table('instructors')
        ->where('instructor_id', $course->instructor_id)
        ->first();
    
    if ($instructor) {
        echo "  ✓ Valid instructor: {$instructor->first_name} {$instructor->last_name}\n";
        continue;
    }
    
    // Maybe the instructor_id is actually a user_id?
    $instructorByUserId = DB::table('instructors')
        ->where('user_id', $course->instructor_id)
        ->first();
    
    if ($instructorByUserId) {
        echo "  ❌ Found instructor by user_id - need to fix\n";
        echo "  -> Changing from user_id {$course->instructor_id} to instructor_id {$instructorByUserId->instructor_id}\n";
        
        DB::table('courses')
            ->where('id', $course->id)
            ->update(['instructor_id' => $instructorByUserId->instructor_id]);
        
        echo "  ✓ Fixed!\n";
    } else {
        echo "  ❌ Cannot find instructor for this course!\n";
    }
    
    echo "\n";
}

echo "=== Database Fix Complete ===\n\n";

// Verify
echo "=== Verification ===\n";
$courses = DB::table('courses')->get();
foreach($courses as $c) {
    $inst = DB::table('instructors')->where('instructor_id', $c->instructor_id)->first();
    $status = $inst ? "✓ {$inst->first_name} {$inst->last_name}" : "❌ INVALID";
    echo "Course {$c->id}: instructor_id={$c->instructor_id} -> {$status}\n";
}
