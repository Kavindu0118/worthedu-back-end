<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssignmentSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'assignment_id',
        'user_id',
        'submission_text',
        'file_path',
        'file_name',
        'file_size_kb',
        'submitted_at',
        'status',
        'marks_obtained',
        'feedback',
        'graded_by',
        'graded_at',
        'is_late',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'graded_at' => 'datetime',
        'marks_obtained' => 'decimal:2',
        'is_late' => 'boolean',
    ];

    /**
     * Get the assignment that owns the submission.
     */
    public function assignment()
    {
        return $this->belongsTo(ModuleAssignment::class, 'assignment_id');
    }

    /**
     * Get the user that submitted the assignment.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the instructor who graded the submission.
     */
    public function grader()
    {
        return $this->belongsTo(User::class, 'graded_by');
    }
}
