<?php

namespace App\Http\Controllers;

use App\Models\CourseModule;
use App\Models\ModuleAssignment;
use App\Http\Resources\ModuleAssignmentResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AssignmentController extends Controller
{
    /**
     * Add an assignment to a module
     */
    public function store(Request $request, $moduleId)
    {
        try {
            Log::info('=== Assignment Store Request ===', [
                'moduleId' => $moduleId,
                'request_data' => $request->all(),
                'has_file' => $request->hasFile('attachment'),
            ]);

            $module = CourseModule::findOrFail($moduleId);
            $course = $module->course;

            // Check ownership
            $user = Auth::user();
            if (!$user || $user->role !== 'instructor') {
                Log::warning('Assignment store: Not an instructor');
                return response()->json(['message' => 'Only instructors can add assignments'], 403);
            }

            $instructor = $user->instructor;
            if ($course->instructor_id !== $instructor->instructor_id) {
                Log::warning('Assignment store: Not the course owner');
                return response()->json(['message' => 'You can only add assignments to your own courses'], 403);
            }

            $data = $request->validate([
                'assignment_title' => 'required|string|max:255',
                'instructions' => 'nullable|string',
                'submission_type' => 'nullable|in:file,text,both',
                'attachment' => 'nullable|file|max:10240', // 10MB max
                'max_points' => 'nullable|integer|min:0',
                'due_date' => 'nullable|date',
                
                // File upload restrictions
                'allowed_file_types' => 'nullable|string', // Comma-separated: pdf,doc,docx
                'max_file_size_mb' => 'nullable|integer|min:1|max:100',
                'max_files' => 'nullable|integer|min:1|max:10',
                
                // Late submission policy
                'allow_late_submission' => 'nullable|boolean',
                'late_submission_deadline' => 'nullable|date|after:due_date',
                'late_penalty_percent' => 'nullable|numeric|min:0|max:100',
                
                // Grading settings
                'require_rubric' => 'nullable|boolean',
                'peer_review_enabled' => 'nullable|boolean',
                'peer_reviews_required' => 'nullable|integer|min:1',
                'grading_criteria' => 'nullable|string',
                
                // Availability settings
                'available_from' => 'nullable|date',
                'show_after_due_date' => 'nullable|boolean',
                
                // Text submission settings
                'min_words' => 'nullable|integer|min:0',
                'max_words' => 'nullable|integer|min:1',
            ]);

            Log::info('Assignment validation passed', ['data' => $data]);

            $attachmentUrl = null;
            if ($request->hasFile('attachment')) {
                $path = $request->file('attachment')->store('course-attachments', 'public');
                $attachmentUrl = asset('storage/' . $path);
                Log::info('Attachment uploaded', ['path' => $path]);
            }

            $assignment = ModuleAssignment::create([
                'module_id' => $moduleId,
                'assignment_title' => $data['assignment_title'],
                'instructions' => $data['instructions'] ?? null,
                'submission_type' => $data['submission_type'] ?? 'file',
                'attachment_url' => $attachmentUrl,
                'max_points' => $data['max_points'] ?? 100,
                'due_date' => $data['due_date'] ?? null,
                
                // File restrictions
                'allowed_file_types' => $data['allowed_file_types'] ?? null,
                'max_file_size_mb' => $data['max_file_size_mb'] ?? 10,
                'max_files' => $data['max_files'] ?? 1,
                
                // Late submission
                'allow_late_submission' => $data['allow_late_submission'] ?? false,
                'late_submission_deadline' => $data['late_submission_deadline'] ?? null,
                'late_penalty_percent' => $data['late_penalty_percent'] ?? 0,
                
                // Grading
                'require_rubric' => $data['require_rubric'] ?? false,
                'peer_review_enabled' => $data['peer_review_enabled'] ?? false,
                'peer_reviews_required' => $data['peer_reviews_required'] ?? null,
                'grading_criteria' => $data['grading_criteria'] ?? null,
                
                // Availability
                'available_from' => $data['available_from'] ?? null,
                'show_after_due_date' => $data['show_after_due_date'] ?? true,
                
                // Text settings
                'min_words' => $data['min_words'] ?? null,
                'max_words' => $data['max_words'] ?? null,
            ]);

            Log::info('Assignment created', ['assignment_id' => $assignment->id]);

            return response()->json([
                'message' => 'Assignment added successfully',
                'assignment' => new ModuleAssignmentResource($assignment),
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Assignment validation error', ['errors' => $e->errors()]);
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Assignment store error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Failed to add assignment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update an assignment
     */
    public function update(Request $request, $id)
    {
        $assignment = ModuleAssignment::findOrFail($id);
        $course = $assignment->module->course;

        // Check ownership
        $user = Auth::user();
        if (!$user || $user->role !== 'instructor') {
            return response()->json(['message' => 'Only instructors can update assignments'], 403);
        }

        $instructor = $user->instructor;
        if ($course->instructor_id !== $instructor->instructor_id) {
            return response()->json(['message' => 'You can only update your own assignments'], 403);
        }

        $data = $request->validate([
            'assignment_title' => 'sometimes|string|max:255',
            'instructions' => 'nullable|string',
            'submission_type' => 'nullable|in:file,text,both',
            'attachment' => 'nullable|file|max:10240',
            'max_points' => 'sometimes|integer|min:0',
            'due_date' => 'nullable|date',
            
            // File upload restrictions
            'allowed_file_types' => 'nullable|string',
            'max_file_size_mb' => 'nullable|integer|min:1|max:100',
            'max_files' => 'nullable|integer|min:1|max:10',
            
            // Late submission policy
            'allow_late_submission' => 'nullable|boolean',
            'late_submission_deadline' => 'nullable|date|after:due_date',
            'late_penalty_percent' => 'nullable|numeric|min:0|max:100',
            
            // Grading settings
            'require_rubric' => 'nullable|boolean',
            'peer_review_enabled' => 'nullable|boolean',
            'peer_reviews_required' => 'nullable|integer|min:1',
            'grading_criteria' => 'nullable|string',
            
            // Availability settings
            'available_from' => 'nullable|date',
            'show_after_due_date' => 'nullable|boolean',
            
            // Text submission settings
            'min_words' => 'nullable|integer|min:0',
            'max_words' => 'nullable|integer|min:1',
        ]);

        // Handle file upload
        if ($request->hasFile('attachment')) {
            // Delete old file if exists
            if ($assignment->attachment_url) {
                $oldPath = str_replace(asset('storage/'), '', $assignment->attachment_url);
                Storage::disk('public')->delete($oldPath);
            }

            $path = $request->file('attachment')->store('course-attachments', 'public');
            $data['attachment_url'] = asset('storage/' . $path);
        }

        $assignment->update($data);

        return response()->json([
            'message' => 'Assignment updated successfully',
            'assignment' => new ModuleAssignmentResource($assignment),
        ]);
    }

    /**
     * Delete an assignment
     */
    public function destroy($id)
    {
        $assignment = ModuleAssignment::findOrFail($id);
        $course = $assignment->module->course;

        // Check ownership
        $user = Auth::user();
        if (!$user || $user->role !== 'instructor') {
            return response()->json(['message' => 'Only instructors can delete assignments'], 403);
        }

        $instructor = $user->instructor;
        if ($course->instructor_id !== $instructor->instructor_id) {
            return response()->json(['message' => 'You can only delete your own assignments'], 403);
        }

        // Delete file if exists
        if ($assignment->attachment_url) {
            $path = str_replace(asset('storage/'), '', $assignment->attachment_url);
            Storage::disk('public')->delete($path);
        }

        $assignment->delete();

        return response()->json([
            'message' => 'Assignment deleted successfully',
        ]);
    }
}
