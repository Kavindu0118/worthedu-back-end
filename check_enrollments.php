<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Enrolled Courses with Tests:\n";
echo "============================\n\n";

$enrollments = DB::table('enrollments')
    ->join('courses', 'enrollments.course_id', '=', 'courses.id')
    ->join('users', 'enrollments.learner_id', '=', 'users.id')
    ->select('enrollments.course_id', 'courses.title', 'users.name as learner')
    ->get();

foreach ($enrollments as $e) {
    $testCount = DB::table('tests')->where('course_id', $e->course_id)->count();
    echo "Course {$e->course_id}: {$e->title} (Learner: {$e->learner})\n";
    echo "   Tests: {$testCount}\n";
}
