<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\LessonProgress;
use App\Models\CourseModule;
use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
        
        // Load course with all nested relationships
        $course = Course::with([
            'instructor.user:id,name,email',
            'courseModules' => function($q) use ($user) {
                $q->orderBy('order_index')
                  ->with(['notes', 'quizzes', 'assignments']);
            }
        ])->findOrFail($id);
        
        // Update last accessed timestamp
        $enrollment->update(['last_accessed_at' => now()]);
        
        // Calculate total and completed lessons (notes are lessons)
        $totalLessons = 0;
        $completedLessons = 0;
        
        foreach ($course->courseModules as $module) {
            $totalLessons += $module->notes->count();
            // TODO: Track note completion status in future
        }
        
        // Map modules with notes (lessons), quizzes, and assignments
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
        
        $course = Course::with(['modules' => function($q) {
            $q->orderBy('order_index');
        }])->findOrFail($id);
        
        // Get progress for all modules
        $moduleProgress = $course->modules->map(function ($module) use ($user) {
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
        $totalModules = $course->modules->count();
        $mandatoryModules = $course->modules->where('is_mandatory', true)->count();
        $completedModules = LessonProgress::where('user_id', $user->id)
            ->whereIn('lesson_id', $course->modules->pluck('id'))
            ->where('status', 'completed')
            ->count();
        $completedMandatory = LessonProgress::where('user_id', $user->id)
            ->whereIn('lesson_id', $course->modules->where('is_mandatory', true)->pluck('id'))
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
                'modules' => $moduleProgress,
            ],
        ]);
    }
}
