<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Enrollment;
use App\Models\Certificate;
use App\Models\LearnerActivityLog;
use App\Models\AssignmentSubmission;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LearnerDashboardController extends Controller
{
    /**
     * Get dashboard overview
     */
    public function index()
    {
        $user = Auth::user();
        
        // Get statistics
        $enrolledCoursesCount = Enrollment::where('learner_id', $user->id)
            ->whereIn('status', ['active', 'paused'])->count();
        
        $completedCoursesCount = Enrollment::where('learner_id', $user->id)
            ->where('status', 'completed')->count();
        
        $totalHours = LearnerActivityLog::where('user_id', $user->id)
            ->sum('hours_spent');
        
        $certificatesCount = Certificate::where('user_id', $user->id)->count();
        
        // Get progress data for last 7 days
        $progressData = LearnerActivityLog::where('user_id', $user->id)
            ->where('activity_date', '>=', Carbon::now()->subDays(7))
            ->orderBy('activity_date')
            ->get()
            ->map(function ($log) {
                return [
                    'date' => $log->activity_date->format('M d'),
                    'hours' => (float) $log->hours_spent
                ];
            });
        
        // Get continue learning courses (in progress, ordered by last accessed)
        $continueLearning = Enrollment::with(['course.courseModules'])
            ->where('learner_id', $user->id)
            ->where('status', 'active')
            ->where('progress', '>', 0)
            ->where('progress', '<', 100)
            ->orderBy('last_accessed_at', 'desc')
            ->limit(3)
            ->get()
            ->map(function ($enrollment) {
                return [
                    'id' => $enrollment->course->id,
                    'title' => $enrollment->course->title,
                    'thumbnail' => $enrollment->course->thumbnail ? asset('storage/' . $enrollment->course->thumbnail) : null,
                    'progress' => (float) $enrollment->progress,
                    'lastAccessed' => $enrollment->last_accessed_at ? $enrollment->last_accessed_at->diffForHumans() : null,
                ];
            });
        
        // Get upcoming assignments (pending, due soon)
        $upcomingAssignments = DB::table('module_assignments as ma')
            ->join('course_modules as cm', 'ma.module_id', '=', 'cm.id')
            ->join('enrollments as e', 'cm.course_id', '=', 'e.course_id')
            ->leftJoin('assignment_submissions as as', function($join) use ($user) {
                $join->on('ma.id', '=', 'as.assignment_id')
                     ->where('as.user_id', '=', $user->id);
            })
            ->where('e.learner_id', $user->id)
            ->whereNull('as.id') // Not yet submitted
            ->where('ma.due_date', '>', now())
            ->where('ma.due_date', '<=', now()->addDays(7))
            ->select([
                'ma.id',
                'ma.assignment_title as title',
                'ma.due_date as dueDate',
                'cm.module_title as module',
                DB::raw("CONCAT('Course ', cm.course_id) as course")
            ])
            ->orderBy('ma.due_date')
            ->limit(5)
            ->get();
        
        // Get recent notifications
        $recentNotifications = Notification::where('user_id', $user->id)
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'read' => $notification->read_at !== null,
                    'createdAt' => $notification->created_at->diffForHumans(),
                ];
            });
        
        return response()->json([
            'success' => true,
            'data' => [
                'learner' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar ? asset('storage/' . $user->avatar) : null,
                    'membershipType' => $user->membership_type ?? 'free',
                ],
                'stats' => [
                    'enrolledCourses' => $enrolledCoursesCount,
                    'completedCourses' => $completedCoursesCount,
                    'totalHours' => round($totalHours, 1),
                    'certificates' => $certificatesCount,
                ],
                'progressData' => $progressData,
                'continueLearning' => $continueLearning,
                'upcomingAssignments' => $upcomingAssignments,
                'recentNotifications' => $recentNotifications,
            ]
        ]);
    }

    /**
     * Get detailed statistics
     */
    public function stats()
    {
        $user = Auth::user();
        
        $totalCourses = Enrollment::where('learner_id', $user->id)->count();
        $activeCourses = Enrollment::where('learner_id', $user->id)
            ->whereIn('status', ['active', 'paused'])->count();
        $completedCourses = Enrollment::where('learner_id', $user->id)
            ->where('status', 'completed')->count();
        
        $totalAssignments = AssignmentSubmission::where('user_id', $user->id)->count();
        $pendingAssignments = DB::table('module_assignments as ma')
            ->join('course_modules as cm', 'ma.module_id', '=', 'cm.id')
            ->join('enrollments as e', 'cm.course_id', '=', 'e.course_id')
            ->leftJoin('assignment_submissions as as', function($join) use ($user) {
                $join->on('ma.id', '=', 'as.assignment_id')
                     ->where('as.user_id', '=', $user->id);
            })
            ->where('e.learner_id', $user->id)
            ->whereNull('as.id')
            ->count();
        
        $averageScore = AssignmentSubmission::where('user_id', $user->id)
            ->whereNotNull('marks_obtained')
            ->avg(DB::raw('(marks_obtained / (SELECT max_points FROM module_assignments WHERE id = assignment_submissions.assignment_id)) * 100'));
        
        return response()->json([
            'success' => true,
            'data' => [
                'courses' => [
                    'total' => $totalCourses,
                    'active' => $activeCourses,
                    'completed' => $completedCourses,
                ],
                'assignments' => [
                    'total' => $totalAssignments,
                    'pending' => $pendingAssignments,
                    'averageScore' => $averageScore ? round($averageScore, 2) : 0,
                ],
            ]
        ]);
    }

    /**
     * Get activity for last N days
     */
    public function activity(Request $request)
    {
        $user = Auth::user();
        $days = $request->input('days', 7);
        
        $activity = LearnerActivityLog::where('user_id', $user->id)
            ->where('activity_date', '>=', Carbon::now()->subDays($days))
            ->orderBy('activity_date')
            ->get()
            ->map(function ($log) {
                return [
                    'date' => $log->activity_date->format('Y-m-d'),
                    'hoursSpent' => (float) $log->hours_spent,
                    'lessonsCompleted' => $log->lessons_completed,
                    'quizzesTaken' => $log->quizzes_taken,
                    'assignmentsSubmitted' => $log->assignments_submitted,
                ];
            });
        
        return response()->json([
            'success' => true,
            'data' => $activity
        ]);
    }
}
