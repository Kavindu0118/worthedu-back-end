<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Checking User and Enrollments\n";
echo "=============================\n\n";

$user = DB::table('users')->where('email', 'test@example.com')->first();

if ($user) {
    echo "User found:\n";
    echo "  ID: " . $user->id . "\n";
    echo "  Email: " . $user->email . "\n";
    echo "  Role: " . $user->role . "\n\n";
    
    $enrollments = DB::table('enrollments')
        ->where('learner_id', $user->id)
        ->get();
    
    echo "Enrollments: " . count($enrollments) . "\n";
    
    if (count($enrollments) > 0) {
        foreach ($enrollments as $enrollment) {
            $course = DB::table('courses')->where('id', $enrollment->course_id)->first();
            echo "  - Course ID: " . $enrollment->course_id;
            if ($course) {
                echo " (" . $course->title . ")";
            }
            echo "\n";
            echo "    Status: " . $enrollment->status . "\n";
            echo "    Progress: " . $enrollment->progress . "%\n";
        }
    } else {
        echo "  No enrollments found. Let's create one...\n\n";
        
        // Create an enrollment
        $course = DB::table('courses')->first();
        if ($course) {
            DB::table('enrollments')->insert([
                'learner_id' => $user->id,
                'course_id' => $course->id,
                'status' => 'active',
                'progress' => 0,
                'enrolled_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
                'last_accessed_at' => now(),
            ]);
            echo "  Created enrollment for course: " . $course->title . "\n";
        }
    }
} else {
    echo "User not found\n";
}
