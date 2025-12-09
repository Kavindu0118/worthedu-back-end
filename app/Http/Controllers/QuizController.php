<?php

namespace App\Http\Controllers;

use App\Models\CourseModule;
use App\Models\ModuleQuiz;
use App\Http\Resources\ModuleQuizResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QuizController extends Controller
{
    /**
     * Add a quiz to a module
     */
    public function store(Request $request, $moduleId)
    {
        $module = CourseModule::findOrFail($moduleId);
        $course = $module->course;

        // Check ownership
        $user = Auth::user();
        if (!$user || $user->role !== 'instructor') {
            return response()->json(['message' => 'Only instructors can add quizzes'], 403);
        }

        $instructor = $user->instructor;
        if ($course->instructor_id !== $instructor->instructor_id) {
            return response()->json(['message' => 'You can only add quizzes to your own courses'], 403);
        }

        $data = $request->validate([
            'quiz_title' => 'required|string|max:255',
            'quiz_description' => 'nullable|string',
            'quiz_data' => 'required|array',
            'total_points' => 'nullable|integer',
            'time_limit' => 'nullable|integer',
        ]);

        $quiz = ModuleQuiz::create([
            'module_id' => $moduleId,
            'quiz_title' => $data['quiz_title'],
            'quiz_description' => $data['quiz_description'] ?? null,
            'quiz_data' => $data['quiz_data'],
            'total_points' => $data['total_points'] ?? 0,
            'time_limit' => $data['time_limit'] ?? null,
        ]);

        return response()->json([
            'message' => 'Quiz added successfully',
            'quiz' => new ModuleQuizResource($quiz),
        ], 201);
    }

    /**
     * Update a quiz
     */
    public function update(Request $request, $id)
    {
        $quiz = ModuleQuiz::findOrFail($id);
        $course = $quiz->module->course;

        // Check ownership
        $user = Auth::user();
        if (!$user || $user->role !== 'instructor') {
            return response()->json(['message' => 'Only instructors can update quizzes'], 403);
        }

        $instructor = $user->instructor;
        if ($course->instructor_id !== $instructor->instructor_id) {
            return response()->json(['message' => 'You can only update your own quizzes'], 403);
        }

        $data = $request->validate([
            'quiz_title' => 'sometimes|string|max:255',
            'quiz_description' => 'nullable|string',
            'quiz_data' => 'sometimes|array',
            'total_points' => 'sometimes|integer',
            'time_limit' => 'nullable|integer',
        ]);

        $quiz->update($data);

        return response()->json([
            'message' => 'Quiz updated successfully',
            'quiz' => new ModuleQuizResource($quiz),
        ]);
    }

    /**
     * Delete a quiz
     */
    public function destroy($id)
    {
        $quiz = ModuleQuiz::findOrFail($id);
        $course = $quiz->module->course;

        // Check ownership
        $user = Auth::user();
        if (!$user || $user->role !== 'instructor') {
            return response()->json(['message' => 'Only instructors can delete quizzes'], 403);
        }

        $instructor = $user->instructor;
        if ($course->instructor_id !== $instructor->instructor_id) {
            return response()->json(['message' => 'You can only delete your own quizzes'], 403);
        }

        $quiz->delete();

        return response()->json([
            'message' => 'Quiz deleted successfully',
        ]);
    }
}
