<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Enrollment;
use App\Models\Certificate;
use App\Models\LearnerActivityLog;
use App\Models\AssignmentSubmission;
use App\Models\Notification;
use App\Models\CourseModule;
use App\Models\LessonProgress;
use App\Models\QuizAttempt;
use App\Models\Test;
use App\Models\TestSubmission;
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
            ->map(function ($enrollment) use ($user) {
                $progressDetails = $this->calculateCourseProgress($enrollment->course_id, $user->id);
                
                return [
                    'id' => $enrollment->course->id,
                    'title' => $enrollment->course->title,
                    'thumbnail' => $enrollment->course->thumbnail ? asset('storage/' . $enrollment->course->thumbnail) : null,
                    'progress' => (float) $enrollment->progress,
                    'lastAccessed' => $enrollment->last_accessed_at ? $enrollment->last_accessed_at->diffForHumans() : null,
                    'progressDetails' => $progressDetails,
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
        
        // Get current learning streak
        $streakData = $this->calculateStreakData($user->id);
        
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
                'streak' => [
                    'current' => $streakData['currentStreak'],
                    'longest' => $streakData['longestStreak'],
                    'isActiveToday' => $streakData['isActiveToday'],
                ],
                'progressData' => $progressData,
                'continueLearning' => $continueLearning,
                'upcomingAssignments' => $upcomingAssignments,
                'recentNotifications' => $recentNotifications,
            ]
        ]);
    }
    
    /**
     * Calculate streak data helper
     */
    private function calculateStreakData($userId)
    {
        $activityDates = LearnerActivityLog::where('user_id', $userId)
            ->where('hours_spent', '>', 0)
            ->orderBy('activity_date', 'desc')
            ->pluck('activity_date')
            ->map(function($date) {
                return Carbon::parse($date)->startOfDay();
            });
        
        if ($activityDates->isEmpty()) {
            return [
                'currentStreak' => 0,
                'longestStreak' => 0,
                'isActiveToday' => false,
            ];
        }
        
        $currentStreak = 0;
        $today = Carbon::now()->startOfDay();
        $checkDate = $today;
        
        foreach ($activityDates as $activityDate) {
            if ($activityDate->equalTo($checkDate)) {
                $currentStreak++;
                $checkDate = $checkDate->subDay();
            } else {
                break;
            }
        }
        
        if ($currentStreak === 0 && $activityDates->first()->equalTo($today->copy()->subDay())) {
            $currentStreak = 1;
            $checkDate = $today->copy()->subDays(2);
            
            foreach ($activityDates->skip(1) as $activityDate) {
                if ($activityDate->equalTo($checkDate)) {
                    $currentStreak++;
                    $checkDate = $checkDate->subDay();
                } else {
                    break;
                }
            }
        }
        
        $longestStreak = 0;
        $tempStreak = 1;
        $previousDate = $activityDates->first();
        
        foreach ($activityDates->skip(1) as $activityDate) {
            if ($previousDate->diffInDays($activityDate) === 1) {
                $tempStreak++;
            } else {
                $longestStreak = max($longestStreak, $tempStreak);
                $tempStreak = 1;
            }
            $previousDate = $activityDate;
        }
        $longestStreak = max($longestStreak, $tempStreak);
        
        return [
            'currentStreak' => $currentStreak,
            'longestStreak' => $longestStreak,
            'isActiveToday' => $activityDates->first()->equalTo($today),
        ];
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

    /**
     * Get learning streak information
     */
    public function streak()
    {
        $user = Auth::user();
        
        // Get all activity dates
        $activityDates = LearnerActivityLog::where('user_id', $user->id)
            ->where('hours_spent', '>', 0)
            ->orderBy('activity_date', 'desc')
            ->pluck('activity_date')
            ->map(function($date) {
                return Carbon::parse($date)->startOfDay();
            });
        
        if ($activityDates->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'currentStreak' => 0,
                    'longestStreak' => 0,
                    'lastActiveDate' => null,
                    'isActiveToday' => false,
                ]
            ]);
        }
        
        // Calculate current streak
        $currentStreak = 0;
        $today = Carbon::now()->startOfDay();
        $checkDate = $today;
        
        foreach ($activityDates as $activityDate) {
            if ($activityDate->equalTo($checkDate)) {
                $currentStreak++;
                $checkDate = $checkDate->subDay();
            } else {
                break;
            }
        }
        
        // If not active today, check yesterday for grace period
        if ($currentStreak === 0 && $activityDates->first()->equalTo($today->copy()->subDay())) {
            $currentStreak = 1;
            $checkDate = $today->copy()->subDays(2);
            
            foreach ($activityDates->skip(1) as $activityDate) {
                if ($activityDate->equalTo($checkDate)) {
                    $currentStreak++;
                    $checkDate = $checkDate->subDay();
                } else {
                    break;
                }
            }
        }
        
        // Calculate longest streak
        $longestStreak = 0;
        $tempStreak = 1;
        $previousDate = $activityDates->first();
        
        foreach ($activityDates->skip(1) as $activityDate) {
            if ($previousDate->diffInDays($activityDate) === 1) {
                $tempStreak++;
            } else {
                $longestStreak = max($longestStreak, $tempStreak);
                $tempStreak = 1;
            }
            $previousDate = $activityDate;
        }
        $longestStreak = max($longestStreak, $tempStreak);
        
        return response()->json([
            'success' => true,
            'data' => [
                'currentStreak' => $currentStreak,
                'longestStreak' => $longestStreak,
                'lastActiveDate' => $activityDates->first()->toDateString(),
                'isActiveToday' => $activityDates->first()->equalTo($today),
            ]
        ]);
    }

    /**
     * Get personalized course recommendations
     */
    public function recommendations()
    {
        $user = Auth::user();
        
        // Get user's enrolled courses to find categories and levels
        $enrolledCourses = Enrollment::with('course')
            ->where('learner_id', $user->id)
            ->get()
            ->pluck('course');
        
        $categories = $enrolledCourses->pluck('category')->unique()->toArray();
        $levels = $enrolledCourses->pluck('level')->unique()->toArray();
        
        // Get IDs of courses already enrolled in
        $enrolledCourseIds = $enrolledCourses->pluck('id')->toArray();
        
        // Get recommendations based on similar categories
        $recommendations = DB::table('courses as c')
            ->leftJoin('instructors as i', 'c.instructor_id', '=', 'i.instructor_id')
            ->leftJoin('users as u', 'i.user_id', '=', 'u.id')
            ->whereNotIn('c.id', $enrolledCourseIds)
            ->where('c.status', 'published')
            ->select([
                'c.id',
                'c.title',
                'c.description',
                'c.thumbnail',
                'c.category',
                'c.level',
                'c.duration',
                'c.price',
                'c.student_count',
                'u.name as instructor_name',
                DB::raw('CASE 
                    WHEN c.category IN (' . implode(',', array_fill(0, count($categories), '?')) . ') THEN 2
                    WHEN c.level IN (' . implode(',', array_fill(0, count($levels), '?')) . ') THEN 1
                    ELSE 0 
                END as relevance_score')
            ])
            ->orderByDesc('relevance_score')
            ->orderByDesc('c.student_count')
            ->limit(10)
            ->get();
        
        $bindings = array_merge($categories, $levels);
        $recommendations = DB::select(
            "SELECT c.id, c.title, c.description, c.thumbnail, c.category, c.level, 
                    c.duration, c.price, c.student_count, u.name as instructor_name,
                    CASE 
                        WHEN c.category IN (" . implode(',', array_fill(0, count($categories), '?')) . ") THEN 2
                        WHEN c.level IN (" . implode(',', array_fill(0, count($levels), '?')) . ") THEN 1
                        ELSE 0 
                    END as relevance_score
             FROM courses c
             LEFT JOIN instructors i ON c.instructor_id = i.instructor_id
             LEFT JOIN users u ON i.user_id = u.id
             WHERE c.id NOT IN (" . implode(',', array_fill(0, count($enrolledCourseIds), '?')) . ")
               AND c.status = 'published'
             ORDER BY relevance_score DESC, c.student_count DESC
             LIMIT 10",
            array_merge($bindings, $enrolledCourseIds)
        );
        
        return response()->json([
            'success' => true,
            'data' => collect($recommendations)->map(function($course) {
                return [
                    'id' => $course->id,
                    'title' => $course->title,
                    'description' => $course->description,
                    'thumbnail' => $course->thumbnail ? url('storage/' . $course->thumbnail) : null,
                    'category' => $course->category,
                    'level' => $course->level,
                    'duration' => $course->duration,
                    'price' => (float) $course->price,
                    'studentCount' => $course->student_count ?? 0,
                    'instructor' => $course->instructor_name,
                ];
            })
        ]);
    }

    /**
     * Get performance analytics
     */
    public function performance()
    {
        $user = Auth::user();
        
        // Get all enrollments with courses
        $enrollments = Enrollment::with('course')
            ->where('learner_id', $user->id)
            ->get();
        
        // Calculate average completion rate
        $avgProgress = $enrollments->avg('progress') ?? 0;
        
        // Get assignment performance
        $assignmentStats = AssignmentSubmission::where('user_id', $user->id)
            ->whereNotNull('marks_obtained')
            ->selectRaw('
                COUNT(*) as total_graded,
                AVG(marks_obtained) as avg_marks,
                AVG(CASE WHEN is_late = 1 THEN 1 ELSE 0 END) * 100 as late_percentage
            ')
            ->first();
        
        // Get quiz performance
        $quizStats = DB::table('quiz_attempts')
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->selectRaw('
                COUNT(*) as total_attempts,
                AVG(score) as avg_score,
                AVG(CASE WHEN passed = 1 THEN 1 ELSE 0 END) * 100 as pass_rate
            ')
            ->first();
        
        // Time management
        $avgTimePerDay = LearnerActivityLog::where('user_id', $user->id)
            ->where('activity_date', '>=', Carbon::now()->subDays(30))
            ->avg('hours_spent') ?? 0;
        
        // Course completion time analysis
        $completedCourses = $enrollments->where('status', 'completed');
        $avgCompletionDays = $completedCourses->map(function($enrollment) {
            if ($enrollment->completed_at) {
                return $enrollment->created_at->diffInDays($enrollment->completed_at);
            }
            return null;
        })->filter()->avg() ?? 0;
        
        return response()->json([
            'success' => true,
            'data' => [
                'overallProgress' => round($avgProgress, 2),
                'completionRate' => [
                    'completed' => $enrollments->where('status', 'completed')->count(),
                    'inProgress' => $enrollments->whereIn('status', ['active', 'paused'])->count(),
                    'total' => $enrollments->count(),
                ],
                'assignments' => [
                    'totalGraded' => $assignmentStats->total_graded ?? 0,
                    'averageScore' => round($assignmentStats->avg_marks ?? 0, 2),
                    'lateSubmissionRate' => round($assignmentStats->late_percentage ?? 0, 2),
                ],
                'quizzes' => [
                    'totalAttempts' => $quizStats->total_attempts ?? 0,
                    'averageScore' => round($quizStats->avg_score ?? 0, 2),
                    'passRate' => round($quizStats->pass_rate ?? 0, 2),
                ],
                'timeManagement' => [
                    'avgHoursPerDay' => round($avgTimePerDay, 2),
                    'avgDaysToComplete' => round($avgCompletionDays, 1),
                ],
                'insights' => $this->generateInsights($avgProgress, $assignmentStats, $quizStats, $avgTimePerDay),
            ]
        ]);
    }

    /**
     * Generate performance insights
     */
    private function generateInsights($avgProgress, $assignmentStats, $quizStats, $avgTimePerDay)
    {
        $insights = [];
        
        if ($avgProgress >= 75) {
            $insights[] = [
                'type' => 'positive',
                'message' => 'Great progress! You\'re completing your courses efficiently.',
            ];
        } elseif ($avgProgress < 30) {
            $insights[] = [
                'type' => 'warning',
                'message' => 'Consider dedicating more time to complete your courses.',
            ];
        }
        
        if (($assignmentStats->late_percentage ?? 0) > 30) {
            $insights[] = [
                'type' => 'warning',
                'message' => 'Try to submit assignments before the deadline to avoid penalties.',
            ];
        }
        
        if (($quizStats->pass_rate ?? 0) < 60) {
            $insights[] = [
                'type' => 'tip',
                'message' => 'Review lesson materials before taking quizzes to improve your scores.',
            ];
        }
        
        if ($avgTimePerDay < 0.5) {
            $insights[] = [
                'type' => 'tip',
                'message' => 'Aim for at least 30 minutes of learning per day for better retention.',
            ];
        } elseif ($avgTimePerDay > 2) {
            $insights[] = [
                'type' => 'positive',
                'message' => 'Excellent dedication! Your consistent study habits will pay off.',
            ];
        }
        
        return $insights;
    }

    /**
     * Get certificates
     */
    public function certificates()
    {
        $user = Auth::user();
        
        $certificates = Certificate::with('course')
            ->where('user_id', $user->id)
            ->orderBy('issued_at', 'desc')
            ->get()
            ->map(function ($cert) {
                return [
                    'id' => $cert->id,
                    'certificateNumber' => $cert->certificate_number,
                    'course' => [
                        'id' => $cert->course->id,
                        'title' => $cert->course->title,
                        'category' => $cert->course->category,
                    ],
                    'issuedAt' => $cert->issued_at->toDateString(),
                    'issuedAtFormatted' => $cert->issued_at->format('F d, Y'),
                ];
            });
        
        return response()->json([
            'success' => true,
            'data' => $certificates
        ]);
    }

    /**
     * Calculate detailed course progress including modules, quizzes, assignments, and tests
     */
    private function calculateCourseProgress($courseId, $userId)
    {
        // Get all modules for the course
        $modules = CourseModule::where('course_id', $courseId)->get();
        
        $totalItems = 0;
        $completedItems = 0;
        
        $moduleCount = $modules->count();
        $completedModules = 0;
        
        $quizCount = 0;
        $completedQuizzes = 0;
        
        $assignmentCount = 0;
        $completedAssignments = 0;
        
        $testCount = 0;
        $completedTests = 0;
        
        foreach ($modules as $module) {
            // Check module completion (via lesson_progress)
            $moduleProgress = LessonProgress::where('user_id', $userId)
                ->where('lesson_id', $module->id)
                ->where('status', 'completed')
                ->exists();
            
            if ($moduleProgress) {
                $completedModules++;
            }
            
            // Count quizzes in this module
            $quizzes = \App\Models\ModuleQuiz::where('module_id', $module->id)->get();
            foreach ($quizzes as $quiz) {
                $quizCount++;
                
                // Check if quiz is completed (at least one successful attempt)
                $quizCompleted = QuizAttempt::where('user_id', $userId)
                    ->where('quiz_id', $quiz->id)
                    ->where('status', 'completed')
                    ->exists();
                
                if ($quizCompleted) {
                    $completedQuizzes++;
                }
            }
            
            // Count assignments in this module
            $assignments = \App\Models\ModuleAssignment::where('module_id', $module->id)->get();
            foreach ($assignments as $assignment) {
                $assignmentCount++;
                
                // Check if assignment is submitted
                $assignmentSubmitted = AssignmentSubmission::where('user_id', $userId)
                    ->where('assignment_id', $assignment->id)
                    ->whereIn('status', ['submitted', 'graded'])
                    ->exists();
                
                if ($assignmentSubmitted) {
                    $completedAssignments++;
                }
            }
            
            // Count tests in this module
            $tests = Test::where('module_id', $module->id)->get();
            foreach ($tests as $test) {
                $testCount++;
                
                // Check if test is submitted
                $testSubmitted = TestSubmission::where('student_id', $userId)
                    ->where('test_id', $test->id)
                    ->whereIn('submission_status', ['submitted', 'late'])
                    ->exists();
                
                if ($testSubmitted) {
                    $completedTests++;
                }
            }
        }
        
        // Calculate total items and completed items
        $totalItems = $moduleCount + $quizCount + $assignmentCount + $testCount;
        $completedItems = $completedModules + $completedQuizzes + $completedAssignments + $completedTests;
        
        // Calculate overall percentage
        $overallPercentage = $totalItems > 0 ? round(($completedItems / $totalItems) * 100, 1) : 0;
        
        return [
            'overall_percentage' => $overallPercentage,
            'modules' => [
                'total' => $moduleCount,
                'completed' => $completedModules,
                'percentage' => $moduleCount > 0 ? round(($completedModules / $moduleCount) * 100, 1) : 0
            ],
            'quizzes' => [
                'total' => $quizCount,
                'completed' => $completedQuizzes,
                'percentage' => $quizCount > 0 ? round(($completedQuizzes / $quizCount) * 100, 1) : 0
            ],
            'assignments' => [
                'total' => $assignmentCount,
                'completed' => $completedAssignments,
                'percentage' => $assignmentCount > 0 ? round(($completedAssignments / $assignmentCount) * 100, 1) : 0
            ],
            'tests' => [
                'total' => $testCount,
                'completed' => $completedTests,
                'percentage' => $testCount > 0 ? round(($completedTests / $testCount) * 100, 1) : 0
            ],
            'total_items' => $totalItems,
            'completed_items' => $completedItems,
        ];
    }
}
