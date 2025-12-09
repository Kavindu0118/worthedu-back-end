<?php

namespace App\Helpers;

use App\Models\CourseModule;
use App\Models\Enrollment;
use App\Models\LessonProgress;
use App\Models\Certificate;
use App\Models\Notification;
use Illuminate\Support\Str;

class ProgressHelper
{
    /**
     * Calculate course progress percentage for a user
     * 
     * @param int $userId
     * @param int $courseId
     * @return float Progress percentage (0-100)
     */
    public static function calculateCourseProgress($userId, $courseId)
    {
        // Get all mandatory modules for the course
        $mandatoryModules = CourseModule::where('course_id', $courseId)
            ->where('is_mandatory', true)
            ->pluck('id');
        
        if ($mandatoryModules->isEmpty()) {
            // If no mandatory modules, check all modules
            $allModules = CourseModule::where('course_id', $courseId)->pluck('id');
            if ($allModules->isEmpty()) {
                return 0;
            }
            $mandatoryModules = $allModules;
        }
        
        // Count completed mandatory modules
        $completedCount = LessonProgress::where('user_id', $userId)
            ->whereIn('lesson_id', $mandatoryModules)
            ->where('status', 'completed')
            ->count();
        
        $totalCount = $mandatoryModules->count();
        
        if ($totalCount === 0) {
            return 0;
        }
        
        return round(($completedCount / $totalCount) * 100, 2);
    }

    /**
     * Update enrollment progress and handle completion
     * 
     * @param int $userId
     * @param int $courseId
     * @return array Updated enrollment data
     */
    public static function updateEnrollmentProgress($userId, $courseId)
    {
        $enrollment = Enrollment::where('learner_id', $userId)
            ->where('course_id', $courseId)
            ->first();
        
        if (!$enrollment) {
            return [
                'success' => false,
                'message' => 'Enrollment not found',
            ];
        }
        
        // Calculate new progress
        $newProgress = self::calculateCourseProgress($userId, $courseId);
        $oldProgress = $enrollment->progress;
        
        // Update enrollment
        $enrollment->update([
            'progress' => $newProgress,
            'last_accessed_at' => now(),
        ]);
        
        // Check if course just completed (progress reached 100%)
        if ($newProgress >= 100 && $oldProgress < 100) {
            self::completeCourse($userId, $courseId);
        }
        
        return [
            'success' => true,
            'progress' => $newProgress,
            'completed' => $newProgress >= 100,
        ];
    }

    /**
     * Mark course as completed and generate certificate
     * 
     * @param int $userId
     * @param int $courseId
     * @return void
     */
    public static function completeCourse($userId, $courseId)
    {
        // Update enrollment status
        $enrollment = Enrollment::where('learner_id', $userId)
            ->where('course_id', $courseId)
            ->first();
        
        if ($enrollment) {
            $enrollment->update([
                'status' => 'completed',
                'completed_at' => now(),
                'progress' => 100.00,
            ]);
        }
        
        // Generate certificate
        self::generateCertificate($userId, $courseId);
        
        // Send completion notification
        Notification::create([
            'user_id' => $userId,
            'type' => 'course_completed',
            'title' => 'Course Completed!',
            'message' => 'Congratulations! You have completed the course and earned a certificate.',
            'related_id' => $courseId,
            'related_type' => 'course',
        ]);
    }

    /**
     * Generate certificate for completed course
     * 
     * @param int $userId
     * @param int $courseId
     * @return Certificate|null
     */
    public static function generateCertificate($userId, $courseId)
    {
        // Check if certificate already exists
        $existingCertificate = Certificate::where('user_id', $userId)
            ->where('course_id', $courseId)
            ->first();
        
        if ($existingCertificate) {
            return $existingCertificate;
        }
        
        // Generate unique certificate number
        $certificateNumber = 'CERT-' . strtoupper(Str::random(8)) . '-' . date('Y');
        
        // Create certificate
        $certificate = Certificate::create([
            'user_id' => $userId,
            'course_id' => $courseId,
            'certificate_number' => $certificateNumber,
            'issued_at' => now(),
            // TODO: Generate PDF and store file_path
            // 'file_path' => 'certificates/cert_' . $certificateNumber . '.pdf',
        ]);
        
        // Send certificate notification
        Notification::create([
            'user_id' => $userId,
            'type' => 'certificate_issued',
            'title' => 'Certificate Issued',
            'message' => 'Your certificate has been issued. Certificate Number: ' . $certificateNumber,
            'related_id' => $certificate->id,
            'related_type' => 'certificate',
        ]);
        
        return $certificate;
    }

    /**
     * Get overall learning statistics for a user
     * 
     * @param int $userId
     * @return array Statistics array
     */
    public static function getUserStatistics($userId)
    {
        $enrollments = Enrollment::where('learner_id', $userId)->get();
        
        $totalCourses = $enrollments->count();
        $completedCourses = $enrollments->where('status', 'completed')->count();
        $inProgressCourses = $enrollments->where('status', 'active')->count();
        
        $totalProgress = $enrollments->sum('progress');
        $averageProgress = $totalCourses > 0 ? round($totalProgress / $totalCourses, 2) : 0;
        
        $certificates = Certificate::where('user_id', $userId)->count();
        
        return [
            'total_courses' => $totalCourses,
            'completed_courses' => $completedCourses,
            'in_progress_courses' => $inProgressCourses,
            'average_progress' => $averageProgress,
            'certificates_earned' => $certificates,
        ];
    }
}
