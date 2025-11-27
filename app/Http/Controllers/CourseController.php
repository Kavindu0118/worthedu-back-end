<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Course;
use App\Models\Module;
use App\Models\Quiz;

class CourseController extends Controller
{
    /**
     * Create a new course with modules and quizzes
     */
    public function store(Request $request)
    {
        Log::info('Course creation request received', [
            'user_id' => Auth::id(),
            'title' => $request->input('title'),
            'modules_raw' => substr(json_encode($request->input('modules')), 0, 100),
        ]);

        try {
            // Handle JSON string for modules if sent as string
            $modulesData = $request->input('modules');
            if (is_string($modulesData)) {
                $modulesData = json_decode($modulesData, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return response()->json([
                        'message' => 'Invalid JSON for modules',
                        'error' => json_last_error_msg()
                    ], 422);
                }
                $request->merge(['modules' => $modulesData]);
            }

            // Validate the request
            $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'level' => 'required|in:beginner,intermediate,advanced',
            'duration' => 'nullable|string',
            'thumbnail' => 'nullable|string',
            'status' => 'required|in:draft,published,archived',
            'modules' => 'required|array|min:1',
            'modules.*.title' => 'required|string|max:255',
            'modules.*.description' => 'nullable|string',
            'modules.*.content' => 'nullable|string',
            'modules.*.duration' => 'nullable|integer',
            'modules.*.quizzes' => 'nullable|array',
            'modules.*.quizzes.*.question' => 'required|string',
            'modules.*.quizzes.*.options' => 'required|array|min:2',
            'modules.*.quizzes.*.correct_answer' => 'required|integer|min:0',
            'modules.*.quizzes.*.points' => 'nullable|integer|min:0',
            ]);

            // Get authenticated instructor
            $user = Auth::user();
            if (!$user || $user->role !== 'instructor') {
                return response()->json(['message' => 'Only instructors can create courses'], 403);
            }

            $instructor = $user->instructor;
            if (!$instructor) {
                return response()->json(['message' => 'Instructor profile not found'], 404);
            }

            // Check instructor status
            if ($instructor->status !== 'approved') {
                return response()->json(['message' => 'Your instructor account is not approved yet'], 403);
            }

