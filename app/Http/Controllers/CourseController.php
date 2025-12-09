<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Course;
use App\Models\CourseModule;
use App\Http\Resources\CourseDetailResource;

class CourseController extends Controller
{
    /**
     * Create a new course (modules/quizzes/assignments added separately via other endpoints)
     */
    public function store(Request $request)
    {
        try {
            // Validate the request
            $data = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'category' => 'required|string|max:100',
                'price' => 'required|numeric|min:0',
                'level' => 'required|in:beginner,intermediate,advanced',
                'duration' => 'nullable|string',
                'thumbnail' => 'nullable|string',
                'status' => 'required|in:draft,published,archived',
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

            // Create the course
            $course = Course::create([
                'instructor_id' => $instructor->instructor_id,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'category' => $data['category'] ?? null,
                'price' => $data['price'],
                'level' => $data['level'],
                'duration' => $data['duration'] ?? null,
                'thumbnail' => $data['thumbnail'] ?? null,
                'status' => $data['status'] ?? 'draft',
                'student_count' => 0,
            ]);

            return response()->json([
                'message' => 'Course created successfully',
                'course' => $course,
            ], 201);

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
     * Get all published courses with filtering (public endpoint)
     */
    public function index(Request $request)
    {
        try {
            $query = Course::query()
                ->where('status', 'published')
                ->withCount('courseModules');

            // Filter by category (case-insensitive)
            if ($request->has('category') && $request->category !== '' && $request->category !== 'All') {
                $query->where('category', $request->category);
            }

            // Filter by level
            if ($request->has('level') && $request->level !== '') {
                $query->where('level', $request->level);
            }

            // Search in title and description (case-insensitive)
            if ($request->has('search') && !empty($request->search)) {
                $searchTerm = $request->search;
                $query->where(function($q) use ($searchTerm) {
                    $q->where('title', 'like', "%{$searchTerm}%")
                      ->orWhere('description', 'like', "%{$searchTerm}%");
                });
            }

            // Order by most recent
            $query->orderBy('created_at', 'desc');

            $courses = $query->get()->map(function($course) {
                return [
                    'id' => $course->id,
                    'instructor_id' => $course->instructor_id,
                    'title' => $course->title,
                    'category' => $course->category,
                    'description' => $course->description,
                    'price' => (float) $course->price,
                    'level' => $course->level,
                    'duration' => $course->duration,
                    'thumbnail' => $course->thumbnail ? url('storage/' . $course->thumbnail) : null,
                    'status' => $course->status,
                    'student_count' => $course->student_count ?? 0,
                    'modules_count' => $course->course_modules_count ?? 0,
                    'created_at' => $course->created_at,
                    'updated_at' => $course->updated_at,
                ];
            });

            return response()->json($courses);

        } catch (\Throwable $e) {
            Log::error('Failed to fetch courses', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Failed to fetch courses',
                'error' => $e->getMessage()
            ], 500);
        }
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
        $courses = Course::with(['courseModules', 'enrollments'])
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
        $courses = Course::with(['courseModules', 'enrollments'])
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
     * Get a single course by ID with all nested content
     */
    public function show($id)
    {
        $course = Course::with([
            'courseModules.quizzes',
            'courseModules.assignments',
            'courseModules.notes',
            'instructor'
        ])->find($id);

        if (!$course) {
            return response()->json(['message' => 'Course not found'], 404);
        }

        return new CourseDetailResource($course);
    }

    /**
     * Update a course
     */
    public function update(Request $request, $id)
    {
        Log::info('=== Course Update Request Started ===', [
            'course_id' => $id,
            'request_all' => $request->all(),
            'request_method' => $request->method(),
        ]);

        $course = Course::find($id);

        if (!$course) {
            Log::warning('Course not found', ['id' => $id]);
            return response()->json(['message' => 'Course not found'], 404);
        }

        Log::info('Course found', [
            'course_id' => $course->id,
            'current_title' => $course->title,
            'current_category' => $course->category,
        ]);

        // Check if the authenticated user is the course instructor
        $user = Auth::user();
        if (!$user || $user->role !== 'instructor') {
            Log::warning('Not an instructor', ['user_id' => $user ? $user->id : null]);
            return response()->json(['message' => 'Only instructors can update courses'], 403);
        }

        $instructor = $user->instructor;
        if (!$instructor || $course->instructor_id !== $instructor->instructor_id) {
            Log::warning('Ownership check failed', [
                'course_instructor_id' => $course->instructor_id,
                'user_instructor_id' => $instructor ? $instructor->instructor_id : null,
            ]);
            return response()->json(['message' => 'You can only update your own courses'], 403);
        }

        Log::info('Authorization passed');

        // Validate all possible update fields - use 'sometimes' to only validate when present
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'category' => 'sometimes|string|max:100',
            'description' => 'sometimes|required|string',
            'price' => 'sometimes|required|numeric|min:0',
            'level' => 'sometimes|required|in:beginner,intermediate,advanced',
            'duration' => 'sometimes|string|max:50',
            'status' => 'sometimes|required|in:draft,published,archived',
        ]);

        Log::info('Validation passed', ['validated' => $validated]);

        // Handle thumbnail upload if present
        if ($request->hasFile('thumbnail')) {
            $thumbnailPath = $request->file('thumbnail')->store('course-thumbnails', 'public');
            $validated['thumbnail'] = $thumbnailPath;
            Log::info('Thumbnail uploaded', ['path' => $thumbnailPath]);
        }

        // Update the course with validated data
        Log::info('Attempting to update course', ['data' => $validated]);
        $course->update($validated);
        Log::info('Course updated in database');
        
        // Refresh the course model to get updated data
        $course->refresh();

        Log::info('After refresh', [
            'course_id' => $course->id,
            'title' => $course->title,
            'category' => $course->category,
            'updated_at' => $course->updated_at,
        ]);
        
        // Load relationships for complete data
        $course->load([
            'courseModules' => function($query) {
                $query->orderBy('order_index');
            },
            'courseModules.quizzes',
            'courseModules.assignments',
            'courseModules.notes'
        ]);

        // Format response manually to avoid binary data issues
        $response = [
            'id' => $course->id,
            'instructor_id' => $course->instructor_id,
            'title' => $course->title,
            'category' => $course->category,
            'description' => $course->description,
            'price' => (float) $course->price,
            'level' => $course->level,
            'duration' => $course->duration,
            'thumbnail' => $course->thumbnail,
            'status' => $course->status,
            'student_count' => $course->student_count ?? 0,
            'modules_count' => $course->courseModules->count(),
            'created_at' => $course->created_at->toISOString(),
            'updated_at' => $course->updated_at->toISOString(),
            'modules' => $course->courseModules->map(function ($module) {
                return [
                    'id' => $module->id,
                    'course_id' => $module->course_id,
                    'module_title' => $module->module_title,
                    'module_description' => $module->module_description ?? '',
                    'order_index' => $module->order_index,
                    'duration' => $module->duration ?? '',
                    'created_at' => $module->created_at->toISOString(),
                    'updated_at' => $module->updated_at->toISOString(),
                    'quizzes' => $module->quizzes->map(function ($quiz) {
                        return [
                            'id' => $quiz->id,
                            'module_id' => $quiz->module_id,
                            'quiz_title' => $quiz->quiz_title,
                            'quiz_description' => $quiz->quiz_description ?? '',
                            'quiz_data' => is_string($quiz->quiz_data) ? json_decode($quiz->quiz_data, true) : $quiz->quiz_data,
                            'total_points' => $quiz->total_points ?? 0,
                            'time_limit' => $quiz->time_limit,
                            'created_at' => $quiz->created_at->toISOString(),
                        ];
                    }),
                    'assignments' => $module->assignments->map(function ($assignment) {
                        return [
                            'id' => $assignment->id,
                            'module_id' => $assignment->module_id,
                            'assignment_title' => $assignment->assignment_title,
                            'instructions' => $assignment->instructions ?? '',
                            'attachment_url' => $assignment->attachment_url,
                            'max_points' => $assignment->max_points ?? 100,
                            'due_date' => $assignment->due_date,
                            'created_at' => $assignment->created_at->toISOString(),
                        ];
                    }),
                    'notes' => $module->notes->map(function ($note) {
                        return [
                            'id' => $note->id,
                            'module_id' => $note->module_id,
                            'note_title' => $note->note_title,
                            'note_body' => $note->note_body ?? '',
                            'attachment_url' => $note->attachment_url,
                            'created_at' => $note->created_at->toISOString(),
                        ];
                    }),
                ];
            }),
        ];

        Log::info('Course updated successfully', [
            'course_id' => $course->id,
            'new_title' => $course->title,
        ]);

        // Return same format as showInstructorCourse
        return response()->json(['data' => $response]);
    }

