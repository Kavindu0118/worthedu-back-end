<?php
/**
 * Test saving multiple questions directly through model
 */

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ModuleQuiz;
use App\Models\CourseModule;

echo "=== Testing Direct Quiz Creation with Multiple Questions ===\n\n";

// Get a module
$module = CourseModule::first();
if (!$module) {
    echo "No module found\n";
    exit(1);
}

echo "Module: {$module->module_title} (ID: {$module->id})\n\n";

// Create quiz with multiple questions
$quizData = [
    [
        'question' => 'What is 2 + 2?',
        'options' => ['2', '3', '4', '5'],
        'correct_answer' => '4',
        'points' => 10
    ],
    [
        'question' => 'What is 3 * 3?',
        'options' => ['6', '8', '9', '12'],
        'correct_answer' => '9',
        'points' => 10
    ],
    [
        'question' => 'What is 10 / 2?',
        'options' => ['3', '4', '5', '6'],
        'correct_answer' => '5',
        'points' => 10
    ]
];

echo "Creating quiz with " . count($quizData) . " questions...\n";

$quiz = ModuleQuiz::create([
    'module_id' => $module->id,
    'quiz_title' => 'Multi-Question Test Quiz',
    'quiz_description' => 'Test quiz with 3 questions',
    'quiz_data' => $quizData,
    'total_points' => 30,
    'time_limit' => 15
]);

echo "✅ Quiz created! ID: {$quiz->id}\n\n";

// Verify the quiz was saved correctly
$savedQuiz = ModuleQuiz::find($quiz->id);
echo "Verifying saved quiz...\n";
echo "Questions count: " . count($savedQuiz->quiz_data) . "\n\n";

foreach ($savedQuiz->quiz_data as $index => $question) {
    echo "Question " . ($index + 1) . ":\n";
    echo "  Text: " . $question['question'] . "\n";
    echo "  Options: " . implode(', ', $question['options']) . "\n";
    echo "  Points: " . $question['points'] . "\n";
    echo "\n";
}

echo "=== Testing Learner Quiz Start with Multiple Questions ===\n\n";

// Now test the start method logic
$questions = [];
if ($savedQuiz->quiz_data && is_array($savedQuiz->quiz_data)) {
    echo "Processing " . count($savedQuiz->quiz_data) . " questions from quiz_data...\n\n";
    
    foreach ($savedQuiz->quiz_data as $index => $questionData) {
        echo "Processing question " . ($index + 1) . "...\n";
        
        $questions[] = [
            'id' => $index + 1,
            'question_text' => $questionData['question'] ?? '',
            'question_type' => 'multiple_choice',
            'points' => $questionData['points'] ?? 10,
            'options' => array_map(function($option, $idx) {
                return [
                    'id' => $idx + 1,
                    'option_text' => $option
                ];
            }, $questionData['options'] ?? [], array_keys($questionData['options'] ?? []))
        ];
    }
}

echo "\nFormatted questions count: " . count($questions) . "\n";
echo "\nFormatted questions:\n";
print_r($questions);

echo "\n=== End ===\n";
