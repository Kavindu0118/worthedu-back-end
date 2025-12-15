<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$module = \App\Models\CourseModule::with(['quizzes', 'assignments', 'notes', 'lessons'])->first();

if ($module) {
    echo "Module ID: " . $module->id . PHP_EOL;
    echo "Module Title: " . $module->module_title . PHP_EOL;
    echo "Notes Count: " . $module->notes->count() . PHP_EOL;
    echo "Lessons Count: " . $module->lessons->count() . PHP_EOL;
    echo "Quizzes Count: " . $module->quizzes->count() . PHP_EOL;
    echo "Assignments Count: " . $module->assignments->count() . PHP_EOL;
    echo PHP_EOL;
    
    if ($module->quizzes->count() > 0) {
        echo "Quizzes:" . PHP_EOL;
        foreach ($module->quizzes as $quiz) {
            echo "  - ID: " . $quiz->id . ", Title: " . $quiz->quiz_title . PHP_EOL;
        }
    }
    
    if ($module->assignments->count() > 0) {
        echo PHP_EOL . "Assignments:" . PHP_EOL;
        foreach ($module->assignments as $assignment) {
            echo "  - ID: " . $assignment->id . ", Title: " . $assignment->assignment_title . PHP_EOL;
        }
    }
} else {
    echo "No module found" . PHP_EOL;
}
