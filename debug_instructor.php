<?php
/**
 * Debug instructor authentication issue
 */

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Instructor;
use Illuminate\Support\Facades\DB;

echo "=== Debugging Instructor Authentication ===\n\n";

// Find instructors
echo "1. Users with role 'instructor':\n";
$instructorUsers = User::where('role', 'instructor')->get();
foreach ($instructorUsers as $user) {
    echo "   User ID: {$user->id}, Name: {$user->name}, Email: {$user->email}\n";
    
    // Check if they have an instructor record
    $instructor = Instructor::where('user_id', $user->id)->first();
    if ($instructor) {
        echo "   -> Instructor record: instructor_id = {$instructor->instructor_id}\n";
    } else {
        echo "   -> ❌ NO instructor record found!\n";
    }
    
    // Test the relationship
    $relatedInstructor = $user->instructor;
    if ($relatedInstructor) {
        echo "   -> Relationship works: instructor_id = {$relatedInstructor->instructor_id}\n";
    } else {
        echo "   -> ❌ Relationship returns NULL\n";
    }
    echo "\n";
}

echo "\n2. All records in instructors table:\n";
$allInstructors = Instructor::all();
foreach ($allInstructors as $inst) {
    $user = User::find($inst->user_id);
    echo "   instructor_id: {$inst->instructor_id}, user_id: {$inst->user_id}, ";
    echo "User: " . ($user ? $user->email : 'NOT FOUND') . "\n";
}

echo "\n3. Courses and their instructor_id:\n";
$courses = DB::table('courses')->select('id', 'title', 'instructor_id')->get();
foreach ($courses as $course) {
    $instructor = Instructor::find($course->instructor_id);
    $status = $instructor ? "✓ Valid (user: {$instructor->user_id})" : "❌ Invalid";
    echo "   Course {$course->id}: '{$course->title}' -> instructor_id: {$course->instructor_id} - {$status}\n";
}

echo "\n=== Debug Complete ===\n";
