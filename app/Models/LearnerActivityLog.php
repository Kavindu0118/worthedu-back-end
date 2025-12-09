<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LearnerActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'activity_date',
        'hours_spent',
        'lessons_completed',
        'quizzes_taken',
        'assignments_submitted',
    ];

    protected $casts = [
        'activity_date' => 'date',
        'hours_spent' => 'decimal:2',
        'lessons_completed' => 'integer',
        'quizzes_taken' => 'integer',
        'assignments_submitted' => 'integer',
    ];

    /**
     * Get the user that owns the activity log.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
