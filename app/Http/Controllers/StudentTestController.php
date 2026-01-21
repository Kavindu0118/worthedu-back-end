<?php

namespace App\Http\Controllers;

use App\Models\Test;
use App\Models\TestSubmission;
use App\Models\TestAnswer;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class StudentTestController extends Controller
{
    public function show($testId)
    {
        try {
            $test = Test::with('questions')->findOrFail($testId);
            
            $enrolled = Enrollment::where('course_id', $test->course_id)
                ->where('learner_id', auth()->id())
                ->exists();

            if (!$enrolled) {
                return response()->json(['success' => false, 'message' => 'Not enrolled in this course'], 403);
            }

            $attemptCount = TestSubmission::where('test_id', $testId)
                ->where('student_id', auth()->id())
                ->whereIn('submission_status', ['submitted', 'late'])
                ->count();

            $canAttempt = $this->canStartTest($test, $attemptCount);

            $currentSubmission = TestSubmission::where('test_id', $testId)
                ->where('student_id', auth()->id())
                ->where('submission_status', 'in_progress')
                ->with('answers')
                ->first();

            return response()->json([
                'success' => true,
                'data' => [
                    'test' => $test,
                    'can_attempt' => $canAttempt['can_attempt'],
                    'reason' => $canAttempt['reason'] ?? null,
                    'remaining_attempts' => max(0, $test->max_attempts - $attemptCount),
                    'current_submission' => $currentSubmission,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Get test error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function startTest($testId)
    {
        try {
            $test = Test::findOrFail($testId);
            
            $enrolled = Enrollment::where('course_id', $test->course_id)
                ->where('learner_id', auth()->id())
                ->exists();

            if (!$enrolled) {
                return response()->json(['success' => false, 'message' => 'Not enrolled'], 403);
            }
            
            $attemptCount = TestSubmission::where('test_id', $testId)
                ->where('student_id', auth()->id())
                ->whereIn('submission_status', ['submitted', 'late'])
                ->count();

            $canAttempt = $this->canStartTest($test, $attemptCount);
            
            if (!$canAttempt['can_attempt']) {
                return response()->json(['success' => false, 'message' => $canAttempt['reason']], 403);
            }

            $submission = TestSubmission::create([
                'test_id' => $testId,
                'student_id' => auth()->id(),
                'attempt_number' => $attemptCount + 1,
                'started_at' => now(),
                'submission_status' => 'in_progress',
                'grading_status' => 'pending'
            ]);

            return response()->json(['success' => true, 'data' => $submission]);
        } catch (\Exception $e) {
            Log::error('Start test error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function submitTest(Request $request, $submissionId)
    {
        DB::beginTransaction();
        try {
            $submission = TestSubmission::with('test.questions')->findOrFail($submissionId);
            
            if ($submission->student_id !== auth()->id()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            $timeTaken = now()->diffInMinutes($submission->started_at);
            $isLate = now()->greaterThan($submission->test->end_date);

            foreach ($request->answers as $answerData) {
                TestAnswer::updateOrCreate(
                    [
                        'submission_id' => $submissionId,
                        'question_id' => $answerData['question_id']
                    ],
                    [
                        'question_type' => $answerData['question_type'],
                        'selected_option' => $answerData['selected_option'] ?? null,
                        'text_answer' => $answerData['text_answer'] ?? null,
                        'file_url' => $answerData['file_url'] ?? null,
                        'file_name' => $answerData['file_name'] ?? null,
                        'max_points' => $answerData['max_points']
                    ]
                );
            }

            $this->autoGradeSubmission($submission);

            $submission->update([
                'submitted_at' => now(),
                'submission_status' => $isLate ? 'late' : 'submitted',
                'time_taken' => $timeTaken
            ]);

            DB::commit();
            return response()->json(['success' => true, 'data' => $submission]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Submit test error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function uploadFile(Request $request, $submissionId)
    {
        try {
            $request->validate([
                'file' => 'required|file|max:10240',
                'question_id' => 'required|exists:test_questions,id'
            ]);

            $submission = TestSubmission::findOrFail($submissionId);
            
            if ($submission->student_id !== auth()->id()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            $path = $request->file('file')->store('test-answers', 'public');
            $url = Storage::url($path);

            return response()->json([
                'success' => true,
                'data' => [
                    'file_url' => $url,
                    'file_name' => $request->file('file')->getClientOriginalName()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Upload file error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function autosave(Request $request, $submissionId)
    {
        try {
            $submission = TestSubmission::findOrFail($submissionId);
            
            if ($submission->student_id !== auth()->id()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            foreach ($request->answers as $answerData) {
                TestAnswer::updateOrCreate(
                    ['submission_id' => $submissionId, 'question_id' => $answerData['question_id']],
                    $answerData
                );
            }

            return response()->json(['success' => true, 'data' => ['saved_at' => now()]]);
        } catch (\Exception $e) {
            Log::error('Autosave error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getResults($testId)
    {
        try {
            $test = Test::with('questions')->findOrFail($testId);
            
            // Check enrollment
            $enrolled = Enrollment::where('course_id', $test->course_id)
                ->where('learner_id', auth()->id())
                ->exists();

            if (!$enrolled) {
                return response()->json(['success' => false, 'message' => 'Not enrolled in this course'], 403);
            }

            // Get the latest submission
            $submission = TestSubmission::where('test_id', $testId)
                ->where('student_id', auth()->id())
                ->whereIn('submission_status', ['submitted', 'late'])
                ->with(['answers.question'])
                ->latest('submitted_at')
                ->first();

            if (!$submission) {
                return response()->json(['success' => false, 'message' => 'No submission found'], 404);
            }

            // Check if results are published (either individual submission or test-wide)
            $resultsAvailable = $submission->grading_status === 'published' || $test->results_published;

            if (!$resultsAvailable) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'test' => [
                            'id' => $test->id,
                            'test_title' => $test->test_title,
                            'total_marks' => $test->total_marks,
                            'passing_marks' => $test->passing_marks,
                        ],
                        'submission' => [
                            'id' => $submission->id,
                            'submitted_at' => $submission->submitted_at,
                            'submission_status' => $submission->submission_status,
                            'grading_status' => $submission->grading_status,
                        ],
                        'results_published' => false,
                        'message' => 'Results will be available once your instructor publishes them.'
                    ]
                ]);
            }

            // Calculate pass/fail
            $passed = $test->passing_marks ? $submission->total_score >= $test->passing_marks : null;
            $percentage = $test->total_marks > 0 ? round(($submission->total_score / $test->total_marks) * 100, 2) : 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'test' => [
                        'id' => $test->id,
                        'test_title' => $test->test_title,
                        'test_description' => $test->test_description,
                        'total_marks' => $test->total_marks,
                        'passing_marks' => $test->passing_marks,
                    ],
                    'submission' => $submission,
                    'results_published' => true,
                    'score' => $submission->total_score,
                    'percentage' => $percentage,
                    'passed' => $passed,
                    'instructor_feedback' => $submission->instructor_feedback,
                    'answers' => $submission->answers,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Get results error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    private function canStartTest($test, $attemptCount)
    {
        if (!$test->hasStarted()) {
            return ['can_attempt' => false, 'reason' => 'Test has not started yet'];
        }
        
        if ($test->hasEnded()) {
            return ['can_attempt' => false, 'reason' => 'Test deadline has passed'];
        }

        if ($attemptCount >= $test->max_attempts) {
            return ['can_attempt' => false, 'reason' => 'Maximum attempts reached'];
        }

        return ['can_attempt' => true];
    }

    private function autoGradeSubmission($submission)
    {
        $totalScore = 0;
        
        foreach ($submission->answers as $answer) {
            $question = $submission->test->questions->firstWhere('id', $answer->question_id);
            
            if ($question && $question->type === 'mcq') {
                $isCorrect = $answer->selected_option === $question->correct_answer;
                $answer->update([
                    'is_correct' => $isCorrect,
                    'points_awarded' => $isCorrect ? $question->points : 0
                ]);
                $totalScore += $answer->points_awarded ?? 0;
            }
        }

        $allMCQ = $submission->test->questions->every(fn($q) => $q->type === 'mcq');
        if ($allMCQ) {
            $submission->update([
                'total_score' => $totalScore,
                'grading_status' => 'graded'
            ]);
        }
    }
}
