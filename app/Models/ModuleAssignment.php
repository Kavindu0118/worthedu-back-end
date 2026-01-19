<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModuleAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'module_id',
        'assignment_title',
        'instructions',
        'submission_type',
        'attachment_url',
        'max_points',
        'due_date',
        'allowed_file_types',
        'max_file_size_mb',
        'max_files',
        'allow_late_submission',
        'late_submission_deadline',
        'late_penalty_percent',
        'require_rubric',
        'peer_review_enabled',
        'peer_reviews_required',
        'available_from',
        'show_after_due_date',
        'min_words',
        'max_words',
        'grading_criteria',
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'late_submission_deadline' => 'datetime',
        'available_from' => 'datetime',
        'allow_late_submission' => 'boolean',
        'require_rubric' => 'boolean',
        'peer_review_enabled' => 'boolean',
        'show_after_due_date' => 'boolean',
        'late_penalty_percent' => 'decimal:2',
    ];

    protected $appends = [
        'title',
        'description',
        'max_marks',
        'allowed_file_types',
        'attachment_url',
    ];

    public function module()
    {
        return $this->belongsTo(CourseModule::class, 'module_id');
    }

    public function submissions()
    {
        return $this->hasMany(AssignmentSubmission::class, 'assignment_id');
    }

    public function getTitleAttribute()
    {
        return $this->attributes['assignment_title'] ?? null;
    }

    public function getDescriptionAttribute()
    {
        return $this->attributes['instructions'] ?? null;
    }

    public function getMaxMarksAttribute()
    {
        return $this->attributes['max_points'] ?? $this->attributes['max_marks'] ?? null;
    }

    public function getAllowedFileTypesAttribute($value = null)
    {
        $val = $value ?? ($this->attributes['allowed_file_types'] ?? null);
        if (!$val) {
            return [];
        }
        if (is_array($val)) {
            return $val;
        }
        $decoded = json_decode($val, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }
        if (strpos($val, ',') !== false) {
            return array_map('trim', explode(',', $val));
        }
        return [$val];
    }

    public function getAttachmentUrlAttribute($value = null)
    {
        $val = $value ?? ($this->attributes['attachment_url'] ?? null);
        if (!$val) {
            return null;
        }
        if (filter_var($val, FILTER_VALIDATE_URL)) {
            return $val;
        }
        return url('storage/' . ltrim($val, '/'));
    }
}
