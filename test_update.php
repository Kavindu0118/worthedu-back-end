<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Course;

echo "Testing Course Update...\n\n";

$course = Course::find(1);

if (!$course) {
    echo "Course ID 1 not found!\n";
    exit;
}

echo "BEFORE UPDATE:\n";
echo "ID: " . $course->id . "\n";
echo "Title: " . $course->title . "\n";
echo "Category: " . ($course->category ?? 'NULL') . "\n";
echo "Description: " . substr($course->description, 0, 50) . "...\n";
echo "Status: " . $course->status . "\n\n";

// Attempt update
$updateData = [
    'title' => 'TEST UPDATED TITLE - ' . date('H:i:s'),
    'category' => 'TEST CATEGORY',
    'description' => 'This is a test description updated at ' . date('Y-m-d H:i:s'),
];

echo "Attempting to update with:\n";
print_r($updateData);
echo "\n";

$result = $course->update($updateData);
echo "Update result: " . ($result ? 'SUCCESS' : 'FAILED') . "\n\n";

// Refresh and check
$course->refresh();

echo "AFTER UPDATE:\n";
echo "ID: " . $course->id . "\n";
echo "Title: " . $course->title . "\n";
echo "Category: " . ($course->category ?? 'NULL') . "\n";
echo "Description: " . substr($course->description, 0, 50) . "...\n";
echo "Status: " . $course->status . "\n";
echo "Updated At: " . $course->updated_at . "\n";
