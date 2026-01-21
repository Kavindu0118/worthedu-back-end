# Test Feature Implementation - Controllers Code

## Copy the following code to the respective controller files

### File: app/Http/Controllers/InstructorTestController.php

```php
<?php

namespace App\Http\Controllers;

use App\Models\Test;
use App\Models\TestQuestion;
use App\Models\TestSubmission;
use App\Models\TestAnswer;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InstructorTestController extends Controller
{
    /**
     * Get all tests for a course
     */
    public function getTestsByCourse($courseId)
    {
        try {
            $course = Course::findOrFail($courseId);
            
            if ($course->instructor_id !== auth()->id()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            $tests = Test::where('course_id', $courseId)
                ->with(['module:id,module_title', 'course:id,title'])
                ->withCount(['submissions', 'submissions as graded_count' => fn($q) => $q->where('grading_status', 'graded')])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json(['success' => true, 'data' => $tests]);
        } catch (\Exception $e) {
            Log::error('Get tests error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error fetching tests'], 500);
        }
    }

    /**
     * Get test details
     */
    public function show($testId)
    {
        try {
            $test = Test::with(['module', 'course', 'questions'])->findOrFail($testId);

            if ($test->course->instructor_id !== auth()->id()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            return response()->json(['success' => true, 'data' => $test]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Test not found'], 404);
        }
    }

    /**
     * Create test
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'module_id' => 'required|exists:course_modules,id',
            'test_title' => 'required|string|max:255',
            'test_description' => 'required|string',
            'instructions' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'time_limit' => 'nullable|integer',
            'max_attempts' => 'required|integer|min:1',
            'total_marks' => 'required|integer|min:1',
            'passing_marks' => 'nullable|integer',
            'questions' => 'required|array|min:1',
        ]);

        DB::beginTransaction();
        try {
            $module = \App\Models\CourseModule::findOrFail($validated['module_id']);
            
            $test = Test::create(array_merge($validated, [
                'course_id' => $module->course_id,
                'status' => 'draft',
                'created_by' => auth()->id()
            ]));

            foreach ($request->questions as $q) {
                TestQuestion::create(array_merge($q, ['test_id' => $test->id]));
            }

            $test->updateStatus();
            DB::commit();

            return response()->json(['success' => true, 'data' => $test->load('questions')], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Update test
     */
    public function update(Request $request, $testId)
    {
        $test = Test::findOrFail($testId);
        
        if ($test->course->instructor_id !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $test->update($request->only(['test_title', 'test_description', 'instructions', 'start_date', 'end_date']));
        $test->updateStatus();

        return response()->json(['success' => true, 'data' => $test]);
    }

    /**
     * Delete test
     */
    public function destroy($testId)
    {
        $test = Test::findOrFail($testId);
        
        if ($test->course->instructor_id !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $test->delete();
        return response()->json(['success' => true, 'message' => 'Test deleted']);
    }

    /**
     * Get test submissions
     */
    public function getSubmissions($testId)
    {
        $test = Test::findOrFail($testId);
        
        if ($test->course->instructor_id !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $submissions = TestSubmission::where('test_id', $testId)
            ->with(['student:id,name,email', 'answers'])
            ->get();

        return response()->json(['success' => true, 'data' => $submissions]);
    }

    /**
     * Get submission details
     */
    public function getSubmissionDetails($submissionId)
    {
        $submission = TestSubmission::with(['test', 'student', 'answers.question'])->findOrFail($submissionId);
        
        if ($submission->test->course->instructor_id !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        return response()->json(['success' => true, 'data' => $submission]);
    }

    /**
     * Grade submission
     */
    public function gradeSubmission(Request $request, $submissionId)
    {
        $submission = TestSubmission::findOrFail($submissionId);
        
        if ($submission->test->course->instructor_id !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        DB::beginTransaction();
        try {
            $totalScore = 0;

            foreach ($request->answers as $answerData) {
                $answer = TestAnswer::where('submission_id', $submissionId)
                    ->where('question_id', $answerData['question_id'])
                    ->first();
                
                if ($answer) {
                    $answer->update([
                        'points_awarded' => $answerData['points_awarded'],
                        'feedback' => $answerData['feedback'] ?? null
                    ]);
                    $totalScore += $answerData['points_awarded'];
                }
            }

            $submission->update([
                'total_score' => $totalScore,
                'grading_status' => $request->publish_results ? 'published' : 'graded',
                'graded_at' => now(),
                'graded_by' => auth()->id(),
                'instructor_feedback' => $request->instructor_feedback
            ]);

            DB::commit();
            return response()->json(['success' => true, 'data' => $submission->fresh()]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Publish/unpublish results
     */
    public function publishResults(Request $request, $testId)
    {
        $test = Test::findOrFail($testId);
        
        if ($test->course->instructor_id !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $test->update(['results_published' => $request->publish]);
        
        if ($request->publish) {
            TestSubmission::where('test_id', $testId)
                ->where('grading_status', 'graded')
                ->update(['grading_status' => 'published']);
        }

        return response()->json(['success' => true, 'data' => $test]);
    }

    /**
     * Get test statistics
     */
    public function getStatistics($testId)
    {
        $test = Test::findOrFail($testId);
        
        if ($test->course->instructor_id !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $enrolledCount = \App\Models\Enrollment::where('course_id', $test->course_id)->count();
        
        $submissions = TestSubmission::where('test_id', $testId)
            ->whereIn('submission_status', ['submitted', 'late'])
            ->get();

        $stats = [
            'test_id' => $testId,
            'total_students_enrolled' => $enrolledCount,
            'total_submissions' => $submissions->count(),
            'submitted_count' => $submissions->where('submission_status', 'submitted')->count(),
            'pending_count' => $enrolledCount - $submissions->count(),
            'late_submissions' => $submissions->where('submission_status', 'late')->count(),
            'average_score' => round($submissions->avg('total_score'), 2),
            'highest_score' => $submissions->max('total_score'),
            'lowest_score' => $submissions->min('total_score'),
            'pass_rate' => $test->passing_marks ? 
                round(($submissions->where('total_score', '>=', $test->passing_marks)->count() / max($submissions->count(), 1)) * 100, 2) : null
        ];

        return response()->json(['success' => true, 'data' => $stats]);
    }
}
```

