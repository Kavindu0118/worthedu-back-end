<?php

namespace App\Http\Controllers;

use App\Models\ModuleAssignment;
use App\Models\AssignmentSubmission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class InstructorSubmissionController extends Controller
{
    /**
     * Get all submissions for a specific assignment
     * 
     * @param int $assignmentId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAssignmentSubmissions($assignmentId)
    {
        try {
            // Find the assignment
            $assignment = ModuleAssignment::with(['module.course'])->find($assignmentId);

            if (!$assignment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Assignment not found',
                ], 404);
            }

            // Get all submissions for this assignment with student details
            $submissions = AssignmentSubmission::where('assignment_id', $assignmentId)
                ->with(['user' => function($query) {
                    $query->select('id', 'name', 'username', 'email', 'avatar');
                }])
                ->orderBy('submitted_at', 'desc')
                ->get()
                ->map(function ($submission) use ($assignment) {
                    // Calculate grade letter
                    $grade = null;
                    if ($submission->marks_obtained !== null && $assignment->max_points > 0) {
                        $percentage = ($submission->marks_obtained / $assignment->max_points) * 100;
                        $grade = $this->calculateGradeLetter($percentage);
                    }

                    return [
                        'id' => $submission->id,
                        'assignment_id' => $submission->assignment_id,
                        'student_id' => $submission->user_id,
                        'student_name' => $submission->user->name ?? 'Unknown',
                        'student_username' => $submission->user->username ?? null,
                        'student_email' => $submission->user->email ?? null,
                        'student_avatar' => $submission->user->avatar ?? null,
                        'submission_text' => $submission->submission_text,
                        'file_path' => $submission->file_path,
                        'file_name' => $submission->file_name,
                        'file_size_kb' => $submission->file_size_kb,
                        'submitted_at' => $submission->submitted_at,
                        'status' => $submission->status,
                        'marks_obtained' => $submission->marks_obtained,
                        'max_points' => $assignment->max_points,
                        'grade' => $grade,
                        'feedback' => $submission->feedback,
                        'graded_by' => $submission->graded_by,
                        'graded_at' => $submission->graded_at,
                        'is_late' => $submission->is_late,
                        'created_at' => $submission->created_at,
                        'updated_at' => $submission->updated_at,
                    ];
                });

            // Get statistics
            $stats = [
                'total_submissions' => $submissions->count(),
                'graded' => $submissions->where('status', 'graded')->count(),
                'pending' => $submissions->where('status', 'submitted')->count(),
                'average_marks' => $submissions->where('marks_obtained', '!=', null)->avg('marks_obtained'),
            ];

            return response()->json([
                'success' => true,
                'assignment' => [
                    'id' => $assignment->id,
                    'title' => $assignment->assignment_title,
                    'instructions' => $assignment->instructions,
                    'max_points' => $assignment->max_points,
                    'due_date' => $assignment->due_date,
                    'module_title' => $assignment->module->module_title ?? 'Unknown',
                    'course_title' => $assignment->module->course->title ?? 'Unknown',
                ],
                'statistics' => $stats,
                'submissions' => $submissions->values(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving submissions',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get single submission details
     * 
     * @param int $submissionId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSubmissionDetails($submissionId)
    {
        try {
            $submission = AssignmentSubmission::with([
                'user' => function($query) {
                    $query->select('id', 'name', 'username', 'email', 'avatar', 'phone');
                },
                'assignment.module.course'
            ])->find($submissionId);

            if (!$submission) {
                return response()->json([
                    'success' => false,
                    'message' => 'Submission not found',
                ], 404);
            }

            $assignment = $submission->assignment;
            
            // Calculate grade
            $grade = null;
            $percentage = null;
            if ($submission->marks_obtained !== null && $assignment->max_points > 0) {
                $percentage = ($submission->marks_obtained / $assignment->max_points) * 100;
                $grade = $this->calculateGradeLetter($percentage);
            }

            return response()->json([
                'success' => true,
                'submission' => [
                    'id' => $submission->id,
                    'assignment_id' => $submission->assignment_id,
                    'assignment_title' => $assignment->assignment_title,
                    'assignment_instructions' => $assignment->instructions,
                    'max_points' => $assignment->max_points,
                    'due_date' => $assignment->due_date,
                    'student' => [
                        'id' => $submission->user->id,
                        'name' => $submission->user->name,
                        'username' => $submission->user->username,
                        'email' => $submission->user->email,
                        'avatar' => $submission->user->avatar,
                        'phone' => $submission->user->phone ?? null,
                    ],
                    'submission_text' => $submission->submission_text,
                    'file_path' => $submission->file_path,
                    'file_name' => $submission->file_name,
                    'file_size_kb' => $submission->file_size_kb,
                    'submitted_at' => $submission->submitted_at,
                    'status' => $submission->status,
                    'marks_obtained' => $submission->marks_obtained,
                    'percentage' => $percentage ? round($percentage, 2) : null,
                    'grade' => $grade,
                    'feedback' => $submission->feedback,
                    'graded_by' => $submission->graded_by,
                    'graded_at' => $submission->graded_at,
                    'is_late' => $submission->is_late,
                    'created_at' => $submission->created_at,
                    'updated_at' => $submission->updated_at,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving submission details',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Grade a submission
     * 
     * @param Request $request
     * @param int $submissionId
     * @return \Illuminate\Http\JsonResponse
     */
    public function gradeSubmission(Request $request, $submissionId)
    {
        try {
            // Find the submission
            $submission = AssignmentSubmission::with(['assignment', 'user'])->find($submissionId);

            if (!$submission) {
                return response()->json([
                    'success' => false,
                    'message' => 'Submission not found',
                ], 404);
            }

            // Validate the request
            $validated = $request->validate([
                'marks_obtained' => 'required|numeric|min:0|max:' . $submission->assignment->max_points,
                'feedback' => 'nullable|string|max:2000',
                'grade' => 'nullable|string|in:A,A-,B+,B,B-,C+,C,C-,D+,D,F',
            ]);

            // Update the submission
            $submission->marks_obtained = $validated['marks_obtained'];
            $submission->feedback = $validated['feedback'] ?? null;
            $submission->status = 'graded';
            $submission->graded_by = auth()->id() ?? 1; // Use authenticated user ID
            $submission->graded_at = now();
            $submission->save();

            // Calculate grade if not provided
            $percentage = ($validated['marks_obtained'] / $submission->assignment->max_points) * 100;
            $calculatedGrade = $validated['grade'] ?? $this->calculateGradeLetter($percentage);

            return response()->json([
                'success' => true,
                'message' => 'Submission graded successfully',
                'submission' => [
                    'id' => $submission->id,
                    'student_name' => $submission->user->name,
                    'student_email' => $submission->user->email,
                    'assignment_title' => $submission->assignment->assignment_title,
                    'marks_obtained' => $submission->marks_obtained,
                    'max_points' => $submission->assignment->max_points,
                    'percentage' => round($percentage, 2),
                    'grade' => $calculatedGrade,
                    'feedback' => $submission->feedback,
                    'status' => $submission->status,
                    'graded_at' => $submission->graded_at,
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
                'message' => 'Error grading submission',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download submission file
     * 
     * @param int $submissionId
     * @return \Illuminate\Http\Response
     */
    public function downloadSubmissionFile($submissionId)
    {
        try {
            $submission = AssignmentSubmission::find($submissionId);

            if (!$submission) {
                return response()->json([
                    'success' => false,
                    'message' => 'Submission not found',
                ], 404);
            }

            if (!$submission->file_path || !Storage::exists($submission->file_path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Submission file not found',
                ], 404);
            }

            $filePath = storage_path('app/' . $submission->file_path);
            $fileName = $submission->file_name ?? 'submission_file';

            return response()->download($filePath, $fileName);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error downloading submission file',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get submissions for all assignments in a module
     * 
     * @param int $moduleId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getModuleSubmissions($moduleId)
    {
        try {
            // Get all assignments for this module
            $assignments = ModuleAssignment::where('module_id', $moduleId)
                ->with(['submissions.user'])
                ->get();

            if ($assignments->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No assignments found for this module',
                    'assignments' => [],
                ], 200);
            }

            $assignmentsData = $assignments->map(function ($assignment) {
                $submissions = $assignment->submissions;
                
                return [
                    'assignment_id' => $assignment->id,
                    'assignment_title' => $assignment->assignment_title,
                    'max_points' => $assignment->max_points,
                    'due_date' => $assignment->due_date,
                    'total_submissions' => $submissions->count(),
                    'graded_submissions' => $submissions->where('status', 'graded')->count(),
                    'pending_submissions' => $submissions->where('status', 'submitted')->count(),
                    'average_marks' => $submissions->where('marks_obtained', '!=', null)->avg('marks_obtained'),
                ];
            });

            return response()->json([
                'success' => true,
                'module_id' => $moduleId,
                'assignments' => $assignmentsData->values(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving module submissions',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Calculate grade letter based on percentage
     * 
     * @param float $percentage
     * @return string
     */
    private function calculateGradeLetter($percentage)
    {
        if ($percentage >= 93) return 'A';
        if ($percentage >= 90) return 'A-';
        if ($percentage >= 87) return 'B+';
        if ($percentage >= 83) return 'B';
        if ($percentage >= 80) return 'B-';
        if ($percentage >= 77) return 'C+';
        if ($percentage >= 73) return 'C';
        if ($percentage >= 70) return 'C-';
        if ($percentage >= 67) return 'D+';
        if ($percentage >= 60) return 'D';
        return 'F';
    }
}
