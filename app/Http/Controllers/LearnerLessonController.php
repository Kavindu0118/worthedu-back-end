<?php

namespace App\Http\Controllers;

use App\Models\CourseModule;
use App\Models\LessonProgress;
use App\Models\Enrollment;
use App\Helpers\ProgressHelper;
use App\Helpers\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class LearnerLessonController extends Controller
{
    /**
     * Get lesson/module content with progress
     */
    public function show($id)
    {
        $user = Auth::user();
        
        $module = CourseModule::with(['course:id,title', 'notes'])->findOrFail($id);
        
        // Check if user is enrolled in the course
        $enrollment = Enrollment::where('learner_id', $user->id)
            ->where('course_id', $module->course_id)
            ->first();
        
        if (!$enrollment) {
            return response()->json([
                'success' => false,
                'message' => 'You must be enrolled in this course to view lessons',
            ], 403);
        }
        
        // Update last accessed time
        $enrollment->update(['last_accessed_at' => now()]);
        
        // Get progress
        $progress = LessonProgress::where('user_id', $user->id)
            ->where('lesson_id', $id)
            ->first();
        
        // Get next and previous lessons
        $nextModule = CourseModule::where('course_id', $module->course_id)
            ->where('order_index', '>', $module->order_index)
            ->orderBy('order_index')
            ->first();
        
        $prevModule = CourseModule::where('course_id', $module->course_id)
            ->where('order_index', '<', $module->order_index)
            ->orderBy('order_index', 'desc')
            ->first();
        
        // Format notes for learner
        $notes = $module->notes->map(function($note) {
            return [
                'id' => $note->id,
                'title' => $note->note_title,
                'body' => $note->note_body,
                'attachment_url' => $note->full_attachment_url,
                'attachment_name' => $note->attachment_name,
                'created_at' => $note->created_at->toISOString(),
            ];
        });
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $module->id,
                'course_id' => $module->course_id,
                'course_title' => $module->course->title,
                'title' => $module->module_title,
                'description' => $module->module_description,
                'type' => $module->type ?? 'reading',
                'content_url' => $module->content_url,
                'content_text' => $module->content_text,
                'duration' => $module->duration,
                'duration_minutes' => $module->duration_minutes,
                'order_index' => $module->order_index,
                'is_mandatory' => $module->is_mandatory ?? true,
                'notes' => $notes,
                'progress' => $progress ? [
                    'status' => $progress->status,
                    'started_at' => $progress->started_at ? $progress->started_at->toISOString() : null,
                    'completed_at' => $progress->completed_at ? $progress->completed_at->toISOString() : null,
                    'time_spent_minutes' => $progress->time_spent_minutes,
                    'last_position' => $progress->last_position,
                ] : [
                    'status' => 'not_started',
                    'started_at' => null,
                    'completed_at' => null,
                    'time_spent_minutes' => 0,
                    'last_position' => null,
                ],
                'navigation' => [
                    'next_module' => $nextModule ? [
                        'id' => $nextModule->id,
                        'title' => $nextModule->module_title,
                    ] : null,
                    'previous_module' => $prevModule ? [
                        'id' => $prevModule->id,
                        'title' => $prevModule->module_title,
                    ] : null,
                ],
            ],
        ]);
    }

    /**
     * Start a lesson (mark as in progress)
     */
    public function start($id)
    {
        $user = Auth::user();
        
        $module = CourseModule::findOrFail($id);
        
        // Check enrollment
        $enrollment = Enrollment::where('learner_id', $user->id)
            ->where('course_id', $module->course_id)
            ->first();
        
        if (!$enrollment) {
            return response()->json([
                'success' => false,
                'message' => 'You must be enrolled in this course',
            ], 403);
        }
        
        // Create or update progress
        $progress = LessonProgress::updateOrCreate(
            [
                'user_id' => $user->id,
                'lesson_id' => $id,
            ],
            [
                'status' => 'in_progress',
                'started_at' => now(),
            ]
        );
        
        return response()->json([
            'success' => true,
            'message' => 'Lesson started',
            'data' => [
                'lesson_id' => $id,
                'status' => $progress->status,
                'started_at' => $progress->started_at->toISOString(),
            ],
        ]);
    }

    /**
     * Update lesson progress (time spent, video position, etc.)
     */
    public function updateProgress($id, Request $request)
    {
        $user = Auth::user();
        
        $validator = Validator::make($request->all(), [
            'time_spent_minutes' => 'nullable|integer|min:0',
            'last_position' => 'nullable|string|max:50',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }
        
        $module = CourseModule::findOrFail($id);
        
        // Check enrollment
        $enrollment = Enrollment::where('learner_id', $user->id)
            ->where('course_id', $module->course_id)
            ->first();
        
        if (!$enrollment) {
            return response()->json([
                'success' => false,
                'message' => 'You must be enrolled in this course',
            ], 403);
        }
        
        // Find or create progress
        $progress = LessonProgress::firstOrCreate(
            [
                'user_id' => $user->id,
                'lesson_id' => $id,
            ],
            [
                'status' => 'in_progress',
                'started_at' => now(),
            ]
        );
        
        // Update progress
        $updateData = [];
        
        if ($request->has('time_spent_minutes')) {
            $updateData['time_spent_minutes'] = $request->time_spent_minutes;
        }
        
        if ($request->has('last_position')) {
            $updateData['last_position'] = $request->last_position;
        }
        
        if (!empty($updateData)) {
            $progress->update($updateData);
        }
        
        // Update enrollment last accessed
        $enrollment->update(['last_accessed_at' => now()]);
        
        return response()->json([
            'success' => true,
            'message' => 'Progress updated',
            'data' => [
                'lesson_id' => $id,
                'time_spent_minutes' => $progress->time_spent_minutes,
                'last_position' => $progress->last_position,
            ],
        ]);
    }

    /**
     * Complete a lesson
     */
    public function complete($id)
    {
        $user = Auth::user();
        
        $module = CourseModule::findOrFail($id);
        
        // Check enrollment
        $enrollment = Enrollment::where('learner_id', $user->id)
            ->where('course_id', $module->course_id)
            ->first();
        
        if (!$enrollment) {
            return response()->json([
                'success' => false,
                'message' => 'You must be enrolled in this course',
            ], 403);
        }
        
        // Update or create progress
        $progress = LessonProgress::updateOrCreate(
            [
                'user_id' => $user->id,
                'lesson_id' => $id,
            ],
            [
                'status' => 'completed',
                'completed_at' => now(),
            ]
        );
        
        // If this is the first time marking as started, set started_at
        if (!$progress->started_at) {
            $progress->update(['started_at' => now()]);
        }
        
        // Log lesson completion activity
        ActivityLogger::logActivity($user->id, 'lesson', $progress->time_spent_minutes ?? 0);
        
        // Recalculate course progress
        ProgressHelper::updateEnrollmentProgress($user->id, $module->course_id);
        
        return response()->json([
            'success' => true,
            'message' => 'Lesson completed',
            'data' => [
                'lesson_id' => $id,
                'status' => 'completed',
                'completed_at' => $progress->completed_at->toISOString(),
            ],
        ]);
    }
}
