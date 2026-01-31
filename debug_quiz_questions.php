<?php
/**
 * Debug script to check quiz question structure
 */

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ModuleQuiz;

echo "=== Checking Quiz Questions Structure ===\n\n";

// Get a quiz with multiple questions
$quiz = ModuleQuiz::whereNotNull('quiz_data')->first();

if (!$quiz) {
    echo "No quiz found with quiz_data\n";
    exit(1);
}

echo "Quiz ID: {$quiz->id}\n";
echo "Quiz Title: {$quiz->quiz_title}\n\n";

echo "Quiz Data Structure:\n";
print_r($quiz->quiz_data);

echo "\n\n=== Testing Question Formatting Logic ===\n\n";

$questions = [];
if ($quiz->quiz_data && is_array($quiz->quiz_data)) {
    echo "Total questions in quiz_data: " . count($quiz->quiz_data) . "\n\n";
    
    foreach ($quiz->quiz_data as $index => $questionData) {
        echo "Question " . ($index + 1) . ":\n";
        echo "  Question text: " . ($questionData['question'] ?? 'N/A') . "\n";
        echo "  Points: " . ($questionData['points'] ?? 10) . "\n";
        
        if (isset($questionData['options']) && is_array($questionData['options'])) {
            echo "  Options count: " . count($questionData['options']) . "\n";
            echo "  Options: \n";
            foreach ($questionData['options'] as $optIdx => $option) {
                echo "    [$optIdx] $option\n";
            }
        }
        
        // Test the current buggy logic
        $buggyOptions = array_map(function($option, $idx) {
            return [
                'id' => $idx + 1,
                'option_text' => $option
            ];
        }, $questionData['options'] ?? [], array_keys($questionData['options'] ?? []));
        
        echo "  Buggy formatted options count: " . count($buggyOptions) . "\n";
        print_r($buggyOptions);
        
        echo "\n";
    }
}

echo "\n\n=== End Debug ===\n";
