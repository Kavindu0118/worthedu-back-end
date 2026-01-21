<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Test extends Model
{
    use HasFactory;

    protected $fillable = [
        'module_id',
        'course_id',
        'test_title',
        'test_description',
        'instructions',
        'start_date',
        'end_date',
        'time_limit',
        'max_attempts',
        'total_marks',
        'passing_marks',
        'status',
        'visibility_status',
        'grading_status',
        'results_published',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'results_published' => 'boolean',
    ];

    /**
     * Relationship to course module
     */
    public function module()
    {
        return $this->belongsTo(CourseModule::class, 'module_id');
    }

    /**
     * Relationship to course
     */
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Relationship to creator
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relationship to questions
     */
    public function questions()
    {
        return $this->hasMany(TestQuestion::class)->orderBy('order_index');
    }

    /**
     * Relationship to submissions
     */
    public function submissions()
    {
        return $this->hasMany(TestSubmission::class);
    }

    /**
     * Check if test is currently active
     */
    public function isActive()
    {
        $now = now();
        return $now->between($this->start_date, $this->end_date) && $this->status === 'active';
    }

    /**
     * Check if test has started
     */
    public function hasStarted()
    {
        return now()->greaterThanOrEqualTo($this->start_date);
    }

    /**
     * Check if test has ended
     */
    public function hasEnded()
    {
        return now()->greaterThan($this->end_date);
    }

    /**
     * Auto-update status based on current time
     */
    public function updateStatus()
    {
        $now = now();
        
        if ($now->lessThan($this->start_date)) {
            $this->status = 'scheduled';
            $this->visibility_status = 'hidden';
        } elseif ($now->between($this->start_date, $this->end_date)) {
            $this->status = 'active';
            $this->visibility_status = 'visible';
        } else {
            $this->status = 'closed';
        }
        
        $this->save();
    }
}
