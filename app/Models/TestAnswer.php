<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'submission_id',
        'question_id',
        'question_type',
        'selected_option',
        'text_answer',
        'file_url',
        'file_name',
        'file_size',
        'points_awarded',
        'max_points',
        'is_correct',
        'feedback',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
    ];

    public function submission()
    {
        return $this->belongsTo(TestSubmission::class, 'submission_id');
    }

    public function question()
    {
        return $this->belongsTo(TestQuestion::class, 'question_id');
    }
}
