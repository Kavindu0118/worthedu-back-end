<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseModule;
use App\Http\Resources\CourseModuleResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ModuleController extends Controller
{
    /**
     * Add a module to a course
     */
    public function store(Request $request, $courseId)
    {
        $course = Course::findOrFail($courseId);

        // Check if user owns this course
        $user = Auth::user();
        if (!$user || $user->role !== 'instructor') {
            return response()->json(['message' => 'Only instructors can add modules'], 403);
        }

        $instructor = $user->instructor;
        if ($course->instructor_id !== $instructor->instructor_id) {
            return response()->json(['message' => 'You can only add modules to your own courses'], 403);
        }

        $data = $request->validate([
            'module_title' => 'required|string|max:255',
            'module_description' => 'nullable|string',
            'order_index' => 'nullable|integer',
            'duration' => 'nullable|string',
        ]);

        $module = CourseModule::create([
            'course_id' => $courseId,
            'module_title' => $data['module_title'],
            'module_description' => $data['module_description'] ?? null,
            'order_index' => $data['order_index'] ?? 0,
            'duration' => $data['duration'] ?? null,
        ]);

        return response()->json([
            'message' => 'Module added successfully',
            'module' => new CourseModuleResource($module),
        ], 201);
    }

    /**
     * Update a module
     */
    public function update(Request $request, $id)
    {
        $module = CourseModule::findOrFail($id);
        $course = $module->course;

        // Check ownership
        $user = Auth::user();
        if (!$user || $user->role !== 'instructor') {
            return response()->json(['message' => 'Only instructors can update modules'], 403);
        }

        $instructor = $user->instructor;
        if ($course->instructor_id !== $instructor->instructor_id) {
            return response()->json(['message' => 'You can only update your own modules'], 403);
        }

        $data = $request->validate([
            'module_title' => 'sometimes|string|max:255',
            'module_description' => 'nullable|string',
            'order_index' => 'sometimes|integer',
            'duration' => 'nullable|string',
        ]);

        $module->update($data);

        return response()->json([
            'message' => 'Module updated successfully',
            'module' => new CourseModuleResource($module),
        ]);
    }

    /**
     * Delete a module
     */
    public function destroy($id)
    {
        $module = CourseModule::findOrFail($id);
        $course = $module->course;

        // Check ownership
        $user = Auth::user();
        if (!$user || $user->role !== 'instructor') {
            return response()->json(['message' => 'Only instructors can delete modules'], 403);
        }

        $instructor = $user->instructor;
        if ($course->instructor_id !== $instructor->instructor_id) {
            return response()->json(['message' => 'You can only delete your own modules'], 403);
        }

        $module->delete();

        return response()->json([
            'message' => 'Module deleted successfully',
        ]);
    }
}
