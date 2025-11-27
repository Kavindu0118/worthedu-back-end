<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'learner_id',
        'course_id',
        'enrolled_at',
        'progress',
        'status',
    ];

    protected $casts = [
        'enrolled_at' => 'datetime',
    ];

    public function learner()
    {
        return $this->belongsTo(Learner::class, 'learner_id', 'learner_id');
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}
