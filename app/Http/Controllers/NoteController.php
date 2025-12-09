<?php

namespace App\Http\Controllers;

use App\Models\CourseModule;
use App\Models\ModuleNote;
use App\Http\Resources\ModuleNoteResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class NoteController extends Controller
{
    /**
     * Add a note to a module
     */
    public function store(Request $request, $moduleId)
    {
        $module = CourseModule::findOrFail($moduleId);
        $course = $module->course;

        // Check ownership
        $user = Auth::user();
        if (!$user || $user->role !== 'instructor') {
            return response()->json(['message' => 'Only instructors can add notes'], 403);
        }

        $instructor = $user->instructor;
        if ($course->instructor_id !== $instructor->instructor_id) {
            return response()->json(['message' => 'You can only add notes to your own courses'], 403);
        }

        $data = $request->validate([
            'note_title' => 'required|string|max:255',
            'note_body' => 'required|string',
            'attachment' => 'nullable|file|max:10240', // 10MB max
        ]);

        $attachmentUrl = null;
        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('course-attachments', 'public');
            $attachmentUrl = asset('storage/' . $path);
        }

        $note = ModuleNote::create([
            'module_id' => $moduleId,
            'note_title' => $data['note_title'],
            'note_body' => $data['note_body'],
            'attachment_url' => $attachmentUrl,
        ]);

        return response()->json([
            'message' => 'Note added successfully',
            'note' => new ModuleNoteResource($note),
        ], 201);
    }

    /**
     * Update a note
     */
    public function update(Request $request, $id)
    {
        $note = ModuleNote::findOrFail($id);
        $course = $note->module->course;

        // Check ownership
        $user = Auth::user();
        if (!$user || $user->role !== 'instructor') {
            return response()->json(['message' => 'Only instructors can update notes'], 403);
        }

        $instructor = $user->instructor;
        if ($course->instructor_id !== $instructor->instructor_id) {
            return response()->json(['message' => 'You can only update your own notes'], 403);
        }

        $data = $request->validate([
            'note_title' => 'sometimes|string|max:255',
            'note_body' => 'sometimes|string',
            'attachment' => 'nullable|file|max:10240',
        ]);

        // Handle file upload
        if ($request->hasFile('attachment')) {
            // Delete old file if exists
            if ($note->attachment_url) {
                $oldPath = str_replace(asset('storage/'), '', $note->attachment_url);
                Storage::disk('public')->delete($oldPath);
            }

            $path = $request->file('attachment')->store('course-attachments', 'public');
            $data['attachment_url'] = asset('storage/' . $path);
        }

        $note->update($data);

        return response()->json([
            'message' => 'Note updated successfully',
            'note' => new ModuleNoteResource($note),
        ]);
    }

    /**
     * Delete a note
     */
    public function destroy($id)
    {
        $note = ModuleNote::findOrFail($id);
        $course = $note->module->course;

        // Check ownership
        $user = Auth::user();
        if (!$user || $user->role !== 'instructor') {
            return response()->json(['message' => 'Only instructors can delete notes'], 403);
        }

        $instructor = $user->instructor;
        if ($course->instructor_id !== $instructor->instructor_id) {
            return response()->json(['message' => 'You can only delete your own notes'], 403);
        }

        // Delete file if exists
        if ($note->attachment_url) {
            $path = str_replace(asset('storage/'), '', $note->attachment_url);
            Storage::disk('public')->delete($path);
        }

        $note->delete();

        return response()->json([
            'message' => 'Note deleted successfully',
        ]);
    }
}
