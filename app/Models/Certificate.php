<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'course_id',
        'certificate_number',
        'quiz_weight',
        'assignment_weight',
        'test_weight',
        'final_grade',
        'letter_grade',
        'status',
        'completed_at',
        'issued_at',
        'can_view',
        'file_path',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'completed_at' => 'datetime',
        'quiz_weight' => 'decimal:2',
        'assignment_weight' => 'decimal:2',
        'test_weight' => 'decimal:2',
        'final_grade' => 'decimal:2',
        'can_view' => 'boolean',
    ];

    /**
     * Get the user that owns the certificate.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the course that this certificate belongs to.
     */
    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}
