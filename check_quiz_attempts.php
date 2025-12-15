<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\QuizAttempt;
use Illuminate\Support\Facades\DB;

echo "=== Quiz Attempts Summary ===\n\n";

// Get all attempts grouped by status
$statusCounts = QuizAttempt::select('status', DB::raw('COUNT(*) as count'))
    ->groupBy('status')
    ->get();

echo "Attempts by Status:\n";
foreach ($statusCounts as $status) {
    echo "  {$status->status}: {$status->count}\n";
}

echo "\n=== User-specific Attempts ===\n\n";

// Get attempts by user and quiz
$userAttempts = QuizAttempt::select(
    'quiz_id', 
    'user_id', 
    'status', 
    DB::raw('COUNT(*) as count')
)
->groupBy('quiz_id', 'user_id', 'status')
->orderBy('quiz_id')
->orderBy('user_id')
->get();

foreach ($userAttempts as $attempt) {
    echo "Quiz {$attempt->quiz_id} - User {$attempt->user_id} - Status: {$attempt->status} - Count: {$attempt->count}\n";
}

echo "\n=== Auto-Abandoning Old In-Progress Attempts ===\n";

$abandoned = QuizAttempt::where('status', 'in_progress')
    ->update(['status' => 'abandoned']);

echo "Abandoned {$abandoned} in-progress attempts\n";

echo "\n=== Updated Status Counts ===\n";

$statusCounts = QuizAttempt::select('status', DB::raw('COUNT(*) as count'))
    ->groupBy('status')
    ->get();

foreach ($statusCounts as $status) {
    echo "  {$status->status}: {$status->count}\n";
}

echo "\n✓ Done! Users can now start quizzes again.\n";
