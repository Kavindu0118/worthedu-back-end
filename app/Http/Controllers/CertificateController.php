<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\QuizAttempt;
use App\Models\AssignmentSubmission;
use App\Models\TestSubmission;
use App\Models\ModuleQuiz;
use App\Models\ModuleAssignment;
use App\Models\Test;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CertificateController extends Controller
{
    /**
     * Get all certificates for the authenticated learner
     */
    public function index()
    {
        $user = Auth::user();
        
        $certificates = Certificate::with(['course'])
            ->where('user_id', $user->id)
            ->orderBy('issued_at', 'desc')
            ->get()
            ->map(function ($certificate) {
                return [
                    'id' => $certificate->id,
                    'courseId' => $certificate->course_id,
                    'courseTitle' => $certificate->course->title,
                    'courseThumbnail' => $certificate->course->thumbnail ? url('storage/' . $certificate->course->thumbnail) : null,
                    'finalGrade' => (float) $certificate->final_grade,
                    'letterGrade' => $certificate->letter_grade,
                    'status' => $certificate->status,
                    'issuedAt' => $certificate->issued_at->toIso8601String(),
                    'canView' => (bool) $certificate->can_view,
                ];
            });
        
        return response()->json([
            'success' => true,
            'data' => $certificates
        ]);
    }

    /**
     * Get specific certificate with full grade breakdown
     */
    public function show($id)
    {
        $user = Auth::user();
        
        $certificate = Certificate::with(['course.instructor.user'])
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->first();
        
        if (!$certificate) {
            return response()->json([
                'success' => false,
                'message' => 'Certificate not found'
            ], 404);
        }
        
        // Check if certificate can be viewed
        if (!$certificate->can_view) {
            return response()->json([
                'success' => false,
                'message' => 'Certificate not available yet. All test results must be published first.'
            ], 403);
        }
        
        // Calculate grade breakdown
        $gradeBreakdown = $this->calculateGradeBreakdown(
            $certificate->course_id,
            $user->id,
            $certificate->quiz_weight,
            $certificate->assignment_weight,
            $certificate->test_weight
        );
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $certificate->id,
                'courseId' => $certificate->course_id,
                'courseTitle' => $certificate->course->title,
                'courseThumbnail' => $certificate->course->thumbnail ? url('storage/' . $certificate->course->thumbnail) : null,
                'studentId' => $user->id,
                'studentName' => $user->name,
                'studentEmail' => $user->email,
                'instructorName' => $certificate->course->instructor && $certificate->course->instructor->user 
                    ? $certificate->course->instructor->user->name 
                    : 'N/A',
                'completedAt' => $certificate->completed_at ? $certificate->completed_at->toIso8601String() : null,
                'issuedAt' => $certificate->issued_at->toIso8601String(),
                'certificateNumber' => $certificate->certificate_number,
                'canView' => true,
                'gradeBreakdown' => $gradeBreakdown,
            ]
        ]);
    }

    /**
     * Get certificate for a specific course
     */
    public function getByCourse($courseId)
    {
        $user = Auth::user();
        
        $certificate = Certificate::with(['course.instructor.user'])
            ->where('course_id', $courseId)
            ->where('user_id', $user->id)
            ->first();
        
        if (!$certificate) {
            return response()->json([
                'success' => false,
                'message' => 'Certificate not found for this course'
            ], 404);
        }
        
        // Check if certificate can be viewed
        if (!$certificate->can_view) {
            return response()->json([
                'success' => false,
                'message' => 'Certificate not available yet. All test results must be published first.'
            ], 403);
        }
        
        // Calculate grade breakdown
        $gradeBreakdown = $this->calculateGradeBreakdown(
            $certificate->course_id,
            $user->id,
            $certificate->quiz_weight,
            $certificate->assignment_weight,
            $certificate->test_weight
        );
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $certificate->id,
                'courseId' => $certificate->course_id,
                'courseTitle' => $certificate->course->title,
                'courseThumbnail' => $certificate->course->thumbnail ? url('storage/' . $certificate->course->thumbnail) : null,
                'studentId' => $user->id,
                'studentName' => $user->name,
                'studentEmail' => $user->email,
                'instructorName' => $certificate->course->instructor && $certificate->course->instructor->user 
                    ? $certificate->course->instructor->user->name 
                    : 'N/A',
                'completedAt' => $certificate->completed_at ? $certificate->completed_at->toIso8601String() : null,
                'issuedAt' => $certificate->issued_at->toIso8601String(),
                'certificateNumber' => $certificate->certificate_number,
                'canView' => true,
                'gradeBreakdown' => $gradeBreakdown,
            ]
        ]);
    }

    /**
     * Generate or update certificate for a course
     * This should be called when a student completes a course
     */
    public function generateCertificate($courseId, $userId)
    {
        // Check if certificate already exists
        $certificate = Certificate::where('course_id', $courseId)
            ->where('user_id', $userId)
            ->first();
        
        // Get enrollment to check completion
        $enrollment = Enrollment::where('course_id', $courseId)
            ->where('learner_id', $userId)
            ->first();
        
        if (!$enrollment) {
            return null;
        }
        
        // Check if all tests are published
        $canView = $this->checkAllTestsPublished($courseId);
        
        // Get weights (can be customized per course later)
        $quizWeight = 0.15;
        $assignmentWeight = 0.25;
        $testWeight = 0.60;
        
        // Calculate grade breakdown
        $gradeBreakdown = $this->calculateGradeBreakdown(
            $courseId,
            $userId,
            $quizWeight,
            $assignmentWeight,
            $testWeight
        );
        
        $certificateData = [
            'user_id' => $userId,
            'course_id' => $courseId,
            'quiz_weight' => $quizWeight,
            'assignment_weight' => $assignmentWeight,
            'test_weight' => $testWeight,
            'final_grade' => $gradeBreakdown['finalGrade'],
            'letter_grade' => $gradeBreakdown['letterGrade'],
            'status' => $gradeBreakdown['status'],
            'completed_at' => $enrollment->completed_at ?? now(),
            'can_view' => $canView,
        ];
        
        if ($certificate) {
            // Update existing certificate
            $certificate->update($certificateData);
        } else {
            // Create new certificate
            $certificateData['certificate_number'] = $this->generateCertificateNumber();
            $certificateData['issued_at'] = now();
            $certificate = Certificate::create($certificateData);
        }
        
        return $certificate;
    }

    /**
     * Calculate grade breakdown for a student in a course
     */
    private function calculateGradeBreakdown($courseId, $userId, $quizWeight, $assignmentWeight, $testWeight)
    {
        // Get all modules for the course
        $moduleIds = DB::table('course_modules')
            ->where('course_id', $courseId)
            ->pluck('id');
        
        // Calculate quiz scores
        $quizzes = ModuleQuiz::whereIn('module_id', $moduleIds)->get();
        $quizTotal = 0;
        $quizMax = 0;
        $quizCount = 0;
        
        foreach ($quizzes as $quiz) {
            $quizCount++;
            $quizMax += $quiz->total_points ?? 0;
            
            $bestAttempt = QuizAttempt::where('user_id', $userId)
                ->where('quiz_id', $quiz->id)
                ->where('status', 'completed')
                ->orderBy('score', 'desc')
                ->first();
            
            if ($bestAttempt) {
                $quizTotal += $bestAttempt->points_earned ?? 0;
            }
        }
        
        $quizPercentage = $quizMax > 0 ? ($quizTotal / $quizMax) * 100 : 0;
        
        // Calculate assignment scores
        $assignments = ModuleAssignment::whereIn('module_id', $moduleIds)->get();
        $assignmentTotal = 0;
        $assignmentMax = 0;
        $assignmentCount = 0;
        
        foreach ($assignments as $assignment) {
            $assignmentCount++;
            $assignmentMax += $assignment->max_points ?? 0;
            
            $submission = AssignmentSubmission::where('user_id', $userId)
                ->where('assignment_id', $assignment->id)
                ->whereIn('status', ['submitted', 'graded'])
                ->first();
            
            if ($submission && $submission->marks_obtained !== null) {
                $assignmentTotal += $submission->marks_obtained;
            }
        }
        
        $assignmentPercentage = $assignmentMax > 0 ? ($assignmentTotal / $assignmentMax) * 100 : 0;
        
        // Calculate test scores
        $tests = Test::whereIn('module_id', $moduleIds)->get();
        $testTotal = 0;
        $testMax = 0;
        $testCount = 0;
        
        foreach ($tests as $test) {
            $testCount++;
            $testMax += $test->total_marks ?? 0;
            
            $submission = TestSubmission::where('student_id', $userId)
                ->where('test_id', $test->id)
                ->whereIn('submission_status', ['submitted', 'late'])
                ->whereNotNull('total_score')
                ->orderBy('total_score', 'desc')
                ->first();
            
            if ($submission) {
                $testTotal += $submission->total_score;
            }
        }
        
        $testPercentage = $testMax > 0 ? ($testTotal / $testMax) * 100 : 0;
        
        // Calculate weighted scores
        $quizWeightedScore = $quizPercentage * $quizWeight;
        $assignmentWeightedScore = $assignmentPercentage * $assignmentWeight;
        $testWeightedScore = $testPercentage * $testWeight;
        
        // Calculate final grade
        $finalGrade = $quizWeightedScore + $assignmentWeightedScore + $testWeightedScore;
        
        // Determine letter grade
        if ($finalGrade >= 90) {
            $letterGrade = 'A';
        } elseif ($finalGrade >= 80) {
            $letterGrade = 'B';
        } elseif ($finalGrade >= 70) {
            $letterGrade = 'C';
        } elseif ($finalGrade >= 60) {
            $letterGrade = 'D';
        } else {
            $letterGrade = 'F';
        }
        
        // Determine status
        $status = $finalGrade >= 60 ? 'pass' : 'fail';
        
        return [
            'quizzes' => [
                'totalScore' => round($quizTotal, 2),
                'maxScore' => round($quizMax, 2),
                'percentage' => round($quizPercentage, 2),
                'weight' => $quizWeight,
                'weightedScore' => round($quizWeightedScore, 2),
                'count' => $quizCount,
            ],
            'assignments' => [
                'totalScore' => round($assignmentTotal, 2),
                'maxScore' => round($assignmentMax, 2),
                'percentage' => round($assignmentPercentage, 2),
                'weight' => $assignmentWeight,
                'weightedScore' => round($assignmentWeightedScore, 2),
                'count' => $assignmentCount,
            ],
            'tests' => [
                'totalScore' => round($testTotal, 2),
                'maxScore' => round($testMax, 2),
                'percentage' => round($testPercentage, 2),
                'weight' => $testWeight,
                'weightedScore' => round($testWeightedScore, 2),
                'count' => $testCount,
            ],
            'finalGrade' => round($finalGrade, 2),
            'letterGrade' => $letterGrade,
            'status' => $status,
        ];
    }

    /**
     * Check if all tests in a course have published results
     */
    private function checkAllTestsPublished($courseId)
    {
        $moduleIds = DB::table('course_modules')
            ->where('course_id', $courseId)
            ->pluck('id');
        
        $totalTests = Test::whereIn('module_id', $moduleIds)->count();
        
        if ($totalTests === 0) {
            return true; // No tests, so considered as all published
        }
        
        $publishedTests = Test::whereIn('module_id', $moduleIds)
            ->where('results_published', true)
            ->count();
        
        return $totalTests === $publishedTests;
    }

    /**
     * Generate unique certificate number
     */
    private function generateCertificateNumber()
    {
        $year = date('Y');
        $lastCertificate = Certificate::whereYear('issued_at', $year)
            ->orderBy('id', 'desc')
            ->first();
        
        $nextNumber = $lastCertificate ? (intval(substr($lastCertificate->certificate_number, -5)) + 1) : 1;
        
        return 'CERT-' . $year . '-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Update certificate visibility when test results are published
     */
    public function updateCertificateVisibility($courseId)
    {
        $canView = $this->checkAllTestsPublished($courseId);
        
        Certificate::where('course_id', $courseId)
            ->update(['can_view' => $canView]);
        
        return $canView;
    }
}
