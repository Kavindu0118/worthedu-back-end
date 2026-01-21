<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Instructor Dashboard Verification ===\n\n";

// 1. Check database integrity
echo "1. DATABASE INTEGRITY:\n";
$courses = DB::table('courses')->get();
$allValid = true;
foreach($courses as $c) {
    $inst = DB::table('instructors')->where('instructor_id', $c->instructor_id)->first();
    if ($inst) {
        echo "   ✓ Course {$c->id} ({$c->title}) -> instructor_id {$c->instructor_id} ({$inst->first_name})\n";
    } else {
        echo "   ❌ Course {$c->id} ({$c->title}) -> INVALID instructor_id {$c->instructor_id}\n";
        $allValid = false;
    }
}

if ($allValid) {
    echo "   ✓ All courses have valid instructor_id\n";
}

// 2. Check instructor-user mapping
echo "\n2. INSTRUCTOR-USER MAPPING:\n";
$instructors = DB::table('instructors')->get();
foreach($instructors as $inst) {
    $user = DB::table('users')->where('id', $inst->user_id)->first();
    if ($user) {
        echo "   ✓ instructor_id {$inst->instructor_id} -> user_id {$inst->user_id} ({$user->name}, {$user->email})\n";
    } else {
        echo "   ❌ instructor_id {$inst->instructor_id} -> user_id {$inst->user_id} (USER NOT FOUND)\n";
    }
}

// 3. List courses by instructor with their user accounts
echo "\n3. COURSES BY INSTRUCTOR:\n";
$instructors = DB::table('instructors')
    ->join('users', 'instructors.user_id', '=', 'users.id')
    ->select('instructors.*', 'users.name', 'users.email')
    ->get();

foreach($instructors as $inst) {
    $courses = DB::table('courses')->where('instructor_id', $inst->instructor_id)->get();
    echo "\n   👤 {$inst->name} ({$inst->email})\n";
    echo "      instructor_id: {$inst->instructor_id}, user_id: {$inst->user_id}, status: {$inst->status}\n";
    
    if ($courses->isEmpty()) {
        echo "      Courses: (none)\n";
    } else {
        echo "      Courses:\n";
        foreach($courses as $c) {
            $modules = DB::table('course_modules')->where('course_id', $c->id)->count();
            echo "        • {$c->title} (ID: {$c->id}, Modules: {$modules})\n";
        }
    }
}

// 4. Show login instructions
echo "\n4. TO TEST THE INSTRUCTOR DASHBOARD:\n";
echo "   Login as one of these approved instructors:\n";

$approved = DB::table('instructors')
    ->join('users', 'instructors.user_id', '=', 'users.id')
    ->where('instructors.status', 'approved')
    ->select('instructors.*', 'users.name', 'users.email')
    ->get();

foreach($approved as $inst) {
    $courseCount = DB::table('courses')->where('instructor_id', $inst->instructor_id)->count();
    echo "   • Email: {$inst->email} (Courses: {$courseCount})\n";
}

echo "\n=== Verification Complete ===\n";
