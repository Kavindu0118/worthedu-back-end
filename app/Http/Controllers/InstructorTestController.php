<?php

namespace App\Http\Controllers;

use App\Models\Test;
use App\Models\TestQuestion;
use App\Models\TestSubmission;
use App\Models\TestAnswer;
use App\Models\Course;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class InstructorTestController extends Controller
{
    /**
     * Get the instructor_id for the authenticated user
     */
    private function getInstructorId()
    {
        $user = Auth::user();
        
        Log::info('getInstructorId called', [
            'user_id' => $user ? $user->id : null,
            'user_email' => $user ? $user->email : null,
            'user_role' => $user ? $user->role : null,
        ]);
        
        if (!$user) {
            Log::warning('No authenticated user found');
            return null;
        }
        
        if ($user->role !== 'instructor') {
            Log::warning('User is not an instructor', [
                'user_id' => $user->id, 
                'role' => $user->role
            ]);
            return null;
        }
        
        // Reload instructor relationship to avoid caching issues
        $instructor = \App\Models\Instructor::where('user_id', $user->id)->first();
        
        Log::info('Instructor lookup', [
            'user_id' => $user->id,
            'instructor_found' => $instructor ? true : false,
            'instructor_id' => $instructor ? $instructor->instructor_id : null
        ]);
        
        return $instructor ? $instructor->instructor_id : null;
    }

    public function getTestsByCourse($courseId)
    {
        try {
            $instructorId = $this->getInstructorId();
            if (!$instructorId) {
                return response()->json(['success' => false, 'message' => 'Unauthorized - not an instructor'], 403);
            }

            $course = Course::findOrFail($courseId);
            
            if ($course->instructor_id !== $instructorId) {
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

    public function show($testId)
    {
        try {
            $instructorId = $this->getInstructorId();
            if (!$instructorId) {
                return response()->json(['success' => false, 'message' => 'Unauthorized - not an instructor'], 403);
            }

            $test = Test::with(['module', 'course', 'questions'])->findOrFail($testId);

            if ($test->course->instructor_id !== $instructorId) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            return response()->json(['success' => true, 'data' => $test]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Test not found'], 404);
        }
    }

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

        $instructorId = $this->getInstructorId();
        if (!$instructorId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized - not an instructor'], 403);
        }

        DB::beginTransaction();
        try {
            $module = \App\Models\CourseModule::findOrFail($validated['module_id']);
            
            if ($module->course->instructor_id !== $instructorId) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            
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
            Log::error('Create test error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $testId)
    {
        try {
            $instructorId = $this->getInstructorId();
            if (!$instructorId) {
                return response()->json(['success' => false, 'message' => 'Unauthorized - not an instructor'], 403);
            }

            $test = Test::findOrFail($testId);
            
            if ($test->course->instructor_id !== $instructorId) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            $test->update($request->only(['test_title', 'test_description', 'instructions', 'start_date', 'end_date', 'time_limit', 'max_attempts', 'total_marks', 'passing_marks']));
            
            if ($request->has('questions')) {
                $test->questions()->delete();
                foreach ($request->questions as $q) {
                    TestQuestion::create(array_merge($q, ['test_id' => $test->id]));
                }
            }
            
            $test->updateStatus();

            return response()->json(['success' => true, 'data' => $test->fresh()->load('questions')]);
        } catch (\Exception $e) {
            Log::error('Update test error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy($testId)
    {
        try {
            $instructorId = $this->getInstructorId();
            if (!$instructorId) {
                return response()->json(['success' => false, 'message' => 'Unauthorized - not an instructor'], 403);
            }

            $test = Test::findOrFail($testId);
            
            if ($test->course->instructor_id !== $instructorId) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            $test->delete();
            return response()->json(['success' => true, 'message' => 'Test deleted']);
        } catch (\Exception $e) {
            Log::error('Delete test error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getSubmissions($testId)
    {
        try {
            $instructorId = $this->getInstructorId();
            if (!$instructorId) {
                return response()->json(['success' => false, 'message' => 'Unauthorized - not an instructor'], 403);
            }

            $test = Test::with('questions')->findOrFail($testId);
            
            if ($test->course->instructor_id !== $instructorId) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            $submissions = TestSubmission::where('test_id', $testId)
                ->with(['student:id,name,email', 'answers.question'])
                ->orderBy('submitted_at', 'desc')
                ->get();

            // Calculate statistics
            $submittedCount = $submissions->whereIn('submission_status', ['submitted', 'late'])->count();
            $gradedCount = $submissions->whereIn('grading_status', ['graded', 'published'])->count();
            $averageScore = $submissions->whereNotNull('total_score')->avg('total_score');

            return response()->json([
                'success' => true,
                'data' => [
                    'test' => $test,
                    'submissions' => $submissions,
                    'statistics' => [
                        'total_submissions' => $submissions->count(),
                        'submitted_count' => $submittedCount,
                        'in_progress_count' => $submissions->where('submission_status', 'in_progress')->count(),
                        'graded_count' => $gradedCount,
                        'pending_grading' => $submittedCount - $gradedCount,
                        'average_score' => $averageScore ? round($averageScore, 2) : null,
                        'total_marks' => $test->total_marks,
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Get submissions error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getSubmissionDetails($submissionId)
    {
        try {
            $instructorId = $this->getInstructorId();
            if (!$instructorId) {
                return response()->json(['success' => false, 'message' => 'Unauthorized - not an instructor'], 403);
            }

            $submission = TestSubmission::with(['test', 'student', 'answers.question'])->findOrFail($submissionId);
            
            if ($submission->test->course->instructor_id !== $instructorId) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            return response()->json(['success' => true, 'data' => $submission]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function gradeSubmission(Request $request, $submissionId)
    {
        $instructorId = $this->getInstructorId();
        if (!$instructorId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized - not an instructor'], 403);
        }

        DB::beginTransaction();
        try {
            $submission = TestSubmission::findOrFail($submissionId);
            
            if ($submission->test->course->instructor_id !== $instructorId) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

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
            Log::error('Grade submission error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function publishResults(Request $request, $testId)
    {
        try {
            $instructorId = $this->getInstructorId();
            if (!$instructorId) {
                return response()->json(['success' => false, 'message' => 'Unauthorized - not an instructor'], 403);
            }

            $test = Test::findOrFail($testId);
            
            if ($test->course->instructor_id !== $instructorId) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            $test->update(['results_published' => $request->publish]);
            
            if ($request->publish) {
                TestSubmission::where('test_id', $testId)
                    ->where('grading_status', 'graded')
                    ->update(['grading_status' => 'published']);
            }

            return response()->json(['success' => true, 'data' => $test]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getStatistics($testId)
    {
        try {
            $instructorId = $this->getInstructorId();
            if (!$instructorId) {
                return response()->json(['success' => false, 'message' => 'Unauthorized - not an instructor'], 403);
            }

            $test = Test::findOrFail($testId);
            
            if ($test->course->instructor_id !== $instructorId) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            $enrolledCount = Enrollment::where('course_id', $test->course_id)->count();
            
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
        } catch (\Exception $e) {
            Log::error('Get statistics error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
