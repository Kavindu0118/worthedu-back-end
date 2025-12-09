<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'quiz_id',
        'user_id',
        'attempt_number',
        'started_at',
        'completed_at',
        'time_taken_minutes',
        'score',
        'points_earned',
        'total_points',
        'status',
        'passed',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'score' => 'decimal:2',
        'points_earned' => 'decimal:2',
        'total_points' => 'decimal:2',
        'passed' => 'boolean',
        'time_taken_minutes' => 'integer',
    ];

    /**
     * Get the quiz that owns the attempt.
     */
    public function quiz()
    {
        return $this->belongsTo(ModuleQuiz::class, 'quiz_id');
    }

    /**
     * Get the user that made the attempt.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the answers for this attempt.
     */
    public function answers()
    {
        return $this->hasMany(QuizAnswer::class, 'attempt_id');
    }
}
