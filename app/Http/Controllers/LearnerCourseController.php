<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\LessonProgress;
use App\Models\CourseModule;
use App\Models\Lesson;
use App\Models\Test;
use App\Models\TestSubmission;
use App\Models\QuizAttempt;
use App\Models\AssignmentSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LearnerCourseController extends Controller
{
    /**
     * Get list of enrolled courses with optional status filter
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $status = $request->query('status'); // active, completed, paused, dropped
        
        $query = Enrollment::where('learner_id', $user->id)
            ->with(['course' => function($q) {
                $q->select('id', 'title', 'description', 'thumbnail', 'category', 'level', 'duration', 'instructor_id')
                  ->with(['instructor.user:id,name,email']);
            }]);
        
        if ($status) {
            $query->where('status', $status);
        }
        
        $enrollments = $query->orderBy('last_accessed_at', 'desc')
                            ->orderBy('created_at', 'desc')
                            ->get();
        
        $courses = $enrollments->map(function ($enrollment) {
            $course = $enrollment->course;
            return [
                'id' => $course->id,
                'title' => $course->title,
                'description' => $course->description,
                'thumbnail' => $course->thumbnail ? url('storage/' . $course->thumbnail) : null,
                'category' => $course->category,
                'level' => $course->level,
                'duration' => $course->duration,
                'instructor' => $course->instructor && $course->instructor->user ? [
                    'id' => $course->instructor->user->id,
                    'name' => $course->instructor->user->name,
                ] : null,
                'enrollment' => [
                    'id' => $enrollment->id,
                    'status' => $enrollment->status,
                    'progress' => (float) $enrollment->progress,
                    'enrolled_at' => $enrollment->created_at->toISOString(),
                    'last_accessed' => $enrollment->last_accessed_at ? $enrollment->last_accessed_at->toISOString() : null,
                    'completed_at' => $enrollment->completed_at ? $enrollment->completed_at->toISOString() : null,
                ],
            ];
        });
        
        return response()->json([
            'success' => true,
            'data' => $courses,
        ]);
    }

    /**
     * Get list of available courses (not enrolled)
     */
    public function available(Request $request)
    {
        $user = Auth::user();
        $category = $request->query('category');
        $search = $request->query('search');
        $level = $request->query('level');
        
        // Get IDs of courses user is already enrolled in
        $enrolledCourseIds = Enrollment::where('learner_id', $user->id)
            ->pluck('course_id')
            ->toArray();
        
        $query = Course::whereNotIn('id', $enrolledCourseIds)
            ->where('status', 'published')
            ->with(['instructor.user:id,name,email']);
        
        if ($category) {
            $query->where('category', $category);
        }
        
        if ($level) {
            $query->where('level', $level);
        }
        
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }
        
        $courses = $query->orderBy('created_at', 'desc')
                        ->get()
                        ->map(function ($course) {
                            return [
                                'id' => $course->id,
                                'title' => $course->title,
                                'description' => $course->description,
                                'thumbnail' => $course->thumbnail ? url('storage/' . $course->thumbnail) : null,
                                'category' => $course->category,
                                'level' => $course->level,
                                'duration' => $course->duration,
                                'student_count' => $course->student_count ?? 0,
                                'instructor' => $course->instructor && $course->instructor->user ? [
                                    'id' => $course->instructor->user->id,
                                    'name' => $course->instructor->user->name,
                                ] : null,
                            ];
                        });
        
        return response()->json([
            'success' => true,
            'data' => $courses,
        ]);
    }

    /**
     * Get detailed course information with modules and progress
     */
    public function show($id)
    {
        $user = Auth::user();
        
        // Check if user is enrolled in this course
        $enrollment = Enrollment::where('learner_id', $user->id)
            ->where('course_id', $id)
            ->first();
        
        if (!$enrollment) {
            return response()->json([
                'success' => false,
                'message' => 'You are not enrolled in this course',
            ], 403);
        }
        
        // Load course with all nested relationships including tests
        $course = Course::with([
            'instructor.user:id,name,email',
            'courseModules' => function($q) use ($user) {
                $q->orderBy('order_index')
                  ->with(['notes', 'quizzes', 'assignments', 'tests']);
            }
        ])->findOrFail($id);
        
        // Update last accessed timestamp
        $enrollment->update(['last_accessed_at' => now()]);
        
        // Calculate detailed progress
        $progressDetails = $this->calculateDetailedProgress($course, $user->id);
        
        // Calculate total and completed lessons (notes are lessons)
        $totalLessons = 0;
        $completedLessons = 0;
        
        foreach ($course->courseModules as $module) {
            $totalLessons += $module->notes->count();
            // TODO: Track note completion status in future
        }
        
        // Map modules with notes (lessons), quizzes, tests, and assignments
        $modules = $course->courseModules->map(function ($module) use ($user) {
            return [
                'id' => $module->id,
                'title' => $module->module_title,
                'description' => $module->module_description,
                'order' => $module->order_index,
                'duration' => $module->duration,
                'notes' => $module->notes->map(function ($note) {
                    return [
                        'id' => $note->id,
                        'title' => $note->note_title,
                        'body' => $note->note_body,
                        'attachment_url' => $note->full_attachment_url,
                        'attachment_name' => $note->attachment_name,
                        'created_at' => $note->created_at->toISOString(),
                    ];
                }),
                'quizzes' => $module->quizzes->map(function ($quiz) {
                    return [
                        'id' => $quiz->id,
                        'title' => $quiz->quiz_title,
                        'description' => $quiz->quiz_description,
                        'duration' => $quiz->time_limit,
                        'total_marks' => $quiz->total_points,
                        'passing_percentage' => $quiz->passing_percentage,
                        'created_at' => $quiz->created_at->toISOString(),
                    ];
                }),
                'tests' => $module->tests
                    ->filter(function($test) {
                        // Only show tests that are published or have visibility_status = 'visible'
                        return $test->visibility_status === 'visible' || 
                               $test->status === 'active' || 
                               $test->status === 'scheduled';
                    })
                    ->map(function ($test) use ($user) {
                        return $this->formatTestForLearner($test, $user->id);
                    })->values(),
                'assignments' => $module->assignments->map(function ($assignment) {
                    return [
                        'id' => $assignment->id,
                        'title' => $assignment->assignment_title,
                        'description' => $assignment->instructions,
                        'deadline' => $assignment->due_date ? $assignment->due_date->toISOString() : null,
                        'max_marks' => $assignment->max_points,
                        'created_at' => $assignment->created_at->toISOString(),
                    ];
                }),
            ];
        });
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $course->id,
                'title' => $course->title,
                'description' => $course->description,
                'thumbnail' => $course->thumbnail ? url('storage/' . $course->thumbnail) : null,
                'category' => $course->category,
                'level' => $course->level,
                'duration' => $course->duration,
                'student_count' => $course->student_count ?? 0,
                'instructor' => $course->instructor && $course->instructor->user ? [
                    'id' => $course->instructor->user->id,
                    'name' => $course->instructor->user->name,
                    'email' => $course->instructor->user->email,
                ] : null,
                'modules' => $modules,
                'totalLessons' => $totalLessons,
                'completedLessons' => $completedLessons,
                'progressDetails' => $progressDetails,
                'enrollment' => [
                    'id' => $enrollment->id,
                    'status' => $enrollment->status,
                    'progress' => (float) $enrollment->progress,
                    'enrolled_at' => $enrollment->created_at->toISOString(),
                    'last_accessed' => $enrollment->last_accessed_at ? $enrollment->last_accessed_at->toISOString() : null,
                    'completed_at' => $enrollment->completed_at ? $enrollment->completed_at->toISOString() : null,
                ],
            ],
        ]);
    }

    /**
     * Enroll in a course
     */
    public function enroll($id)
    {
        $user = Auth::user();
        
        $course = Course::findOrFail($id);
        
        // Check if already enrolled
        $existingEnrollment = Enrollment::where('learner_id', $user->id)
            ->where('course_id', $id)
            ->first();
        
        if ($existingEnrollment) {
            return response()->json([
                'success' => false,
                'message' => 'You are already enrolled in this course',
            ], 400);
        }
        
        // Create enrollment
        $enrollment = Enrollment::create([
            'learner_id' => $user->id,
            'course_id' => $id,
            'status' => 'active',
            'progress' => 0.00,
            'last_accessed_at' => now(),
        ]);
        
        // Update student count
        $course->increment('student_count');
        
        return response()->json([
            'success' => true,
            'message' => 'Successfully enrolled in the course',
            'data' => [
                'enrollment_id' => $enrollment->id,
                'course_id' => $course->id,
                'course_title' => $course->title,
                'enrolled_at' => $enrollment->created_at->toISOString(),
            ],
        ]);
    }

    /**
     * Get detailed progress for a course
     */
    public function progress($id)
    {
        $user = Auth::user();
        
        $enrollment = Enrollment::where('learner_id', $user->id)
            ->where('course_id', $id)
            ->firstOrFail();
        
        $course = Course::with(['courseModules' => function($q) {
            $q->orderBy('order_index');
        }])->findOrFail($id);
        
        // Calculate detailed progress
        $progressDetails = $this->calculateDetailedProgress($course, $user->id);
        
        // Get progress for all modules
        $moduleProgress = $course->courseModules->map(function ($module) use ($user) {
            $progress = LessonProgress::where('user_id', $user->id)
                ->where('lesson_id', $module->id)
                ->first();
            
            return [
                'module_id' => $module->id,
                'module_title' => $module->module_title,
                'type' => $module->type ?? 'reading',
                'is_mandatory' => $module->is_mandatory ?? true,
                'status' => $progress ? $progress->status : 'not_started',
                'time_spent_minutes' => $progress ? $progress->time_spent_minutes : 0,
                'completed_at' => $progress && $progress->completed_at ? $progress->completed_at->toISOString() : null,
            ];
        });
        
        // Calculate statistics
        $totalModules = $course->courseModules->count();
        $mandatoryModules = $course->courseModules->where('is_mandatory', true)->count();
        $completedModules = LessonProgress::where('user_id', $user->id)
            ->whereIn('lesson_id', $course->courseModules->pluck('id'))
            ->where('status', 'completed')
            ->count();
        $completedMandatory = LessonProgress::where('user_id', $user->id)
            ->whereIn('lesson_id', $course->courseModules->where('is_mandatory', true)->pluck('id'))
            ->where('status', 'completed')
            ->count();
        
        return response()->json([
            'success' => true,
            'data' => [
                'course_id' => $course->id,
                'course_title' => $course->title,
                'overall_progress' => (float) $enrollment->progress,
                'status' => $enrollment->status,
                'statistics' => [
                    'total_modules' => $totalModules,
                    'mandatory_modules' => $mandatoryModules,
                    'completed_modules' => $completedModules,
                    'completed_mandatory' => $completedMandatory,
                ],
                'progressDetails' => $progressDetails,
                'modules' => $moduleProgress,
            ],
        ]);
    }

    /**
     * Format test data for learner view with submission status
     */
    private function formatTestForLearner($test, $learnerId)
    {
        $now = Carbon::now();
        $startDate = Carbon::parse($test->start_date);
        $endDate = Carbon::parse($test->end_date);
        
        // Calculate test status based on dates
        if ($now->lt($startDate)) {
            $status = 'scheduled'; // Not yet available
        } elseif ($now->gte($startDate) && $now->lte($endDate)) {
            $status = 'active'; // Can take test now
        } else {
            $status = 'closed'; // Deadline passed
        }
        
        // Get submission information for this learner
        $submissions = TestSubmission::where('test_id', $test->id)
            ->where('student_id', $learnerId)
            ->orderBy('attempt_number', 'desc')
            ->get();
        
        $attemptsUsed = $submissions->count();
        $latestSubmission = $submissions->first();
        
        $submissionStatus = null;
        $gradingStatus = null;
        $submittedAt = null;
        $totalScore = null;
        $attemptNumber = 0;
        
        if ($latestSubmission) {
            // Check if submitted or in progress
            $submissionStatus = $latestSubmission->submitted_at ? 'submitted' : 'in_progress';
            
            // Check grading status
            if ($latestSubmission->grading_status === 'graded' || $latestSubmission->grading_status === 'published') {
                $gradingStatus = $latestSubmission->grading_status;
            }
            
            $submittedAt = $latestSubmission->submitted_at;
            $totalScore = $latestSubmission->total_score;
            $attemptNumber = $latestSubmission->attempt_number;
        }
        
        return [
            'id' => $test->id,
            'test_title' => $test->test_title,
            'test_description' => $test->test_description,
            'instructions' => $test->instructions,
            'total_marks' => $test->total_marks,
            'passing_marks' => $test->passing_marks,
            'time_limit' => $test->time_limit,
            'max_attempts' => $test->max_attempts,
            'start_date' => $startDate->toIso8601String(),
            'end_date' => $endDate->toIso8601String(),
            'status' => $status,
            'is_published' => $test->visibility_status === 'visible' || $test->status !== 'draft',
            'submission_status' => $submissionStatus,
            'grading_status' => $gradingStatus,
            'submitted_at' => $submittedAt ? Carbon::parse($submittedAt)->toIso8601String() : null,
            'attempt_number' => $attemptNumber,
            'total_score' => $totalScore,
            'attempts_used' => $attemptsUsed,
            'attempts_remaining' => max(0, $test->max_attempts - $attemptsUsed),
        ];
    }

    /**
     * Calculate detailed progress for a course including modules, quizzes, assignments, and tests
     */
    private function calculateDetailedProgress($course, $userId)
    {
        $totalItems = 0;
        $completedItems = 0;
        
        $moduleCount = $course->courseModules->count();
        $completedModules = 0;
        
        $quizCount = 0;
        $completedQuizzes = 0;
        
        $assignmentCount = 0;
        $completedAssignments = 0;
        
        $testCount = 0;
        $completedTests = 0;
        
        foreach ($course->courseModules as $module) {
            // Check module completion (via lesson_progress)
            $moduleProgress = LessonProgress::where('user_id', $userId)
                ->where('lesson_id', $module->id)
                ->where('status', 'completed')
                ->exists();
            
            if ($moduleProgress) {
                $completedModules++;
            }
            
            // Count quizzes in this module
            $quizzes = \App\Models\ModuleQuiz::where('module_id', $module->id)->get();
            foreach ($quizzes as $quiz) {
                $quizCount++;
                
                // Check if quiz is completed (at least one successful attempt)
                $quizCompleted = QuizAttempt::where('user_id', $userId)
                    ->where('quiz_id', $quiz->id)
                    ->where('status', 'completed')
                    ->exists();
                
                if ($quizCompleted) {
                    $completedQuizzes++;
                }
            }
            
            // Count assignments in this module
            $assignments = \App\Models\ModuleAssignment::where('module_id', $module->id)->get();
            foreach ($assignments as $assignment) {
                $assignmentCount++;
                
                // Check if assignment is submitted
                $assignmentSubmitted = AssignmentSubmission::where('user_id', $userId)
                    ->where('assignment_id', $assignment->id)
                    ->whereIn('status', ['submitted', 'graded'])
                    ->exists();
                
                if ($assignmentSubmitted) {
                    $completedAssignments++;
                }
            }
            
            // Count tests in this module
            $tests = Test::where('module_id', $module->id)->get();
            foreach ($tests as $test) {
                $testCount++;
                
                // Check if test is submitted
                $testSubmitted = TestSubmission::where('student_id', $userId)
                    ->where('test_id', $test->id)
                    ->whereIn('submission_status', ['submitted', 'late'])
                    ->exists();
                
                if ($testSubmitted) {
                    $completedTests++;
                }
            }
        }
        
        // Calculate total items and completed items
        $totalItems = $moduleCount + $quizCount + $assignmentCount + $testCount;
        $completedItems = $completedModules + $completedQuizzes + $completedAssignments + $completedTests;
        
        // Calculate overall percentage
        $overallPercentage = $totalItems > 0 ? round(($completedItems / $totalItems) * 100, 1) : 0;
        
        return [
            'overall_percentage' => $overallPercentage,
            'modules' => [
                'total' => $moduleCount,
                'completed' => $completedModules,
                'percentage' => $moduleCount > 0 ? round(($completedModules / $moduleCount) * 100, 1) : 0
            ],
            'quizzes' => [
                'total' => $quizCount,
                'completed' => $completedQuizzes,
                'percentage' => $quizCount > 0 ? round(($completedQuizzes / $quizCount) * 100, 1) : 0
            ],
            'assignments' => [
                'total' => $assignmentCount,
                'completed' => $completedAssignments,
                'percentage' => $assignmentCount > 0 ? round(($completedAssignments / $assignmentCount) * 100, 1) : 0
            ],
            'tests' => [
                'total' => $testCount,
                'completed' => $completedTests,
                'percentage' => $testCount > 0 ? round(($completedTests / $testCount) * 100, 1) : 0
            ],
            'total_items' => $totalItems,
            'completed_items' => $completedItems,
        ];
    }
}
