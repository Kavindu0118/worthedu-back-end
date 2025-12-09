<?php

namespace App\Helpers;

use App\Models\LearnerActivityLog;
use Carbon\Carbon;

class ActivityLogger
{
    /**
     * Log user activity for the current day
     * 
     * @param int $userId
     * @param string $type Type of activity: 'lesson', 'quiz', 'assignment'
     * @param int $durationMinutes Duration in minutes (for lessons and quizzes)
     * @return LearnerActivityLog
     */
    public static function logActivity($userId, $type, $durationMinutes = 0)
    {
        $today = Carbon::today()->toDateString();
        
        // Find or create activity log for today
        $activityLog = LearnerActivityLog::firstOrCreate(
            [
                'user_id' => $userId,
                'activity_date' => $today,
            ],
            [
                'hours_spent' => 0,
                'lessons_completed' => 0,
                'quizzes_taken' => 0,
                'assignments_submitted' => 0,
            ]
        );
        
        // Update based on activity type
        switch ($type) {
            case 'lesson':
                $activityLog->increment('lessons_completed');
                if ($durationMinutes > 0) {
                    $hours = round($durationMinutes / 60, 2);
                    $activityLog->increment('hours_spent', $hours);
                }
                break;
                
            case 'quiz':
                $activityLog->increment('quizzes_taken');
                if ($durationMinutes > 0) {
                    $hours = round($durationMinutes / 60, 2);
                    $activityLog->increment('hours_spent', $hours);
                }
                break;
                
            case 'assignment':
                $activityLog->increment('assignments_submitted');
                break;
                
            case 'time':
                // Just add time without incrementing counters
                if ($durationMinutes > 0) {
                    $hours = round($durationMinutes / 60, 2);
                    $activityLog->increment('hours_spent', $hours);
                }
                break;
        }
        
        return $activityLog->fresh();
    }

    /**
     * Get activity logs for a user within a date range
     * 
     * @param int $userId
     * @param int $days Number of days to retrieve (default 7)
     * @return \Illuminate\Support\Collection
     */
    public static function getActivityLogs($userId, $days = 7)
    {
        $startDate = Carbon::today()->subDays($days - 1);
        
        return LearnerActivityLog::where('user_id', $userId)
            ->where('activity_date', '>=', $startDate)
            ->orderBy('activity_date', 'asc')
            ->get();
    }

    /**
     * Get activity summary for a user
     * 
     * @param int $userId
     * @param int $days Number of days to summarize (default 30)
     * @return array Summary statistics
     */
    public static function getActivitySummary($userId, $days = 30)
    {
        $startDate = Carbon::today()->subDays($days - 1);
        
        $logs = LearnerActivityLog::where('user_id', $userId)
            ->where('activity_date', '>=', $startDate)
            ->get();
        
        return [
            'period_days' => $days,
            'total_hours' => round($logs->sum('hours_spent'), 2),
            'total_lessons' => $logs->sum('lessons_completed'),
            'total_quizzes' => $logs->sum('quizzes_taken'),
            'total_assignments' => $logs->sum('assignments_submitted'),
            'average_daily_hours' => round($logs->avg('hours_spent'), 2),
            'most_active_day' => $logs->sortByDesc('hours_spent')->first(),
        ];
    }

    /**
     * Get activity data formatted for charts
     * 
     * @param int $userId
     * @param int $days Number of days (default 7)
     * @return array Chart data with dates and values
     */
    public static function getChartData($userId, $days = 7)
    {
        $startDate = Carbon::today()->subDays($days - 1);
        $endDate = Carbon::today();
        
        $logs = LearnerActivityLog::where('user_id', $userId)
            ->where('activity_date', '>=', $startDate)
            ->where('activity_date', '<=', $endDate)
            ->orderBy('activity_date', 'asc')
            ->get()
            ->keyBy('activity_date');
        
        $chartData = [];
        $currentDate = $startDate->copy();
        
        while ($currentDate->lte($endDate)) {
            $dateString = $currentDate->toDateString();
            $log = $logs->get($dateString);
            
            $chartData[] = [
                'date' => $currentDate->format('M d'),
                'full_date' => $dateString,
                'hours' => $log ? (float) $log->hours_spent : 0,
                'lessons' => $log ? $log->lessons_completed : 0,
                'quizzes' => $log ? $log->quizzes_taken : 0,
                'assignments' => $log ? $log->assignments_submitted : 0,
            ];
            
            $currentDate->addDay();
        }
        
        return $chartData;
    }

    /**
     * Log study session with automatic time tracking
     * 
     * @param int $userId
     * @param \DateTime $startTime
     * @param \DateTime $endTime
     * @return LearnerActivityLog
     */
    public static function logStudySession($userId, $startTime, $endTime)
    {
        $durationMinutes = Carbon::parse($startTime)->diffInMinutes(Carbon::parse($endTime));
        
        return self::logActivity($userId, 'time', $durationMinutes);
    }

    /**
     * Get streak information (consecutive days of activity)
     * 
     * @param int $userId
     * @return array Streak data
     */
    public static function getStreak($userId)
    {
        $logs = LearnerActivityLog::where('user_id', $userId)
            ->orderBy('activity_date', 'desc')
            ->get();
        
        if ($logs->isEmpty()) {
            return [
                'current_streak' => 0,
                'longest_streak' => 0,
                'last_activity_date' => null,
            ];
        }
        
        $currentStreak = 0;
        $longestStreak = 0;
        $tempStreak = 0;
        
        $today = Carbon::today();
        $expectedDate = $today;
        
        foreach ($logs as $log) {
            $activityDate = Carbon::parse($log->activity_date);
            
            if ($activityDate->eq($expectedDate)) {
                $tempStreak++;
                if ($expectedDate->eq($today) || $expectedDate->eq($today->copy()->subDay())) {
                    $currentStreak = $tempStreak;
                }
                $expectedDate = $expectedDate->copy()->subDay();
            } else {
                $longestStreak = max($longestStreak, $tempStreak);
                $tempStreak = 1;
                $expectedDate = $activityDate->copy()->subDay();
            }
        }
        
        $longestStreak = max($longestStreak, $tempStreak);
        
        return [
            'current_streak' => $currentStreak,
            'longest_streak' => $longestStreak,
            'last_activity_date' => $logs->first()->activity_date,
        ];
    }
}
