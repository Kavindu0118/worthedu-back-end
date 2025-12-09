<?php

namespace App\Http\Controllers;

use App\Models\CourseModule;
use App\Models\ModuleAssignment;
use App\Http\Resources\ModuleAssignmentResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AssignmentController extends Controller
{
    /**
     * Add an assignment to a module
     */
    public function store(Request $request, $moduleId)
    {
        try {
            \Log::info('=== Assignment Store Request ===', [
                'moduleId' => $moduleId,
                'request_data' => $request->all(),
                'has_file' => $request->hasFile('attachment'),
            ]);

            $module = CourseModule::findOrFail($moduleId);
            $course = $module->course;

            // Check ownership
            $user = Auth::user();
            if (!$user || $user->role !== 'instructor') {
                \Log::warning('Assignment store: Not an instructor');
                return response()->json(['message' => 'Only instructors can add assignments'], 403);
            }

            $instructor = $user->instructor;
            if ($course->instructor_id !== $instructor->instructor_id) {
                \Log::warning('Assignment store: Not the course owner');
                return response()->json(['message' => 'You can only add assignments to your own courses'], 403);
            }

            $data = $request->validate([
                'assignment_title' => 'required|string|max:255',
                'instructions' => 'nullable|string',
                'attachment' => 'nullable|file|max:10240', // 10MB max
                'max_points' => 'nullable|integer',
                'due_date' => 'nullable|date',
            ]);

            \Log::info('Assignment validation passed', ['data' => $data]);

            $attachmentUrl = null;
            if ($request->hasFile('attachment')) {
                $path = $request->file('attachment')->store('course-attachments', 'public');
                $attachmentUrl = asset('storage/' . $path);
                \Log::info('Attachment uploaded', ['path' => $path]);
            }

            $assignment = ModuleAssignment::create([
                'module_id' => $moduleId,
                'assignment_title' => $data['assignment_title'],
                'instructions' => $data['instructions'] ?? null,
                'attachment_url' => $attachmentUrl,
                'max_points' => $data['max_points'] ?? 100,
                'due_date' => $data['due_date'] ?? null,
            ]);

            \Log::info('Assignment created', ['assignment_id' => $assignment->id]);

            return response()->json([
                'message' => 'Assignment added successfully',
                'assignment' => new ModuleAssignmentResource($assignment),
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Assignment validation error', ['errors' => $e->errors()]);
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Assignment store error', [
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
            'attachment' => 'nullable|file|max:10240',
            'max_points' => 'sometimes|integer',
            'due_date' => 'nullable|date',
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
