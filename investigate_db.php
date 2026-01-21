<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Database Investigation ===\n\n";

// Check instructors table
echo "1. INSTRUCTORS TABLE:\n";
$instructors = DB::table('instructors')->get();
foreach($instructors as $i) {
    echo "   instructor_id: {$i->instructor_id}, user_id: {$i->user_id}, name: {$i->first_name} {$i->last_name}, status: {$i->status}\n";
}

// Check users with instructor role
echo "\n2. USERS WITH INSTRUCTOR ROLE:\n";
$users = DB::table('users')->where('role', 'instructor')->get();
foreach($users as $u) {
    echo "   user_id: {$u->id}, name: {$u->name}, email: {$u->email}\n";
    
    // Check if they have an instructor record
    $inst = DB::table('instructors')->where('user_id', $u->id)->first();
    if ($inst) {
        echo "      -> Has instructor record (instructor_id: {$inst->instructor_id})\n";
    } else {
        echo "      -> ❌ NO INSTRUCTOR RECORD!\n";
    }
}

// Check courses
echo "\n3. COURSES TABLE:\n";
$courses = DB::table('courses')->get();
foreach($courses as $c) {
    echo "   course_id: {$c->id}, title: {$c->title}, instructor_id: {$c->instructor_id}\n";
    
    // Check if instructor_id is valid
    $inst = DB::table('instructors')->where('instructor_id', $c->instructor_id)->first();
    if ($inst) {
        echo "      -> Owner: {$inst->first_name} {$inst->last_name}\n";
    } else {
        echo "      -> ❌ INVALID instructor_id!\n";
    }
}

// Check modules
echo "\n4. MODULES TABLE:\n";
$modules = DB::table('course_modules')->get();
if ($modules->isEmpty()) {
    echo "   (no modules found)\n";
} else {
    foreach($modules as $m) {
        echo "   module_id: {$m->id}, course_id: {$m->course_id}, title: {$m->module_title}\n";
    }
}

echo "\n=== Investigation Complete ===\n";
