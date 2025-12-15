<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ModuleQuiz;
use Illuminate\Support\Facades\DB;

echo "=== Checking Quiz Data ===\n\n";

$quizzes = ModuleQuiz::all();

foreach ($quizzes as $quiz) {
    echo "Quiz ID: " . $quiz->id . "\n";
    echo "Title: " . $quiz->title . "\n";
    echo "Quiz Data Type: " . gettype($quiz->quiz_data) . "\n";
    
    if (is_array($quiz->quiz_data)) {
        echo "Questions Count: " . count($quiz->quiz_data) . "\n";
        if (count($quiz->quiz_data) > 0) {
            echo "First Question: " . json_encode($quiz->quiz_data[0], JSON_PRETTY_PRINT) . "\n";
        } else {
            echo "⚠️  NO QUESTIONS IN QUIZ_DATA!\n";
        }
    } else {
        echo "Quiz Data (raw): " . $quiz->quiz_data . "\n";
    }
    echo "\n" . str_repeat("-", 50) . "\n\n";
}

// Check if quiz_data column exists and what it contains
echo "=== Raw Database Data ===\n\n";
$rawQuizzes = DB::table('module_quizzes')->get();
foreach ($rawQuizzes as $quiz) {
    echo "Quiz ID: " . $quiz->id . "\n";
    echo "Title: " . $quiz->quiz_title . "\n";
    echo "quiz_data (raw): " . substr($quiz->quiz_data, 0, 200) . "...\n\n";
}