### File: app/Http/Controllers/StudentTestController.php

```php
<?php

namespace App\Http\Controllers;

use App\Models\Test;
use App\Models\TestSubmission;
use App\Models\TestAnswer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class StudentTestController extends Controller
{
    /**
     * Get test view for student
     */
    public function show($testId)
    {
        $test = Test::with('questions')->findOrFail($testId);
        
        // Check enrollment
        $enrolled = \App\Models\Enrollment::where('course_id', $test->course_id)
            ->where('student_id', auth()->id())
            ->exists();

        if (!$enrolled) {
            return response()->json(['success' => false, 'message' => 'Not enrolled'], 403);
        }

        $attemptCount = TestSubmission::where('test_id', $testId)
            ->where('student_id', auth()->id())
            ->whereIn('submission_status', ['submitted', 'late'])
            ->count();

        $canAttempt = $this->canStartTest($test, $attemptCount);

        $currentSubmission = TestSubmission::where('test_id', $testId)
            ->where('student_id', auth()->id())
            ->where('submission_status', 'in_progress')
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
    }

    /**
     * Start test
     */
    public function startTest($testId)
    {
        $test = Test::findOrFail($testId);
        
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
    }

    /**
     * Submit test
     */
    public function submitTest(Request $request, $submissionId)
    {
        $submission = TestSubmission::findOrFail($submissionId);
        
        if ($submission->student_id !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        DB::beginTransaction();
        try {
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
                        'max_points' => $answerData['max_points']
                    ]
                );
            }

            // Auto-grade MCQs
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
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Upload file for test answer
     */
    public function uploadFile(Request $request, $submissionId)
    {
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
    }

    /**
     * Auto-save progress
     */
    public function autosave(Request $request, $submissionId)
    {
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
    }

    /**
     * Get results
     */
    public function getResults($testId)
    {
        $submission = TestSubmission::where('test_id', $testId)
            ->where('student_id', auth()->id())
            ->where('grading_status', 'published')
            ->with('answers')
            ->latest()
            ->first();

        if (!$submission) {
            return response()->json(['success' => false, 'message' => 'Results not published yet'], 403);
        }

        return response()->json(['success' => true, 'data' => $submission]);
    }

    // Helper methods
    private function canStartTest($test, $attemptCount)
    {
        if (!$test->isActive()) {
            if (!$test->hasStarted()) {
                return ['can_attempt' => false, 'reason' => 'Test has not started yet'];
            }
            if ($test->hasEnded()) {
                return ['can_attempt' => false, 'reason' => 'Test deadline has passed'];
            }
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
            $question = $answer->question;
            
            if ($question->type === 'mcq') {
                $isCorrect = $answer->selected_option === $question->correct_answer;
                $answer->update([
                    'is_correct' => $isCorrect,
                    'points_awarded' => $isCorrect ? $question->points : 0
                ]);
                $totalScore += $answer->points_awarded;
            }
        }

        // Only update total_score if all questions are MCQ
        $allMCQ = $submission->test->questions->every(fn($q) => $q->type === 'mcq');
        if ($allMCQ) {
            $submission->update([
                'total_score' => $totalScore,
                'grading_status' => 'graded'
            ]);
        }
    }
}
```

## Routes to add in routes/api.php

```php
// Instructor test routes
Route::prefix('instructor')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/courses/{courseId}/tests', [InstructorTestController::class, 'getTestsByCourse']);
    Route::get('/tests/{testId}', [InstructorTestController::class, 'show']);
    Route::post('/tests', [InstructorTestController::class, 'store']);
    Route::put('/tests/{testId}', [InstructorTestController::class, 'update']);
    Route::delete('/tests/{testId}', [InstructorTestController::class, 'destroy']);
    Route::get('/tests/{testId}/submissions', [InstructorTestController::class, 'getSubmissions']);
    Route::get('/submissions/{submissionId}', [InstructorTestController::class, 'getSubmissionDetails']);
    Route::post('/submissions/{submissionId}/grade', [InstructorTestController::class, 'gradeSubmission']);
    Route::post('/tests/{testId}/publish-results', [InstructorTestController::class, 'publishResults']);
    Route::get('/tests/{testId}/statistics', [InstructorTestController::class, 'getStatistics']);
});

// Student test routes  
Route::prefix('student')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/tests/{testId}', [StudentTestController::class, 'show']);
    Route::post('/tests/{testId}/start', [StudentTestController::class, 'startTest']);
    Route::post('/submissions/{submissionId}/submit', [StudentTestController::class, 'submitTest']);
    Route::post('/submissions/{submissionId}/upload', [StudentTestController::class, 'uploadFile']);
    Route::post('/submissions/{submissionId}/autosave', [StudentTestController::class, 'autosave']);
    Route::get('/tests/{testId}/results', [StudentTestController::class, 'getResults']);
});
```

## Next Steps

1. Copy the InstructorTestController code to: `app/Http/Controllers/InstructorTestController.php`
2. Copy the StudentTestController code to: `app/Http/Controllers/StudentTestController.php`
3. Add the routes to: `routes/api.php`
4. Test the endpoints

All database tables have been created and models are set up!
