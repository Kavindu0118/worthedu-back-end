<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TestController extends Controller
{
    /**
     * Get all tests for a course (Instructor)
     */
    public function index(Request $request, $courseId)
    {
        try {
            // TODO: Implement database query when tables are created
            // For now, return empty array
            return response()->json([
                'data' => []
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching tests: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to fetch tests',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific test (Instructor)
     */
    public function show($testId)
    {
        try {
            // TODO: Implement database query when tables are created
            return response()->json([
                'data' => [
                    'id' => $testId,
                    'test_title' => 'Sample Test',
                    'questions' => []
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching test: ' . $e->getMessage());
            return response()->json([
                'message' => 'Test not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Create a new test (Instructor)
     */
    public function store(Request $request)
    {
        try {
            Log::info('Creating test with data:', $request->all());

            // Validate request
            $validated = $request->validate([
                'module_id' => 'required|integer',
                'test_title' => 'required|string|max:255',
                'test_description' => 'required|string',
                'instructions' => 'nullable|string',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after:start_date',
                'time_limit' => 'nullable|integer|min:1',
                'max_attempts' => 'required|integer|min:1',
                'total_marks' => 'required|integer|min:1',
                'passing_marks' => 'nullable|integer|min:0',
                'questions' => 'required|array|min:1',
                'questions.*.question' => 'required|string',
                'questions.*.type' => 'required|in:mcq,descriptive,file_upload',
                'questions.*.points' => 'required|integer|min:1',
                'questions.*.order_index' => 'required|integer|min:0',
            ]);

            // TODO: Save to database when tables are created
            // For now, return mock response
            $mockTest = [
                'id' => rand(1000, 9999),
                'module_id' => $validated['module_id'],
                'test_title' => $validated['test_title'],
                'test_description' => $validated['test_description'],
                'instructions' => $validated['instructions'] ?? '',
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'time_limit' => $validated['time_limit'],
                'max_attempts' => $validated['max_attempts'],
                'total_marks' => $validated['total_marks'],
                'passing_marks' => $validated['passing_marks'] ?? 0,
                'status' => 'draft',
                'visibility_status' => 'hidden',
                'results_published' => false,
                'questions' => $validated['questions'],
                'created_at' => now()->toIso8601String(),
                'updated_at' => now()->toIso8601String(),
            ];

            Log::info('Test created successfully (mock):', ['test_id' => $mockTest['id']]);

            return response()->json([
                'message' => 'Test created successfully',
                'data' => $mockTest
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Test validation failed:', $e->errors());
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creating test: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to create test',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a test (Instructor)
     */
    public function update(Request $request, $testId)
    {
        try {
            // TODO: Implement database update when tables are created
            return response()->json([
                'message' => 'Test updated successfully',
                'data' => [
                    'id' => $testId,
                    'test_title' => $request->test_title
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error updating test: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to update test',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a test (Instructor)
     */
    public function destroy($testId)
    {
        try {
            // TODO: Implement database deletion when tables are created
            return response()->json([
                'message' => 'Test deleted successfully'
            ], 204);
        } catch (\Exception $e) {
            Log::error('Error deleting test: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to delete test',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get test submissions (Instructor)
     */
    public function getSubmissions($testId)
    {
        try {
            // TODO: Implement database query when tables are created
            return response()->json([
                'data' => []
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching submissions: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to fetch submissions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get submission details (Instructor)
     */
    public function getSubmissionDetails($submissionId)
    {
        try {
            // TODO: Implement database query when tables are created
            return response()->json([
                'data' => [
                    'id' => $submissionId,
                    'answers' => []
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching submission: ' . $e->getMessage());
            return response()->json([
                'message' => 'Submission not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Grade a submission (Instructor)
     */
    public function gradeSubmission(Request $request, $submissionId)
    {
        try {
            // TODO: Implement grading logic when tables are created
            return response()->json([
                'message' => 'Submission graded successfully',
                'data' => [
                    'id' => $submissionId,
                    'grading_status' => 'graded'
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error grading submission: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to grade submission',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Publish/unpublish test results (Instructor)
     */
    public function publishResults(Request $request, $testId)
    {
        try {
            // TODO: Implement publish logic when tables are created
            return response()->json([
                'message' => 'Results published successfully',
                'data' => [
                    'id' => $testId,
                    'results_published' => $request->publish ?? true
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error publishing results: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to publish results',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get test statistics (Instructor)
     */
    public function getStatistics($testId)
    {
        try {
            // TODO: Implement statistics calculation when tables are created
            return response()->json([
                'data' => [
                    'test_id' => $testId,
                    'total_submissions' => 0,
                    'average_score' => 0
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching statistics: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to fetch statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ==================== STUDENT ENDPOINTS ====================

    /**
     * Get student test view
     */
    public function getStudentTest($testId)
    {
        try {
            // TODO: Implement student test view logic when tables are created
            return response()->json([
                'data' => [
                    'test' => [
                        'id' => $testId,
                        'test_title' => 'Sample Test'
                    ],
                    'can_attempt' => true,
                    'remaining_attempts' => 1
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching student test: ' . $e->getMessage());
            return response()->json([
                'message' => 'Test not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Start a test (Student)
     */
    public function startTest($testId)
    {
        try {
            // TODO: Implement test start logic when tables are created
            return response()->json([
                'data' => [
                    'id' => rand(1000, 9999),
                    'test_id' => $testId,
                    'submission_status' => 'in_progress',
                    'started_at' => now()->toIso8601String()
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error starting test: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to start test',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Submit a test (Student)
     */
    public function submitTest(Request $request, $submissionId)
    {
        try {
            // TODO: Implement test submission logic when tables are created
            return response()->json([
                'data' => [
                    'id' => $submissionId,
                    'submission_status' => 'submitted',
                    'submitted_at' => now()->toIso8601String()
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error submitting test: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to submit test',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Auto-save test progress (Student)
     */
    public function autosave(Request $request, $submissionId)
    {
        try {
            // TODO: Implement autosave logic when tables are created
            return response()->json([
                'data' => [
                    'saved_at' => now()->toIso8601String()
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error autosaving test: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to autosave',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload file for test answer (Student)
     */
    public function uploadFile(Request $request, $submissionId)
    {
        try {
            $request->validate([
                'file' => 'required|file|max:10240', // 10MB max
                'question_id' => 'required|integer'
            ]);

            // TODO: Implement file upload logic when tables are created
            return response()->json([
                'data' => [
                    'file_url' => 'https://example.com/uploads/file.pdf',
                    'file_name' => $request->file('file')->getClientOriginalName()
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error uploading file: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to upload file',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get test results (Student)
     */
    public function getResults($testId)
    {
        try {
            // TODO: Implement results retrieval when tables are created
            return response()->json([
                'data' => [
                    'test_id' => $testId,
                    'total_score' => 0,
                    'answers' => []
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching results: ' . $e->getMessage());
            return response()->json([
                'message' => 'Results not available',
                'error' => $e->getMessage()
            ], 403);
        }
    }
}
