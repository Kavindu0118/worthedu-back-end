<?php
// Direct test of the module submissions endpoint  
header('Content-Type: application/json');

// Include Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ModuleAssignment;
use App\Models\AssignmentSubmission;

echo "Testing Module Submissions Endpoint Logic\n";
echo "==========================================\n\n";

$moduleId = 5;

try {
    // Test the query that's failing
    echo "Testing query for module ID: $moduleId\n";
    
    $assignments = ModuleAssignment::where('module_id', $moduleId)
        ->with(['submissions.user'])
        ->get();

    echo "Assignments found: " . $assignments->count() . "\n";
    
    foreach ($assignments as $assignment) {
        echo "\nAssignment: " . $assignment->assignment_title . "\n";
        echo "  Submissions: " . $assignment->submissions->count() . "\n";
        
        foreach ($assignment->submissions as $submission) {
            echo "    - Student: " . ($submission->user->name ?? 'N/A') . "\n";
        }
    }
    
    echo "\nSuccess! The relationship is working.\n";
    
} catch (\Exception $e) {
    echo "\nERROR: " . $e->getMessage() . "\n";
    echo "\nTrace:\n" . $e->getTraceAsString() . "\n";
}
