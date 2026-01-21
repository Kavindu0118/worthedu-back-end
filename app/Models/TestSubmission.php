<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'test_id',
        'student_id',
        'submitted_at',
        'submission_status',
        'attempt_number',
        'started_at',
        'time_taken',
        'total_score',
        'grading_status',
        'graded_at',
        'graded_by',
        'instructor_feedback',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'started_at' => 'datetime',
        'graded_at' => 'datetime',
    ];

    public function test()
    {
        return $this->belongsTo(Test::class);
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function grader()
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    public function answers()
    {
        return $this->hasMany(TestAnswer::class, 'submission_id');
    }

    public function isGraded()
    {
        return $this->grading_status === 'graded' || $this->grading_status === 'published';
    }

    public function isLate()
    {
        if (!$this->submitted_at || !$this->test) {
            return false;
        }
        
        return $this->submitted_at->greaterThan($this->test->end_date);
    }
}
