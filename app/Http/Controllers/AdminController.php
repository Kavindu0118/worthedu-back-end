<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Instructor;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    /**
     * Get all students details for admin dashboard
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllStudents(Request $request)
    {
        try {
            // Get all learners (users with role 'learner')
            $students = User::where('role', 'learner')
                ->with(['enrollments', 'enrollments.course'])
                ->get()
                ->map(function ($student) {
                    // Calculate enrollments count
                    $enrollmentsCount = $student->enrollments->count();
                    
                    // Calculate completed courses
                    $completedCourses = $student->enrollments()
                        ->where('status', 'completed')
                        ->count();
                    
                    // Calculate in-progress courses
                    $inProgressCourses = $student->enrollments()
                        ->where('status', 'in_progress')
                        ->count();
                    
                    // Get average progress
                    $avgProgress = $student->enrollments()->avg('progress') ?? 0;
                    
                    return [
                        'id' => $student->id,
                        'name' => $student->name,
                        'username' => $student->username,
                        'email' => $student->email,
                        'phone' => $student->phone,
                        'avatar' => $student->avatar,
                        'bio' => $student->bio,
                        'date_of_birth' => $student->date_of_birth,
                        'membership_type' => $student->membership_type,
                        'membership_expires_at' => $student->membership_expires_at,
                        'created_at' => $student->created_at,
                        'enrollments_count' => $enrollmentsCount,
                        'completed_courses' => $completedCourses,
                        'in_progress_courses' => $inProgressCourses,
                        'average_progress' => round($avgProgress, 2),
                        'last_login' => $student->updated_at,
                    ];
                });

            return response()->json([
                'success' => true,
                'total_students' => $students->count(),
                'students' => $students,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving students data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all instructors with their status for admin dashboard
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllInstructors(Request $request)
    {
        try {
            // Get all instructors with their user details
            $instructors = Instructor::with(['user'])
                ->get()
                ->map(function ($instructor) {
                    // Get courses count for this instructor
                    $coursesCount = Course::where('instructor_id', $instructor->instructor_id)->count();
                    
                    // Get total enrollments in instructor's courses
                    $totalEnrollments = Course::where('instructor_id', $instructor->instructor_id)
                        ->withCount('enrollments')
                        ->get()
                        ->sum('enrollments_count');

                    return [
                        'instructor_id' => $instructor->instructor_id,
                        'user_id' => $instructor->user_id,
                        'name' => $instructor->first_name . ' ' . $instructor->last_name,
                        'first_name' => $instructor->first_name,
                        'last_name' => $instructor->last_name,
                        'email' => $instructor->user->email ?? null,
                        'phone' => $instructor->mobile_number,
                        'date_of_birth' => $instructor->date_of_birth,
                        'address' => $instructor->address,
                        'highest_qualification' => $instructor->highest_qualification,
                        'subject_area' => $instructor->subject_area,
                        'status' => $instructor->status,
                        'note' => $instructor->note,
                        'courses_count' => $coursesCount,
                        'total_students' => $totalEnrollments,
                        'created_at' => $instructor->created_at,
                        'updated_at' => $instructor->updated_at,
                    ];
                });

            // Group by status for easier filtering
            $statusSummary = [
                'pending' => $instructors->where('status', 'pending')->count(),
                'approved' => $instructors->where('status', 'approved')->count(),
                'rejected' => $instructors->where('status', 'rejected')->count(),
            ];

            return response()->json([
                'success' => true,
                'total_instructors' => $instructors->count(),
                'status_summary' => $statusSummary,
                'instructors' => $instructors->values(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving instructors data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all courses listed on the website for admin dashboard
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllCourses(Request $request)
    {
        try {
            // Get all courses with instructor and enrollment details
            $courses = Course::with(['instructor.user', 'courseModules'])
                ->withCount(['enrollments', 'courseModules'])
                ->get()
                ->map(function ($course) {
                    // Calculate average progress across all enrollments
                    $avgProgress = DB::table('enrollments')
                        ->where('course_id', $course->id)
                        ->avg('progress') ?? 0;
                    
                    // Count completed enrollments
                    $completedEnrollments = DB::table('enrollments')
                        ->where('course_id', $course->id)
                        ->where('status', 'completed')
                        ->count();

                    // Calculate revenue based on enrollments count * course price
                    $revenue = $course->enrollments_count * $course->price;

                    return [
                        'id' => $course->id,
                        'title' => $course->title,
                        'category' => $course->category,
                        'description' => $course->description,
                        'price' => $course->price,
                        'level' => $course->level,
                        'duration' => $course->duration,
                        'thumbnail' => $course->thumbnail,
                        'status' => $course->status,
                        'instructor_id' => $course->instructor_id,
                        'instructor_name' => $course->instructor && $course->instructor->user 
                            ? $course->instructor->user->name 
                            : null,
                        'student_count' => $course->student_count,
                        'enrollments_count' => $course->enrollments_count,
                        'modules_count' => $course->course_modules_count,
                        'completed_count' => $completedEnrollments,
                        'average_progress' => round($avgProgress, 2),
                        'estimated_revenue' => $revenue,
                        'created_at' => $course->created_at,
                        'updated_at' => $course->updated_at,
                    ];
                });

            // Calculate summary statistics
            $summary = [
                'total_courses' => $courses->count(),
                'published_courses' => $courses->where('status', 'published')->count(),
                'draft_courses' => $courses->where('status', 'draft')->count(),
                'total_enrollments' => $courses->sum('enrollments_count'),
                'total_revenue' => $courses->sum('estimated_revenue'),
            ];

            return response()->json([
                'success' => true,
                'summary' => $summary,
                'courses' => $courses->values(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving courses data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update instructor status (approve/reject/pending)
     * 
     * @param Request $request
     * @param int $instructorId
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateInstructorStatus(Request $request, $instructorId)
    {
        try {
            // Validate the request
            $validated = $request->validate([
                'status' => 'required|in:pending,approved,rejected',
                'note' => 'nullable|string|max:500',
            ]);

            // Find the instructor
            $instructor = Instructor::where('instructor_id', $instructorId)->first();

            if (!$instructor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Instructor not found',
                ], 404);
            }

            // Update the status
            $instructor->status = $validated['status'];
            
            // Update note if provided
            if (isset($validated['note'])) {
                $instructor->note = $validated['note'];
            }

            $instructor->save();

            // Return updated instructor details
            $coursesCount = Course::where('instructor_id', $instructor->instructor_id)->count();
            $totalEnrollments = Course::where('instructor_id', $instructor->instructor_id)
                ->withCount('enrollments')
                ->get()
                ->sum('enrollments_count');

            return response()->json([
                'success' => true,
                'message' => 'Instructor status updated successfully',
                'instructor' => [
                    'instructor_id' => $instructor->instructor_id,
                    'user_id' => $instructor->user_id,
                    'name' => $instructor->first_name . ' ' . $instructor->last_name,
                    'first_name' => $instructor->first_name,
                    'last_name' => $instructor->last_name,
                    'email' => $instructor->user->email ?? null,
                    'phone' => $instructor->mobile_number,
                    'date_of_birth' => $instructor->date_of_birth,
                    'address' => $instructor->address,
                    'highest_qualification' => $instructor->highest_qualification,
                    'subject_area' => $instructor->subject_area,
                    'status' => $instructor->status,
                    'note' => $instructor->note,
                    'courses_count' => $coursesCount,
                    'total_students' => $totalEnrollments,
                    'created_at' => $instructor->created_at,
                    'updated_at' => $instructor->updated_at,
                ],
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating instructor status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get single instructor details including CV download info
     * 
     * @param int $instructorId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getInstructorDetails($instructorId)
    {
        try {
            $instructor = Instructor::with(['user'])->where('instructor_id', $instructorId)->first();

            if (!$instructor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Instructor not found',
                ], 404);
            }

            $coursesCount = Course::where('instructor_id', $instructor->instructor_id)->count();
            $totalEnrollments = Course::where('instructor_id', $instructor->instructor_id)
                ->withCount('enrollments')
                ->get()
                ->sum('enrollments_count');

            return response()->json([
                'success' => true,
                'instructor' => [
                    'instructor_id' => $instructor->instructor_id,
                    'user_id' => $instructor->user_id,
                    'name' => $instructor->first_name . ' ' . $instructor->last_name,
                    'first_name' => $instructor->first_name,
                    'last_name' => $instructor->last_name,
                    'email' => $instructor->user->email ?? null,
                    'phone' => $instructor->mobile_number,
                    'date_of_birth' => $instructor->date_of_birth,
                    'address' => $instructor->address,
                    'highest_qualification' => $instructor->highest_qualification,
                    'subject_area' => $instructor->subject_area,
                    'status' => $instructor->status,
                    'note' => $instructor->note,
                    'courses_count' => $coursesCount,
                    'total_students' => $totalEnrollments,
                    'has_cv' => !empty($instructor->cv),
                    'created_at' => $instructor->created_at,
                    'updated_at' => $instructor->updated_at,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving instructor details',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download instructor CV
     * 
     * @param int $instructorId
     * @return \Illuminate\Http\Response
     */
    public function downloadInstructorCV($instructorId)
    {
        try {
            $instructor = Instructor::where('instructor_id', $instructorId)->first();

            if (!$instructor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Instructor not found',
                ], 404);
            }

            if (empty($instructor->cv)) {
                return response()->json([
                    'success' => false,
                    'message' => 'CV not found for this instructor',
                ], 404);
            }

            // Return the CV as a downloadable file
            $fileName = $instructor->first_name . '_' . $instructor->last_name . '_CV.pdf';
            
            return response($instructor->cv)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error downloading CV',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a course by ID along with all related data
     * 
     * @param int $courseId
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteCourse($courseId)
    {
        try {
            // Find the course
            $course = Course::find($courseId);

            if (!$course) {
                return response()->json([
                    'success' => false,
                    'message' => 'Course not found',
                ], 404);
            }

            // Store course details for response
            $courseTitle = $course->title;
            $courseInstructor = $course->instructor ? $course->instructor->user->name ?? 'Unknown' : 'Unknown';

            // Get counts before deletion for response
            $enrollmentCount = $course->enrollments()->count();
            $moduleCount = $course->courseModules()->count();

            // Delete all related data in proper order
            
            // 1. Delete all enrollments for this course
            $course->enrollments()->delete();
            
            // 2. Delete course modules and their contents
            $course->courseModules()->delete();
            
            // 3. Delete any other related records if they exist
            // (quizzes, assignments, notes might be related through modules)
            
            // 4. Finally, delete the course itself
            $course->delete();

            return response()->json([
                'success' => true,
                'message' => 'Course and all related data deleted successfully',
                'deleted_course' => [
                    'id' => $courseId,
                    'title' => $courseTitle,
                    'instructor' => $courseInstructor,
                ],
                'deleted_records' => [
                    'enrollments' => $enrollmentCount,
                    'modules' => $moduleCount,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting course',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
