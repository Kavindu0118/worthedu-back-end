<?php

namespace App\Http\Controllers;

use App\Models\ModuleAssignment;
use App\Models\AssignmentSubmission;
use App\Models\Enrollment;
use App\Helpers\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class LearnerAssignmentController extends Controller
{
    /**
     * Get list of assignments with optional filters
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $status = $request->query('status'); // pending, submitted, graded
        $courseId = $request->query('course_id');
        
        // Get enrolled course IDs
        $enrolledCourseIds = Enrollment::where('learner_id', $user->id)
            ->pluck('course_id')
            ->toArray();
        
        $query = ModuleAssignment::with(['module.course:id,title'])
            ->whereHas('module', function($q) use ($enrolledCourseIds) {
                $q->whereIn('course_id', $enrolledCourseIds);
            });
        
        if ($courseId) {
            $query->whereHas('module', function($q) use ($courseId) {
                $q->where('course_id', $courseId);
            });
        }
        
        $assignments = $query->orderBy('due_date', 'asc')
            ->get()
            ->map(function ($assignment) use ($user, $status) {
                $submission = AssignmentSubmission::where('assignment_id', $assignment->id)
                    ->where('user_id', $user->id)
                    ->first();
                
                $assignmentStatus = 'pending';
                if ($submission) {
                    $assignmentStatus = $submission->status;
                }
                
                // Filter by status if provided
                if ($status && $assignmentStatus !== $status) {
                    return null;
                }
                
                return [
                    'id' => $assignment->id,
                    'title' => $assignment->title,
                    'description' => $assignment->description,
                    'course_id' => $assignment->module->course_id,
                    'course_title' => $assignment->module->course->title,
                    'due_date' => $assignment->due_date ? $assignment->due_date->toISOString() : null,
                    'max_marks' => $assignment->max_marks,
                    'allow_late_submission' => $assignment->allow_late_submission ?? false,
                    'status' => $assignmentStatus,
                    'submission' => $submission ? [
                        'id' => $submission->id,
                        'submitted_at' => $submission->submitted_at->toISOString(),
                        'marks_obtained' => $submission->marks_obtained,
                        'is_late' => $submission->is_late,
                    ] : null,
                ];
            })
            ->filter()
            ->values();
        
        return response()->json([
            'success' => true,
            'data' => $assignments,
        ]);
    }

    /**
     * Get assignment details
     */
    public function show($id)
    {
        $user = Auth::user();
        
        $assignment = ModuleAssignment::with(['module.course:id,title'])->findOrFail($id);
        
        // Check enrollment
        $enrollment = Enrollment::where('learner_id', $user->id)
            ->where('course_id', $assignment->module->course_id)
            ->first();
        
        if (!$enrollment) {
            return response()->json([
                'success' => false,
                'message' => 'You must be enrolled in this course',
            ], 403);
        }
        
        // Get submission if exists
        $submission = AssignmentSubmission::where('assignment_id', $id)
            ->where('user_id', $user->id)
            ->with('grader:id,name,email')
            ->first();
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $assignment->id,
                'title' => $assignment->title,
                'description' => $assignment->description,
                'instructions' => $assignment->instructions ?? $assignment->description,
                'course_id' => $assignment->module->course_id,
                'course_title' => $assignment->module->course->title,
                'due_date' => $assignment->due_date ? $assignment->due_date->toISOString() : null,
                'max_marks' => $assignment->max_marks,
                'allow_late_submission' => $assignment->allow_late_submission ?? false,
                'late_penalty_percent' => $assignment->late_penalty_percent ?? 0,
                'max_file_size_mb' => $assignment->max_file_size_mb ?? 10,
                'allowed_file_types' => $assignment->allowed_file_types,
                'submission' => $submission ? [
                    'id' => $submission->id,
                    'submission_text' => $submission->submission_text,
                    'file_path' => $submission->file_path ? url('storage/' . $submission->file_path) : null,
                    'file_name' => $submission->file_name,
                    'file_size_kb' => $submission->file_size_kb,
                    'submitted_at' => $submission->submitted_at->toISOString(),
                    'status' => $submission->status,
                    'marks_obtained' => $submission->marks_obtained,
                    'feedback' => $submission->feedback,
                    'graded_at' => $submission->graded_at ? $submission->graded_at->toISOString() : null,
                    'graded_by' => $submission->grader ? [
                        'id' => $submission->grader->id,
                        'name' => $submission->grader->name,
                    ] : null,
                    'is_late' => $submission->is_late,
                ] : null,
            ],
        ]);
    }

    /**
     * Submit assignment
     */
    public function submit($id, Request $request)
    {
        $user = Auth::user();
        
        $assignment = ModuleAssignment::with('module')->findOrFail($id);
        
        // Check enrollment
        $enrollment = Enrollment::where('learner_id', $user->id)
            ->where('course_id', $assignment->module->course_id)
            ->first();
        
        if (!$enrollment) {
            return response()->json([
                'success' => false,
                'message' => 'You must be enrolled in this course',
            ], 403);
        }
        
        // Check if already submitted
        $existingSubmission = AssignmentSubmission::where('assignment_id', $id)
            ->where('user_id', $user->id)
            ->first();
        
        if ($existingSubmission && $existingSubmission->status !== 'returned') {
            return response()->json([
                'success' => false,
                'message' => 'You have already submitted this assignment',
            ], 400);
        }
        
        // Validate request
        $maxFileSizeMB = $assignment->max_file_size_mb ?? 10;
        $validator = Validator::make($request->all(), [
            'submission_text' => 'nullable|string',
            'file' => "nullable|file|max:" . ($maxFileSizeMB * 1024),
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }
        
        // Check if late
        $isLate = false;
        if ($assignment->due_date && now()->gt($assignment->due_date)) {
            if (!$assignment->allow_late_submission) {
                return response()->json([
                    'success' => false,
                    'message' => 'This assignment is past due and does not accept late submissions',
                ], 400);
            }
            $isLate = true;
        }
        
        // Handle file upload
        $filePath = null;
        $fileName = null;
        $fileSizeKb = null;
        
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $fileName = $file->getClientOriginalName();
            $fileSizeKb = round($file->getSize() / 1024, 2);
            $filePath = $file->store('assignments', 'public');
        }
        
        // Create or update submission
        $submission = AssignmentSubmission::updateOrCreate(
            [
                'assignment_id' => $id,
                'user_id' => $user->id,
            ],
            [
                'submission_text' => $request->submission_text,
                'file_path' => $filePath,
                'file_name' => $fileName,
                'file_size_kb' => $fileSizeKb,
                'submitted_at' => now(),
                'status' => 'submitted',
                'is_late' => $isLate,
            ]
        );
        
        // Log assignment submission activity
        ActivityLogger::logActivity($user->id, 'assignment', 0);
        
        return response()->json([
            'success' => true,
            'message' => 'Assignment submitted successfully',
            'data' => [
                'submission_id' => $submission->id,
                'assignment_id' => $id,
                'submitted_at' => $submission->submitted_at->toISOString(),
                'is_late' => $submission->is_late,
            ],
        ]);
    }

    /**
     * Get submission details
     */
    public function getSubmission($id)
    {
        $user = Auth::user();
        
        $assignment = ModuleAssignment::with('module')->findOrFail($id);
        
        // Check enrollment
        $enrollment = Enrollment::where('learner_id', $user->id)
            ->where('course_id', $assignment->module->course_id)
            ->first();
        
        if (!$enrollment) {
            return response()->json([
                'success' => false,
                'message' => 'You must be enrolled in this course',
            ], 403);
        }
        
        $submission = AssignmentSubmission::where('assignment_id', $id)
            ->where('user_id', $user->id)
            ->with('grader:id,name,email')
            ->first();
        
        if (!$submission) {
            return response()->json([
                'success' => false,
                'message' => 'No submission found for this assignment',
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $submission->id,
                'assignment_id' => $assignment->id,
                'assignment_title' => $assignment->title,
                'submission_text' => $submission->submission_text,
                'file_path' => $submission->file_path ? url('storage/' . $submission->file_path) : null,
                'file_name' => $submission->file_name,
                'file_size_kb' => $submission->file_size_kb,
                'submitted_at' => $submission->submitted_at->toISOString(),
                'status' => $submission->status,
                'marks_obtained' => $submission->marks_obtained,
                'max_marks' => $assignment->max_marks,
                'feedback' => $submission->feedback,
                'graded_at' => $submission->graded_at ? $submission->graded_at->toISOString() : null,
                'graded_by' => $submission->grader ? [
                    'id' => $submission->grader->id,
                    'name' => $submission->grader->name,
                    'email' => $submission->grader->email,
                ] : null,
                'is_late' => $submission->is_late,
            ],
        ]);
    }
}
