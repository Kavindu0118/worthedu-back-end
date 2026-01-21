<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Test Submissions ===\n";
$subs = DB::table('test_submissions')->get();
foreach ($subs as $sub) {
    echo "ID: {$sub->id}, Test: {$sub->test_id}, Student: {$sub->student_id}, Status: {$sub->submission_status}, Started: {$sub->started_at}\n";
}

if ($subs->isEmpty()) {
    echo "No test submissions found.\n";
}

echo "\n=== Tests ===\n";
$tests = DB::table('tests')->get();
foreach ($tests as $test) {
    echo "ID: {$test->id}, Title: {$test->test_title}, Course: {$test->course_id}, Module: {$test->module_id}\n";
}
