<?php
// Test grading validation
header('Content-Type: application/json');

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\AssignmentSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

echo "Testing Grade Submission Validation\n";
echo "====================================\n\n";

$submissionId = 3;

try {
    $submission = AssignmentSubmission::with('assignment')->find($submissionId);
    
    if (!$submission) {
        echo "ERROR: Submission $submissionId not found\n";
        exit;
    }
    
    echo "Submission ID: {$submission->id}\n";
    echo "Assignment: {$submission->assignment->assignment_title}\n";
    echo "Max Points: {$submission->assignment->max_points}\n\n";
    
    // Test different payload scenarios
    $testPayloads = [
        [
            'name' => 'Standard payload (marks_obtained)',
            'data' => [
                'marks_obtained' => 85,
                'feedback' => 'Good work!'
            ]
        ],
        [
            'name' => 'Frontend might send marks/score',
            'data' => [
                'marks' => 85,
                'feedback' => 'Good work!'
            ]
        ],
        [
            'name' => 'Frontend might send score',
            'data' => [
                'score' => 85,
                'feedback' => 'Good work!'
            ]
        ],
        [
            'name' => 'With grade field',
            'data' => [
                'marks_obtained' => 85,
                'feedback' => 'Good work!',
                'grade' => 'B'
            ]
        ]
    ];
    
    foreach ($testPayloads as $test) {
        echo "Testing: {$test['name']}\n";
        echo "Payload: " . json_encode($test['data']) . "\n";
        
        $validator = Validator::make($test['data'], [
            'marks_obtained' => 'required|numeric|min:0|max:' . $submission->assignment->max_points,
            'feedback' => 'nullable|string|max:2000',
            'grade' => 'nullable|string|in:A,A-,B+,B,B-,C+,C,C-,D+,D,F',
        ]);
        
        if ($validator->fails()) {
            echo "❌ VALIDATION FAILED:\n";
            foreach ($validator->errors()->all() as $error) {
                echo "   - $error\n";
            }
        } else {
            echo "✅ VALIDATION PASSED\n";
        }
        echo "\n";
    }
    
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
