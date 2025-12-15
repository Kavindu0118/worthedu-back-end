<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lesson extends Model
{
    protected $fillable = [
        'module_id',
        'title',
        'content',
        'video_url',
        'duration',
        'order_no',
    ];

    /**
     * Get the module that owns the lesson
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(CourseModule::class, 'module_id');
    }

    /**
     * Get all progress records for this lesson
     */
    public function progress(): HasMany
    {
        return $this->hasMany(LessonProgress::class, 'lesson_id');
    }

    /**
     * Get progress for a specific user
     */
    public function userProgress($userId)
    {
        return $this->progress()->where('user_id', $userId)->first();
    }
}
