<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== CHECKING QUIZ COLUMNS ===" . PHP_EOL . PHP_EOL;
$quiz = \App\Models\ModuleQuiz::first();
if ($quiz) {
    echo "Quiz ID: " . $quiz->id . PHP_EOL;
    echo "Columns in database:" . PHP_EOL;
    foreach ($quiz->getAttributes() as $key => $value) {
        echo "  - $key: " . (is_null($value) ? 'NULL' : substr($value, 0, 50)) . PHP_EOL;
    }
} else {
    echo "No quiz found" . PHP_EOL;
}

echo PHP_EOL . "=== CHECKING ASSIGNMENT COLUMNS ===" . PHP_EOL . PHP_EOL;
$assignment = \App\Models\ModuleAssignment::first();
if ($assignment) {
    echo "Assignment ID: " . $assignment->id . PHP_EOL;
    echo "Columns in database:" . PHP_EOL;
    foreach ($assignment->getAttributes() as $key => $value) {
        echo "  - $key: " . (is_null($value) ? 'NULL' : substr($value, 0, 50)) . PHP_EOL;
    }
} else {
    echo "No assignment found" . PHP_EOL;
}
