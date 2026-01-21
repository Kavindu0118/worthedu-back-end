<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// Add modules to courses that don't have any
$courses = DB::table('courses')->get();

foreach($courses as $course) {
    $moduleCount = DB::table('course_modules')->where('course_id', $course->id)->count();
    
    if ($moduleCount == 0) {
        DB::table('course_modules')->insert([
            'course_id' => $course->id,
            'module_title' => 'Module 1',
            'module_description' => 'First module for ' . $course->title,
            'order_index' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        echo "✓ Added module to course {$course->id}: {$course->title}\n";
    } else {
        echo "• Course {$course->id} already has {$moduleCount} module(s)\n";
    }
}

echo "\nDone!\n";
