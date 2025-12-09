<?php

namespace App\Http\Controllers;

use App\Models\ModuleQuiz;
use App\Models\QuizAttempt;
use App\Models\QuizAnswer;
use App\Models\Enrollment;
use App\Helpers\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class LearnerQuizController extends Controller
{
    /**
     * Get list of quizzes with optional filters
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $courseId = $request->query('course_id');
        $status = $request->query('status'); // available, completed
        
        // Get enrolled course IDs
        $enrolledCourseIds = Enrollment::where('learner_id', $user->id)
            ->pluck('course_id')
            ->toArray();
        
        $query = ModuleQuiz::with(['module.course:id,title'])
            ->whereHas('module', function($q) use ($enrolledCourseIds) {
                $q->whereIn('course_id', $enrolledCourseIds);
            });
        
        if ($courseId) {
            $query->whereHas('module', function($q) use ($courseId) {
                $q->where('course_id', $courseId);
            });
        }
        
        $quizzes = $query->get()
            ->map(function ($quiz) use ($user, $status) {
                $attempts = QuizAttempt::where('quiz_id', $quiz->id)
                    ->where('user_id', $user->id)
                    ->get();
                
                $completedAttempts = $attempts->where('status', 'completed')->count();
                $bestScore = $attempts->where('status', 'completed')->max('score');
                $lastAttempt = $attempts->sortByDesc('created_at')->first();
                
                $quizStatus = $completedAttempts > 0 ? 'completed' : 'available';
                
                // Filter by status if provided
                if ($status && $quizStatus !== $status) {
                    return null;
                }
                
                return [
                    'id' => $quiz->id,
                    'title' => $quiz->title,
                    'description' => $quiz->description,
                    'course_id' => $quiz->module->course_id,
                    'course_title' => $quiz->module->course->title,
                    'total_marks' => $quiz->total_marks,
                    'passing_percentage' => $quiz->passing_percentage ?? 60,
                    'time_limit_minutes' => $quiz->time_limit_minutes,
                    'max_attempts' => $quiz->max_attempts,
                    'attempts_count' => $attempts->count(),
                    'completed_attempts' => $completedAttempts,
                    'best_score' => $bestScore,
                    'last_attempt' => $lastAttempt ? [
                        'id' => $lastAttempt->id,
                        'score' => $lastAttempt->score,
                        'passed' => $lastAttempt->passed,
                        'completed_at' => $lastAttempt->completed_at ? $lastAttempt->completed_at->toISOString() : null,
                    ] : null,
                ];
            })
            ->filter()
            ->values();
        
        return response()->json([
            'success' => true,
            'data' => $quizzes,
        ]);
    }

    /**
     * Get quiz details with previous attempts
     */
    public function show($id)
    {
        $user = Auth::user();
        
        $quiz = ModuleQuiz::with(['module.course:id,title'])->findOrFail($id);
        
        // Check enrollment
        $enrollment = Enrollment::where('learner_id', $user->id)
            ->where('course_id', $quiz->module->course_id)
            ->first();
        
        if (!$enrollment) {
            return response()->json([
                'success' => false,
                'message' => 'You must be enrolled in this course',
            ], 403);
        }
        
        // Get previous attempts
        $attempts = QuizAttempt::where('quiz_id', $id)
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($attempt) {
                return [
                    'id' => $attempt->id,
                    'attempt_number' => $attempt->attempt_number,
                    'started_at' => $attempt->started_at->toISOString(),
                    'completed_at' => $attempt->completed_at ? $attempt->completed_at->toISOString() : null,
                    'time_taken_minutes' => $attempt->time_taken_minutes,
                    'score' => $attempt->score,
                    'points_earned' => $attempt->points_earned,
                    'total_points' => $attempt->total_points,
                    'status' => $attempt->status,
                    'passed' => $attempt->passed,
                ];
            });
        
        // Check if can attempt
        $canAttempt = true;
        $remainingAttempts = null;
        
        if ($quiz->max_attempts) {
            $attemptCount = $attempts->count();
            $remainingAttempts = $quiz->max_attempts - $attemptCount;
            $canAttempt = $remainingAttempts > 0;
        }
        
        // Check availability window
        $isAvailable = true;
        if ($quiz->available_from && now()->lt($quiz->available_from)) {
            $isAvailable = false;
        }
        if ($quiz->available_until && now()->gt($quiz->available_until)) {
            $isAvailable = false;
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'description' => $quiz->description,
                'course_id' => $quiz->module->course_id,
                'course_title' => $quiz->module->course->title,
                'total_marks' => $quiz->total_marks,
                'passing_percentage' => $quiz->passing_percentage ?? 60,
                'time_limit_minutes' => $quiz->time_limit_minutes,
                'max_attempts' => $quiz->max_attempts,
                'show_correct_answers' => $quiz->show_correct_answers ?? false,
                'randomize_questions' => $quiz->randomize_questions ?? false,
                'available_from' => $quiz->available_from ? $quiz->available_from->toISOString() : null,
                'available_until' => $quiz->available_until ? $quiz->available_until->toISOString() : null,
                'can_attempt' => $canAttempt && $isAvailable,
                'remaining_attempts' => $remainingAttempts,
                'is_available' => $isAvailable,
                'attempts' => $attempts,
            ],
        ]);
    }

    /**
     * Start a new quiz attempt
     */
    public function start($id)
    {
        $user = Auth::user();
        
        $quiz = ModuleQuiz::with('module')->findOrFail($id);
        
        // Check enrollment
        $enrollment = Enrollment::where('learner_id', $user->id)
            ->where('course_id', $quiz->module->course_id)
            ->first();
        
        if (!$enrollment) {
            return response()->json([
                'success' => false,
                'message' => 'You must be enrolled in this course',
            ], 403);
        }
        
        // Check max attempts
        if ($quiz->max_attempts) {
            $attemptCount = QuizAttempt::where('quiz_id', $id)
                ->where('user_id', $user->id)
                ->count();
            
            if ($attemptCount >= $quiz->max_attempts) {
                return response()->json([
                    'success' => false,
                    'message' => 'Maximum attempts reached for this quiz',
                ], 400);
            }
        }
        
        // Check availability
        if ($quiz->available_from && now()->lt($quiz->available_from)) {
            return response()->json([
                'success' => false,
                'message' => 'This quiz is not yet available',
            ], 400);
        }
        
        if ($quiz->available_until && now()->gt($quiz->available_until)) {
            return response()->json([
                'success' => false,
                'message' => 'This quiz is no longer available',
            ], 400);
        }
        
        // Create attempt
        $attemptNumber = QuizAttempt::where('quiz_id', $id)
            ->where('user_id', $user->id)
            ->count() + 1;
        
        $attempt = QuizAttempt::create([
            'quiz_id' => $id,
            'user_id' => $user->id,
            'attempt_number' => $attemptNumber,
            'started_at' => now(),
            'status' => 'in_progress',
            'total_points' => $quiz->total_marks,
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Quiz attempt started',
            'data' => [
                'attempt_id' => $attempt->id,
                'quiz_id' => $quiz->id,
                'attempt_number' => $attemptNumber,
                'started_at' => $attempt->started_at->toISOString(),
                'time_limit_minutes' => $quiz->time_limit_minutes,
            ],
        ]);
    }

    /**
     * Submit answer for a question
     */
    public function submitAnswer($attemptId, Request $request)
    {
        $user = Auth::user();
        
        $validator = Validator::make($request->all(), [
            'question_id' => 'required|exists:quiz_question_options,id',
            'selected_option_ids' => 'required|array',
            'selected_option_ids.*' => 'exists:quiz_question_options,id',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }
        
        $attempt = QuizAttempt::where('id', $attemptId)
            ->where('user_id', $user->id)
            ->firstOrFail();
        
        if ($attempt->status !== 'in_progress') {
            return response()->json([
                'success' => false,
                'message' => 'This quiz attempt is not in progress',
            ], 400);
        }
        
        // Check time limit
        $quiz = ModuleQuiz::findOrFail($attempt->quiz_id);
        if ($quiz->time_limit_minutes) {
            $elapsedMinutes = $attempt->started_at->diffInMinutes(now());
            if ($elapsedMinutes > $quiz->time_limit_minutes) {
                $attempt->update(['status' => 'abandoned']);
                return response()->json([
                    'success' => false,
                    'message' => 'Time limit exceeded for this quiz',
                ], 400);
            }
        }
        
        // Save answer
        $answer = QuizAnswer::updateOrCreate(
            [
                'attempt_id' => $attemptId,
                'question_id' => $request->question_id,
            ],
            [
                'selected_option_ids' => json_encode($request->selected_option_ids),
            ]
        );
        
        return response()->json([
            'success' => true,
            'message' => 'Answer saved',
            'data' => [
                'question_id' => $request->question_id,
                'answer_id' => $answer->id,
            ],
        ]);
    }

    /**
     * Submit quiz and calculate score
     */
    public function submitQuiz($attemptId)
    {
        $user = Auth::user();
        
        $attempt = QuizAttempt::where('id', $attemptId)
            ->where('user_id', $user->id)
            ->firstOrFail();
        
        if ($attempt->status !== 'in_progress') {
            return response()->json([
                'success' => false,
                'message' => 'This quiz attempt is not in progress',
            ], 400);
        }
        
        $quiz = ModuleQuiz::findOrFail($attempt->quiz_id);
        
        // Calculate time taken
        $timeTaken = $attempt->started_at->diffInMinutes(now());
        
        // TODO: Calculate score based on correct answers
        // For now, set placeholder values
        $score = 0;
        $pointsEarned = 0;
        
        // Determine if passed
        $passingPercentage = $quiz->passing_percentage ?? 60;
        $passed = $score >= $passingPercentage;
        
        // Update attempt
        $attempt->update([
            'completed_at' => now(),
            'time_taken_minutes' => $timeTaken,
            'score' => $score,
            'points_earned' => $pointsEarned,
            'status' => 'completed',
            'passed' => $passed,
        ]);
        
        // Log quiz completion activity
        ActivityLogger::logActivity($user->id, 'quiz', $timeTaken);
        
        return response()->json([
            'success' => true,
            'message' => 'Quiz submitted successfully',
            'data' => [
                'attempt_id' => $attempt->id,
                'score' => $score,
                'points_earned' => $pointsEarned,
                'total_points' => $attempt->total_points,
                'time_taken_minutes' => $timeTaken,
                'passed' => $passed,
                'passing_percentage' => $passingPercentage,
            ],
        ]);
    }

    /**
     * Get quiz attempt results
     */
    public function getAttempt($attemptId)
    {
        $user = Auth::user();
        
        $attempt = QuizAttempt::where('id', $attemptId)
            ->where('user_id', $user->id)
            ->with('quiz')
            ->firstOrFail();
        
        $quiz = $attempt->quiz;
        
        // Get answers
        $answers = QuizAnswer::where('attempt_id', $attemptId)->get();
        
        return response()->json([
            'success' => true,
            'data' => [
                'attempt_id' => $attempt->id,
                'quiz_id' => $quiz->id,
                'quiz_title' => $quiz->title,
                'attempt_number' => $attempt->attempt_number,
                'started_at' => $attempt->started_at->toISOString(),
                'completed_at' => $attempt->completed_at ? $attempt->completed_at->toISOString() : null,
                'time_taken_minutes' => $attempt->time_taken_minutes,
                'score' => $attempt->score,
                'points_earned' => $attempt->points_earned,
                'total_points' => $attempt->total_points,
                'status' => $attempt->status,
                'passed' => $attempt->passed,
                'passing_percentage' => $quiz->passing_percentage ?? 60,
                'show_correct_answers' => $quiz->show_correct_answers ?? false,
                'answers_count' => $answers->count(),
            ],
        ]);
    }
}
