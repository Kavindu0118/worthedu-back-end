<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Get a user token
$user = \App\Models\User::where('role', 'learner')->first();

if (!$user) {
    echo "No learner found" . PHP_EOL;
    exit;
}

// Get API token from user
echo "User ID: " . $user->id . PHP_EOL;
echo "User Name: " . $user->name . PHP_EOL;
echo "User Email: " . $user->email . PHP_EOL;

// Find a module with notes
$moduleWithNotes = \App\Models\CourseModule::whereHas('notes')->first();

if (!$moduleWithNotes) {
    echo "No modules with notes found" . PHP_EOL;
    exit;
}

echo "\nModule ID: " . $moduleWithNotes->id . PHP_EOL;
echo "Module Title: " . $moduleWithNotes->module_title . PHP_EOL;
echo "Course ID: " . $moduleWithNotes->course_id . PHP_EOL;
echo "Notes Count: " . $moduleWithNotes->notes()->count() . PHP_EOL;

// Check enrollment
$enrollment = \App\Models\Enrollment::where('learner_id', $user->id)
    ->where('course_id', $moduleWithNotes->course_id)
    ->first();

if (!$enrollment) {
    echo "\nCreating enrollment..." . PHP_EOL;
    $enrollment = \App\Models\Enrollment::create([
        'learner_id' => $user->id,
        'course_id' => $moduleWithNotes->course_id,
        'status' => 'active',
        'progress' => 0,
    ]);
}

echo "\nNow test this API endpoint:" . PHP_EOL;
echo "GET http://localhost:8000/api/learner/lessons/{$moduleWithNotes->id}" . PHP_EOL;
echo "\nWith header:" . PHP_EOL;
echo "Authorization: Bearer YOUR_TOKEN_HERE" . PHP_EOL;

// Show notes data
echo "\n\n=== NOTES DATA ===" . PHP_EOL;
$notes = $moduleWithNotes->notes;
foreach ($notes as $note) {
    echo "\nNote ID: " . $note->id . PHP_EOL;
    echo "Title: " . $note->note_title . PHP_EOL;
    echo "Body: " . $note->note_body . PHP_EOL;
    echo "Attachment URL (raw): " . ($note->getRawOriginal('attachment_url') ?: 'NULL') . PHP_EOL;
    echo "Full Attachment URL (accessor): " . ($note->full_attachment_url ?: 'NULL') . PHP_EOL;
    echo "Attachment Name (accessor): " . ($note->attachment_name ?: 'NULL') . PHP_EOL;
}
