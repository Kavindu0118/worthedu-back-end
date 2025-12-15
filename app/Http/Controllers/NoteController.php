<?php

namespace App\Http\Controllers;

use App\Models\CourseModule;
use App\Models\ModuleNote;
use App\Http\Resources\ModuleNoteResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

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
        if (!$instructor || $course->instructor_id !== $instructor->instructor_id) {
            return response()->json(['message' => 'You can only add notes to your own courses'], 403);
        }

        // Log request for debugging
        \Log::info('=== Note Store Request ===', [
            'moduleId' => $moduleId,
            'note_title' => $request->input('note_title'),
            'note_body' => $request->input('note_body'),
            'has_attachment' => $request->hasFile('attachment'),
            'attachment_in_request' => $request->has('attachment'),
            'file_size' => $request->hasFile('attachment') ? $request->file('attachment')->getSize() : null,
            'file_mime' => $request->hasFile('attachment') ? $request->file('attachment')->getMimeType() : null,
            'all_keys' => array_keys($request->all()),
        ]);

        // Only validate attachment if it exists
        $rules = [
            'note_title' => 'required|string|max:255',
            'note_body' => 'required|string',
        ];

        // Only add attachment validation if file is actually present
        if ($request->hasFile('attachment')) {
            $rules['attachment'] = 'file|max:102400|mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,mp4,mov,avi,mkv,webm,zip,rar';
        }

        $validator = Validator::make($request->all(), $rules, [
            'attachment.max' => 'The file must not be larger than 100MB.',
            'attachment.mimes' => 'The file must be one of: PDF, DOC, DOCX, PPT, PPTX, XLS, XLSX, MP4, MOV, AVI, MKV, WEBM, ZIP, RAR.',
        ]);

        if ($validator->fails()) {
            \Log::warning('Note validation failed', [
                'errors' => $validator->errors()->toArray(),
            ]);
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        $attachmentUrl = null;
        $attachmentType = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            
            // Validate file uploaded successfully
            if (!$file->isValid()) {
                \Log::error('File upload failed', [
                    'error' => $file->getErrorMessage(),
                    'error_code' => $file->getError(),
                ]);
                return response()->json([
                    'message' => 'File upload failed',
                    'errors' => ['attachment' => ['The file failed to upload. Please try again.']]
                ], 422);
            }
            
            $extension = $file->getClientOriginalExtension();
            $filename = time() . '_' . uniqid() . '.' . $extension;
            $path = $file->storeAs('course-attachments', $filename, 'public');
            $attachmentUrl = asset('storage/' . $path);
            $attachmentType = $file->getMimeType();
            
            \Log::info('File uploaded successfully', [
                'path' => $path,
                'url' => $attachmentUrl,
            ]);
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
        if (!$instructor || $course->instructor_id !== $instructor->instructor_id) {
            return response()->json(['message' => 'You can only update your own notes'], 403);
        }

        $data = $request->validate([
            'note_title' => 'sometimes|string|max:255',
            'note_body' => 'sometimes|string',
            'attachment' => 'nullable|file|max:102400|mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,mp4,mov,avi,mkv,webm,zip,rar', // 100MB max
        ]);

        // Handle file upload
        if ($request->hasFile('attachment')) {
            // Delete old file if exists
            if ($note->attachment_url) {
                $oldPath = str_replace(asset('storage/'), '', $note->attachment_url);
                Storage::disk('public')->delete($oldPath);
            }

            $file = $request->file('attachment');
            $extension = $file->getClientOriginalExtension();
            $filename = time() . '_' . uniqid() . '.' . $extension;
            $path = $file->storeAs('course-attachments', $filename, 'public');
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
        if (!$instructor || $course->instructor_id !== $instructor->instructor_id) {
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
