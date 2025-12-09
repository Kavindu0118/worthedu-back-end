<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LessonProgress extends Model
{
    use HasFactory;

    protected $table = 'lesson_progress';

    protected $fillable = [
        'user_id',
        'lesson_id',
        'status',
        'started_at',
        'completed_at',
        'time_spent_minutes',
        'last_position',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'time_spent_minutes' => 'integer',
    ];

    /**
     * Get the user that owns the progress.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the course module (lesson) that this progress belongs to.
     */
    public function lesson()
    {
        return $this->belongsTo(CourseModule::class, 'lesson_id');
    }

    /**
     * Alias for lesson() - for better readability in code
     */
    public function module()
    {
        return $this->belongsTo(CourseModule::class, 'lesson_id');
    }
}