            DB::beginTransaction();
            try {
            // Create the course
            $course = Course::create([
                'instructor_id' => $instructor->instructor_id,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'price' => $data['price'],
                'level' => $data['level'],
                'duration' => $data['duration'] ?? null,
                'thumbnail' => $data['thumbnail'] ?? null,
                'status' => $data['status'] ?? 'draft',
            ]);

            // Create modules and their quizzes
            foreach ($data['modules'] as $index => $moduleData) {
                $module = Module::create([
                    'course_id' => $course->id,
                    'title' => $moduleData['title'],
                    'description' => $moduleData['description'] ?? null,
                    'content' => $moduleData['content'] ?? null,
                    'duration' => $moduleData['duration'] ?? null,
                    'order' => $index + 1,
                ]);

                // Create quizzes for this module
                if (isset($moduleData['quizzes']) && is_array($moduleData['quizzes'])) {
                    foreach ($moduleData['quizzes'] as $quizData) {
                        Quiz::create([
                            'module_id' => $module->id,
                            'question' => $quizData['question'],
                            'options' => $quizData['options'],
                            'correct_answer' => $quizData['correct_answer'],
                            'points' => $quizData['points'] ?? 10,
                        ]);
                    }
                }
            }

            DB::commit();

            // Load the course with its modules and quizzes
            $course->load(['modules.quizzes']);

            return response()->json([
                'message' => 'Course created successfully',
                'course' => $course,
            ], 201);

            } catch (\Throwable $e) {
                DB::rollBack();
                Log::error('Course creation database error', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                return response()->json([
                    'message' => 'Failed to create course',
                    'error' => $e->getMessage()
                ], 500);
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Course validation error', ['errors' => $e->errors()]);
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Course creation error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Failed to process course request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all courses (optional: filter by instructor)
     */
    public function index(Request $request)
    {
        $query = Course::with(['modules.quizzes', 'instructor']);

        // Filter by instructor if requested
        if ($request->has('instructor_id')) {
            $query->where('instructor_id', $request->instructor_id);
        }

        // Filter by level
        if ($request->has('level')) {
            $query->where('level', $request->level);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        } else {
            // By default, only show published courses
            $query->where('status', 'published');
        }

        $courses = $query->get();

        return response()->json([
            'courses' => $courses,
        ]);
    }

    /**
     * Get instructor dashboard data
     */
    public function dashboard(Request $request)
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'instructor') {
            return response()->json(['message' => 'Only instructors can access this'], 403);
        }

        $instructor = $user->instructor;
        if (!$instructor) {
            return response()->json(['message' => 'Instructor profile not found'], 404);
        }

        // Get all courses for this instructor
        $courses = Course::with(['modules.quizzes', 'enrollments'])
            ->where('instructor_id', $instructor->instructor_id)
            ->get();

        // Calculate stats
        $totalStudents = $courses->sum(function ($course) {
            return $course->enrollments->count();
        });

        $totalEarnings = $courses->sum(function ($course) {
            return $course->enrollments->count() * $course->price;
        });

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role,
                'instructor' => [
                    'instructor_id' => $instructor->instructor_id,
                    'first_name' => $instructor->first_name,
                    'last_name' => $instructor->last_name,
                    'status' => $instructor->status,
                ]
            ],
            'courses' => $courses,
            'stats' => [
                'total_courses' => $courses->count(),
                'total_students' => $totalStudents,
                'total_earnings' => $totalEarnings,
            ]
        ]);
    }

    /**
     * Get instructor's own courses with stats
     */
    public function instructorCourses(Request $request)
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'instructor') {
            return response()->json(['message' => 'Only instructors can access this'], 403);
        }

        $instructor = $user->instructor;
        if (!$instructor) {
            return response()->json(['message' => 'Instructor profile not found'], 404);
        }

        // Get all courses for this instructor
        $courses = Course::with(['modules.quizzes', 'enrollments'])
            ->where('instructor_id', $instructor->instructor_id)
            ->get();

        // Calculate stats
        $totalStudents = $courses->sum(function ($course) {
            return $course->enrollments->count();
        });

        $totalEarnings = $courses->sum(function ($course) {
            return $course->enrollments->count() * $course->price;
        });

        return response()->json([
            'courses' => $courses,
            'stats' => [
                'total_courses' => $courses->count(),
                'total_students' => $totalStudents,
                'total_earnings' => $totalEarnings,
            ]
        ]);
    }

    /**
     * Get a single course by ID
     */
    public function show($id)
    {
        $course = Course::with(['modules.quizzes', 'instructor'])->find($id);

        if (!$course) {
            return response()->json(['message' => 'Course not found'], 404);
        }

        return response()->json([
            'course' => $course,
        ]);
    }

    /**
     * Update a course
     */
    public function update(Request $request, $id)
    {
        $course = Course::find($id);

        if (!$course) {
            return response()->json(['message' => 'Course not found'], 404);
        }

        // Check if the authenticated user is the course instructor
        $user = Auth::user();
        if (!$user || $user->role !== 'instructor') {
            return response()->json(['message' => 'Only instructors can update courses'], 403);
        }

        $instructor = $user->instructor;
        if (!$instructor || $course->instructor_id !== $instructor->instructor_id) {
            return response()->json(['message' => 'You can only update your own courses'], 403);
        }

        $data = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'price' => 'sometimes|numeric|min:0',
            'level' => 'sometimes|in:beginner,intermediate,advanced',
            'duration' => 'sometimes|integer',
            'thumbnail' => 'sometimes|string',
            'status' => 'sometimes|in:draft,published,archived',
        ]);

        $course->update($data);

        return response()->json([
            'message' => 'Course updated successfully',
            'course' => $course,
        ]);
    }

    /**
     * Delete a course
     */
    public function destroy($id)
    {
        $course = Course::find($id);

        if (!$course) {
            return response()->json(['message' => 'Course not found'], 404);
        }

        // Check if the authenticated user is the course instructor
        $user = Auth::user();
        if (!$user || $user->role !== 'instructor') {
            return response()->json(['message' => 'Only instructors can delete courses'], 403);
        }

        $instructor = $user->instructor;
        if (!$instructor || $course->instructor_id !== $instructor->instructor_id) {
            return response()->json(['message' => 'You can only delete your own courses'], 403);
        }

        $course->delete();

        return response()->json([
            'message' => 'Course deleted successfully',
        ]);
    }
}