    /**
     * Get course details with all nested content (for instructor course management)
     */
    public function showInstructorCourse($id)
    {
        try {
            // Get authenticated user
            $user = Auth::user();
            
            if (!$user) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }

            // Get instructor profile
            $instructor = $user->instructor;
            
            if (!$instructor) {
                return response()->json(['message' => 'Not an instructor'], 403);
            }

            // Find course with all nested relationships
            $course = Course::with([
                'courseModules' => function($query) {
                    $query->orderBy('order_index');
                },
                'courseModules.quizzes',
                'courseModules.assignments',
                'courseModules.notes'
            ])->findOrFail($id);

            // Verify ownership
            if ($course->instructor_id != $instructor->instructor_id) {
                return response()->json(['message' => 'Unauthorized - This course does not belong to you'], 403);
            }

            // Format the response
            $data = [
                'id' => $course->id,
                'instructor_id' => $course->instructor_id,
                'title' => $course->title,
                'category' => $course->category ?? '',
                'description' => $course->description,
                'price' => (float) $course->price,
                'level' => $course->level ?? 'beginner',
                'duration' => $course->duration ?? '',
                'thumbnail' => $course->thumbnail ? url('storage/' . $course->thumbnail) : null,
                'status' => $course->status,
                'student_count' => $course->student_count ?? 0,
                'modules_count' => $course->courseModules->count(),
                'created_at' => $course->created_at->toISOString(),
                'updated_at' => $course->updated_at->toISOString(),
                'modules' => $course->courseModules->map(function ($module) {
                    return [
                        'id' => $module->id,
                        'course_id' => $module->course_id,
                        'module_title' => $module->module_title,
                        'module_description' => $module->module_description ?? '',
                        'order_index' => $module->order_index,
                        'duration' => $module->duration ?? '',
                        'created_at' => $module->created_at->toISOString(),
                        'updated_at' => $module->updated_at->toISOString(),
                        'quizzes' => $module->quizzes->map(function ($quiz) {
                            return [
                                'id' => $quiz->id,
                                'module_id' => $quiz->module_id,
                                'quiz_title' => $quiz->quiz_title,
                                'quiz_description' => $quiz->quiz_description ?? '',
                                'quiz_data' => is_string($quiz->quiz_data) ? json_decode($quiz->quiz_data, true) : $quiz->quiz_data,
                                'total_points' => $quiz->total_points ?? 0,
                                'time_limit' => $quiz->time_limit,
                                'created_at' => $quiz->created_at->toISOString(),
                            ];
                        }),
                        'assignments' => $module->assignments->map(function ($assignment) {
                            return [
                                'id' => $assignment->id,
                                'module_id' => $assignment->module_id,
                                'assignment_title' => $assignment->assignment_title,
                                'instructions' => $assignment->instructions ?? '',
                                'attachment_url' => $assignment->attachment_url ? url('storage/' . $assignment->attachment_url) : null,
                                'max_points' => $assignment->max_points ?? 100,
                                'due_date' => $assignment->due_date,
                                'created_at' => $assignment->created_at->toISOString(),
                            ];
                        }),
                        'notes' => $module->notes->map(function ($note) {
                            return [
                                'id' => $note->id,
                                'module_id' => $note->module_id,
                                'note_title' => $note->note_title,
                                'note_body' => $note->note_body ?? '',
                                'attachment_url' => $note->attachment_url ? url('storage/' . $note->attachment_url) : null,
                                'created_at' => $note->created_at->toISOString(),
                            ];
                        }),
                    ];
                }),
            ];

            return response()->json(['data' => $data]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Course not found'], 404);
        } catch (\Exception $e) {
            Log::error('Course show error: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to retrieve course', 'error' => $e->getMessage()], 500);
        }
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
