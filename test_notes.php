<?php
/**
 * Test script to verify notes API functionality
 * Run: php test_notes.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\CourseModule;

echo "=== Testing Notes API Configuration ===\n\n";

// Check if instructor users exist
$instructors = User::where('role', 'instructor')->with('instructor')->get();
echo "Found " . $instructors->count() . " instructor users\n";

if ($instructors->isEmpty()) {
    echo "⚠️  WARNING: No instructors found. Create an instructor user first.\n\n";
} else {
    foreach ($instructors as $user) {
        echo "\nInstructor User:\n";
        echo "  - User ID: {$user->id}\n";
        echo "  - Name: {$user->name}\n";
        echo "  - Email: {$user->email}\n";
        
        if ($user->instructor) {
            echo "  - Instructor Profile ID: {$user->instructor->instructor_id}\n";
            echo "  ✅ Has instructor profile\n";
        } else {
            echo "  ❌ Missing instructor profile!\n";
        }
    }
}

// Check modules
echo "\n--- Course Modules ---\n";
$modules = CourseModule::with('course.instructor.user')->take(5)->get();
foreach ($modules as $module) {
    echo "\nModule ID: {$module->id}\n";
    echo "  - Title: {$module->module_title}\n";
    echo "  - Course: {$module->course->title}\n";
    if ($module->course->instructor) {
        echo "  - Instructor ID: {$module->course->instructor_id}\n";
        echo "  - Instructor Name: {$module->course->instructor->user->name}\n";
    }
}

// Check file upload limits
echo "\n--- PHP Upload Configuration ---\n";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "post_max_size: " . ini_get('post_max_size') . "\n";
echo "max_execution_time: " . ini_get('max_execution_time') . "s\n";
echo "max_input_time: " . ini_get('max_input_time') . "s\n";

// Check storage directory
$storagePath = storage_path('app/public/course-attachments');
echo "\n--- Storage Directory ---\n";
echo "Path: {$storagePath}\n";
if (is_dir($storagePath)) {
    echo "✅ Directory exists\n";
    echo "Writable: " . (is_writable($storagePath) ? 'Yes' : 'No') . "\n";
} else {
    echo "⚠️  Directory does not exist. Run: php artisan storage:link\n";
}

echo "\n=== Test Complete ===\n";
