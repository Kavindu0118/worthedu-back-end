<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\LessonProgress;
use App\Models\CourseModule;
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
                  ->with('instructor:id,name,email');
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
                'instructor' => $course->instructor ? [
                    'id' => $course->instructor->id,
                    'name' => $course->instructor->name,
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
            ->with('instructor:id,name,email');
        
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
                                'instructor' => $course->instructor ? [
                                    'id' => $course->instructor->id,
                                    'name' => $course->instructor->name,
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
        
        $course = Course::with([
            'instructor:id,name,email,bio',
            'modules' => function($q) {
                $q->orderBy('order_index');
            }
        ])->findOrFail($id);
        
        // Get enrollment if exists
        $enrollment = Enrollment::where('learner_id', $user->id)
            ->where('course_id', $id)
            ->first();
        
        // Get progress for each module
        $modules = $course->modules->map(function ($module) use ($user) {
            $progress = LessonProgress::where('user_id', $user->id)
                ->where('lesson_id', $module->id)
                ->first();
            
            return [
                'id' => $module->id,
                'title' => $module->module_title,
                'description' => $module->module_description,
                'type' => $module->type ?? 'reading',
                'duration' => $module->duration,
                'duration_minutes' => $module->duration_minutes,
                'order_index' => $module->order_index,
                'is_mandatory' => $module->is_mandatory ?? true,
                'progress' => $progress ? [
                    'status' => $progress->status,
                    'completed_at' => $progress->completed_at ? $progress->completed_at->toISOString() : null,
                    'time_spent_minutes' => $progress->time_spent_minutes,
                ] : null,
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
                'instructor' => $course->instructor ? [
                    'id' => $course->instructor->id,
                    'name' => $course->instructor->name,
                    'email' => $course->instructor->email,
                    'bio' => $course->instructor->bio,
                ] : null,
                'modules' => $modules,
                'enrollment' => $enrollment ? [
                    'id' => $enrollment->id,
                    'status' => $enrollment->status,
                    'progress' => (float) $enrollment->progress,
                    'enrolled_at' => $enrollment->created_at->toISOString(),
                    'last_accessed' => $enrollment->last_accessed_at ? $enrollment->last_accessed_at->toISOString() : null,
                    'completed_at' => $enrollment->completed_at ? $enrollment->completed_at->toISOString() : null,
                ] : null,
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
