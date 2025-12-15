<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModuleQuiz extends Model
{
    use HasFactory;

    protected $fillable = [
        'module_id',
        'quiz_title',
        'quiz_description',
        'quiz_data',
        'total_points',
        'time_limit',
        'passing_percentage',
        'max_attempts',
        'show_correct_answers',
        'randomize_questions',
        'available_from',
        'available_until',
    ];

    protected $casts = [
        'quiz_data' => 'array',
        'available_from' => 'datetime',
        'available_until' => 'datetime',
        'show_correct_answers' => 'boolean',
        'randomize_questions' => 'boolean',
    ];

    // Accessors for backward compatibility
    public function getTitleAttribute()
    {
        return $this->quiz_title;
    }

    public function getDescriptionAttribute()
    {
        return $this->quiz_description;
    }

    public function getTotalMarksAttribute()
    {
        return $this->total_points;
    }

    public function getTimeLimitMinutesAttribute()
    {
        return $this->time_limit;
    }

    public function module()
    {
        return $this->belongsTo(CourseModule::class, 'module_id');
    }
}
