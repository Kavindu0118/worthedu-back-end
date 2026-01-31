<?php
/**
 * Check all quizzes for question count
 */

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ModuleQuiz;

echo "=== Checking All Quizzes Question Counts ===\n\n";

$quizzes = ModuleQuiz::all();

if ($quizzes->isEmpty()) {
    echo "No quizzes found\n";
    exit;
}

foreach ($quizzes as $quiz) {
    echo "Quiz ID: {$quiz->id} - {$quiz->quiz_title}\n";
    
    if ($quiz->quiz_data && is_array($quiz->quiz_data)) {
        $questionCount = count($quiz->quiz_data);
        echo "  Questions: $questionCount\n";
        
        foreach ($quiz->quiz_data as $index => $question) {
            echo "    Q" . ($index + 1) . ": " . ($question['question'] ?? 'No question text') . "\n";
        }
    } else {
        echo "  Questions: 0 (quiz_data is null or not an array)\n";
    }
    
    echo "\n";
}

echo "=== End ===\n";
