<?php
// Direct test of grading endpoint
header('Content-Type: application/json');

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\AssignmentSubmission;

echo "Testing Grade Submission Endpoint\n";
echo "==================================\n\n";

$submissionId = 3;

try {
    $submission = AssignmentSubmission::with(['assignment', 'user'])->find($submissionId);
    
    if (!$submission) {
        echo "ERROR: Submission $submissionId not found\n";
        exit;
    }
    
    echo "Submission found:\n";
    echo "  - ID: {$submission->id}\n";
    echo "  - User: " . ($submission->user->name ?? 'N/A') . "\n";
    echo "  - Assignment: " . ($submission->assignment->assignment_title ?? 'N/A') . "\n";
    echo "  - Max Points: " . ($submission->assignment->max_points ?? 'N/A') . "\n";
    echo "  - Current Status: {$submission->status}\n";
    echo "  - Current Marks: " . ($submission->marks_obtained ?? 'null') . "\n\n";
    
    // Simulate a grade update
    $testMarks = 85;
    $testFeedback = "Good work on this assignment!";
    
    echo "Simulating grade update:\n";
    echo "  - Marks: $testMarks\n";
    echo "  - Feedback: $testFeedback\n\n";
    
    $submission->marks_obtained = $testMarks;
    $submission->feedback = $testFeedback;
    $submission->status = 'graded';
    $submission->graded_at = now();
    $submission->graded_by = 1;
    
    if ($submission->save()) {
        echo "✅ SUCCESS: Grade saved!\n";
        
        // Refresh from DB
        $submission->refresh();
        echo "\nUpdated values:\n";
        echo "  - Status: {$submission->status}\n";
        echo "  - Marks: {$submission->marks_obtained}\n";
        echo "  - Feedback: {$submission->feedback}\n";
        echo "  - Graded At: {$submission->graded_at}\n";
    } else {
        echo "❌ FAILED: Could not save\n";
    }
    
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "\nTrace:\n" . $e->getTraceAsString() . "\n";
}
