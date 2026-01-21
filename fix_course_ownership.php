<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Course Ownership Fix\n";
echo "====================\n\n";

// Find courses with invalid instructor_id
$courses = DB::table('courses')->get();

echo "All Courses:\n";
foreach ($courses as $course) {
    $instructor = DB::table('users')->where('id', $course->instructor_id)->first();
    
    echo "\nCourse ID {$course->id}: {$course->title}\n";
    echo "  Instructor ID: {$course->instructor_id}";
    
    if ($instructor) {
        echo " ✓ ({$instructor->name})\n";
    } else {
        echo " ❌ (USER DOESN'T EXIST)\n";
    }
}

echo "\n\nAvailable Instructors:\n";
$instructors = DB::table('users')->where('role', 'instructor')->get();
foreach ($instructors as $inst) {
    echo "  {$inst->id}. {$inst->name} ({$inst->email})\n";
}

echo "\n\nWould you like to reassign orphaned courses? (yes/no): ";
$handle = fopen ("php://stdin","r");
$line = trim(fgets($handle));

if(strtolower($line) === 'yes') {
    echo "\nEnter instructor ID to assign orphaned courses to: ";
    $newInstructorId = trim(fgets($handle));
    
    $instructor = DB::table('users')
        ->where('id', $newInstructorId)
        ->where('role', 'instructor')
        ->first();
    
    if (!$instructor) {
        echo "❌ Invalid instructor ID\n";
        exit(1);
    }
    
    // Find orphaned courses
    $orphanedCourses = [];
    foreach ($courses as $course) {
        $owner = DB::table('users')->where('id', $course->instructor_id)->first();
        if (!$owner) {
            $orphanedCourses[] = $course;
        }
    }
    
    if (empty($orphanedCourses)) {
        echo "No orphaned courses found\n";
        exit(0);
    }
    
    echo "\nReassigning " . count($orphanedCourses) . " courses to {$instructor->name}...\n";
    foreach ($orphanedCourses as $course) {
        DB::table('courses')
            ->where('id', $course->id)
            ->update(['instructor_id' => $newInstructorId]);
        echo "  ✓ Course {$course->id}: {$course->title}\n";
    }
    
    echo "\n✅ Done! Courses reassigned.\n";
} else {
    echo "Operation cancelled.\n";
}

fclose($handle);
